import type { APIRoute } from "astro";
import bcrypt from "bcryptjs";
import { db, row } from "@/lib/db";
import { namePattern, emailPattern, phonePattern } from "@/lib/validation";
import { verifyPassword, clearSession } from "@/lib/auth";
import { stripe } from "@/lib/stripe";
import { audit } from "@/lib/audit";

const done = (redirect: any, message: string) => redirect(`/profile?message=${encodeURIComponent(message)}`);

const normalizePhone = (value: string) => {
  const digits = value.replace(/\D/g, "");
  return digits.length === 12 && digits.startsWith("52") ? digits.slice(2) : digits;
};

export const POST: APIRoute = async ({ request, locals, redirect, cookies }) => {
  if (!locals.user) return redirect("/login");

  const form = await request.formData();
  const action = String(form.get("action"));

  if (action === "update_profile") {
    const name = String(form.get("name") || "").trim();
    const email = String(form.get("email") || "").trim().toLowerCase();
    const phone = normalizePhone(String(form.get("phone") || "").trim());
    const address = String(form.get("address") || "").trim();

    if (!namePattern.test(name)) return done(redirect, "Ingresa un nombre valido");
    if (!emailPattern.test(email)) return done(redirect, "Ingresa un correo valido");
    if (!phonePattern.test(phone)) return done(redirect, "Ingresa un telefono valido de 10 digitos");
    if (address.length < 10) return done(redirect, "Ingresa una direccion completa");

    if (await row("SELECT id_usuario FROM usuario WHERE lower(correo)=lower(?) AND id_usuario<>?", [email, locals.user.id])) {
      return done(redirect, "El email ya esta en uso");
    }

    const before = await row<any>("SELECT nombre,apellido,correo,telefono,direccion FROM usuario WHERE id_usuario=?", [locals.user.id]);
    const [first, ...last] = name.split(/\s+/);

    await db.execute({
      sql: "UPDATE usuario SET nombre=?,apellido=?,correo=?,telefono=?,direccion=?,fecha_actualizacion=CURRENT_TIMESTAMP WHERE id_usuario=?",
      args: [first, last.join(" "), email, phone, address, locals.user.id]
    });

    await audit("profile.update", "usuario", locals.user.id, locals.user.id, before, { first, last: last.join(" "), email, phone, address }, request);
    return done(redirect, "Perfil actualizado correctamente");
  }

  if (action === "change_password") {
    const current = String(form.get("current_password") || "");
    const next = String(form.get("new_password") || "");
    const confirm = String(form.get("confirm_password") || "");
    const user = await row<any>('SELECT hash_contraseña FROM usuario WHERE id_usuario=?', [locals.user.id]);

    if (!user || !await verifyPassword(locals.user.id, current, user.hash_contraseña)) {
      return done(redirect, "La contraseña actual es incorrecta");
    }

    if (next.length < 10 || next !== confirm) {
      return done(redirect, "La nueva contraseña debe coincidir y tener al menos 10 caracteres");
    }

    await db.execute({ sql: 'UPDATE usuario SET hash_contraseña=? WHERE id_usuario=?', args: [await bcrypt.hash(next, 12), locals.user.id] });
    await audit("password.change", "usuario", locals.user.id, locals.user.id, null, null, request);
    return done(redirect, "Contraseña cambiada correctamente");
  }

  if (action === "cancel_subscription") {
    const id = Number(form.get("subscription_id"));
    const sub = await row<any>("SELECT * FROM suscripcion_usuario WHERE id_suscripcion=? AND id_usuario=?", [id, locals.user.id]);

    if (sub?.stripe_subscription_id && stripe) await stripe.subscriptions.cancel(sub.stripe_subscription_id);

    await db.execute({
      sql: "UPDATE suscripcion_usuario SET estado='cancelada',renovacion_automatica=0,estado_stripe='canceled',fecha_cancelacion=CURRENT_TIMESTAMP WHERE id_suscripcion=? AND id_usuario=?",
      args: [id, locals.user.id]
    });

    await audit("subscription.cancel", "suscripcion_usuario", id, locals.user.id, sub, null, request);
    return done(redirect, "Suscripción cancelada");
  }

  if (action === "revoke_sessions") {
    await db.execute({ sql: "UPDATE auth_session SET fecha_revocacion=CURRENT_TIMESTAMP WHERE id_usuario=? AND fecha_revocacion IS NULL", args: [locals.user.id] });
    await audit("sessions.revoke_all", "usuario", locals.user.id, locals.user.id, null, null, request);
    await clearSession(cookies);
    return redirect("/login");
  }

  return done(redirect, "Acción no válida");
};
