<?php
require_once 'config/database.php';
startSession();

// Verificar si el usuario está logueado
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Usuario no autenticado']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit();
}

$pdo = getConnection();
$user_id = $_SESSION['user_id'];
$payment_method_id = (int)($_POST['payment_method_id'] ?? 0);

if ($payment_method_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de método de pago inválido']);
    exit();
}

try {
    // Verificar que el método de pago pertenece al usuario
    $stmt = $pdo->prepare("SELECT id_metodo_pago FROM metodo_pago_usuario WHERE id_metodo_pago = ? AND id_usuario = ? AND esta_activo = 1");
    $stmt->execute([$payment_method_id, $user_id]);
    
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Método de pago no encontrado']);
        exit();
    }

    // Iniciar transacción
    $pdo->beginTransaction();

    // Quitar el predeterminado de todos los métodos del usuario
    $stmt = $pdo->prepare("UPDATE metodo_pago_usuario SET es_predeterminado = 0 WHERE id_usuario = ? AND esta_activo = 1");
    $stmt->execute([$user_id]);

    // Establecer el nuevo método como predeterminado
    $stmt = $pdo->prepare("UPDATE metodo_pago_usuario SET es_predeterminado = 1 WHERE id_metodo_pago = ? AND id_usuario = ?");
    
    if ($stmt->execute([$payment_method_id, $user_id])) {
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Método de pago establecido como predeterminado']);
    } else {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Error al establecer el método predeterminado']);
    }

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => 'Error en la base de datos: ' . $e->getMessage()]);
}
?> 