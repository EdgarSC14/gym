import { db, row } from "@/lib/db";
import { stripe } from "@/lib/stripe";

function formatCardExpiry(month: number, year: number) {
  return `${String(month).padStart(2, "0")}/${String(year).slice(-2)}`;
}

export async function syncStripePaymentMethods(userId: number) {
  if (!stripe) return;

  const user = await row<{ stripe_customer_id?: string }>("SELECT stripe_customer_id FROM usuario WHERE id_usuario=?", [userId]);
  if (!user?.stripe_customer_id) return;

  const customer = await stripe.customers.retrieve(user.stripe_customer_id);
  if (customer.deleted) return;

  const defaultPaymentMethod =
    typeof customer.invoice_settings.default_payment_method === "string"
      ? customer.invoice_settings.default_payment_method
      : customer.invoice_settings.default_payment_method?.id;

  const methods = await stripe.paymentMethods.list({ customer: user.stripe_customer_id, type: "card", limit: 100 });
  const activeIds = methods.data.map((method) => method.id);
  const tx = await db.transaction("write");

  try {
    if (activeIds.length) {
      await tx.execute({
        sql: `UPDATE metodo_pago_usuario SET esta_activo=0,es_predeterminado=0,fecha_actualizacion=CURRENT_TIMESTAMP WHERE id_usuario=? AND stripe_payment_method_id IS NOT NULL AND stripe_payment_method_id NOT IN (${activeIds.map(() => "?").join(",")})`,
        args: [userId, ...activeIds]
      });
    } else {
      await tx.execute({
        sql: "UPDATE metodo_pago_usuario SET esta_activo=0,es_predeterminado=0,fecha_actualizacion=CURRENT_TIMESTAMP WHERE id_usuario=? AND stripe_payment_method_id IS NOT NULL",
        args: [userId]
      });
    }

    if (defaultPaymentMethod) {
      await tx.execute({ sql: "UPDATE metodo_pago_usuario SET es_predeterminado=0 WHERE id_usuario=?", args: [userId] });
    }

    for (const method of methods.data) {
      if (!method.card) continue;

      const card = method.card;
      const isDefault = defaultPaymentMethod === method.id ? 1 : 0;
      const existing = (await tx.execute({
        sql: "SELECT id_metodo_pago FROM metodo_pago_usuario WHERE stripe_payment_method_id=?",
        args: [method.id]
      })).rows[0];

      const args = [
        userId,
        card.brand,
        formatCardExpiry(card.exp_month, card.exp_year),
        method.billing_details.name || "Titular",
        method.id,
        card.last4,
        isDefault
      ];

      if (existing) {
        await tx.execute({
          sql: "UPDATE metodo_pago_usuario SET id_usuario=?,tipo_tarjeta=?,fecha_vencimiento=?,nombre_titular=?,stripe_payment_method_id=?,ultimos_cuatro=?,es_predeterminado=?,esta_activo=1,fecha_actualizacion=CURRENT_TIMESTAMP WHERE id_metodo_pago=?",
          args: [...args, Number(existing.id_metodo_pago)]
        });
      } else {
        await tx.execute({
          sql: "INSERT INTO metodo_pago_usuario(id_usuario,tipo_tarjeta,fecha_vencimiento,nombre_titular,stripe_payment_method_id,ultimos_cuatro,es_predeterminado) VALUES (?,?,?,?,?,?,?)",
          args
        });
      }
    }

    const defaultCount = (await tx.execute({
      sql: "SELECT COUNT(*) total FROM metodo_pago_usuario WHERE id_usuario=? AND esta_activo=1 AND es_predeterminado=1",
      args: [userId]
    })).rows[0];

    if (Number(defaultCount?.total || 0) === 0) {
      await tx.execute({
        sql: "UPDATE metodo_pago_usuario SET es_predeterminado=1,fecha_actualizacion=CURRENT_TIMESTAMP WHERE id_metodo_pago=(SELECT id_metodo_pago FROM metodo_pago_usuario WHERE id_usuario=? AND esta_activo=1 ORDER BY fecha_creacion DESC LIMIT 1)",
        args: [userId]
      });
    }

    await tx.commit();
  } catch (error) {
    await tx.rollback();
    throw error;
  }
}
