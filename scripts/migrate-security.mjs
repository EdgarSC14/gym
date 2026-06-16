import { createClient } from "@libsql/client";
import bcrypt from "bcryptjs";

const db = createClient({
  url: process.env.TURSO_DATABASE_URL || "file:local.db",
  authToken: process.env.TURSO_AUTH_TOKEN || undefined,
});

const alters = [
  "ALTER TABLE usuario ADD COLUMN email_verificado INTEGER NOT NULL DEFAULT 0 CHECK (email_verificado IN (0,1))",
  "ALTER TABLE usuario ADD COLUMN ultimo_acceso TEXT",
  "ALTER TABLE producto ADD COLUMN sku TEXT",
  "ALTER TABLE producto ADD COLUMN umbral_stock_bajo INTEGER NOT NULL DEFAULT 10 CHECK (umbral_stock_bajo >= 0)",
  "ALTER TABLE producto ADD COLUMN stock_reservado INTEGER NOT NULL DEFAULT 0 CHECK (stock_reservado >= 0)",
  "ALTER TABLE producto ADD COLUMN fecha_actualizacion TEXT",
  "ALTER TABLE pedido ADD COLUMN moneda TEXT NOT NULL DEFAULT 'mxn'",
  "ALTER TABLE pedido ADD COLUMN subtotal_centavos INTEGER NOT NULL DEFAULT 0 CHECK (subtotal_centavos >= 0)",
  "ALTER TABLE pedido ADD COLUMN total_centavos INTEGER NOT NULL DEFAULT 0 CHECK (total_centavos >= 0)",
  "ALTER TABLE pedido ADD COLUMN fecha_actualizacion TEXT",
  "ALTER TABLE item_pedido ADD COLUMN nombre_snapshot TEXT",
  "ALTER TABLE item_pedido ADD COLUMN sku_snapshot TEXT",
  "ALTER TABLE item_pedido ADD COLUMN precio_unitario_centavos INTEGER NOT NULL DEFAULT 0 CHECK (precio_unitario_centavos >= 0)",
  "ALTER TABLE pago ADD COLUMN moneda TEXT NOT NULL DEFAULT 'mxn'",
  "ALTER TABLE pago ADD COLUMN monto_centavos INTEGER NOT NULL DEFAULT 0 CHECK (monto_centavos >= 0)",
  "ALTER TABLE pago ADD COLUMN stripe_payment_intent_id TEXT",
  "ALTER TABLE pago ADD COLUMN fecha_actualizacion TEXT",
  "ALTER TABLE suscripcion_usuario ADD COLUMN stripe_checkout_session_id TEXT",
  "ALTER TABLE suscripcion_usuario ADD COLUMN fecha_cancelacion TEXT",
  "ALTER TABLE suscripcion_usuario ADD COLUMN estado_stripe TEXT",
  "ALTER TABLE metodo_pago_usuario ADD COLUMN fecha_actualizacion TEXT",
];

