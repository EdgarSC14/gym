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
    $stmt = $pdo->prepare("SELECT id_metodo_pago, es_predeterminado FROM metodo_pago_usuario WHERE id_metodo_pago = ? AND id_usuario = ? AND esta_activo = 1");
    $stmt->execute([$payment_method_id, $user_id]);
    $payment_method = $stmt->fetch();

    if (!$payment_method) {
        echo json_encode(['success' => false, 'message' => 'Método de pago no encontrado']);
        exit();
    }

    // Contar métodos de pago activos
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM metodo_pago_usuario WHERE id_usuario = ? AND esta_activo = 1");
    $stmt->execute([$user_id]);
    $total_methods = $stmt->fetchColumn();

    if ($payment_method['es_predeterminado']) {
        // Si es el método predeterminado, no permitir eliminarlo si es el único
        if ($total_methods <= 1) {
            // Eliminar el método de pago (marcar como inactivo)
            $stmt = $pdo->prepare("UPDATE metodo_pago_usuario SET esta_activo = 0 WHERE id_metodo_pago = ? AND id_usuario = ?");
            
            if ($stmt->execute([$payment_method_id, $user_id])) {
                echo json_encode(['success' => true, 'message' => 'Método de pago eliminado correctamente']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al eliminar el método de pago']);
            }
        } else {
            // Si hay otros métodos, eliminar este y establecer otro como predeterminado
            $stmt = $pdo->prepare("UPDATE metodo_pago_usuario SET esta_activo = 0 WHERE id_metodo_pago = ? AND id_usuario = ?");
            $stmt->execute([$payment_method_id, $user_id]);
            
            // Establecer otro método como predeterminado
            $stmt = $pdo->prepare("UPDATE metodo_pago_usuario SET es_predeterminado = 1 WHERE id_usuario = ? AND esta_activo = 1 ORDER BY fecha_creacion DESC LIMIT 1");
            $stmt->execute([$user_id]);
            
            echo json_encode(['success' => true, 'message' => 'Método de pago eliminado correctamente']);
        }
    } else {
        // Si no es predeterminado, simplemente eliminarlo
        $stmt = $pdo->prepare("UPDATE metodo_pago_usuario SET esta_activo = 0 WHERE id_metodo_pago = ? AND id_usuario = ?");
        
        if ($stmt->execute([$payment_method_id, $user_id])) {
            echo json_encode(['success' => true, 'message' => 'Método de pago eliminado correctamente']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al eliminar el método de pago']);
        }
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error en la base de datos: ' . $e->getMessage()]);
}
?> 