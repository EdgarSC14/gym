PRAGMA foreign_keys = ON;

CREATE TABLE IF NOT EXISTS usuario (
  id_usuario INTEGER PRIMARY KEY AUTOINCREMENT,
  nombre_usuario TEXT NOT NULL UNIQUE,
  correo TEXT NOT NULL UNIQUE,
  hash_contraseña TEXT NOT NULL,
  nombre TEXT NOT NULL,
  apellido TEXT NOT NULL DEFAULT '',
  telefono TEXT NOT NULL DEFAULT '',
  direccion TEXT NOT NULL DEFAULT '',
  rol TEXT NOT NULL DEFAULT 'usuario' CHECK (rol IN ('usuario','administrador')),
  esta_activo INTEGER NOT NULL DEFAULT 1 CHECK (esta_activo IN (0,1)),
  stripe_customer_id TEXT,
  email_verificado INTEGER NOT NULL DEFAULT 0 CHECK (email_verificado IN (0,1)),
  ultimo_acceso TEXT,
  fecha_creacion TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  fecha_actualizacion TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS categoria_producto (
  id_categoria INTEGER PRIMARY KEY AUTOINCREMENT,
  nombre TEXT NOT NULL,
  descripcion TEXT,
  esta_activo INTEGER NOT NULL DEFAULT 1 CHECK (esta_activo IN (0,1))
);

CREATE TABLE IF NOT EXISTS producto (
  id_producto INTEGER PRIMARY KEY AUTOINCREMENT,
  nombre TEXT NOT NULL,
  descripcion TEXT,
  precio NUMERIC NOT NULL CHECK (precio >= 0),
  precio_anterior NUMERIC,
  cantidad_stock INTEGER NOT NULL DEFAULT 0 CHECK (cantidad_stock >= 0),
  stock_reservado INTEGER NOT NULL DEFAULT 0 CHECK (stock_reservado >= 0),
  umbral_stock_bajo INTEGER NOT NULL DEFAULT 10 CHECK (umbral_stock_bajo >= 0),
  sku TEXT,
  id_categoria INTEGER,
  url_imagen TEXT,
  calificacion NUMERIC NOT NULL DEFAULT 0 CHECK (calificacion BETWEEN 0 AND 5),
  es_destacado INTEGER NOT NULL DEFAULT 0 CHECK (es_destacado IN (0,1)),
  esta_activo INTEGER NOT NULL DEFAULT 1 CHECK (esta_activo IN (0,1)),
  fecha_creacion TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  fecha_actualizacion TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (id_categoria) REFERENCES categoria_producto(id_categoria)
);

CREATE TABLE IF NOT EXISTS reseña_producto (
  id_reseña INTEGER PRIMARY KEY AUTOINCREMENT,
  id_producto INTEGER NOT NULL,
  id_usuario INTEGER NOT NULL,
  calificacion INTEGER NOT NULL CHECK (calificacion BETWEEN 1 AND 5),
  comentario TEXT,
  fecha_creacion TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (id_producto) REFERENCES producto(id_producto),
  FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario)
);

