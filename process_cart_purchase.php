<?php
require_once 'config/database.php';
startSession();

// Log para debug - rastrear ejecuciones
error_log("=== INICIO DE PROCESAMIENTO DE COMPRA ===");
error_log("Timestamp: " . date('Y-m-d H:i:s'));
error_log("Método: " . $_SERVER['REQUEST_METHOD']);
error_log("Usuario ID: " . ($_SESSION['user_id'] ?? 'NO LOGUEADO'));

// Verificar si el usuario está logueado
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Usuario no autenticado']);
    exit;
}

// Verificar si es una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Funciones de validación de tarjeta
function validateLuhn($cardNumber) {
    $sum = 0;
    $isEven = false;
    
    for ($i = strlen($cardNumber) - 1; $i >= 0; $i--) {
        $digit = intval($cardNumber[$i]);
        
        if ($isEven) {
            $digit *= 2;
            if ($digit > 9) {
                $digit -= 9;
            }
        }
        
        $sum += $digit;
        $isEven = !$isEven;
    }
    
    return $sum % 10 === 0;
}

function getCardType($cardNumber) {
    $cardNumber = preg_replace('/\s/', '', $cardNumber);
    
    $patterns = [
        'visa' => '/^4[0-9]{15}$/',
        'mastercard' => '/^5[1-5][0-9]{14}$/',
        'amex' => '/^3[47][0-9]{13}$/',
        'discover' => '/^6(?:011|5[0-9]{2})[0-9]{12}$/',
        'diners' => '/^3(?:0[0-5]|[68][0-9])[0-9]{11}$/',
        'jcb' => '/^(?:2131|1800|35\d{3})\d{11}$/'
    ];
    
    foreach ($patterns as $type => $pattern) {
        if (preg_match($pattern, $cardNumber)) {
            return $type;
        }
    }
    
    return 'unknown';
}

function validateCVV($cvv, $cardType) {
    $cvvLength = strlen($cvv);
    
    switch ($cardType) {
        case 'amex':
            return $cvvLength === 4;
        case 'visa':
        case 'mastercard':
        case 'discover':
        case 'diners':
        case 'jcb':
            return $cvvLength === 3;
        default:
            return $cvvLength >= 3 && $cvvLength <= 4;
    }
}

function validateExpiryDate($expiry) {
    if (!preg_match('/^\d{2}\/\d{2}$/', $expiry)) {
        return false;
    }
    
    $parts = explode('/', $expiry);
    $month = intval($parts[0]);
    $year = 2000 + intval($parts[1]);
    
    $currentMonth = intval(date('m'));
    $currentYear = intval(date('Y'));
    
    if ($month < 1 || $month > 12) {
        return false;
    }
    
    if ($year < $currentYear || ($year == $currentYear && $month < $currentMonth)) {
        return false;
    }
    
    return true;
}

// Obtener datos del carrito desde el cuerpo de la petición
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['cart_items']) || !isset($input['payment_method'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Datos de compra incompletos']);
    exit;
}

$cart_items = $input['cart_items'];
$payment_method = $input['payment_method'];
$total_amount = $input['total_amount'] ?? 0;

if (empty($cart_items)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'El carrito está vacío']);
    exit;
}

