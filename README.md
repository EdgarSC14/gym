# Fit 360 - Sistema de Gimnasio

Fit 360 es una aplicación web para gimnasio construida con **Astro 5**, **TypeScript**, **libSQL/Turso** y **Stripe**. El sistema cubre catálogo de productos, servicios por suscripción, carrito, pagos, perfil de usuario y un panel de administración completo.

> La versión PHP heredada fue migrada a Astro. Los archivos `.php` de la migración ya no forman parte del sistema activo.

## Características

- Autenticación con sesiones HTTP-only y contraseñas hasheadas con bcrypt.
- Catálogo de productos con categorías, stock, carrito y reseñas.
- Servicios protegidos por suscripción activa.
- Planes de suscripción y checkout con Stripe.
- Perfil de usuario con datos personales, pedidos, suscripciones y métodos de pago tokenizados.
- Panel de administración con dashboard, CRUD, inventario, multimedia, auditoría y estadísticas.
- Gestión de productos y servicios con selectores de archivos disponibles y previsualización de imagen/video.
- Tablas administrativas responsivas con búsqueda y scroll horizontal contenido.
- Auditoría de acciones administrativas y cambios sensibles.
- Cabeceras de seguridad desde middleware.
- Sitemap y robots generados por Astro.

## Stack

- **Frontend/SSR**: Astro 5 con adaptador Node standalone.
- **Lenguaje**: TypeScript.
- **Base de datos**: libSQL, local con `file:local.db` o remota con Turso.
- **Pagos**: Stripe Checkout, Setup Intents y webhooks.
- **Estilos**: CSS en `public/styles/`.
- **Package manager**: pnpm.

## Requisitos

- Node.js 20 o superior.
- pnpm 11.
- Base de datos libSQL/Turso o archivo local `local.db`.
- Cuenta de Stripe para pagos reales, opcional en desarrollo.

## Instalación

1. Instala dependencias:

```bash
pnpm install
```

2. Copia variables de entorno:

```bash
cp .env.example .env
```

3. Configura `.env`:

```env
TURSO_DATABASE_URL=file:local.db
TURSO_AUTH_TOKEN=
SESSION_SECRET=replace-with-a-long-random-secret
STRIPE_SECRET_KEY=
STRIPE_WEBHOOK_SECRET=
PUBLIC_SITE_URL=http://localhost:4321
```

4. Crea o migra la base de datos local desde el dump legado:

```bash
pnpm db:migrate
pnpm db:harden
```

5. Levanta el servidor de desarrollo:

```bash
pnpm dev
```

La app queda disponible en `http://localhost:4321` o en el puerto que Astro indique si ese puerto está ocupado.

## Scripts

- `pnpm dev`: inicia Astro en modo desarrollo.
- `pnpm build`: ejecuta `astro check` y compila la app server-side.
- `pnpm preview`: previsualiza el build.
- `pnpm check`: valida tipos y plantillas Astro.
- `pnpm db:migrate`: crea el esquema y carga datos desde `fit360_db.sql`.
- `pnpm db:harden`: aplica migraciones de seguridad sobre datos sensibles y permisos.

## Estructura

```text
gym/
├── astro.config.mjs          # Astro SSR con adaptador Node
├── db/schema.sql             # Esquema libSQL actual
├── scripts/                  # Migración y endurecimiento de datos
├── src/
│   ├── components/           # Layouts, header, footer y componentes UI
│   ├── lib/                  # DB, auth, Stripe, auditoría, inventario
│   ├── middleware.ts         # Sesión, permisos admin y cabeceras de seguridad
│   └── pages/
│       ├── admin/            # Panel administrativo
│       ├── api/              # Endpoints de auth, carrito, admin, Stripe, perfil
│       ├── checkout/         # Flujos de checkout
│       ├── products/         # Detalle de producto y reseñas
│       ├── services/         # Detalle protegido de servicio
│       ├── index.astro       # Home
│       ├── products.astro    # Catálogo
│       ├── profile.astro     # Perfil
│       ├── login.astro       # Login
│       └── register.astro    # Registro
├── public/
│   ├── admin-script.js       # Interacciones del panel admin
│   ├── styles/               # CSS público y admin
│   └── assets/
│       ├── products/         # Imágenes seleccionables para productos
│       ├── services/         # Imágenes seleccionables para servicios
│       └── videos/           # Videos seleccionables para servicios
└── local.db                  # Base local de desarrollo, si se usa file:local.db
```

## Rutas principales

### Público y usuarios

