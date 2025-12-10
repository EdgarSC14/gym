<?php
require_once 'config/database.php';
startSession();

echo "<h1>Prueba de Conexión y Consultas del Perfil</h1>";

try {
    $pdo = getConnection();
    echo "<p style='color: green;'>✓ Conexión a la base de datos exitosa</p>";
    
    // Verificar si hay un usuario logueado
    if (isLoggedIn()) {
        $user_id = $_SESSION['user_id'];
        echo "<p>Usuario ID en sesión: $user_id</p>";
        
        // Probar consulta de usuario
        $stmt = $pdo->prepare("SELECT * FROM usuario WHERE id_usuario = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
        if ($user) {
            echo "<p style='color: green;'>✓ Usuario encontrado en la base de datos</p>";
            echo "<p>Nombre: " . ($user['nombre'] ?? 'N/A') . " " . ($user['apellido'] ?? 'N/A') . "</p>";
            echo "<p>Email: " . ($user['correo'] ?? 'N/A') . "</p>";
            echo "<p>Teléfono: " . ($user['telefono'] ?? 'N/A') . "</p>";
            echo "<p>Dirección: " . ($user['direccion'] ?? 'N/A') . "</p>";
        } else {
            echo "<p style='color: red;'>✗ Usuario no encontrado en la base de datos</p>";
        }
        
        // Probar consulta de pedidos
        $stmt = $pdo->prepare("
            SELECT 
                o.id_pedido,
                o.monto_total,
                o.estado,
                o.metodo_pago,
                o.fecha_creacion,
                GROUP_CONCAT(p.nombre SEPARATOR ', ') as product_names,
                GROUP_CONCAT(oi.cantidad SEPARATOR ', ') as quantities,
                GROUP_CONCAT(p.url_imagen SEPARATOR ', ') as images
            FROM pedido o 
            LEFT JOIN item_pedido oi ON o.id_pedido = oi.id_pedido 
            LEFT JOIN producto p ON oi.id_producto = p.id_producto 
            WHERE o.id_usuario = ? 
            GROUP BY o.id_pedido
            ORDER BY o.fecha_creacion DESC
        ");
        $stmt->execute([$user_id]);
        $orders = $stmt->fetchAll();
        
        echo "<p style='color: green;'>✓ Consulta de pedidos ejecutada correctamente</p>";
        echo "<p>Número de pedidos encontrados: " . count($orders) . "</p>";
        
        // Probar consulta de suscripciones
        $stmt = $pdo->prepare("
            SELECT s.*, sp.nombre as plan_name, sp.precio 
            FROM suscripcion_usuario s 
            JOIN plan_suscripcion sp ON s.id_plan = sp.id_plan 
            WHERE s.id_usuario = ? AND s.estado = 'activa'
        ");
        $stmt->execute([$user_id]);
        $subscriptions = $stmt->fetchAll();
        
        echo "<p style='color: green;'>✓ Consulta de suscripciones ejecutada correctamente</p>";
        echo "<p>Número de suscripciones activas: " . count($subscriptions) . "</p>";
        
        // Probar consulta de métodos de pago
        $stmt = $pdo->prepare("
            SELECT id_metodo_pago, tipo_tarjeta, numero_tarjeta, fecha_vencimiento, nombre_titular, es_predeterminado, esta_activo, fecha_creacion
            FROM metodo_pago_usuario 
            WHERE id_usuario = ? AND esta_activo = 1 
            ORDER BY es_predeterminado DESC, fecha_creacion DESC
        ");
        $stmt->execute([$user_id]);
        $payment_methods = $stmt->fetchAll();
        
        echo "<p style='color: green;'>✓ Consulta de métodos de pago ejecutada correctamente</p>";
        echo "<p>Número de métodos de pago: " . count($payment_methods) . "</p>";
        
    } else {
        echo "<p style='color: red;'>✗ No hay usuario logueado</p>";
        echo "<p>Variables de sesión disponibles:</p>";
        echo "<pre>" . print_r($_SESSION, true) . "</pre>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
}

echo "<h2>Información de la sesión:</h2>";
echo "<pre>" . print_r($_SESSION, true) . "</pre>";
?> 