const creates = [
  `CREATE TABLE IF NOT EXISTS schema_migration (version TEXT PRIMARY KEY, applied_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP)`,
  `CREATE TABLE IF NOT EXISTS rol (id_rol INTEGER PRIMARY KEY AUTOINCREMENT, codigo TEXT NOT NULL UNIQUE, nombre TEXT NOT NULL, descripcion TEXT, esta_activo INTEGER NOT NULL DEFAULT 1 CHECK (esta_activo IN (0,1)))`,
  `CREATE TABLE IF NOT EXISTS permiso (id_permiso INTEGER PRIMARY KEY AUTOINCREMENT, codigo TEXT NOT NULL UNIQUE, descripcion TEXT)`,
  `CREATE TABLE IF NOT EXISTS usuario_rol (id_usuario INTEGER NOT NULL, id_rol INTEGER NOT NULL, fecha_creacion TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY(id_usuario,id_rol), FOREIGN KEY(id_usuario) REFERENCES usuario(id_usuario) ON DELETE CASCADE, FOREIGN KEY(id_rol) REFERENCES rol(id_rol) ON DELETE CASCADE)`,
  `CREATE TABLE IF NOT EXISTS rol_permiso (id_rol INTEGER NOT NULL, id_permiso INTEGER NOT NULL, PRIMARY KEY(id_rol,id_permiso), FOREIGN KEY(id_rol) REFERENCES rol(id_rol) ON DELETE CASCADE, FOREIGN KEY(id_permiso) REFERENCES permiso(id_permiso) ON DELETE CASCADE)`,
  `CREATE TABLE IF NOT EXISTS auth_session (id_session TEXT PRIMARY KEY, id_usuario INTEGER NOT NULL, token_hash TEXT NOT NULL UNIQUE, user_agent TEXT, ip_address TEXT, fecha_creacion TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP, fecha_expiracion TEXT NOT NULL, fecha_revocacion TEXT, ultimo_uso TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY(id_usuario) REFERENCES usuario(id_usuario) ON DELETE CASCADE)`,
  `CREATE TABLE IF NOT EXISTS auth_login_attempt (id_attempt INTEGER PRIMARY KEY AUTOINCREMENT, correo TEXT NOT NULL, ip_address TEXT, exitoso INTEGER NOT NULL DEFAULT 0 CHECK(exitoso IN (0,1)), fecha_creacion TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP)`,
  `CREATE TABLE IF NOT EXISTS auth_token (id_token TEXT PRIMARY KEY, id_usuario INTEGER NOT NULL, tipo TEXT NOT NULL CHECK(tipo IN ('password_reset','email_verification')), token_hash TEXT NOT NULL UNIQUE, fecha_expiracion TEXT NOT NULL, fecha_uso TEXT, fecha_creacion TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY(id_usuario) REFERENCES usuario(id_usuario) ON DELETE CASCADE)`,
  `CREATE TABLE IF NOT EXISTS stripe_event (event_id TEXT PRIMARY KEY, tipo TEXT NOT NULL, estado TEXT NOT NULL DEFAULT 'recibido' CHECK(estado IN ('recibido','procesado','fallido')), error TEXT, fecha_creacion TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP, fecha_procesado TEXT)`,
  `CREATE TABLE IF NOT EXISTS intento_pago (id_intento INTEGER PRIMARY KEY AUTOINCREMENT, id_pedido INTEGER, id_suscripcion INTEGER, stripe_checkout_session_id TEXT UNIQUE, stripe_payment_intent_id TEXT UNIQUE, monto_centavos INTEGER NOT NULL CHECK(monto_centavos>=0), moneda TEXT NOT NULL DEFAULT 'mxn', estado TEXT NOT NULL DEFAULT 'creado', codigo_error TEXT, mensaje_error TEXT, fecha_creacion TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP, fecha_actualizacion TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY(id_pedido) REFERENCES pedido(id_pedido) ON DELETE RESTRICT, FOREIGN KEY(id_suscripcion) REFERENCES suscripcion_usuario(id_suscripcion) ON DELETE RESTRICT, CHECK((id_pedido IS NOT NULL) <> (id_suscripcion IS NOT NULL)))`,
  `CREATE TABLE IF NOT EXISTS reembolso (id_reembolso INTEGER PRIMARY KEY AUTOINCREMENT, id_pago INTEGER NOT NULL, stripe_refund_id TEXT UNIQUE, monto_centavos INTEGER NOT NULL CHECK(monto_centavos>0), moneda TEXT NOT NULL DEFAULT 'mxn', estado TEXT NOT NULL, motivo TEXT, fecha_creacion TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY(id_pago) REFERENCES pago(id_pago) ON DELETE RESTRICT)`,
  `CREATE TABLE IF NOT EXISTS carrito (id_carrito INTEGER PRIMARY KEY AUTOINCREMENT, id_usuario INTEGER NOT NULL UNIQUE, fecha_creacion TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP, fecha_actualizacion TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY(id_usuario) REFERENCES usuario(id_usuario) ON DELETE CASCADE)`,
  `CREATE TABLE IF NOT EXISTS carrito_item (id_carrito_item INTEGER PRIMARY KEY AUTOINCREMENT, id_carrito INTEGER NOT NULL, id_producto INTEGER NOT NULL, cantidad INTEGER NOT NULL CHECK(cantidad>0), fecha_creacion TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP, fecha_actualizacion TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP, UNIQUE(id_carrito,id_producto), FOREIGN KEY(id_carrito) REFERENCES carrito(id_carrito) ON DELETE CASCADE, FOREIGN KEY(id_producto) REFERENCES producto(id_producto) ON DELETE RESTRICT)`,
  `CREATE TABLE IF NOT EXISTS reserva_inventario (id_reserva INTEGER PRIMARY KEY AUTOINCREMENT, id_pedido INTEGER NOT NULL, id_producto INTEGER NOT NULL, cantidad INTEGER NOT NULL CHECK(cantidad>0), estado TEXT NOT NULL DEFAULT 'activa' CHECK(estado IN ('activa','consumida','liberada','expirada')), fecha_expiracion TEXT NOT NULL, fecha_creacion TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP, fecha_actualizacion TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP, UNIQUE(id_pedido,id_producto), FOREIGN KEY(id_pedido) REFERENCES pedido(id_pedido) ON DELETE RESTRICT, FOREIGN KEY(id_producto) REFERENCES producto(id_producto) ON DELETE RESTRICT)`,
  `CREATE TABLE IF NOT EXISTS movimiento_inventario (id_movimiento INTEGER PRIMARY KEY AUTOINCREMENT, id_producto INTEGER NOT NULL, tipo TEXT NOT NULL CHECK(tipo IN ('entrada','venta','devolucion','ajuste','reserva','liberacion')), cantidad INTEGER NOT NULL, stock_anterior INTEGER NOT NULL, stock_nuevo INTEGER NOT NULL, referencia_tipo TEXT, referencia_id INTEGER, id_usuario_actor INTEGER, nota TEXT, fecha_creacion TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY(id_producto) REFERENCES producto(id_producto) ON DELETE RESTRICT, FOREIGN KEY(id_usuario_actor) REFERENCES usuario(id_usuario) ON DELETE SET NULL)`,
  `CREATE TABLE IF NOT EXISTS proveedor (id_proveedor INTEGER PRIMARY KEY AUTOINCREMENT, nombre TEXT NOT NULL, correo TEXT, telefono TEXT, direccion TEXT, identificador_fiscal TEXT, esta_activo INTEGER NOT NULL DEFAULT 1 CHECK(esta_activo IN (0,1)), fecha_creacion TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP, fecha_actualizacion TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP)`,
  `CREATE TABLE IF NOT EXISTS producto_proveedor (id_producto INTEGER NOT NULL, id_proveedor INTEGER NOT NULL, sku_proveedor TEXT, costo_centavos INTEGER NOT NULL DEFAULT 0 CHECK(costo_centavos>=0), es_principal INTEGER NOT NULL DEFAULT 0 CHECK(es_principal IN (0,1)), PRIMARY KEY(id_producto,id_proveedor), FOREIGN KEY(id_producto) REFERENCES producto(id_producto) ON DELETE CASCADE, FOREIGN KEY(id_proveedor) REFERENCES proveedor(id_proveedor) ON DELETE CASCADE)`,
  `CREATE TABLE IF NOT EXISTS orden_compra (id_orden_compra INTEGER PRIMARY KEY AUTOINCREMENT, id_proveedor INTEGER NOT NULL, estado TEXT NOT NULL DEFAULT 'borrador' CHECK(estado IN ('borrador','enviada','parcial','recibida','cancelada')), total_centavos INTEGER NOT NULL DEFAULT 0 CHECK(total_centavos>=0), moneda TEXT NOT NULL DEFAULT 'mxn', fecha_creacion TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP, fecha_actualizacion TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY(id_proveedor) REFERENCES proveedor(id_proveedor) ON DELETE RESTRICT)`,
  `CREATE TABLE IF NOT EXISTS item_orden_compra (id_item INTEGER PRIMARY KEY AUTOINCREMENT, id_orden_compra INTEGER NOT NULL, id_producto INTEGER NOT NULL, cantidad INTEGER NOT NULL CHECK(cantidad>0), cantidad_recibida INTEGER NOT NULL DEFAULT 0 CHECK(cantidad_recibida>=0), costo_unitario_centavos INTEGER NOT NULL CHECK(costo_unitario_centavos>=0), FOREIGN KEY(id_orden_compra) REFERENCES orden_compra(id_orden_compra) ON DELETE CASCADE, FOREIGN KEY(id_producto) REFERENCES producto(id_producto) ON DELETE RESTRICT)`,
  `CREATE TABLE IF NOT EXISTS plan_servicio (id_plan INTEGER NOT NULL, id_servicio INTEGER NOT NULL, PRIMARY KEY(id_plan,id_servicio), FOREIGN KEY(id_plan) REFERENCES plan_suscripcion(id_plan) ON DELETE CASCADE, FOREIGN KEY(id_servicio) REFERENCES servicio(id_servicio) ON DELETE CASCADE)`,
  `CREATE TABLE IF NOT EXISTS audit_log (id_audit INTEGER PRIMARY KEY AUTOINCREMENT, id_usuario_actor INTEGER, accion TEXT NOT NULL, entidad TEXT NOT NULL, entidad_id TEXT, datos_anteriores TEXT, datos_nuevos TEXT, ip_address TEXT, request_id TEXT, fecha_creacion TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY(id_usuario_actor) REFERENCES usuario(id_usuario) ON DELETE SET NULL)`,
  `CREATE TABLE IF NOT EXISTS historial_estado_pedido (id_historial INTEGER PRIMARY KEY AUTOINCREMENT, id_pedido INTEGER NOT NULL, estado_anterior TEXT, estado_nuevo TEXT NOT NULL, id_usuario_actor INTEGER, nota TEXT, fecha_creacion TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY(id_pedido) REFERENCES pedido(id_pedido) ON DELETE RESTRICT, FOREIGN KEY(id_usuario_actor) REFERENCES usuario(id_usuario) ON DELETE SET NULL)`,
  `CREATE TRIGGER IF NOT EXISTS trg_item_pedido_origen_insert BEFORE INSERT ON item_pedido WHEN (NEW.id_producto IS NULL) = (NEW.id_servicio IS NULL) BEGIN SELECT RAISE(ABORT, 'item_pedido requiere exactamente un origen'); END`,
  `CREATE TRIGGER IF NOT EXISTS trg_item_pedido_origen_update BEFORE UPDATE ON item_pedido WHEN (NEW.id_producto IS NULL) = (NEW.id_servicio IS NULL) BEGIN SELECT RAISE(ABORT, 'item_pedido requiere exactamente un origen'); END`,
  `CREATE TRIGGER IF NOT EXISTS trg_pago_origen_insert BEFORE INSERT ON pago WHEN (NEW.id_pedido IS NULL) = (NEW.id_suscripcion IS NULL) BEGIN SELECT RAISE(ABORT, 'pago requiere exactamente un origen'); END`,
  `CREATE TRIGGER IF NOT EXISTS trg_pago_origen_update BEFORE UPDATE ON pago WHEN (NEW.id_pedido IS NULL) = (NEW.id_suscripcion IS NULL) BEGIN SELECT RAISE(ABORT, 'pago requiere exactamente un origen'); END`,
];

