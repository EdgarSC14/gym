<?php
require_once 'config/database.php';
startSession();

// Verificar si el usuario está logueado
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

// Verificar que sea una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$pdo = getConnection();
$user_id = $_SESSION['user_id'];
$subscription_id = $_POST['subscription_id'] ?? null;

if (!$subscription_id) {
    echo json_encode(['success' => false, 'message' => 'ID de suscripción requerido']);
    exit;
}

try {
    // Verificar que la suscripción pertenece al usuario y está activa
    $stmt = $pdo->prepare("SELECT id_suscripcion FROM suscripcion_usuario WHERE id_suscripcion = ? AND id_usuario = ? AND estado = 'activa'");
    $stmt->execute([$subscription_id, $user_id]);
    
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Suscripción no encontrada o ya cancelada']);
        exit;
    }
    
    // Cancelar la suscripción
    $stmt = $pdo->prepare("UPDATE suscripcion_usuario SET estado = 'cancelada' WHERE id_suscripcion = ? AND id_usuario = ?");
    if ($stmt->execute([$subscription_id, $user_id])) {
        echo json_encode(['success' => true, 'message' => 'Suscripción cancelada correctamente']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al cancelar la suscripción']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error en la base de datos: ' . $e->getMessage()]);
}
?> 