- `/`: home con servicios, planes y productos destacados.
- `/products`: catálogo de productos.
- `/products/[id]`: detalle de producto y reseñas.
- `/services/[id]`: detalle de servicio, protegido por suscripción.
- `/checkout/cart`: checkout del carrito.
- `/checkout/subscription/[id]`: checkout de suscripción.
- `/checkout/success`: confirmación y sincronización post-Stripe.
- `/profile`: perfil, pedidos, suscripciones y métodos de pago.
- `/login` y `/register`: autenticación.

### API

- `/api/auth/*`: login, registro y logout.
- `/api/cart`: sincronización del carrito.
- `/api/profile`: edición de perfil, contraseña, suscripciones y sesiones.
- `/api/payment-method`: métodos de pago.
- `/api/reviews/*`: reseñas de productos.
- `/api/stripe/*`: checkout, setup, sync y webhook.
- `/api/admin/*`: CRUD admin, inventario y multimedia.

## Panel de administración

El panel está en `/admin` y requiere un usuario con permiso `admin.access`.

Secciones disponibles:

- `/admin`: dashboard operativo con métricas, ingresos y alertas.
- `/admin/users`: usuarios y roles.
- `/admin/products`: productos, precios, stock, categoría, destacado e imagen.
- `/admin/services`: servicios, beneficios, duración, imagen y video.
- `/admin/plans`: planes de suscripción.
- `/admin/suppliers`: proveedores.
- `/admin/inventory`: existencias y ajustes de inventario.
- `/admin/media`: subida y eliminación de archivos multimedia.
- `/admin/audit`: eventos auditados.
- `/admin/sales`: pagos, ingresos, estados y rankings.

### UX administrativa actual

- Crear y editar abre formularios en ventanas emergentes.
- Las tablas tienen búsqueda y scroll horizontal dentro de la tarjeta.
- Productos: `url_imagen` se selecciona desde `public/assets/products` con previsualización.
- Servicios: `url_imagen` se selecciona desde `public/assets/services` y `url_video` desde `public/assets/videos`, ambos con previsualización.
- Las tablas de productos y servicios muestran miniaturas/previews compactas de los archivos asociados.

## Multimedia

Los archivos administrables viven en:

- `public/assets/products`: imágenes de productos.
- `public/assets/services`: imágenes de servicios.
- `public/assets/videos`: videos de servicios.

El valor guardado en base de datos usa rutas relativas, por ejemplo:

- `assets/products/archivo.webp`
- `assets/services/archivo.jpg`
- `assets/videos/archivo.mp4`

## Base de datos

El esquema actual está en `db/schema.sql`. Algunas tablas principales:

- `usuario`, `rol`, `permiso`, `usuario_rol`, `rol_permiso`.
- `auth_session` para sesiones.
- `producto`, `categoria_producto`, `reseña_producto`.
- `servicio`, `imagen_servicio`.
- `plan_suscripcion`, `suscripcion_usuario`.
- `carrito`, `carrito_item`, `pedido`, `item_pedido`.
- `pago`, `intento_pago`, `metodo_pago_usuario`.
- `proveedor`, `movimiento_inventario`, `audit_log`.

## Seguridad

- Contraseñas con bcrypt.
- Sesiones mediante cookie HTTP-only.
- Permisos por rol para rutas administrativas.
- Protección básica CSRF por validación de origen en métodos no GET/HEAD.
- Cabeceras `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy` y `Permissions-Policy`.
- Stripe guarda los datos sensibles de tarjeta; la app conserva marca, últimos cuatro dígitos y metadatos necesarios.
- Acciones administrativas y sensibles se registran en `audit_log`.

## Stripe

Stripe es opcional para desarrollo si no se prueban pagos reales. Para habilitarlo:

1. Define `STRIPE_SECRET_KEY`.
2. Define `STRIPE_WEBHOOK_SECRET` para validar webhooks.
3. Define `PUBLIC_SITE_URL` con el dominio público real en producción.
4. Configura el webhook de Stripe hacia `/api/stripe/webhook`.

## Migración desde PHP

La migración a Astro ya está aplicada. El código activo vive en `src/`, `public/`, `db/` y `scripts/`. Los archivos `.php` heredados fueron eliminados para evitar confusión y mantenimiento duplicado.

Los documentos `MIGRATION_ANALYSIS.md`, `MIGRATION_RUNBOOK.md` y `DATABASE_AUDIT.md` se conservan como referencia histórica del proceso.

## Desarrollo

Antes de entregar cambios:

```bash
pnpm build
```

Este comando corre la validación de Astro y genera el build server-side. Actualmente pueden aparecer hints de variables no usadas en scripts legacy de frontend, pero no deben existir errores.
