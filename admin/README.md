# Panel de Administración - Fit 360

## Descripción
El panel de administración de Fit 360 es una interfaz completa para gestionar todos los aspectos del gimnasio, incluyendo usuarios, productos, servicios, suscripciones, multimedia y estadísticas de ventas.

## Características Principales

### 1. Dashboard Principal
- **Estadísticas en tiempo real**: Usuarios registrados, suscripciones activas, ventas totales, ingresos
- **Acciones rápidas**: Enlaces directos para agregar productos, servicios, editar precios
- **Actividad reciente**: Últimos usuarios registrados y actividades del sistema

### 2. Gestión de Usuarios
- **Lista completa de usuarios**: Con información detallada de cada usuario
- **Estadísticas de usuarios**: Total de usuarios, suscripciones activas, nuevos usuarios del mes
- **Gestión de roles**: Administradores y usuarios regulares
- **Información de suscripciones**: Estado de suscripciones activas por usuario

### 3. Gestión de Productos
- **CRUD completo**: Crear, leer, actualizar y eliminar productos
- **Gestión de imágenes**: Subir y gestionar imágenes de productos
- **Categorías**: Organizar productos por categorías
- **Precios**: Manejo de precios actuales y anteriores (descuentos)
- **Stock**: Control de inventario
- **Productos destacados**: Marcar productos como destacados

### 4. Gestión de Servicios
- **CRUD completo**: Crear, leer, actualizar y eliminar servicios
- **Multimedia**: Subir imágenes y videos para los servicios
- **Precios y duración**: Configurar precios y duración de servicios
- **Descripciones detalladas**: Información completa de cada servicio

### 5. Gestión de Suscripciones
- **Planes de suscripción**: Crear y editar planes (Básico, PRO, Premium)
- **Precios flexibles**: Configurar precios y duración de cada plan
- **Beneficios**: Definir beneficios específicos para cada plan
- **Suscripciones activas**: Ver todas las suscripciones activas
- **Alertas de expiración**: Identificar suscripciones que expiran pronto

### 6. Gestión de Multimedia
- **Subida de archivos**: Drag & drop para subir imágenes y videos
- **Organización**: Separar archivos por tipo (imágenes generales, productos, servicios, videos)
- **Validación**: Verificación de tipos de archivo y tamaños
- **Gestión de rutas**: Copiar rutas de archivos para usar en el sitio
- **Eliminación segura**: Eliminar archivos no utilizados

### 7. Estadísticas y Ventas
- **Dashboard de ventas**: Estadísticas completas de ventas e ingresos
- **Gráficos interactivos**: Visualización de ventas mensuales
- **Productos más vendidos**: Ranking de productos populares
- **Servicios más populares**: Estadísticas de reservas de servicios
- **Ventas recientes**: Historial de transacciones
- **Exportación**: Exportar reportes en formato CSV

## Acceso al Panel

### Credenciales de Administrador
- **Email**: admin@fit360.com
- **Contraseña**: password (configurada en la base de datos)

### URL de Acceso
```
http://localhost/gym/admin/
```

## Estructura de Archivos

```
admin/
├── index.php          # Dashboard principal
├── users.php          # Gestión de usuarios
├── products.php       # Gestión de productos
├── services.php       # Gestión de servicios
├── subscriptions.php  # Gestión de suscripciones
├── media.php          # Gestión de multimedia
├── sales.php          # Estadísticas y ventas
├── admin-style.css    # Estilos del panel
├── admin-script.js    # Funcionalidades JavaScript
└── README.md          # Esta documentación
```

## Funcionalidades Técnicas

### Seguridad
- **Autenticación**: Verificación de roles de administrador
- **Validación**: Validación de formularios y archivos
- **Sanitización**: Protección contra XSS y inyección SQL
- **Sesiones seguras**: Manejo seguro de sesiones

### Responsive Design
- **Móvil**: Interfaz optimizada para dispositivos móviles
- **Tablet**: Diseño adaptativo para tablets
- **Desktop**: Interfaz completa para computadoras

### Características Avanzadas
- **Búsqueda en tiempo real**: Filtrado instantáneo de tablas
- **Ordenamiento**: Ordenar por cualquier columna
- **Exportación**: Exportar datos en formato CSV
- **Notificaciones**: Mensajes de éxito y error
- **Confirmaciones**: Confirmación antes de eliminar elementos

## Base de Datos

### Tablas Principales
- `users`: Información de usuarios
- `products`: Catálogo de productos
- `services`: Servicios del gimnasio
- `subscription_plans`: Planes de suscripción
- `user_subscriptions`: Suscripciones activas
- `orders`: Pedidos de productos
- `payments`: Transacciones de pago
- `product_categories`: Categorías de productos

## Configuración

### Requisitos del Sistema
- PHP 7.4 o superior
- MySQL 5.7 o superior
- Servidor web (Apache/Nginx)
- Extensiones PHP: PDO, GD, FileInfo

### Configuración de Base de Datos
El archivo `config/database.php` debe estar configurado con:
- Host de base de datos
- Nombre de la base de datos
- Usuario y contraseña
- Puerto (si es necesario)

### Permisos de Archivos
Los directorios de uploads deben tener permisos de escritura:
```bash
chmod 755 assets/
chmod 755 assets/images/
chmod 755 assets/videos/
chmod 755 assets/products/
chmod 755 assets/services/
```

## Uso del Panel

### 1. Iniciar Sesión
1. Acceder a `http://localhost/gym/login.php`
2. Usar credenciales de administrador
3. Será redirigido automáticamente al panel

### 2. Navegación
- **Sidebar**: Menú lateral con todas las secciones
- **Header**: Información del usuario y acciones rápidas
- **Breadcrumbs**: Navegación contextual

### 3. Gestión de Contenido
- **Agregar**: Botones "Agregar" en cada sección
- **Editar**: Iconos de edición en las tablas
- **Eliminar**: Iconos de eliminación con confirmación
- **Buscar**: Campos de búsqueda en tiempo real

### 4. Estadísticas
- **Dashboard**: Vista general de métricas importantes
- **Gráficos**: Visualización de datos con Chart.js
- **Reportes**: Exportación de datos para análisis

## Mantenimiento

### Backup de Base de Datos
Realizar backups regulares de la base de datos:
```sql
mysqldump -u username -p fit360_db > backup.sql
```

### Limpieza de Archivos
- Revisar periódicamente archivos multimedia no utilizados
- Eliminar archivos temporales
- Optimizar imágenes para mejor rendimiento

### Actualizaciones
- Mantener PHP y MySQL actualizados
- Revisar logs de errores regularmente
- Monitorear rendimiento del sistema

## Soporte

Para soporte técnico o preguntas sobre el panel de administración, contactar al equipo de desarrollo.

---

**Fit 360 - Panel de Administración**
*Versión 1.0 - 2025* 