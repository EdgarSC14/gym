# Analisis tecnico y plan de migracion Fit 360

## Estado actual

El proyecto es una aplicacion PHP 7.4+ con PDO y MySQL/MariaDB. Usa sesiones PHP,
HTML renderizado en servidor, JavaScript nativo y dos hojas de estilo:

- `style.css`: sitio publico, autenticacion, catalogo, carrito, checkout y perfil.
- `admin/admin-style.css`: dashboard y CRUD administrativos.
- `admin/admin-script.js`: navegacion, tablas, busqueda, exportacion y utilidades.

Los activos se encuentran en `assets/`, separados en productos, servicios y videos.

## Paginas y navegacion

### Sitio publico

- `index.php`: inicio, servicios, nosotros, planes, productos destacados, carrito y checkout.
- `products.php`: catalogo filtrable por categoria, detalle, resenas y carrito.
- `service-details.php`: detalle protegido de servicio segun plan contratado.
- `login.php`, `register.php`, `logout.php`: autenticacion y sesion.
- `profile.php`: datos personales, cambio de contrasena, metodos de pago,
  suscripciones e historial de pedidos.
- `subscription-checkout.php`, `product-checkout.php`: checkout.

### Endpoints actuales

- Carrito: `process_cart_purchase.php`.
- Resenas: `get_comments.php`, `add_comment.php`.
- Metodos de pago: `get_payment_methods.php`, `add_payment_method.php`,
  `delete_payment_method.php`, `set_default_payment_method.php`.
- Suscripciones: `get_subscriptions.php`, `cancel_subscription.php`.

### Administracion

- `admin/index.php`: metricas y actividad reciente.
- `admin/users.php`: CRUD de usuarios y roles.
- `admin/products.php`: CRUD, categorias, stock, destacados e imagen.
- `admin/services.php`: CRUD, imagen principal, galeria y video.
- `admin/subscriptions.php`: CRUD de planes y listado de suscripciones.
- `admin/media.php`: carga, listado y eliminacion de multimedia.
- `admin/sales.php`: ventas, ingresos, rankings y exportacion.

## Autenticacion, roles y permisos

- La sesion contiene `user_id`, `username` y `role`.
- Roles encontrados: `usuario` y `administrador`.
- Las rutas administrativas verifican sesion y rol administrador.
- Los servicios disponibles dependen del plan:
  - Basico: servicios 1 y 2.
  - PRO: servicios 1 a 4.
  - Premium: servicios 1 a 6.

## Validaciones que deben conservarse

- Registro: obligatorios, email valido y unico, usuario unico, sin espacios
  iniciales, nombres sin numeros, contrasena minima de 8 caracteres y confirmacion.
- Perfil: nombre solo con letras, telefono de 10 digitos, direccion descriptiva,
  email valido y unico, y validacion de cambio de contrasena.
- Compra: autenticacion, direccion, carrito no vacio, cantidades y stock.
- Resenas: autenticacion, producto activo, calificacion de 1 a 5 y comentario.
- Administracion: campos obligatorios, precios/stock no negativos, archivos y roles.

## Base de datos actual

El volcado `fit360_db.sql` contiene estructura y datos de estas 12 tablas:

- `usuario`
- `categoria_producto`
- `producto`
- `reseña_producto`
- `servicio`
- `imagen_servicio`
- `plan_suscripcion`
- `suscripcion_usuario`
- `pedido`
- `item_pedido`
- `pago`
- `metodo_pago_usuario`

Las relaciones y llaves foraneas conectan usuarios con pedidos, suscripciones,
resenas y metodos de pago; pedidos con items y pagos; productos con categorias y
resenas; servicios con galeria; y suscripciones con planes y pagos.

## Riesgos detectados

- Contrasenas y datos completos de tarjeta, incluido CVV, estan en texto plano.
- Los pagos actuales se marcan completados sin comunicarse con un procesador real.
- Hay endpoints de debug y prueba expuestos.
- Parte de la documentacion usa nombres de tablas en ingles que no coinciden con el SQL.
- Algunas eliminaciones administrativas son destructivas y manuales.

## Arquitectura destino

- Astro SSR con adaptador Node.
- `pnpm` como unico gestor de paquetes.
- Turso/libSQL con esquema SQLite equivalente, llaves foraneas e indices.
- Sesion firmada en cookie HTTP-only.
- Contrasenas nuevas con hash; compatibilidad temporal para migrar credenciales
  antiguas al iniciar sesion.
- Stripe Checkout y webhooks. Nunca se almacenaran numeros completos ni CVV;
  `metodo_pago_usuario` conservara IDs de Stripe y metadatos seguros.
- CSS y activos originales reutilizados para conservar la identidad visual.
- Rutas `/api/*` para operaciones y `/admin/*` para gestion.

