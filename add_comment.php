<?php
// Deshabilitar la salida de errores para evitar que interfieran con JSON
error_reporting(0);
ini_set('display_errors', 0);

require_once 'config/database.php';
startSession();

// Asegurar que no haya salida antes del JSON
ob_clean();

header('Content-Type: application/json');

// Verificar si el usuario está logueado
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Debes iniciar sesión para comentar']);
    exit;
}

// Verificar si es una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Obtener datos del formulario
$input = json_decode(file_get_contents('php://input'), true);

$productId = $input['id_producto'] ?? null;
$rating = $input['rating'] ?? null;
$comment = $input['comment'] ?? null;

// Validar datos
if (!$productId || !$rating || !$comment) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Todos los campos son requeridos']);
    exit;
}

if ($rating < 1 || $rating > 5) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'La calificación debe estar entre 1 y 5']);
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
    
    // Insertar el comentario
    $stmt = $pdo->prepare("INSERT INTO reseña_producto (id_producto, id_usuario, calificacion, comentario) VALUES (?, ?, ?, ?)");
    $stmt->execute([$productId, $_SESSION['user_id'], $rating, $comment]);
    
    // Actualizar el rating promedio del producto
    $stmt = $pdo->prepare("
        UPDATE producto 
        SET calificacion = (
            SELECT AVG(calificacion) 
            FROM reseña_producto 
            WHERE id_producto = ?
        ) 
        WHERE id_producto = ?
    ");
    $stmt->execute([$productId, $productId]);
    
    // Obtener el comentario recién insertado con información del usuario
    $stmt = $pdo->prepare("
        SELECT pr.*, CONCAT(u.nombre, ' ', u.apellido) as nombre_completo
        FROM reseña_producto pr 
        JOIN usuario u ON pr.id_usuario = u.id_usuario 
        WHERE pr.id_reseña = ?
    ");
    $stmt->execute([$pdo->lastInsertId()]);
    $newComment = $stmt->fetch();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Comentario agregado exitosamente',
        'comment' => [
            'id' => $newComment['id_reseña'],
            'user' => $newComment['nombre_completo'],
            'rating' => $newComment['calificacion'],
            'date' => $newComment['fecha_creacion'],
            'text' => $newComment['comentario']
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al guardar el comentario: ' . $e->getMessage()]);
}
?> 