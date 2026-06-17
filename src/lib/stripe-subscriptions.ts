import { stripe } from "@/lib/stripe";

type Executor = { execute: (query: { sql: string; args?: any[] } | string) => Promise<any> };

type ActivationInput = {
  userId: number;
  planId: number;
  checkoutSessionId?: string | null;
  stripeSubscriptionId?: string | null;
};

const dateOnly = (date: Date) => date.toISOString().slice(0, 10);

async function retrieveStripeSubscription(stripeSubscriptionId?: string | null) {
  if (!stripe || !stripeSubscriptionId) return null;
  try {
    return await stripe.subscriptions.retrieve(stripeSubscriptionId) as any;
  } catch {
    return null;
  }
}

function stripeSubscriptionIdFromInvoice(invoice: any) {
  const candidate = invoice.subscription
    || invoice.subscription_details?.subscription
    || invoice.parent?.subscription_details?.subscription
    || invoice.lines?.data?.find((line: any) => line.subscription)?.subscription;
  return candidate ? String(candidate) : null;
}

export async function activateSubscriptionRecord(executor: Executor, input: ActivationInput) {
  if (!Number.isFinite(input.userId) || !Number.isFinite(input.planId)) throw new Error("Metadata de suscripción inválida");

  const plan = (await executor.execute({ sql: "SELECT * FROM plan_suscripcion WHERE id_plan=? AND esta_activo=1", args: [input.planId] })).rows[0];
  if (!plan) throw new Error("Plan inexistente");

  const stripeSub = await retrieveStripeSubscription(input.stripeSubscriptionId);
  const stripeStatus = String(stripeSub?.status || "active");
  const start = stripeSub?.current_period_start ? new Date(Number(stripeSub.current_period_start) * 1000) : new Date();
  const end = stripeSub?.current_period_end ? new Date(Number(stripeSub.current_period_end) * 1000) : new Date(start);
  if (!stripeSub?.current_period_end) end.setDate(end.getDate() + Number(plan.duracion_dias));
  const localStatus = stripeStatus === "canceled" ? "cancelada" : "activa";

  await executor.execute({ sql: "UPDATE suscripcion_usuario SET estado='expirada' WHERE id_usuario=? AND estado='activa' AND date(fecha_fin)<date('now')", args: [input.userId] });

  const existing = (await executor.execute({
    sql: "SELECT id_suscripcion FROM suscripcion_usuario WHERE (stripe_subscription_id IS NOT NULL AND stripe_subscription_id=?) OR (stripe_checkout_session_id IS NOT NULL AND stripe_checkout_session_id=?) LIMIT 1",
    args: [input.stripeSubscriptionId || "", input.checkoutSessionId || ""],
  })).rows[0];

  if (existing) {
    await executor.execute({
      sql: "UPDATE suscripcion_usuario SET id_plan=?,fecha_inicio=?,fecha_fin=?,estado=?,stripe_checkout_session_id=COALESCE(?,stripe_checkout_session_id),stripe_subscription_id=COALESCE(?,stripe_subscription_id),estado_stripe=? WHERE id_suscripcion=?",
      args: [input.planId, dateOnly(start), dateOnly(end), localStatus, input.checkoutSessionId || null, input.stripeSubscriptionId || null, stripeStatus, Number(existing.id_suscripcion)],
    });
    return { id: Number(existing.id_suscripcion), stripeSubscriptionId: input.stripeSubscriptionId || null };
  }

  const inserted = await executor.execute({
    sql: "INSERT INTO suscripcion_usuario(id_usuario,id_plan,fecha_inicio,fecha_fin,estado,stripe_checkout_session_id,stripe_subscription_id,estado_stripe) VALUES (?,?,?,?,?,?,?,?)",
    args: [input.userId, input.planId, dateOnly(start), dateOnly(end), localStatus, input.checkoutSessionId || null, input.stripeSubscriptionId || null, stripeStatus],
  });

  return { id: Number(inserted.lastInsertRowid), stripeSubscriptionId: input.stripeSubscriptionId || null };
}

export async function activateSubscriptionFromCheckoutSession(executor: Executor, session: any) {
  const metadata = session.metadata || {};
  if (metadata.type !== "subscription") return null;
  return activateSubscriptionRecord(executor, {
    userId: Number(metadata.user_id),
    planId: Number(metadata.plan_id),
    checkoutSessionId: String(session.id),
    stripeSubscriptionId: session.subscription ? String(session.subscription) : null,
  });
}

export async function activateSubscriptionFromInvoice(executor: Executor, invoice: any) {
  const stripeSubscriptionId = stripeSubscriptionIdFromInvoice(invoice);
  if (!stripeSubscriptionId) return null;

  const existing = (await executor.execute({ sql: "SELECT id_suscripcion FROM suscripcion_usuario WHERE stripe_subscription_id=? LIMIT 1", args: [stripeSubscriptionId] })).rows[0];
  if (existing) {
    await executor.execute({ sql: "UPDATE suscripcion_usuario SET estado_stripe='active' WHERE id_suscripcion=?", args: [Number(existing.id_suscripcion)] });
    return { id: Number(existing.id_suscripcion), stripeSubscriptionId };
  }

  const stripeSub = await retrieveStripeSubscription(stripeSubscriptionId);
  const metadata = { ...(invoice.subscription_details?.metadata || {}), ...(stripeSub?.metadata || {}) };
  if (!metadata.user_id || !metadata.plan_id) return null;

  return activateSubscriptionRecord(executor, {
    userId: Number(metadata.user_id),
    planId: Number(metadata.plan_id),
    stripeSubscriptionId,
  });
}

export { stripeSubscriptionIdFromInvoice };