CREATE TABLE IF NOT EXISTS servicio (
  id_servicio INTEGER PRIMARY KEY AUTOINCREMENT,
  nombre TEXT NOT NULL,
  descripcion TEXT NOT NULL,
  beneficios TEXT NOT NULL,
  duracion_minutos INTEGER NOT NULL CHECK (duracion_minutos > 0),
  url_imagen TEXT NOT NULL,
  url_video TEXT NOT NULL,
  esta_activo INTEGER NOT NULL DEFAULT 1 CHECK (esta_activo IN (0,1)),
  fecha_creacion TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS imagen_servicio (
  id_imagen INTEGER PRIMARY KEY AUTOINCREMENT,
  id_servicio INTEGER NOT NULL,
  url_imagen TEXT NOT NULL,
  fecha_subida TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (id_servicio) REFERENCES servicio(id_servicio) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS plan_suscripcion (
  id_plan INTEGER PRIMARY KEY AUTOINCREMENT,
  nombre TEXT NOT NULL,
  precio NUMERIC NOT NULL CHECK (precio >= 0),
  duracion_dias INTEGER NOT NULL CHECK (duracion_dias > 0),
  descripcion TEXT NOT NULL,
  beneficios TEXT NOT NULL,
  stripe_price_id TEXT,
  esta_activo INTEGER NOT NULL DEFAULT 1 CHECK (esta_activo IN (0,1))
);

CREATE TABLE IF NOT EXISTS suscripcion_usuario (
  id_suscripcion INTEGER PRIMARY KEY AUTOINCREMENT,
  id_usuario INTEGER NOT NULL,
  id_plan INTEGER NOT NULL,
  fecha_inicio TEXT NOT NULL,
  fecha_fin TEXT NOT NULL,
  estado TEXT NOT NULL DEFAULT 'activa' CHECK (estado IN ('activa','expirada','cancelada')),
  renovacion_automatica INTEGER NOT NULL DEFAULT 1 CHECK (renovacion_automatica IN (0,1)),
  stripe_checkout_session_id TEXT,
  stripe_subscription_id TEXT,
  estado_stripe TEXT,
  fecha_cancelacion TEXT,
  fecha_creacion TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (id_plan) REFERENCES plan_suscripcion(id_plan),
  FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario)
);

CREATE TABLE IF NOT EXISTS pedido (
  id_pedido INTEGER PRIMARY KEY AUTOINCREMENT,
  id_usuario INTEGER NOT NULL,
  monto_total NUMERIC NOT NULL CHECK (monto_total >= 0),
  estado TEXT NOT NULL DEFAULT 'pendiente' CHECK (estado IN ('pendiente','pagado','enviado','entregado','cancelado')),
  direccion_envio TEXT,
  metodo_pago TEXT,
  stripe_checkout_session_id TEXT,
  moneda TEXT NOT NULL DEFAULT 'mxn',
  subtotal_centavos INTEGER NOT NULL DEFAULT 0 CHECK (subtotal_centavos >= 0),
  total_centavos INTEGER NOT NULL DEFAULT 0 CHECK (total_centavos >= 0),
  fecha_creacion TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  fecha_actualizacion TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario)
);

CREATE TABLE IF NOT EXISTS item_pedido (
  id_item_pedido INTEGER PRIMARY KEY AUTOINCREMENT,
  id_pedido INTEGER NOT NULL,
  id_producto INTEGER,
  id_servicio INTEGER,
  cantidad INTEGER NOT NULL CHECK (cantidad > 0),
  precio_unitario NUMERIC NOT NULL CHECK (precio_unitario >= 0),
  nombre_snapshot TEXT,
  sku_snapshot TEXT,
  precio_unitario_centavos INTEGER NOT NULL DEFAULT 0 CHECK (precio_unitario_centavos >= 0),
  FOREIGN KEY (id_pedido) REFERENCES pedido(id_pedido),
  FOREIGN KEY (id_producto) REFERENCES producto(id_producto),
  FOREIGN KEY (id_servicio) REFERENCES servicio(id_servicio),
  CHECK (id_producto IS NOT NULL OR id_servicio IS NOT NULL)
);

CREATE TABLE IF NOT EXISTS pago (
  id_pago INTEGER PRIMARY KEY AUTOINCREMENT,
  id_pedido INTEGER,
  id_suscripcion INTEGER,
  monto NUMERIC NOT NULL CHECK (monto >= 0),
  metodo_pago TEXT,
  id_transaccion TEXT,
  stripe_payment_intent_id TEXT,
  estado TEXT NOT NULL DEFAULT 'pendiente' CHECK (estado IN ('pendiente','completado','fallido','reembolsado')),
  moneda TEXT NOT NULL DEFAULT 'mxn',
  monto_centavos INTEGER NOT NULL DEFAULT 0 CHECK (monto_centavos >= 0),
  fecha_pago TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  fecha_actualizacion TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (id_pedido) REFERENCES pedido(id_pedido),
  FOREIGN KEY (id_suscripcion) REFERENCES suscripcion_usuario(id_suscripcion)
);

