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

// Obtener datos del formulario
$card_number = trim($_POST['numero_tarjeta'] ?? '');
$expiry_date = trim($_POST['fecha_vencimiento'] ?? '');
$cvv = trim($_POST['cvv'] ?? '');
$cardholder_name = trim($_POST['nombre_titular'] ?? '');

// Validar datos
if (empty($card_number) || empty($expiry_date) || empty($cvv) || empty($cardholder_name)) {
    echo json_encode(['success' => false, 'message' => 'Todos los campos son requeridos']);
    exit();
}

// Validar que user_id sea un entero válido
if (!is_numeric($user_id) || $user_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de usuario inválido']);
    exit();
}

// Validar formato del número de tarjeta (solo números y espacios)
if (!preg_match('/^[\d\s]+$/', $card_number)) {
    echo json_encode(['success' => false, 'message' => 'Número de tarjeta inválido']);
    exit();
}

// Validar formato de fecha de expiración (MM/YY)
if (!preg_match('/^\d{2}\/\d{2}$/', $expiry_date)) {
    echo json_encode(['success' => false, 'message' => 'Formato de fecha inválido (MM/YY)']);
    exit();
}

// Validar CVV (3-4 dígitos)
if (!preg_match('/^\d{3,4}$/', $cvv)) {
    echo json_encode(['success' => false, 'message' => 'CVV inválido']);
    exit();
}

// Validar que la fecha de expiración no sea pasada
$expiry_parts = explode('/', $expiry_date);
$expiry_month = (int)$expiry_parts[0];
$expiry_year = 2000 + (int)$expiry_parts[1];
$current_month = (int)date('m');
$current_year = (int)date('Y');

if ($expiry_year < $current_year || ($expiry_year == $current_year && $expiry_month < $current_month)) {
    echo json_encode(['success' => false, 'message' => 'La tarjeta ha expirado']);
    exit();
}

try {
    // Determinar tipo de tarjeta basado en el primer dígito
    $first_digit = substr(preg_replace('/\s/', '', $card_number), 0, 1);
    $card_type = '';
    
    switch ($first_digit) {
        case '4':
            $card_type = 'Visa';
            break;
        case '5':
            $card_type = 'Mastercard';
            break;
        case '3':
            $card_type = 'American Express';
            break;
        default:
            $card_type = 'Otro';
    }

    // Almacenar datos sin encriptar (en producción usar métodos más seguros)
    $encrypted_card_number = $card_number;
    $encrypted_cvv = $cvv;

    // Verificar si ya existe una tarjeta activa con los mismos últimos 4 dígitos
    $last_four_digits = substr(preg_replace('/\s/', '', $card_number), -4);
    $stmt = $pdo->prepare("SELECT id_metodo_pago FROM metodo_pago_usuario WHERE id_usuario = ? AND numero_tarjeta LIKE ? AND esta_activo = 1");
    $stmt->execute([$user_id, '%' . $last_four_digits]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Esta tarjeta ya está registrada']);
        exit();
    }

    // Si existe una tarjeta inactiva con los mismos últimos 4 dígitos, reactivarla
    $stmt = $pdo->prepare("SELECT id_metodo_pago FROM metodo_pago_usuario WHERE id_usuario = ? AND numero_tarjeta LIKE ? AND esta_activo = 0");
    $stmt->execute([$user_id, '%' . $last_four_digits]);
    $inactive_card = $stmt->fetch();
    
    if ($inactive_card) {
        // Reactivar la tarjeta existente con los nuevos datos
        $stmt = $pdo->prepare("
            UPDATE metodo_pago_usuario 
            SET tipo_tarjeta = ?, fecha_vencimiento = ?, cvv = ?, nombre_titular = ?, esta_activo = 1
            WHERE id_metodo_pago = ?
        ");
        
        if ($stmt->execute([$card_type, $expiry_date, $encrypted_cvv, $cardholder_name, $inactive_card['id_metodo_pago']])) {
            echo json_encode([
                'success' => true, 
                'message' => 'Tarjeta reactivada correctamente',
                'payment_method' => [
                    'id' => $inactive_card['id_metodo_pago'],
                    'card_type' => $card_type,
                    'last_four_digits' => $last_four_digits,
                    'expiry_date' => $expiry_date,
                    'cardholder_name' => $cardholder_name,
                    'is_default' => false
                ]
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al reactivar la tarjeta']);
        }
        exit();
    }

    // Si es la primera tarjeta, marcarla como predeterminada
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM metodo_pago_usuario WHERE id_usuario = ? AND esta_activo = 1");
    $stmt->execute([$user_id]);
    $is_default = ($stmt->fetchColumn() == 0) ? 1 : 0;

    // Insertar el método de pago en la tabla correcta
    $stmt = $pdo->prepare("
        INSERT INTO metodo_pago_usuario (id_usuario, tipo_tarjeta, numero_tarjeta, fecha_vencimiento, cvv, nombre_titular, es_predeterminado, esta_activo) 
        VALUES (?, ?, ?, ?, ?, ?, ?, 1)
    ");
    
    if ($stmt->execute([$user_id, $card_type, $encrypted_card_number, $expiry_date, $encrypted_cvv, $cardholder_name, $is_default])) {
        $payment_method_id = $pdo->lastInsertId();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Método de pago agregado correctamente',
            'payment_method' => [
                'id' => $payment_method_id,
                'card_type' => $card_type,
                'last_four_digits' => $last_four_digits,
                'expiry_date' => $expiry_date,
                'cardholder_name' => $cardholder_name,
                'is_default' => (bool)$is_default
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al guardar el método de pago']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error en la base de datos: ' . $e->getMessage()]);
}
?> 