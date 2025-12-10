# Funcionalidad del Carrito de Compras - Fit 360

## Descripción
Se ha implementado la funcionalidad completa del carrito de compras que permite a los usuarios:
- Agregar productos al carrito
- Ver el carrito con totales
- Finalizar compras
- Guardar las compras en la base de datos
- Ver el historial de compras en el perfil del usuario

## Archivos Modificados/Creados

### Nuevos Archivos:
1. **`process_cart_purchase.php`** - Procesa las compras del carrito y las guarda en la base de datos
2. **`test_cart_purchase.php`** - Archivo de prueba para verificar la funcionalidad
3. **`CARRITO_COMPRAS_README.md`** - Esta documentación

### Archivos Modificados:
1. **`index.php`** - Actualizado para enviar datos del carrito al servidor
2. **`profile.php`** - Mejorado el historial de compras
3. **`style.css`** - Agregados estilos para el historial de compras

## Funcionalidades Implementadas

### 1. Carrito de Compras
- Agregar productos al carrito
- Modificar cantidades
- Eliminar productos
- Calcular totales (subtotal, envío, total)
- Persistencia en localStorage

### 2. Proceso de Pago
- Validación de usuario logueado
- Verificación de dirección registrada
- Opción de método de pago existente o nuevo
- Validación de datos de tarjeta
- Procesamiento de pago

### 3. Base de Datos
- Creación de órdenes en la tabla `orders`
- Registro de items en la tabla `order_items`
- Registro de pagos en la tabla `payments`
- Actualización de stock de productos

### 4. Historial de Compras
- Vista en el perfil del usuario
- Información detallada de cada orden:
  - Número de orden
  - Fecha y hora
  - Estado de la orden
  - Productos comprados
  - Cantidades
  - Total pagado
  - Método de pago

## Estructura de la Base de Datos

### Tabla `orders`
```sql
CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `status` enum('pending','paid','shipped','delivered','cancelled') DEFAULT 'paid',
  `shipping_address` text DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
);
```

### Tabla `order_items`
```sql
CREATE TABLE `order_items` (
  `order_item_id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `service_id` int(11) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL
);
```

### Tabla `payments`
```sql
CREATE TABLE `payments` (
  `payment_id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) DEFAULT NULL,
  `subscription_id` int(11) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `transaction_id` varchar(100) DEFAULT NULL,
  `status` enum('pending','completed','failed','refunded') DEFAULT 'completed',
  `payment_date` timestamp NOT NULL DEFAULT current_timestamp()
);
```

## Flujo de Compra

1. **Usuario navega a productos** - Puede ver y agregar productos al carrito
2. **Agregar al carrito** - Los productos se guardan en localStorage
3. **Ver carrito** - Modal con productos, cantidades y totales
4. **Proceder al pago** - Verificación de login y dirección
5. **Seleccionar método de pago** - Existente o nueva tarjeta
6. **Procesar pago** - Envío de datos al servidor
7. **Guardar en BD** - Creación de orden, items y pago
8. **Limpiar carrito** - Eliminación de productos del localStorage
9. **Confirmación** - Mensaje de éxito al usuario

## Validaciones Implementadas

### Frontend (JavaScript)
- Usuario debe estar logueado
- Dirección debe estar registrada
- Validación de datos de tarjeta
- Verificación de CVV
- Validación de cantidades

### Backend (PHP)
- Verificación de autenticación
- Validación de datos de entrada
- Verificación de stock disponible
- Transacciones de base de datos
- Manejo de errores

## Estilos CSS

### Nuevos Estilos Agregados:
- `.order-header` - Encabezado de orden con número y estado
- `.order-content` - Contenido principal de la orden
- `.order-products` - Lista de productos en la orden
- `.product-item` - Item individual de producto
- `.order-summary` - Resumen de la orden con total
- Estilos responsivos para móviles

## Pruebas

Para probar la funcionalidad:

1. **Acceder como usuario logueado**
2. **Navegar a productos** en `index.php`
3. **Agregar productos al carrito**
4. **Proceder al pago**
5. **Completar el proceso de pago**
6. **Verificar en el perfil** que aparece en el historial
7. **Usar `test_cart_purchase.php`** para verificar datos en BD

## Notas Importantes

- Los productos se guardan en localStorage del navegador
- Se requiere dirección registrada para proceder al pago
- El stock se actualiza automáticamente al procesar la compra
- Las órdenes se marcan como 'paid' por defecto
- Los pagos se marcan como 'completed' por defecto

## Posibles Mejoras Futuras

1. **Notificaciones por email** al completar compra
2. **Seguimiento de envío** con estados actualizados
3. **Cupones de descuento**
4. **Múltiples direcciones de envío**
5. **Facturación electrónica**
6. **Devoluciones y reembolsos**
7. **Sistema de puntos/fidelidad** 