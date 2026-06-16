import type { APIRoute } from "astro";
import { db, row } from "@/lib/db";
import { stripe } from "@/lib/stripe";
import { audit } from "@/lib/audit";

export const POST: APIRoute = async ({ request, locals, redirect }) => {
  if (!locals.user) return redirect("/login");

  const form = await request.formData();
  const id = Number(form.get("id"));
  const action = String(form.get("action"));
  const method = await row<any>("SELECT * FROM metodo_pago_usuario WHERE id_metodo_pago=? AND id_usuario=?", [id, locals.user.id]);
  if (!method) return redirect("/profile?message=Metodo+invalido");

  const tx = await db.transaction("write");
  try {
    if (action === "default") {
      await tx.execute({ sql: "UPDATE metodo_pago_usuario SET es_predeterminado=0,fecha_actualizacion=CURRENT_TIMESTAMP WHERE id_usuario=?", args: [locals.user.id] });
      await tx.execute({ sql: "UPDATE metodo_pago_usuario SET es_predeterminado=1,fecha_actualizacion=CURRENT_TIMESTAMP WHERE id_metodo_pago=?", args: [id] });
    }

    if (action === "delete") {
      await tx.execute({ sql: "UPDATE metodo_pago_usuario SET esta_activo=0,es_predeterminado=0,fecha_actualizacion=CURRENT_TIMESTAMP WHERE id_metodo_pago=?", args: [id] });
      if (method.es_predeterminado) {
        await tx.execute({ sql: "UPDATE metodo_pago_usuario SET es_predeterminado=1 WHERE id_metodo_pago=(SELECT id_metodo_pago FROM metodo_pago_usuario WHERE id_usuario=? AND esta_activo=1 ORDER BY fecha_creacion DESC LIMIT 1)", args: [locals.user.id] });
      }
    }

    await tx.commit();

    if (stripe && method.stripe_payment_method_id) {
      if (action === "default") {
        const user = await row<any>("SELECT stripe_customer_id FROM usuario WHERE id_usuario=?", [locals.user.id]);
        if (user?.stripe_customer_id) {
          await stripe.customers.update(String(user.stripe_customer_id), {
            invoice_settings: { default_payment_method: String(method.stripe_payment_method_id) },
          });
        }
      }

      if (action === "delete") {
        await stripe.paymentMethods.detach(method.stripe_payment_method_id);
      }
    }

    await audit(`payment_method.${action}`, "metodo_pago_usuario", id, locals.user.id, method, null, request);
    return redirect("/profile?message=Metodo+de+pago+actualizado");
  } catch (e) {
    await tx.rollback();
    return new Response("No fue posible actualizar el método", { status: 400 });
  }
};
