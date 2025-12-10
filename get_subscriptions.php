<?php
require_once 'config/database.php';
startSession();

// Verificar si el usuario está logueado
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$pdo = getConnection();
$user_id = $_SESSION['user_id'];

try {
    // Obtener suscripciones activas
    $stmt = $pdo->prepare("
        SELECT s.*, sp.nombre as plan_name, sp.precio 
        FROM suscripcion_usuario s 
        JOIN plan_suscripcion sp ON s.id_plan = sp.id_plan 
        WHERE s.id_usuario = ? AND s.estado = 'activa'
        ORDER BY s.fecha_creacion DESC
    ");
    $stmt->execute([$user_id]);
    $subscriptions = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true, 
        'subscriptions' => $subscriptions,
        'count' => count($subscriptions)
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error en la base de datos: ' . $e->getMessage()]);
}
?> 