CREATE TABLE IF NOT EXISTS metodo_pago_usuario (
  id_metodo_pago INTEGER PRIMARY KEY AUTOINCREMENT,
  id_usuario INTEGER NOT NULL,
  tipo_tarjeta TEXT NOT NULL,
  fecha_vencimiento TEXT NOT NULL,
  nombre_titular TEXT NOT NULL,
  stripe_payment_method_id TEXT,
  ultimos_cuatro TEXT,
  es_predeterminado INTEGER NOT NULL DEFAULT 0 CHECK (es_predeterminado IN (0,1)),
  esta_activo INTEGER NOT NULL DEFAULT 1 CHECK (esta_activo IN (0,1)),
  fecha_creacion TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  fecha_actualizacion TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario)
);


CREATE TABLE IF NOT EXISTS schema_migration (
  version TEXT PRIMARY KEY,
  applied_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS rol (
  id_rol INTEGER PRIMARY KEY AUTOINCREMENT,
  codigo TEXT NOT NULL UNIQUE,
  nombre TEXT NOT NULL,
  descripcion TEXT,
  esta_activo INTEGER NOT NULL DEFAULT 1 CHECK (esta_activo IN (0,1))
);

CREATE TABLE IF NOT EXISTS permiso (
  id_permiso INTEGER PRIMARY KEY AUTOINCREMENT,
  codigo TEXT NOT NULL UNIQUE,
  descripcion TEXT
);

CREATE TABLE IF NOT EXISTS usuario_rol (
  id_usuario INTEGER NOT NULL,
  id_rol INTEGER NOT NULL,
  fecha_creacion TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_usuario, id_rol),
  FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario) ON DELETE CASCADE,
  FOREIGN KEY (id_rol) REFERENCES rol(id_rol) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS rol_permiso (
  id_rol INTEGER NOT NULL,
  id_permiso INTEGER NOT NULL,
  PRIMARY KEY (id_rol, id_permiso),
  FOREIGN KEY (id_rol) REFERENCES rol(id_rol) ON DELETE CASCADE,
  FOREIGN KEY (id_permiso) REFERENCES permiso(id_permiso) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS auth_session (
  id_session TEXT PRIMARY KEY,
  id_usuario INTEGER NOT NULL,
  token_hash TEXT NOT NULL UNIQUE,
  user_agent TEXT,
  ip_address TEXT,
  fecha_creacion TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  fecha_expiracion TEXT NOT NULL,
  fecha_revocacion TEXT,
  ultimo_uso TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS auth_login_attempt (
  id_attempt INTEGER PRIMARY KEY AUTOINCREMENT,
  correo TEXT NOT NULL,
  ip_address TEXT,
  exitoso INTEGER NOT NULL DEFAULT 0 CHECK (exitoso IN (0,1)),
  fecha_creacion TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS auth_token (
  id_token TEXT PRIMARY KEY,
  id_usuario INTEGER NOT NULL,
  tipo TEXT NOT NULL CHECK (tipo IN ('password_reset','email_verification')),
  token_hash TEXT NOT NULL UNIQUE,
  fecha_expiracion TEXT NOT NULL,
  fecha_uso TEXT,
  fecha_creacion TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS stripe_event (
  event_id TEXT PRIMARY KEY,
  tipo TEXT NOT NULL,
  estado TEXT NOT NULL DEFAULT 'recibido' CHECK (estado IN ('recibido','procesado','fallido')),
  error TEXT,
  fecha_creacion TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  fecha_procesado TEXT
);

CREATE TABLE IF NOT EXISTS intento_pago (
  id_intento INTEGER PRIMARY KEY AUTOINCREMENT,
  id_pedido INTEGER,
  id_suscripcion INTEGER,
  stripe_checkout_session_id TEXT UNIQUE,
  stripe_payment_intent_id TEXT UNIQUE,
  monto_centavos INTEGER NOT NULL CHECK (monto_centavos >= 0),
  moneda TEXT NOT NULL DEFAULT 'mxn',
  estado TEXT NOT NULL DEFAULT 'creado',
  codigo_error TEXT,
  mensaje_error TEXT,
  fecha_creacion TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  fecha_actualizacion TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (id_pedido) REFERENCES pedido(id_pedido) ON DELETE RESTRICT,
  FOREIGN KEY (id_suscripcion) REFERENCES suscripcion_usuario(id_suscripcion) ON DELETE RESTRICT,
  CHECK ((id_pedido IS NOT NULL) <> (id_suscripcion IS NOT NULL))
);

CREATE TABLE IF NOT EXISTS reembolso (
  id_reembolso INTEGER PRIMARY KEY AUTOINCREMENT,
  id_pago INTEGER NOT NULL,
  stripe_refund_id TEXT UNIQUE,
  monto_centavos INTEGER NOT NULL CHECK (monto_centavos > 0),
  moneda TEXT NOT NULL DEFAULT 'mxn',
  estado TEXT NOT NULL,
  motivo TEXT,
  fecha_creacion TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (id_pago) REFERENCES pago(id_pago) ON DELETE RESTRICT
);

CREATE TABLE IF NOT EXISTS carrito (
  id_carrito INTEGER PRIMARY KEY AUTOINCREMENT,
  id_usuario INTEGER NOT NULL UNIQUE,
  fecha_creacion TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  fecha_actualizacion TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS carrito_item (
  id_carrito_item INTEGER PRIMARY KEY AUTOINCREMENT,
  id_carrito INTEGER NOT NULL,
  id_producto INTEGER NOT NULL,
  cantidad INTEGER NOT NULL CHECK (cantidad > 0),
  fecha_creacion TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  fecha_actualizacion TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE (id_carrito, id_producto),
  FOREIGN KEY (id_carrito) REFERENCES carrito(id_carrito) ON DELETE CASCADE,
  FOREIGN KEY (id_producto) REFERENCES producto(id_producto) ON DELETE RESTRICT
);

CREATE TABLE IF NOT EXISTS reserva_inventario (
  id_reserva INTEGER PRIMARY KEY AUTOINCREMENT,
  id_pedido INTEGER NOT NULL,
  id_producto INTEGER NOT NULL,
  cantidad INTEGER NOT NULL CHECK (cantidad > 0),
  estado TEXT NOT NULL DEFAULT 'activa' CHECK (estado IN ('activa','consumida','liberada','expirada')),
  fecha_expiracion TEXT NOT NULL,
  fecha_creacion TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  fecha_actualizacion TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE (id_pedido, id_producto),
  FOREIGN KEY (id_pedido) REFERENCES pedido(id_pedido) ON DELETE RESTRICT,
  FOREIGN KEY (id_producto) REFERENCES producto(id_producto) ON DELETE RESTRICT
);

CREATE TABLE IF NOT EXISTS movimiento_inventario (
  id_movimiento INTEGER PRIMARY KEY AUTOINCREMENT,
  id_producto INTEGER NOT NULL,
  tipo TEXT NOT NULL CHECK (tipo IN ('entrada','venta','devolucion','ajuste','reserva','liberacion')),
  cantidad INTEGER NOT NULL,
  stock_anterior INTEGER NOT NULL,
  stock_nuevo INTEGER NOT NULL,
  referencia_tipo TEXT,
  referencia_id INTEGER,
  id_usuario_actor INTEGER,
  nota TEXT,
  fecha_creacion TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (id_producto) REFERENCES producto(id_producto) ON DELETE RESTRICT,
  FOREIGN KEY (id_usuario_actor) REFERENCES usuario(id_usuario) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS proveedor (
  id_proveedor INTEGER PRIMARY KEY AUTOINCREMENT,
  nombre TEXT NOT NULL,
  correo TEXT,
  telefono TEXT,
  direccion TEXT,
  identificador_fiscal TEXT,
  esta_activo INTEGER NOT NULL DEFAULT 1 CHECK (esta_activo IN (0,1)),
  fecha_creacion TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  fecha_actualizacion TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS producto_proveedor (
  id_producto INTEGER NOT NULL,
  id_proveedor INTEGER NOT NULL,
  sku_proveedor TEXT,
  costo_centavos INTEGER NOT NULL DEFAULT 0 CHECK (costo_centavos >= 0),
  es_principal INTEGER NOT NULL DEFAULT 0 CHECK (es_principal IN (0,1)),
  PRIMARY KEY (id_producto, id_proveedor),
  FOREIGN KEY (id_producto) REFERENCES producto(id_producto) ON DELETE CASCADE,
  FOREIGN KEY (id_proveedor) REFERENCES proveedor(id_proveedor) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS orden_compra (
  id_orden_compra INTEGER PRIMARY KEY AUTOINCREMENT,
  id_proveedor INTEGER NOT NULL,
  estado TEXT NOT NULL DEFAULT 'borrador' CHECK (estado IN ('borrador','enviada','parcial','recibida','cancelada')),
  total_centavos INTEGER NOT NULL DEFAULT 0 CHECK (total_centavos >= 0),
  moneda TEXT NOT NULL DEFAULT 'mxn',
  fecha_creacion TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  fecha_actualizacion TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (id_proveedor) REFERENCES proveedor(id_proveedor) ON DELETE RESTRICT
);

CREATE TABLE IF NOT EXISTS item_orden_compra (
  id_item INTEGER PRIMARY KEY AUTOINCREMENT,
  id_orden_compra INTEGER NOT NULL,
  id_producto INTEGER NOT NULL,
  cantidad INTEGER NOT NULL CHECK (cantidad > 0),
  cantidad_recibida INTEGER NOT NULL DEFAULT 0 CHECK (cantidad_recibida >= 0),
  costo_unitario_centavos INTEGER NOT NULL CHECK (costo_unitario_centavos >= 0),
  FOREIGN KEY (id_orden_compra) REFERENCES orden_compra(id_orden_compra) ON DELETE CASCADE,
  FOREIGN KEY (id_producto) REFERENCES producto(id_producto) ON DELETE RESTRICT
);

CREATE TABLE IF NOT EXISTS plan_servicio (
  id_plan INTEGER NOT NULL,
  id_servicio INTEGER NOT NULL,
  PRIMARY KEY (id_plan, id_servicio),
  FOREIGN KEY (id_plan) REFERENCES plan_suscripcion(id_plan) ON DELETE CASCADE,
  FOREIGN KEY (id_servicio) REFERENCES servicio(id_servicio) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS audit_log (
  id_audit INTEGER PRIMARY KEY AUTOINCREMENT,
  id_usuario_actor INTEGER,
  accion TEXT NOT NULL,
  entidad TEXT NOT NULL,
  entidad_id TEXT,
  datos_anteriores TEXT,
  datos_nuevos TEXT,
  ip_address TEXT,
  request_id TEXT,
  fecha_creacion TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (id_usuario_actor) REFERENCES usuario(id_usuario) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS historial_estado_pedido (
  id_historial INTEGER PRIMARY KEY AUTOINCREMENT,
  id_pedido INTEGER NOT NULL,
  estado_anterior TEXT,
  estado_nuevo TEXT NOT NULL,
  id_usuario_actor INTEGER,
  nota TEXT,
  fecha_creacion TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (id_pedido) REFERENCES pedido(id_pedido) ON DELETE RESTRICT,
  FOREIGN KEY (id_usuario_actor) REFERENCES usuario(id_usuario) ON DELETE SET NULL
);

CREATE INDEX IF NOT EXISTS idx_producto_categoria ON producto(id_categoria);
CREATE INDEX IF NOT EXISTS idx_reseña_producto ON reseña_producto(id_producto);
CREATE INDEX IF NOT EXISTS idx_pedido_usuario ON pedido(id_usuario);
CREATE INDEX IF NOT EXISTS idx_item_pedido ON item_pedido(id_pedido);
CREATE INDEX IF NOT EXISTS idx_pago_pedido ON pago(id_pedido);
CREATE INDEX IF NOT EXISTS idx_suscripcion_usuario ON suscripcion_usuario(id_usuario);
CREATE INDEX IF NOT EXISTS idx_metodo_pago_usuario ON metodo_pago_usuario(id_usuario);

CREATE UNIQUE INDEX IF NOT EXISTS ux_usuario_correo_nocase ON usuario(lower(correo));
CREATE UNIQUE INDEX IF NOT EXISTS ux_usuario_nombre_nocase ON usuario(lower(nombre_usuario));
CREATE UNIQUE INDEX IF NOT EXISTS ux_producto_sku ON producto(sku) WHERE sku IS NOT NULL;
CREATE UNIQUE INDEX IF NOT EXISTS ux_pedido_stripe_session ON pedido(stripe_checkout_session_id) WHERE stripe_checkout_session_id IS NOT NULL;
CREATE UNIQUE INDEX IF NOT EXISTS ux_pago_transaccion ON pago(id_transaccion) WHERE id_transaccion IS NOT NULL;
CREATE UNIQUE INDEX IF NOT EXISTS ux_pago_intent ON pago(stripe_payment_intent_id) WHERE stripe_payment_intent_id IS NOT NULL;
CREATE UNIQUE INDEX IF NOT EXISTS ux_metodo_stripe ON metodo_pago_usuario(stripe_payment_method_id) WHERE stripe_payment_method_id IS NOT NULL;
CREATE UNIQUE INDEX IF NOT EXISTS ux_suscripcion_stripe ON suscripcion_usuario(stripe_subscription_id) WHERE stripe_subscription_id IS NOT NULL;
CREATE UNIQUE INDEX IF NOT EXISTS ux_suscripcion_activa_usuario ON suscripcion_usuario(id_usuario) WHERE estado = 'activa';
CREATE UNIQUE INDEX IF NOT EXISTS ux_metodo_default_usuario ON metodo_pago_usuario(id_usuario) WHERE esta_activo = 1 AND es_predeterminado = 1;
CREATE UNIQUE INDEX IF NOT EXISTS ux_resena_usuario_producto ON reseña_producto(id_usuario,id_producto);
CREATE INDEX IF NOT EXISTS idx_pedido_usuario_fecha ON pedido(id_usuario,fecha_creacion DESC);
CREATE INDEX IF NOT EXISTS idx_pago_estado_fecha ON pago(estado,fecha_pago DESC);
CREATE INDEX IF NOT EXISTS idx_suscripcion_usuario_estado_fin ON suscripcion_usuario(id_usuario,estado,fecha_fin);
CREATE INDEX IF NOT EXISTS idx_producto_activo_categoria ON producto(esta_activo,id_categoria);
CREATE INDEX IF NOT EXISTS idx_producto_activo_destacado ON producto(esta_activo,es_destacado);
CREATE INDEX IF NOT EXISTS idx_reserva_estado_expiracion ON reserva_inventario(estado,fecha_expiracion);
CREATE INDEX IF NOT EXISTS idx_audit_entidad_fecha ON audit_log(entidad,entidad_id,fecha_creacion DESC);
CREATE INDEX IF NOT EXISTS idx_login_attempt_lookup ON auth_login_attempt(correo,ip_address,fecha_creacion DESC);

CREATE TRIGGER IF NOT EXISTS trg_item_pedido_origen_insert
BEFORE INSERT ON item_pedido
WHEN (NEW.id_producto IS NULL) = (NEW.id_servicio IS NULL)
BEGIN
  SELECT RAISE(ABORT, 'item_pedido requiere exactamente un origen');
END;

CREATE TRIGGER IF NOT EXISTS trg_item_pedido_origen_update
BEFORE UPDATE ON item_pedido
WHEN (NEW.id_producto IS NULL) = (NEW.id_servicio IS NULL)
BEGIN
  SELECT RAISE(ABORT, 'item_pedido requiere exactamente un origen');
END;

CREATE TRIGGER IF NOT EXISTS trg_pago_origen_insert
BEFORE INSERT ON pago
WHEN (NEW.id_pedido IS NULL) = (NEW.id_suscripcion IS NULL)
BEGIN
  SELECT RAISE(ABORT, 'pago requiere exactamente un origen');
END;

CREATE TRIGGER IF NOT EXISTS trg_pago_origen_update
BEFORE UPDATE ON pago
WHEN (NEW.id_pedido IS NULL) = (NEW.id_suscripcion IS NULL)
BEGIN
  SELECT RAISE(ABORT, 'pago requiere exactamente un origen');
END;
