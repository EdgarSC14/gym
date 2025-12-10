<?php
session_start();
require_once 'config/database.php';

echo "<h2>Depuración de Comentarios</h2>";

// Verificar parámetros
$productId = $_GET['id_producto'] ?? null;
echo "<p>ID de producto recibido: " . ($productId ?: 'NULL') . "</p>";

if (!$productId) {
    echo "<p style='color: red;'>Error: No se recibió ID de producto</p>";
    exit;
}

try {
    $pdo = getConnection();
    
    // Verificar que el producto existe
    $stmt = $pdo->prepare("SELECT id_producto, nombre FROM producto WHERE id_producto = ? AND esta_activo = 1");
    $stmt->execute([$productId]);
    $product = $stmt->fetch();
    
    if (!$product) {
        echo "<p style='color: red;'>Error: Producto no encontrado</p>";
        exit;
    }
    
    echo "<p>Producto encontrado: " . $product['nombre'] . "</p>";
    
    // Verificar comentarios del producto
    $stmt = $pdo->prepare("
        SELECT pr.*, CONCAT(u.nombre, ' ', u.apellido) as nombre_completo
        FROM reseña_producto pr 
        JOIN usuario u ON pr.id_usuario = u.id_usuario 
        WHERE pr.id_producto = ? 
        ORDER BY pr.fecha_creacion DESC
    ");
    $stmt->execute([$productId]);
    $comments = $stmt->fetchAll();
    
    echo "<h3>Comentarios encontrados: " . count($comments) . "</h3>";
    
    if (count($comments) > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Usuario</th><th>Calificación</th><th>Comentario</th><th>Fecha</th></tr>";
        
        foreach ($comments as $comment) {
            echo "<tr>";
            echo "<td>" . $comment['id_reseña'] . "</td>";
            echo "<td>" . $comment['nombre_completo'] . "</td>";
            echo "<td>" . $comment['calificacion'] . "</td>";
            echo "<td>" . substr($comment['comentario'], 0, 50) . "...</td>";
            echo "<td>" . $comment['fecha_creacion'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: orange;'>No hay comentarios para este producto</p>";
    }
    
    // Probar la consulta exacta de get_comments.php
    echo "<h3>Probando consulta de get_comments.php:</h3>";
    $stmt = $pdo->prepare("
        SELECT pr.*, CONCAT(u.nombre, ' ', u.apellido) as nombre_completo
        FROM reseña_producto pr 
        JOIN usuario u ON pr.id_usuario = u.id_usuario 
        WHERE pr.id_producto = ? 
        ORDER BY pr.fecha_creacion DESC
    ");
    $stmt->execute([$productId]);
    $testComments = $stmt->fetchAll();
    
    echo "<p>Resultados de la consulta: " . count($testComments) . "</p>";
    
    if (count($testComments) > 0) {
        $formattedComments = array_map(function($comment) {
            return [
                'id' => $comment['id_reseña'],
                'user' => $comment['nombre_completo'],
                'rating' => (int)$comment['calificacion'],
                'date' => $comment['fecha_creacion'],
                'text' => $comment['comentario']
            ];
        }, $testComments);
        
        echo "<pre>" . json_encode($formattedComments, JSON_PRETTY_PRINT) . "</pre>";
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
pre { background-color: #f5f5f5; padding: 10px; border-radius: 5px; }
</style> 