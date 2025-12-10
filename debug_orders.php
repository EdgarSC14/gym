<?php
session_start();
require_once 'config/database.php';

// Verificar si el usuario está logueado
if (!isset($_SESSION['user_id'])) {
    echo "Usuario no logueado";
    exit;
}

$user_id = $_SESSION['user_id'];
echo "<h2>Depuración del Historial de Compras</h2>";
echo "<p>Usuario ID: $user_id</p>";

try {
    // Verificar si el usuario existe
    $stmt = $pdo->prepare("SELECT * FROM usuario WHERE id_usuario = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if (!$user) {
        echo "<p style='color: red;'>Error: Usuario no encontrado en la base de datos</p>";
        exit;
    }
    
    echo "<p>Usuario encontrado: " . $user['nombre'] . " " . $user['apellido'] . "</p>";
    
    // Verificar pedidos del usuario
    $stmt = $pdo->prepare("SELECT * FROM pedido WHERE id_usuario = ? ORDER BY fecha_creacion DESC");
    $stmt->execute([$user_id]);
    $orders = $stmt->fetchAll();
    
    echo "<h3>Pedidos encontrados: " . count($orders) . "</h3>";
    
    if (count($orders) > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID Pedido</th><th>Monto Total</th><th>Estado</th><th>Método Pago</th><th>Fecha Creación</th></tr>";
        
        foreach ($orders as $order) {
            echo "<tr>";
            echo "<td>" . $order['id_pedido'] . "</td>";
            echo "<td>$" . number_format($order['monto_total'], 2) . "</td>";
            echo "<td>" . $order['estado'] . "</td>";
            echo "<td>" . $order['metodo_pago'] . "</td>";
            echo "<td>" . $order['fecha_creacion'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Verificar items de pedido
        echo "<h3>Items de Pedido:</h3>";
        $stmt = $pdo->prepare("
            SELECT oi.*, p.nombre as producto_nombre, p.url_imagen, p.precio
            FROM item_pedido oi 
            LEFT JOIN producto p ON oi.id_producto = p.id_producto 
            WHERE oi.id_pedido IN (SELECT id_pedido FROM pedido WHERE id_usuario = ?)
            ORDER BY oi.id_pedido DESC
        ");
        $stmt->execute([$user_id]);
        $items = $stmt->fetchAll();
        
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID Item</th><th>ID Pedido</th><th>ID Producto</th><th>Producto</th><th>Cantidad</th><th>Precio Unitario</th><th>Imagen</th></tr>";
        
        foreach ($items as $item) {
            echo "<tr>";
            echo "<td>" . $item['id_item_pedido'] . "</td>";
            echo "<td>" . $item['id_pedido'] . "</td>";
            echo "<td>" . $item['id_producto'] . "</td>";
            echo "<td>" . $item['producto_nombre'] . "</td>";
            echo "<td>" . $item['cantidad'] . "</td>";
            echo "<td>$" . number_format($item['precio_unitario'], 2) . "</td>";
            echo "<td>" . $item['url_imagen'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
    } else {
        echo "<p style='color: orange;'>No se encontraron pedidos para este usuario</p>";
    }
    
    // Probar la consulta original del profile.php
    echo "<h3>Probando consulta del profile.php:</h3>";
    $stmt = $pdo->prepare("
        SELECT 
            o.id_pedido,
            o.monto_total,
            o.estado,
            o.metodo_pago,
            o.fecha_creacion,
            GROUP_CONCAT(p.nombre SEPARATOR '|||') as product_names,
            GROUP_CONCAT(oi.cantidad SEPARATOR '|||') as quantities,
            GROUP_CONCAT(p.url_imagen SEPARATOR '|||') as images,
            GROUP_CONCAT(p.precio SEPARATOR '|||') as prices,
            GROUP_CONCAT(oi.cantidad * oi.precio_unitario SEPARATOR '|||') as subtotals,
            COUNT(oi.id_item_pedido) as total_items
        FROM pedido o 
        LEFT JOIN item_pedido oi ON o.id_pedido = oi.id_pedido 
        LEFT JOIN producto p ON oi.id_producto = p.id_producto 
        WHERE o.id_usuario = ? 
        GROUP BY o.id_pedido
        ORDER BY o.fecha_creacion DESC
    ");
    $stmt->execute([$user_id]);
    $profile_orders = $stmt->fetchAll();
    
    echo "<p>Resultados de la consulta del profile: " . count($profile_orders) . "</p>";
    
    if (count($profile_orders) > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID Pedido</th><th>Monto Total</th><th>Estado</th><th>Productos</th><th>Cantidades</th><th>Total Items</th></tr>";
        
        foreach ($profile_orders as $order) {
            echo "<tr>";
            echo "<td>" . $order['id_pedido'] . "</td>";
            echo "<td>$" . number_format($order['monto_total'], 2) . "</td>";
            echo "<td>" . $order['estado'] . "</td>";
            echo "<td>" . $order['product_names'] . "</td>";
            echo "<td>" . $order['quantities'] . "</td>";
            echo "<td>" . $order['total_items'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
table { margin: 10px 0; }
th, td { padding: 8px; text-align: left; }
th { background-color: #f2f2f2; }
</style> 