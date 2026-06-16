import type { APIRoute } from "astro";
import bcrypt from "bcryptjs";
import { db, row } from "@/lib/db";
import { setSession } from "@/lib/auth";
import { validateRegistration } from "@/lib/validation";

export const POST: APIRoute = async ({ request, cookies, redirect }) => {
  const form = await request.formData();
  const data = Object.fromEntries(["email", "password", "confirm_password", "first_name", "last_name", "username"].map((key) => [key, String(form.get(key) || "")]));
  const error = validateRegistration(data);
  if (error) return redirect(`/register?error=${encodeURIComponent(error)}`);
  const exists = await row("SELECT id_usuario FROM usuario WHERE lower(correo)=lower(?) OR lower(nombre_usuario)=lower(?)", [data.email.trim(), data.username.trim()]);
  if (exists) return redirect(`/register?error=${encodeURIComponent("El correo o nombre de usuario ya está registrado")}`);
  const result = await db.execute({ sql: 'INSERT INTO usuario (nombre_usuario, correo, hash_contraseña, nombre, apellido) VALUES (?, ?, ?, ?, ?)', args: [data.username.trim(), data.email.trim(), await bcrypt.hash(data.password, 12), data.first_name.trim(), data.last_name.trim()] });
  await db.execute({ sql: "INSERT OR IGNORE INTO usuario_rol(id_usuario,id_rol) SELECT ?,id_rol FROM rol WHERE codigo='usuario'", args: [Number(result.lastInsertRowid)] });
  await setSession(cookies, { id: Number(result.lastInsertRowid), username: data.username.trim(), role: "usuario" }, request);
  return redirect("/");
};
