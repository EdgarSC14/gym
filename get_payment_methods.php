<?php
require_once 'config/database.php';
startSession();

// Verificar si el usuario está logueado
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Usuario no autenticado']);
    exit();
}

$pdo = getConnection();
$user_id = $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("
        SELECT id_metodo_pago, tipo_tarjeta, numero_tarjeta, fecha_vencimiento, nombre_titular, es_predeterminado, esta_activo 
        FROM metodo_pago_usuario 
        WHERE id_usuario = ? AND esta_activo = 1 
        ORDER BY es_predeterminado DESC, fecha_creacion DESC
    ");
    $stmt->execute([$user_id]);
    $payment_methods = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Procesar los datos para mostrar solo los últimos 4 dígitos
    foreach ($payment_methods as &$method) {
        // Extraer los últimos 4 dígitos del número de tarjeta sin encriptar
        $card_number = preg_replace('/\s/', '', $method['numero_tarjeta']);
        $method['last_four_digits'] = substr($card_number, -4);
        $method['numero_tarjeta'] = '**** **** **** ' . $method['last_four_digits'];
    }

    echo json_encode(['success' => true, 'payment_methods' => $payment_methods]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error al obtener métodos de pago: ' . $e->getMessage()]);
}
?> 