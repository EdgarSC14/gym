<?php
require_once 'config/database.php';
startSession();

// Verificar si el usuario está logueado
if (!isLoggedIn()) {
    echo "Usuario no autenticado";
    exit;
}

$pdo = getConnection();
$user_id = $_SESSION['user_id'];

// Obtener órdenes del usuario para verificar
try {
    $stmt = $pdo->prepare("
        SELECT 
            o.id_pedido,
            o.total_amount,
            o.status,
            o.payment_method,
            o.fecha_creacion,
            GROUP_CONCAT(p.nombre SEPARATOR ', ') as product_names,
            GROUP_CONCAT(oi.cantidad SEPARATOR ', ') as quantities
        FROM pedido o 
        LEFT JOIN item_pedido oi ON o.id_pedido = oi.id_pedido 
        LEFT JOIN producto p ON oi.id_producto = p.id_producto 
        WHERE o.id_usuario = ? 
        GROUP BY o.id_pedido
        ORDER BY o.fecha_creacion DESC
    ");
    $stmt->execute([$user_id]);
    $orders = $stmt->fetchAll();
    
    echo "<h2>Historial de Compras del Usuario</h2>";
    echo "<p>Usuario ID: $user_id</p>";
    
    if (empty($orders)) {
        echo "<p>No hay órdenes registradas</p>";
    } else {
        echo "<div style='margin: 20px 0;'>";
        foreach ($orders as $order) {
            echo "<div style='border: 1px solid #ccc; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
            echo "<h3>Orden #{$order['id_pedido']}</h3>";
            echo "<p><strong>Fecha:</strong> " . date('d/m/Y H:i', strtotime($order['fecha_creacion'])) . "</p>";
            echo "<p><strong>Total:</strong> $" . number_format($order['total_amount'], 2) . " MXN</p>";
            echo "<p><strong>Estado:</strong> " . ucfirst($order['status']) . "</p>";
            echo "<p><strong>Método de pago:</strong> " . htmlspecialchars($order['payment_method']) . "</p>";
            echo "<p><strong>Productos:</strong> " . htmlspecialchars($order['product_names']) . "</p>";
            echo "<p><strong>Cantidades:</strong> " . htmlspecialchars($order['quantities']) . "</p>";
            echo "</div>";
        }
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

// Mostrar productos disponibles para agregar al carrito
echo "<h2>Productos Disponibles</h2>";
try {
    $stmt = $pdo->prepare("SELECT id_producto, nombre, precio, url_imagen FROM producto WHERE esta_activo = 1 LIMIT 5");
    $stmt->execute();
    $products = $stmt->fetchAll();
    
    echo "<div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 20px 0;'>";
    foreach ($products as $product) {
        echo "<div style='border: 1px solid #ddd; padding: 10px; border-radius: 5px;'>";
        echo "<h4>" . htmlspecialchars($product['nombre']) . "</h4>";
        echo "<p>Precio: $" . number_format($product['precio'], 2) . " MXN</p>";
        echo "<p>ID: " . $product['id_producto'] . "</p>";
        echo "</div>";
    }
    echo "</div>";
    
} catch (Exception $e) {
    echo "Error obteniendo productos: " . $e->getMessage();
}
?>

<style>
body {
    font-family: Arial, sans-serif;
    margin: 20px;
    background-color: #f5f5f5;
}

h2 {
    color: #333;
    border-bottom: 2px solid #ff1e00;
    padding-bottom: 10px;
}

h3 {
    color: #ff1e00;
    margin-top: 0;
}

p {
    margin: 5px 0;
}
</style> 