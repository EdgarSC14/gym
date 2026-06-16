import type { APIRoute } from "astro";
import { audit } from "@/lib/audit";
import { hasPermission } from "@/lib/auth";
import { db } from "@/lib/db";

export const POST: APIRoute = async ({ request, locals, redirect }) => {
  if (!locals.user || !(await hasPermission(locals.user.id, "inventory.manage"))) {
    return new Response("No autorizado", { status: 403 });
  }
  const form = await request.formData();
  const id = Number(form.get("productId"));
  const quantity = Math.trunc(Number(form.get("quantity")));
  const note = String(form.get("note") || "").trim();
  if (!id || !quantity || !note) return new Response("Datos inválidos", { status: 400 });

  const tx = await db.transaction("write");
  try {
    const product = (
      await tx.execute({
        sql: "SELECT cantidad_stock,stock_reservado FROM producto WHERE id_producto=?",
        args: [id],
      })
    ).rows[0];
    const before = Number(product?.cantidad_stock);
    const next = before + quantity;
    if (!product || next < Number(product.stock_reservado) || next < 0) {
      throw new Error("El ajuste dejaría stock negativo o por debajo de reservas");
    }
    await tx.execute({
      sql: "UPDATE producto SET cantidad_stock=?,fecha_actualizacion=CURRENT_TIMESTAMP WHERE id_producto=?",
      args: [next, id],
    });
    await tx.execute({
      sql: "INSERT INTO movimiento_inventario(id_producto,tipo,cantidad,stock_anterior,stock_nuevo,id_usuario_actor,nota) VALUES (?,'ajuste',?,?,?,?,?)",
      args: [id, quantity, before, next, locals.user.id, note],
    });
    await tx.commit();
    await audit(
      "inventory.adjust",
      "producto",
      id,
      locals.user.id,
      { cantidad_stock: before },
      { cantidad_stock: next, nota: note },
      request,
    );
    return redirect("/admin/inventory");
  } catch (error) {
    await tx.rollback();
    return new Response(error instanceof Error ? error.message : "Error de inventario", {
      status: 400,
    });
  }
};
