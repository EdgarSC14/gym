<?php
require_once 'config/database.php';
startSession();

// Verificar si el usuario está logueado
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Verificar si el usuario tiene una dirección registrada
$pdo = getConnection();
$stmt = $pdo->prepare("SELECT direccion FROM usuario WHERE id_usuario = ?");
$stmt->execute([$_SESSION['user_id']]);
$user_address = $stmt->fetchColumn();

if (!$user_address || trim($user_address) === '') {
    $_SESSION['error_message'] = 'Debes tener una dirección registrada para proceder al pago. Por favor, actualiza tu perfil.';
    header('Location: profile.php');
    exit;
}

// Obtener datos del carrito desde la sesión o parámetros
$cartItems = $_SESSION['cart'] ?? [];
$error = '';
$success = '';

// Procesar el formulario de compra
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $pdo = getConnection();
        
        // Crear el pedido
        $totalAmount = $_POST['total_amount'] ?? 0;
        $shippingAddress = $_POST['shipping_address'] ?? '';
        
        $stmt = $pdo->prepare("INSERT INTO pedido (id_usuario, monto_total, direccion_envio, metodo_pago, estado) VALUES (?, ?, ?, ?, 'pendiente')");
        $stmt->execute([$_SESSION['user_id'], $totalAmount, $shippingAddress, 'tarjeta']);
        
        $orderId = $pdo->lastInsertId();
        
        // Agregar items al pedido
        foreach ($cartItems as $item) {
            $stmt = $pdo->prepare("INSERT INTO item_pedido (id_pedido, id_producto, cantidad, precio_unitario) VALUES (?, ?, ?, ?)");
            $stmt->execute([$orderId, $item['product_id'], $item['quantity'], $item['price']]);
        }
        
        // Registrar el pago
        $stmt = $pdo->prepare("INSERT INTO pago (id_pedido, monto, metodo_pago, estado) VALUES (?, ?, ?, 'completado')");
        $stmt->execute([$orderId, $totalAmount, 'tarjeta']);
        
        // Actualizar el estado del pedido
        $stmt = $pdo->prepare("UPDATE pedido SET estado = 'pagado' WHERE id_pedido = ?");
        $stmt->execute([$orderId]);
        
        // Limpiar el carrito
        unset($_SESSION['cart']);
        
        $success = 'Compra realizada exitosamente';
        
        // Redirigir después de un momento
        header("Refresh: 2; URL=index.php");
        
    } catch (Exception $e) {
        $error = 'Error al procesar la compra';
    }
}

// Calcular totales
$subtotal = 0;
$shipping = 1.00; // Envío fijo
$total = 0;

foreach ($cartItems as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}
$total = $subtotal + $shipping;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="style.css">
    <title>Fit 360 - Finalizar Compra</title>
</head>
<body>
    <header>
        <a href="index.php#home" class="logo">Fit <span>360</span></a>
        <div class='bx bx-menu' id="menu-icon"></div>
        <ul class="navbar">
            <li><a href="index.php#home">Inicio</a></li>
            <li><a href="products.php">Productos</a></li>
        </ul>
        
        <div class="top-btn">
            <a href="profile.php" class="username-link">
                <i class='bx bx-user'></i>
                <span><?= htmlspecialchars($_SESSION['username']) ?></span>
            </a>
            <a href="logout.php" class="nav-btn">Cerrar Sesión</a>
        </div>
    </header>

    <section class="checkout-section">
        <div class="checkout-container">
            <?php if($error): ?>
                <div class="error-message"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <?php if($success): ?>
                <div class="success-message"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            
            <div class="cart-items">
                <h3>Productos en el Carrito</h3>
                <?php if(empty($cartItems)): ?>
                    <p>No hay productos en el carrito</p>
                    <a href="products.php" class="btn">Ver Productos</a>
                <?php else: ?>
                    <?php foreach($cartItems as $item): ?>
                    <div class="cart-item">
                        <div class="item-image">
                            <img src="<?= $item['image'] ?: 'assets/image1.png' ?>" alt="<?= htmlspecialchars($item['name']) ?>">
                        </div>
                        <div class="item-info">
                            <h4><?= htmlspecialchars($item['name']) ?></h4>
                            <p>Cantidad: <?= $item['quantity'] ?></p>
                            <span class="item-price">$<?= number_format($item['price'] * $item['quantity'], 2) ?> MXN</span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="order-summary">
                <h3>Resumen del Pedido</h3>
                <div class="summary-details">
                    <div class="summary-item">
                        <span>Subtotal</span>
                        <span>$<?= number_format($subtotal, 2) ?> MXN</span>
                    </div>
                    <div class="summary-item">
                        <span>Envío</span>
                        <span>$<?= number_format($shipping, 2) ?> MXN</span>
                    </div>
                    <div class="summary-item total">
                        <span>Total</span>
                        <span>$<?= number_format($total, 2) ?> MXN</span>
                    </div>
                </div>

                <form class="payment-form" method="POST" action="">
                    <input type="hidden" name="total_amount" value="<?= $total ?>">
                    
                    <div class="form-group">
                        <label>Dirección de Envío</label>
                        <div class="input-group">
                            <i class='bx bx-map'></i>
                            <textarea name="shipping_address" placeholder="Dirección completa de envío" required></textarea>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Información de Pago</label>
                        <div class="input-group">
                            <i class='bx bx-credit-card'></i>
                            <input type="text" name="card_number" placeholder="Número de tarjeta" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <div class="input-group">
                                <i class='bx bx-calendar'></i>
                                <input type="text" name="expiry" placeholder="MM/AA" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <div class="input-group">
                                <i class='bx bx-lock'></i>
                                <input type="text" name="cvc" placeholder="CVC" required>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="input-group">
                            <i class='bx bx-user'></i>
                            <input type="text" name="card_name" placeholder="Nombre en la tarjeta" required>
                        </div>
                    </div>

                    <button type="submit" class="btn" <?= empty($cartItems) ? 'disabled' : '' ?>>
                        Pagar Ahora
                    </button>
                    
                    <div class="security-info">
                        <i class='bx bx-shield-check'></i>
                        <span>Pago seguro con encriptación SSL</span>
                    </div>
                </form>
            </div>
        </div>

        <a href="index.php#home" class="home-floating-btn">
            <i class='bx bx-chevron-up'></i>
            <span>Inicio</span>
        </a>
    </section>

    <footer class="footer"> 
        <div class="social"> 
            <a href="https://facebook.com/fit360gym" target="_blank"><i class='bx bxl-facebook'></i></a> 
            <a href="https://instagram.com/fit360gym" target="_blank"><i class='bx bxl-instagram' ></i></a> 
        </div> 
        
        <p class="copyright"> 
            &copy; Fit 360 2025 | Todos los derechos reservados. 
        </p> 
    </footer>
</body>
</html>