// Validar tarjeta si es método nuevo
if ($payment_method === 'nueva tarjeta') {
    if (!isset($input['card_data'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Datos de tarjeta requeridos']);
        exit;
    }
    
    $card_data = $input['card_data'];
    $card_number = $card_data['card_number'] ?? '';
    $card_expiry = $card_data['card_expiry'] ?? '';
    $card_cvv = $card_data['card_cvv'] ?? '';
    $card_name = $card_data['card_name'] ?? '';
    $card_type = $card_data['card_type'] ?? '';
    
    // Validar nombre
    if (strlen($card_name) < 3) {
        echo json_encode(['success' => false, 'message' => 'Nombre en la tarjeta inválido']);
        exit;
    }
    
    // Validar número de tarjeta
    $card_number_clean = preg_replace('/\s/', '', $card_number);
    if (strlen($card_number_clean) < 13 || strlen($card_number_clean) > 19) {
        echo json_encode(['success' => false, 'message' => 'Número de tarjeta inválido']);
        exit;
    }
    
    // Validar algoritmo de Luhn
    if (!validateLuhn($card_number_clean)) {
        echo json_encode(['success' => false, 'message' => 'Número de tarjeta inválido']);
        exit;
    }
    
    // Validar tipo de tarjeta
    $detected_type = getCardType($card_number_clean);
    if ($detected_type === 'unknown') {
        echo json_encode(['success' => false, 'message' => 'Tipo de tarjeta no reconocido']);
        exit;
    }
    
    // Validar fecha de vencimiento
    if (!validateExpiryDate($card_expiry)) {
        echo json_encode(['success' => false, 'message' => 'Fecha de vencimiento inválida']);
        exit;
    }
    
    // Validar CVV
    if (!validateCVV($card_cvv, $detected_type)) {
        $expected_length = ($detected_type === 'amex') ? 4 : 3;
        echo json_encode(['success' => false, 'message' => "CVV inválido. Las tarjetas $detected_type requieren $expected_length dígitos"]);
        exit;
    }
}

try {
    $pdo = getConnection();
    $pdo->beginTransaction();

    $user_id = $_SESSION['user_id'];
    
    // Obtener la dirección del usuario
    $stmt = $pdo->prepare("SELECT direccion FROM usuario WHERE id_usuario = ?");
    $stmt->execute([$user_id]);
    $user_address = $stmt->fetchColumn();

    // Crear la orden
    $stmt = $pdo->prepare("
        INSERT INTO pedido (id_usuario, monto_total, estado, direccion_envio, metodo_pago, fecha_creacion) 
        VALUES (?, ?, 'pagado', ?, ?, NOW())
    ");
    $stmt->execute([$user_id, $total_amount, $user_address, $payment_method]);
    $order_id = $pdo->lastInsertId();

    // Agregar items a la orden
    $stmt = $pdo->prepare("
        INSERT INTO item_pedido (id_pedido, id_producto, cantidad, precio_unitario) 
        VALUES (?, ?, ?, ?)
    ");

    foreach ($cart_items as $item) {
        if (isset($item['id_producto']) && isset($item['quantity']) && isset($item['price'])) {
            $stmt->execute([
                $order_id,
                $item['id_producto'],
                $item['quantity'],
                $item['price']
            ]);
        }
    }

    // Crear registro de pago
    $stmt = $pdo->prepare("
        INSERT INTO pago (id_pedido, monto, metodo_pago, estado, fecha_pago) 
        VALUES (?, ?, ?, 'completado', NOW())
    ");
    $stmt->execute([$order_id, $total_amount, $payment_method]);

    // Actualizar stock de productos
    foreach ($cart_items as $item) {
        if (isset($item['id_producto']) && isset($item['quantity'])) {
            // Log para debug
            error_log("Actualizando stock - Producto ID: " . $item['id_producto'] . ", Cantidad: " . $item['quantity']);
            
            $stmt = $pdo->prepare("
                UPDATE producto 
                SET cantidad_stock = cantidad_stock - ? 
                WHERE id_producto = ? AND cantidad_stock >= ?
            ");
            $stmt->execute([$item['quantity'], $item['id_producto'], $item['quantity']]);
            
            // Log para verificar cuántas filas se afectaron
            error_log("Filas afectadas en actualización de stock: " . $stmt->rowCount());
        }
    }

    $pdo->commit();

    // Log de éxito
    error_log("=== COMPRA PROCESADA EXITOSAMENTE ===");
    error_log("Order ID: " . $order_id);
    error_log("Total: " . $total_amount);
    error_log("Items: " . count($cart_items));

    echo json_encode([
        'success' => true, 
        'message' => 'Compra procesada exitosamente',
        'order_id' => $order_id
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log("Error procesando compra: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al procesar la compra']);
}
?> 