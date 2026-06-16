# Ejecucion de Fit 360 Astro

1. Copia `.env.example` a `.env` y configura `TURSO_DATABASE_URL`, `TURSO_AUTH_TOKEN`, `SESSION_SECRET`, `STRIPE_SECRET_KEY`, `STRIPE_WEBHOOK_SECRET` y `PUBLIC_SITE_URL`.
2. Ejecuta `pnpm install`.
3. Importa el volcado con `pnpm db:migrate`.
4. Ejecuta obligatoriamente `pnpm db:harden` para crear RBAC, sesiones, auditoria, carrito, inventario y tablas Stripe; tambien elimina numeros completos y CVV heredados y convierte contraseñas antiguas a bcrypt.
5. Inicia desarrollo con `pnpm dev` o genera produccion con `pnpm build`.
6. Registra en Stripe el webhook `POST /api/stripe/webhook` para `checkout.session.completed`, `checkout.session.expired`, `invoice.paid`, `invoice.payment_failed` y `customer.subscription.deleted`.

Para Turso remoto, usa la URL `libsql://...` y su token. Para desarrollo local, el valor predeterminado es `file:local.db`.