const indexes = [
  "CREATE UNIQUE INDEX IF NOT EXISTS ux_usuario_correo_nocase ON usuario(lower(correo))",
  "CREATE UNIQUE INDEX IF NOT EXISTS ux_usuario_nombre_nocase ON usuario(lower(nombre_usuario))",
  "CREATE UNIQUE INDEX IF NOT EXISTS ux_producto_sku ON producto(sku) WHERE sku IS NOT NULL",
  "CREATE UNIQUE INDEX IF NOT EXISTS ux_pedido_stripe_session ON pedido(stripe_checkout_session_id) WHERE stripe_checkout_session_id IS NOT NULL",
  "CREATE UNIQUE INDEX IF NOT EXISTS ux_pago_transaccion ON pago(id_transaccion) WHERE id_transaccion IS NOT NULL",
  "CREATE UNIQUE INDEX IF NOT EXISTS ux_pago_intent ON pago(stripe_payment_intent_id) WHERE stripe_payment_intent_id IS NOT NULL",
  "CREATE UNIQUE INDEX IF NOT EXISTS ux_metodo_stripe ON metodo_pago_usuario(stripe_payment_method_id) WHERE stripe_payment_method_id IS NOT NULL",
  "CREATE UNIQUE INDEX IF NOT EXISTS ux_suscripcion_stripe ON suscripcion_usuario(stripe_subscription_id) WHERE stripe_subscription_id IS NOT NULL",
  "CREATE UNIQUE INDEX IF NOT EXISTS ux_suscripcion_activa_usuario ON suscripcion_usuario(id_usuario) WHERE estado='activa'",
  "CREATE UNIQUE INDEX IF NOT EXISTS ux_metodo_default_usuario ON metodo_pago_usuario(id_usuario) WHERE esta_activo=1 AND es_predeterminado=1",
  "CREATE UNIQUE INDEX IF NOT EXISTS ux_resena_usuario_producto ON reseña_producto(id_usuario,id_producto)",
  "CREATE INDEX IF NOT EXISTS idx_pedido_usuario_fecha ON pedido(id_usuario,fecha_creacion DESC)",
  "CREATE INDEX IF NOT EXISTS idx_pago_estado_fecha ON pago(estado,fecha_pago DESC)",
  "CREATE INDEX IF NOT EXISTS idx_suscripcion_usuario_estado_fin ON suscripcion_usuario(id_usuario,estado,fecha_fin)",
  "CREATE INDEX IF NOT EXISTS idx_producto_activo_categoria ON producto(esta_activo,id_categoria)",
  "CREATE INDEX IF NOT EXISTS idx_producto_activo_destacado ON producto(esta_activo,es_destacado)",
  "CREATE INDEX IF NOT EXISTS idx_reserva_estado_expiracion ON reserva_inventario(estado,fecha_expiracion)",
  "CREATE INDEX IF NOT EXISTS idx_audit_entidad_fecha ON audit_log(entidad,entidad_id,fecha_creacion DESC)",
  "CREATE INDEX IF NOT EXISTS idx_login_attempt_lookup ON auth_login_attempt(correo,ip_address,fecha_creacion DESC)",
];

