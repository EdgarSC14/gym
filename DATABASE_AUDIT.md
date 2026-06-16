# Auditoria de base de datos Astro + Turso

Fecha de revision: 2026-06-15

## Estado posterior a la implementacion

La migracion `002_security_hardening` fue aplicada y verificada tanto en `local.db` como en Turso remoto `fit360`. Los hallazgos descritos debajo documentan el estado previo que motivo los cambios.

- Se conservaron 6 usuarios, 57 pedidos y 73 pagos.
- Se agregaron RBAC, sesiones revocables, proteccion de login, auditoria, proveedores, carrito persistente, reservas y movimientos de inventario.
- Se agregaron eventos Stripe idempotentes, intentos de pago, reembolsos y estados de Checkout/suscripcion.
- Se eliminaron PAN completos y CVV, se convirtieron todas las contraseñas heredadas a bcrypt y se corrigieron duplicados/suscripciones vencidas.
- `PRAGMA foreign_key_check` e `integrity_check` terminaron sin errores.
- Stripe real queda pendiente de validacion hasta configurar `STRIPE_SECRET_KEY` y `STRIPE_WEBHOOK_SECRET`.

## Veredicto inicial

El esquema actual conserva correctamente los datos heredados y cubre un MVP de
usuarios, productos, pedidos, pagos, servicios y suscripciones. No es suficiente
como esquema final de produccion para autenticacion moderna, permisos granulares,
Stripe idempotente, proveedores, inventario concurrente y auditoria.

Los cambios recomendados fueron aplicados despues de esta auditoria mediante `scripts/migrate-security.mjs`.

## Estado verificado antes de cambios

- 12 tablas de negocio migradas.
- Turso tiene llaves foraneas activas y `PRAGMA foreign_key_check` no reporta
  registros huerfanos.
- Los importes de los 57 pedidos coinciden con sus pagos asociados.
- No hay IDs Stripe duplicados actualmente.
- Hay una resena duplicada para el mismo usuario y producto.
- Hay 4 suscripciones con estado `activa` cuya fecha de fin fue en 2025.
- Todos los usuarios, planes y suscripciones heredados carecen de IDs Stripe.

## Hallazgos prioritarios

### Criticos

1. **Webhooks Stripe sin idempotencia persistente**

   No existe una tabla de eventos Stripe ni restricciones `UNIQUE` sobre
   `id_transaccion` o `stripe_checkout_session_id`. Stripe puede reenviar eventos;
   el webhook actual podria insertar pagos, suscripciones o descontar stock mas de
   una vez.

2. **Stock sin reserva ni historial**

   `producto.cantidad_stock` es el unico registro de inventario. El stock se valida
   antes de crear Checkout y se descuenta despues del pago. Dos compradores pueden
   pagar simultaneamente el mismo inventario. No hay movimientos, reservas,
   ajustes ni trazabilidad.

3. **Operaciones de compra no atomicas**

   La creacion de pedido, items, sesion Stripe, pago y descuento de stock usa
   multiples escrituras separadas. Un fallo intermedio puede dejar pedidos
   incompletos. Deben utilizarse transacciones `write` o `batch`.

4. **Columnas sensibles heredadas**

   `metodo_pago_usuario.numero_tarjeta` y `cvv` permanecen en el esquema aunque los
   datos fueron vaciados. Deben desaparecer; ninguna aplicacion debe aceptar o
   almacenar CVV o PAN completo.

5. **Politicas de eliminacion incompletas**

   Salvo `imagen_servicio`, las llaves foraneas usan la accion predeterminada
   `NO ACTION`. El CRUD administrativo ejecuta eliminaciones fisicas que fallaran
   para usuarios, productos, pedidos o planes con dependencias. Los historicos de
   venta no deben eliminarse en cascada.

### Altos

6. **No existe soporte para proveedores**

   Faltan proveedores, relacion producto-proveedor, costos, ordenes de compra y
   recepciones de inventario.

7. **Roles demasiado simples**

   `usuario.rol` solo admite `usuario` y `administrador`. Sirve para el MVP, pero no
   permite permisos como gestionar inventario, ventas, contenido o usuarios de
   forma independiente.

