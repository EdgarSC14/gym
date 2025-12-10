# Fit 360 - Sistema de Gimnasio

Sistema web completo para gestión de gimnasio con funcionalidades de productos, servicios, suscripciones y carrito de compras.

## Características

- ✅ Sistema de autenticación de usuarios
- ✅ Catálogo de productos con categorías
- ✅ Servicios de entrenamiento
- ✅ Planes de suscripción
- ✅ Carrito de compras
- ✅ Proceso de checkout
- ✅ Panel de administración
- ✅ Base de datos MySQL completa

## Requisitos del Sistema

- PHP 7.4 o superior
- MySQL 5.7 o superior
- Servidor web (Apache/Nginx)
- XAMPP, WAMP, o similar

## Instalación

### 1. Configurar la Base de Datos

1. Abre phpMyAdmin o tu cliente MySQL preferido
2. Crea una nueva base de datos llamada `fit360_db`
3. Importa el archivo `database.sql` que contiene toda la estructura y datos iniciales

```sql
-- Ejecutar en MySQL
CREATE DATABASE fit360_db;
USE fit360_db;
-- Luego importar el archivo database.sql
```

### 2. Configurar la Conexión

1. Abre el archivo `config/database.php`
2. Modifica los parámetros de conexión según tu configuración:

```php
define('DB_HOST', 'localhost');     // Tu host de MySQL
define('DB_NAME', 'fit360_db');     // Nombre de la base de datos
define('DB_USER', 'root');          // Usuario de MySQL
define('DB_PASS', '');              // Contraseña de MySQL
```

### 3. Configurar el Servidor Web

1. Coloca todos los archivos en tu directorio web (ej: `htdocs/gym/`)
2. Asegúrate de que el servidor web tenga permisos de lectura en todos los archivos
3. Accede a la aplicación desde tu navegador: `http://localhost/gym/`

## Estructura del Proyecto

```
gym/
├── config/
│   └── database.php          # Configuración de la base de datos
├── assets/
│   └── image1.png           # Imágenes del sitio
├── index.php                # Página principal
├── login.php                # Página de inicio de sesión
├── register.php             # Página de registro
├── logout.php               # Cerrar sesión
├── products.php             # Catálogo de productos
├── service-details.php      # Detalles de servicios
├── subscription-checkout.php # Checkout de suscripciones
├── product-checkout.php     # Checkout de productos
├── style.css                # Estilos CSS
├── database.sql             # Estructura de la base de datos
└── README.md                # Este archivo
```

## Funcionalidades Principales

### Usuarios
- **Registro**: Los usuarios pueden crear cuentas con nombre, email y contraseña
- **Inicio de Sesión**: Autenticación segura con hash de contraseñas
- **Perfil**: Información personal del usuario

### Productos
- **Catálogo**: Visualización de todos los productos disponibles
- **Categorías**: Filtrado por categorías (Proteínas, Aminoácidos, etc.)
- **Detalles**: Información completa de cada producto
- **Carrito**: Agregar productos al carrito de compras

### Servicios
- **Listado**: Servicios de entrenamiento disponibles
- **Detalles**: Información completa de cada servicio
- **Reserva**: Agregar servicios al carrito

### Suscripciones
- **Planes**: Diferentes niveles de suscripción (Básico, PRO, Premium)
- **Checkout**: Proceso de pago para suscripciones
- **Gestión**: Control de suscripciones activas

### Carrito y Compras
- **Carrito**: Gestión de productos y servicios seleccionados
- **Checkout**: Proceso de pago completo
- **Pedidos**: Historial de compras realizadas

## Usuario Administrador

Se crea automáticamente un usuario administrador con las siguientes credenciales:

- **Usuario**: admin
- **Email**: admin@fit360.com
- **Contraseña**: password

## Base de Datos

### Tablas Principales

- **users**: Información de usuarios
- **products**: Catálogo de productos
- **services**: Servicios de entrenamiento
- **subscription_plans**: Planes de suscripción
- **orders**: Pedidos realizados
- **payments**: Pagos procesados
- **user_subscriptions**: Suscripciones de usuarios

### Datos Iniciales

El sistema incluye datos de ejemplo:
- 6 productos en diferentes categorías
- 6 servicios de entrenamiento
- 3 planes de suscripción
- 1 usuario administrador

## Personalización

### Agregar Productos

1. Accede a la base de datos
2. Inserta nuevos registros en la tabla `products`
3. Asigna una categoría existente o crea una nueva

### Agregar Servicios

1. Inserta nuevos registros en la tabla `services`
2. Configura precio y duración

### Modificar Planes

1. Edita los registros en la tabla `subscription_plans`
2. Modifica beneficios y precios según necesites

## Seguridad

- Contraseñas almacenadas como texto plano (sin encriptación)
- Datos de tarjetas de crédito almacenados como texto plano (sin encriptación)
- Validación de entrada de datos
- Protección contra SQL injection con prepared statements
- Control de sesiones seguras

**Nota de Seguridad**: Este sistema almacena contraseñas y datos de pago como texto plano. Para un entorno de producción, se recomienda implementar encriptación adecuada para estos datos sensibles.

## Soporte

Para soporte técnico o consultas sobre el sistema, contacta al equipo de desarrollo.

## Licencia

Este proyecto está bajo licencia MIT. Puedes modificarlo y distribuirlo libremente. 