await db.execute("PRAGMA foreign_keys=ON");
for (const sql of alters) {
  try { await db.execute(sql); } catch (error) {
    if (!String(error).includes("duplicate column name")) throw error;
  }
}
for (const sql of creates) await db.execute(sql);
for (const sql of ["ALTER TABLE metodo_pago_usuario DROP COLUMN numero_tarjeta","ALTER TABLE metodo_pago_usuario DROP COLUMN cvv"]) { try { await db.execute(sql); } catch (error) { if (!String(error).includes("no such column")) throw error; } }

await db.execute("DELETE FROM reseña_producto WHERE id_reseña NOT IN (SELECT MAX(id_reseña) FROM reseña_producto GROUP BY id_usuario,id_producto)");
await db.execute("UPDATE suscripcion_usuario SET estado='expirada' WHERE estado='activa' AND date(fecha_fin)<date('now')");
for (const sql of indexes) await db.execute(sql);

await db.batch([
  { sql: "UPDATE pedido SET subtotal_centavos=round(monto_total*100),total_centavos=round(monto_total*100) WHERE total_centavos=0", args: [] },
  { sql: "UPDATE item_pedido SET precio_unitario_centavos=round(precio_unitario*100),nombre_snapshot=COALESCE(nombre_snapshot,(SELECT nombre FROM producto WHERE producto.id_producto=item_pedido.id_producto)) WHERE precio_unitario_centavos=0", args: [] },
  { sql: "UPDATE pago SET monto_centavos=round(monto*100) WHERE monto_centavos=0", args: [] },
  { sql: "INSERT OR IGNORE INTO rol(codigo,nombre) VALUES ('usuario','Usuario'),('administrador','Administrador')", args: [] },
  { sql: "INSERT OR IGNORE INTO permiso(codigo,descripcion) VALUES ('admin.access','Acceso al panel'),('users.manage','Gestionar usuarios'),('products.manage','Gestionar productos'),('services.manage','Gestionar servicios'),('plans.manage','Gestionar planes'),('sales.view','Ver ventas'),('suppliers.manage','Gestionar proveedores'),('inventory.manage','Gestionar inventario'),('audit.view','Ver auditoria'),('media.manage','Gestionar multimedia')", args: [] },
  { sql: "INSERT OR IGNORE INTO usuario_rol(id_usuario,id_rol) SELECT u.id_usuario,r.id_rol FROM usuario u JOIN rol r ON r.codigo=u.rol", args: [] },
  { sql: "INSERT OR IGNORE INTO rol_permiso(id_rol,id_permiso) SELECT r.id_rol,p.id_permiso FROM rol r CROSS JOIN permiso p WHERE r.codigo='administrador'", args: [] },
  { sql: "INSERT OR IGNORE INTO plan_servicio(id_plan,id_servicio) SELECT 1,id_servicio FROM servicio WHERE id_servicio IN (1,2)", args: [] },
  { sql: "INSERT OR IGNORE INTO plan_servicio(id_plan,id_servicio) SELECT 2,id_servicio FROM servicio WHERE id_servicio IN (1,2,3,4)", args: [] },
  { sql: "INSERT OR IGNORE INTO plan_servicio(id_plan,id_servicio) SELECT 3,id_servicio FROM servicio WHERE id_servicio IN (1,2,3,4,5,6)", args: [] },
  { sql: "INSERT OR IGNORE INTO schema_migration(version) VALUES ('002_security_hardening')", args: [] },
], "write");

const legacyUsers = (
  await db.execute(
    "SELECT id_usuario,hash_contraseña FROM usuario WHERE hash_contraseña NOT LIKE '$2%'",
  )
).rows;
for (const user of legacyUsers) {
  await db.execute({
    sql: "UPDATE usuario SET hash_contraseña=? WHERE id_usuario=?",
    args: [await bcrypt.hash(String(user.hash_contraseña), 12), Number(user.id_usuario)],
  });
}
console.log("Migracion 002_security_hardening aplicada.");