8. **No existen sesiones revocables**

   La aplicacion usa una cookie firmada sin tabla de sesiones. No se puede cerrar
   sesion en todos los dispositivos, revocar una sesion comprometida ni registrar
   dispositivos/expiraciones.

9. **Stripe parcialmente modelado**

   `stripe_customer_id`, `stripe_price_id` y `stripe_subscription_id` existen, pero
   el flujo actual crea pagos unicos incluso para suscripciones. Faltan eventos,
   intentos de pago, moneda, reembolsos, periodos Stripe y estados completos.

10. **Importes monetarios con afinidad `NUMERIC`**

    SQLite puede almacenar valores `NUMERIC` como INTEGER, REAL o incluso TEXT.
    Para evitar redondeos, los importes nuevos deben guardarse como enteros en
    centavos, por ejemplo `monto_centavos INTEGER`.

11. **Acceso a servicios codificado en TypeScript**

    La relacion entre planes y servicios depende de IDs fijos en el codigo. Falta
    una tabla `plan_servicio`; agregar o reordenar planes/servicios puede romper
    permisos.

12. **Estados desactualizados**

    Cuatro suscripciones vencidas siguen marcadas como activas. El estado no se
    deriva ni se sincroniza automaticamente.

### Medios

13. Falta auditoria de acciones administrativas y financieras.
14. Falta carrito persistente; actualmente vive solo en `localStorage`.
15. Falta umbral configurable para alertas de stock bajo.
16. `item_pedido` permite producto y servicio simultaneamente; solo exige que uno
    no sea nulo.
17. `pago` permite pedido y suscripcion simultaneamente o ambos nulos.
18. No hay unicidad para una sola suscripcion activa o un solo metodo
    predeterminado por usuario.
19. Correos y nombres de usuario usan comparacion sensible a mayusculas.
20. `fecha_actualizacion` depende del codigo; SQLite no soporta el comportamiento
    MySQL `ON UPDATE CURRENT_TIMESTAMP` de forma directa.
21. Los nombres de columnas con `ñ` funcionan, pero complican portabilidad,
    herramientas y consultas. Conviene migrarlos gradualmente a ASCII.

## Compatibilidad MySQL a Turso / SQLite

| MySQL | Equivalencia recomendada |
|---|---|
| `INT AUTO_INCREMENT` | `INTEGER PRIMARY KEY`; `AUTOINCREMENT` solo si nunca se deben reutilizar IDs |
| `VARCHAR(n)` | `TEXT` con `CHECK(length(columna) <= n)` si el limite importa |
| `DECIMAL(10,2)` | `INTEGER` en centavos |
| `DATE` | `TEXT` ISO `YYYY-MM-DD` con validacion |
| `DATETIME` / `TIMESTAMP` | `TEXT` UTC ISO-8601 o `INTEGER` Unix timestamp |
| `ENUM` | `TEXT NOT NULL CHECK (...)` o tabla catalogo |
| `BOOLEAN` / `TINYINT(1)` | `INTEGER NOT NULL CHECK (valor IN (0,1))` |
| `ON UPDATE CURRENT_TIMESTAMP` | Actualizacion explicita desde la app o trigger |
| `FOREIGN KEY` | Compatible, pero debe verificarse `PRAGMA foreign_keys=ON` por conexion |
| `CASCADE`, `SET NULL`, `RESTRICT` | Compatibles; deben declararse explicitamente |

Las tablas actuales no son `STRICT`. SQLite usa afinidad de tipos, no tipos
rigidos; por eso los `CHECK`, validaciones de formato y, cuando sea viable,
tablas `STRICT` reducen datos inconsistentes.

## Propuesta de estructura

### Mantener y reforzar

- `usuario`: conservar; agregar estado/verificacion, normalizacion de correo y
  separar roles si se requieren permisos granulares.
- `categoria_producto`, `producto`, `servicio`, `imagen_servicio`: conservar.
- `pedido`, `item_pedido`, `pago`: conservar historicos, pero ampliar snapshots,
  estados, moneda e idempotencia.
- `plan_suscripcion`, `suscripcion_usuario`: conservar, pero sincronizar con Stripe.
- `reseña_producto`: conservar y decidir si se permite una sola resena por
  usuario/producto.

### Eliminar gradualmente

