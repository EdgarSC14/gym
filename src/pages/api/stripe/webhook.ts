import type { APIRoute } from "astro";
import { db } from "@/lib/db";
import { releaseOrderReservations } from "@/lib/inventory";
import { stripe } from "@/lib/stripe";

async function beginEvent(event: any) {
  const tx = await db.transaction("write");
  const inserted = await tx.execute({ sql: "INSERT OR IGNORE INTO stripe_event(event_id,tipo,estado) VALUES (?,?,'recibido')", args: [event.id, event.type] });
  if (inserted.rowsAffected === 0) { await tx.rollback(); return null; }
  return tx;
}

export const POST: APIRoute = async ({ request }) => {
  if (!stripe) return new Response("Stripe no configurado", { status: 503 });
  let event: any;
  try { event = stripe.webhooks.constructEvent(await request.text(), request.headers.get("stripe-signature") || "", import.meta.env.STRIPE_WEBHOOK_SECRET); }
  catch { return new Response("Firma inválida", { status: 400 }); }

  if (["invoice.paid", "invoice.payment_failed", "customer.subscription.deleted"].includes(event.type)) {
    const object: any = event.data.object; const tx = await beginEvent(event); if (!tx) return new Response("ok");
    try {
      const stripeSubscriptionId = String(object.subscription || object.id);
      if (event.type === "invoice.paid") {
        const sub = (await tx.execute({ sql: "SELECT id_suscripcion FROM suscripcion_usuario WHERE stripe_subscription_id=?", args: [stripeSubscriptionId] })).rows[0];
        if (sub) await tx.execute({ sql: "INSERT OR IGNORE INTO pago(id_suscripcion,monto,metodo_pago,id_transaccion,estado,monto_centavos,moneda) VALUES (?,?,'stripe',?,'completado',?,?)", args: [Number(sub.id_suscripcion), Number(object.amount_paid || 0) / 100, object.id, Number(object.amount_paid || 0), String(object.currency || "mxn")] });
        await tx.execute({ sql: "UPDATE suscripcion_usuario SET estado_stripe='active' WHERE stripe_subscription_id=?", args: [stripeSubscriptionId] });
      } else if (event.type === "invoice.payment_failed") await tx.execute({ sql: "UPDATE suscripcion_usuario SET estado_stripe='past_due' WHERE stripe_subscription_id=?", args: [stripeSubscriptionId] });
      else await tx.execute({ sql: "UPDATE suscripcion_usuario SET estado='cancelada',estado_stripe='canceled',fecha_cancelacion=CURRENT_TIMESTAMP WHERE stripe_subscription_id=?", args: [stripeSubscriptionId] });
      await tx.execute({ sql: "UPDATE stripe_event SET estado='procesado',fecha_procesado=CURRENT_TIMESTAMP WHERE event_id=?", args: [event.id] }); await tx.commit(); return new Response("ok");
    } catch (error) { await tx.rollback(); return new Response(error instanceof Error ? error.message : "Error", { status: 500 }); }
  }

  const session: any = event.data.object; const metadata = session.metadata || {};
  if (event.type === "checkout.session.expired" && metadata.type === "cart") { await releaseOrderReservations(Number(metadata.order_id), "Stripe Checkout expirado"); await db.execute({ sql: "INSERT OR IGNORE INTO stripe_event(event_id,tipo,estado,fecha_procesado) VALUES (?,?,'procesado',CURRENT_TIMESTAMP)", args: [event.id, event.type] }); return new Response("ok"); }
  if (event.type !== "checkout.session.completed") return new Response("ok");

  let setupMethod: any = null;
  if (metadata.type === "setup" && session.setup_intent) { const intent = await stripe.setupIntents.retrieve(String(session.setup_intent)); setupMethod = await stripe.paymentMethods.retrieve(String(intent.payment_method)); }
  const tx = await beginEvent(event); if (!tx) return new Response("ok");
  try {
    if (metadata.type === "setup" && setupMethod?.card) { const card = setupMethod.card; const count = (await tx.execute({ sql: "SELECT COUNT(*) total FROM metodo_pago_usuario WHERE id_usuario=? AND esta_activo=1", args: [Number(metadata.user_id)] })).rows[0]; await tx.execute({ sql: "INSERT OR IGNORE INTO metodo_pago_usuario(id_usuario,tipo_tarjeta,fecha_vencimiento,nombre_titular,stripe_payment_method_id,ultimos_cuatro,es_predeterminado) VALUES (?,?,?,?,?,?,?)", args: [Number(metadata.user_id), card.brand, `${String(card.exp_month).padStart(2, "0")}/${String(card.exp_year).slice(-2)}`, setupMethod.billing_details.name || "Titular", setupMethod.id, card.last4, Number(count?.total || 0) === 0 ? 1 : 0] }); }
    if (metadata.type === "cart") {
      const orderId = Number(metadata.order_id); const order = (await tx.execute({ sql: "SELECT * FROM pedido WHERE id_pedido=?", args: [orderId] })).rows[0]; if (!order) throw new Error("Pedido inexistente");
      const reservations = (await tx.execute({ sql: "SELECT * FROM reserva_inventario WHERE id_pedido=? AND estado='activa'", args: [orderId] })).rows;
      for (const reservation of reservations) { const product = (await tx.execute({ sql: "SELECT cantidad_stock FROM producto WHERE id_producto=?", args: [Number(reservation.id_producto)] })).rows[0]; const before = Number(product?.cantidad_stock); if (before < Number(reservation.cantidad)) throw new Error("Stock insuficiente al confirmar pago"); await tx.execute({ sql: "UPDATE producto SET cantidad_stock=cantidad_stock-?,stock_reservado=MAX(0,stock_reservado-?),fecha_actualizacion=CURRENT_TIMESTAMP WHERE id_producto=?", args: [Number(reservation.cantidad), Number(reservation.cantidad), Number(reservation.id_producto)] }); await tx.execute({ sql: "UPDATE reserva_inventario SET estado='consumida',fecha_actualizacion=CURRENT_TIMESTAMP WHERE id_reserva=?", args: [Number(reservation.id_reserva)] }); await tx.execute({ sql: "INSERT INTO movimiento_inventario(id_producto,tipo,cantidad,stock_anterior,stock_nuevo,referencia_tipo,referencia_id,nota) VALUES (?,'venta',?,?,?,?,?,'Pago Stripe confirmado')", args: [Number(reservation.id_producto), -Number(reservation.cantidad), before, before - Number(reservation.cantidad), "pedido", orderId] }); }
      const intent = session.payment_intent ? String(session.payment_intent) : session.id; await tx.execute({ sql: "UPDATE pedido SET estado='pagado',fecha_actualizacion=CURRENT_TIMESTAMP WHERE id_pedido=?", args: [orderId] }); await tx.execute({ sql: "INSERT INTO pago(id_pedido,monto,metodo_pago,id_transaccion,estado,monto_centavos,moneda,stripe_payment_intent_id) VALUES (?,?,'stripe',?,'completado',?,'mxn',?)", args: [orderId, (session.amount_total || 0) / 100, intent, session.amount_total || 0, session.payment_intent ? intent : null] }); await tx.execute({ sql: "UPDATE intento_pago SET estado='completado',stripe_payment_intent_id=?,fecha_actualizacion=CURRENT_TIMESTAMP WHERE stripe_checkout_session_id=?", args: [session.payment_intent ? intent : null, session.id] }); await tx.execute({ sql: "INSERT INTO historial_estado_pedido(id_pedido,estado_anterior,estado_nuevo,nota) VALUES (?,'pendiente','pagado','Stripe Checkout confirmado')", args: [orderId] }); await tx.execute({ sql: "DELETE FROM carrito_item WHERE id_carrito=(SELECT id_carrito FROM carrito WHERE id_usuario=?)", args: [Number(metadata.user_id)] });
    }
    if (metadata.type === "subscription") { await tx.execute({ sql: "UPDATE suscripcion_usuario SET estado='expirada' WHERE id_usuario=? AND estado='activa' AND date(fecha_fin)<date('now')", args: [Number(metadata.user_id)] }); const plan = (await tx.execute({ sql: "SELECT * FROM plan_suscripcion WHERE id_plan=?", args: [Number(metadata.plan_id)] })).rows[0]; if (!plan) throw new Error("Plan inexistente"); const start = new Date(); const end = new Date(start); end.setDate(end.getDate() + Number(plan.duracion_dias)); await tx.execute({ sql: "INSERT INTO suscripcion_usuario(id_usuario,id_plan,fecha_inicio,fecha_fin,estado,stripe_checkout_session_id,stripe_subscription_id,estado_stripe) VALUES (?,?,?,?, 'activa',?,?,'active')", args: [Number(metadata.user_id), Number(metadata.plan_id), start.toISOString().slice(0, 10), end.toISOString().slice(0, 10), session.id, String(session.subscription)] }); }
    await tx.execute({ sql: "UPDATE stripe_event SET estado='procesado',fecha_procesado=CURRENT_TIMESTAMP WHERE event_id=?", args: [event.id] }); await tx.execute({ sql: "INSERT INTO audit_log(accion,entidad,entidad_id,datos_nuevos) VALUES ('stripe.webhook','stripe_event',?,?)", args: [event.id, JSON.stringify({ type: event.type, metadata })] }); await tx.commit(); return new Response("ok");
  } catch (error) { await tx.rollback(); return new Response(error instanceof Error ? error.message : "Error procesando webhook", { status: 500 }); }
};
