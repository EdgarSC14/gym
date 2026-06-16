import type { APIRoute } from "astro";
import bcrypt from "bcryptjs";
import { resources } from "@/lib/admin";
import { audit } from "@/lib/audit";
import { hasPermission } from "@/lib/auth";
import { db, row } from "@/lib/db";

const resourcePermissions: Partial<Record<keyof typeof resources, string>> = {
  users: "users.manage",
  products: "products.manage",
  services: "services.manage",
  plans: "plans.manage",
  suppliers: "suppliers.manage",
};

async function syncUserRole(userId: number, role: unknown) {
  const roleCode = String(role || "usuario");
  await db.batch(
    [
      { sql: "DELETE FROM usuario_rol WHERE id_usuario=?", args: [userId] },
      {
        sql: "INSERT INTO usuario_rol(id_usuario,id_rol) SELECT ?,id_rol FROM rol WHERE codigo=?",
        args: [userId, roleCode],
      },
    ],
    "write",
  );
}

export const POST: APIRoute = async ({ request, locals, redirect }) => {
  if (!locals.user) return new Response("No autorizado", { status: 403 });

  const form = await request.formData();
  const name = String(form.get("resource")) as keyof typeof resources;
  const config = resources[name];
  if (!config) return new Response("Recurso inválido", { status: 400 });
  const permission = resourcePermissions[name] ?? "admin.access";
  if (!(await hasPermission(locals.user.id, permission))) {
    return new Response("No autorizado", { status: 403 });
  }

  const id = Number(form.get("id") || 0);
  const before = id
    ? await row<any>(`SELECT * FROM ${config.table} WHERE ${config.id}=?`, [id])
    : null;

  if (form.get("action") === "delete") {
    if (name === "users" && id === locals.user.id) return redirect(`/admin/${name}`);
    if (config.fields.includes("esta_activo" as never)) {
      await db.execute({
        sql: `UPDATE ${config.table} SET esta_activo=0 WHERE ${config.id}=?`,
        args: [id],
      });
    } else {
      return new Response("Este recurso conserva historial y no admite eliminación", {
        status: 409,
      });
    }
    await audit("soft_delete", config.table, id, locals.user.id, before, null, request);
    return redirect(`/admin/${name}`);
  }

  const values = await Promise.all(
    config.fields.map(async (field) => {
      const value = String(form.get(field) ?? "").trim() || null;
      return field === "hash_contraseña" && value ? await bcrypt.hash(value, 12) : value;
    }),
  );

  let targetId = id;
  if (id) {
    const pairs = config.fields
      .map((field, index) => ({ field, value: values[index] }))
      .filter(({ field, value }) => !(field === "hash_contraseña" && !value));
    await db.execute({
      sql: `UPDATE ${config.table} SET ${pairs.map(({ field }) => `${field}=?`).join(",")} WHERE ${config.id}=?`,
      args: [...pairs.map(({ value }) => value), id],
    });
    await audit(
      "update",
      config.table,
      id,
      locals.user.id,
      before,
      Object.fromEntries(pairs.map((pair) => [pair.field, pair.value])),
      request,
    );
  } else {
    const result = await db.execute({
      sql: `INSERT INTO ${config.table} (${config.fields.join(",")}) VALUES (${config.fields.map(() => "?").join(",")})`,
      args: values,
    });
    targetId = Number(result.lastInsertRowid);
    await audit(
      "create",
      config.table,
      targetId,
      locals.user.id,
      null,
      Object.fromEntries(config.fields.map((field, index) => [field, values[index]])),
      request,
    );
  }

  if (name === "users") {
    await syncUserRole(targetId, form.get("rol"));
  }
  return redirect(`/admin/${name}`);
};
