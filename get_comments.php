<?php
// Deshabilitar la salida de errores para evitar que interfieran con JSON
error_reporting(0);
ini_set('display_errors', 0);

require_once 'config/database.php';
startSession();

// Asegurar que no haya salida antes del JSON
ob_clean();

header('Content-Type: application/json');

$productId = $_GET['id_producto'] ?? null;

if (!$productId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID de producto requerido']);
    exit;
}

try {
    $pdo = getConnection();
    
    // Verificar que el producto existe
    $stmt = $pdo->prepare("SELECT id_producto FROM producto WHERE id_producto = ? AND esta_activo = 1");
    $stmt->execute([$productId]);
    
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Producto no encontrado']);
        exit;
    }
    
    // Obtener comentarios del producto
    $stmt = $pdo->prepare("
        SELECT pr.*, CONCAT(u.nombre, ' ', u.apellido) as nombre_completo
        FROM reseña_producto pr 
        JOIN usuario u ON pr.id_usuario = u.id_usuario 
        WHERE pr.id_producto = ? 
        ORDER BY pr.fecha_creacion DESC
    ");
    $stmt->execute([$productId]);
    $comments = $stmt->fetchAll();
    
    // Formatear comentarios
    $formattedComments = array_map(function($comment) {
        return [
            'id' => $comment['id_reseña'],
            'user' => $comment['nombre_completo'],
            'rating' => (int)$comment['calificacion'],
            'date' => $comment['fecha_creacion'],
            'text' => $comment['comentario']
        ];
    }, $comments);
    
    echo json_encode([
        'success' => true,
        'comments' => $formattedComments
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al cargar comentarios: ' . $e->getMessage()]);
}
?> 