- `metodo_pago_usuario.numero_tarjeta`
- `metodo_pago_usuario.cvv`

`producto.calificacion` puede conservarse como cache calculado. Si se conserva,
debe actualizarse de forma consistente o recalcularse desde las resenas.

### Tablas nuevas recomendadas

#### Autenticacion y permisos

- `auth_session`: token hash, usuario, expiracion, revocacion, dispositivo e IP.
- `password_reset_token` y `email_verification_token`.
- `rol`, `permiso`, `usuario_rol`, `rol_permiso` si se requiere RBAC real.

#### Stripe y pagos

- `stripe_event`: `event_id UNIQUE`, tipo, fecha, payload/resumen y fecha procesado.
- `payment_attempt`: checkout session, payment intent, estado, error y timestamps.
- `refund`: pago, Stripe refund ID, monto, estado y motivo.
- Restricciones `UNIQUE` sobre IDs Stripe no nulos.

#### Carrito, pedidos e inventario

- `carrito` y `carrito_item` para persistencia entre dispositivos.
- `movimiento_inventario`: entradas, ventas, devoluciones, ajustes y actor.
- `reserva_inventario`: cantidad reservada y expiracion durante Checkout.
- `producto.umbral_stock_bajo`.
- Snapshots en `item_pedido`: nombre, SKU y descripcion/precio al comprar.
- Totales del pedido: subtotal, envio, impuestos, descuento, total y moneda.

#### Proveedores

- `proveedor`
- `producto_proveedor`
- `orden_compra`
- `item_orden_compra`
- `recepcion_compra`

#### Servicios y suscripciones

- `plan_servicio` para reemplazar permisos basados en IDs fijos.
- Campos Stripe de periodo, cancelacion y estado sincronizado.

#### Auditoria

- `audit_log`: actor, accion, entidad, ID, datos anteriores/nuevos, IP,
  request ID y fecha.
- `historial_estado_pedido` para cambios operativos de pedidos.

## Restricciones e indices recomendados

- `UNIQUE lower(correo)` y `UNIQUE lower(nombre_usuario)`.
- `UNIQUE(stripe_checkout_session_id)` cuando no sea nulo.
- `UNIQUE(id_transaccion)` cuando no sea nulo.
- `UNIQUE(stripe_payment_method_id)` cuando no sea nulo.
- `UNIQUE(stripe_subscription_id)` cuando no sea nulo.
- Indice unico parcial para una suscripcion activa por usuario.
- Indice unico parcial para un metodo predeterminado activo por usuario.
- Opcional: `UNIQUE(id_usuario, id_producto)` en resenas.
- `CHECK ((id_producto IS NOT NULL) <> (id_servicio IS NOT NULL))` en items.
- `CHECK ((id_pedido IS NOT NULL) <> (id_suscripcion IS NOT NULL))` en pagos.
- Indices compuestos:
  - `pedido(id_usuario, fecha_creacion DESC)`
  - `pago(estado, fecha_pago DESC)`
  - `suscripcion_usuario(id_usuario, estado, fecha_fin)`
  - `producto(esta_activo, id_categoria)`
  - `producto(esta_activo, es_destacado)`
  - `reseña_producto(id_producto, fecha_creacion DESC)`

## Politica recomendada de eliminacion

- Usuarios, productos, planes y servicios con historico: **soft delete** mediante
  estado activo/inactivo.
- Pedidos, pagos, items, auditoria y movimientos de inventario: **RESTRICT** y no
  eliminar desde CRUD.
- Imagenes secundarias y datos temporales: `ON DELETE CASCADE`.
- Referencias opcionales puramente informativas: `ON DELETE SET NULL`.

## Orden propuesto de implementacion

1. Idempotencia Stripe, restricciones `UNIQUE`, transacciones y stock.
2. Eliminar columnas sensibles y ampliar pagos/pedidos.
3. Definir politicas FK y reemplazar eliminaciones fisicas por soft delete.
4. Agregar proveedores, inventario y alertas.
5. Agregar sesiones revocables, RBAC y auditoria.
6. Normalizar plan-servicio y sincronizar suscripciones Stripe.
7. Migrar gradualmente importes a centavos y tablas criticas a `STRICT`.

