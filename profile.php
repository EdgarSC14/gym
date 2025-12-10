<?php
require_once 'config/database.php';
startSession();

// Verificar si el usuario está logueado
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$pdo = getConnection();
$user_id = $_SESSION['user_id'];

// Obtener información del usuario
try {
    $stmt = $pdo->prepare("SELECT * FROM usuario WHERE id_usuario = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
} catch (Exception $e) {
    $error_message = "Error al cargar información del usuario";
    $user = [];
}

// Obtener historial de compras
try {
    $stmt = $pdo->prepare("
        SELECT 
            o.id_pedido,
            o.monto_total,
            o.estado,
            o.metodo_pago,
            o.fecha_creacion,
            GROUP_CONCAT(p.nombre SEPARATOR ', ') as product_names,
            GROUP_CONCAT(oi.cantidad SEPARATOR ', ') as quantities,
            GROUP_CONCAT(p.url_imagen SEPARATOR ', ') as images
        FROM pedido o 
        LEFT JOIN item_pedido oi ON o.id_pedido = oi.id_pedido 
        LEFT JOIN producto p ON oi.id_producto = p.id_producto 
        WHERE o.id_usuario = ? 
        GROUP BY o.id_pedido
        ORDER BY o.fecha_creacion DESC
    ");
    $stmt->execute([$user_id]);
    $orders = $stmt->fetchAll();
} catch (Exception $e) {
    $orders = [];
}

// Obtener suscripciones activas
try {
    $stmt = $pdo->prepare("
        SELECT s.*, sp.nombre as plan_name, sp.precio 
        FROM suscripcion_usuario s 
        JOIN plan_suscripcion sp ON s.id_plan = sp.id_plan 
        WHERE s.id_usuario = ? AND s.estado = 'activa'
    ");
    $stmt->execute([$user_id]);
    $subscriptions = $stmt->fetchAll();
} catch (Exception $e) {
    $subscriptions = [];
}

// Obtener métodos de pago
try {
    $stmt = $pdo->prepare("
        SELECT id_metodo_pago, tipo_tarjeta, numero_tarjeta, fecha_vencimiento, nombre_titular, es_predeterminado, esta_activo, fecha_creacion
        FROM metodo_pago_usuario 
        WHERE id_usuario = ? AND esta_activo = 1 
        ORDER BY es_predeterminado DESC, fecha_creacion DESC
    ");
    $stmt->execute([$user_id]);
    $payment_methods = $stmt->fetchAll();
} catch (Exception $e) {
    $payment_methods = [];
}

// Mostrar mensaje de error de sesión si existe
if (isset($_SESSION['error_message'])) {
    $error_message = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

// Procesar formularios
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'update_profile':
                $name = trim($_POST['name']);
                $email = trim($_POST['email']);
                $phone = trim($_POST['phone']);
                $address = trim($_POST['address']);
                
                // Validar datos
                if (empty($name)) {
                    $error_message = "El nombre es requerido";
                    break;
                }
                
                if (empty($phone)) {
                    $error_message = "El teléfono es requerido";
                    break;
                }
                
                if (empty($address)) {
                    $error_message = "La dirección es requerida";
                    break;
                }
                
                // Validar que los campos no empiecen con espacios
                if (preg_match('/^\s/', $_POST['name'])) {
                    $error_message = "El nombre no puede empezar con espacios en blanco";
                    break;
                }
                
                if (preg_match('/^\s/', $_POST['email'])) {
                    $error_message = "El email no puede empezar con espacios en blanco";
                    break;
                }
                
                if (!empty($_POST['phone']) && preg_match('/^\s/', $_POST['phone'])) {
                    $error_message = "El teléfono no puede empezar con espacios en blanco";
                    break;
                }
                
                if (!empty($_POST['address']) && preg_match('/^\s/', $_POST['address'])) {
                    $error_message = "La dirección no puede empezar con espacios en blanco";
                    break;
                }
                
                if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $error_message = "El email no es válido";
                    break;
                }
                
                // Validar que el nombre solo contenga letras y espacios (no números)
                if (!empty($name) && !preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑ ]+$/u', $name)) {
                    $error_message = "El nombre solo debe contener letras y espacios, no números";
                    break;
                }
                
                // Validar que el teléfono tenga exactamente 10 dígitos
                if (!empty($phone)) {
                    if (!preg_match('/^\\d{10}$/', $phone)) {
                        $error_message = "El teléfono debe tener exactamente 10 dígitos";
                        break;
                    }
                }
                
                // Validar formato de dirección (debe contener calle, número, colonia, ciudad)
                if (!empty($address)) {
                    if (strlen($address) < 10) {
                        $error_message = "La dirección debe ser más específica (calle, número, colonia, ciudad)";
                        break;
                    }
                    // Verificar que contenga elementos básicos de una dirección
                    if (!preg_match('/.*(calle|avenida|blvd|colonia|ciudad|municipio).*/i', $address)) {
                        $error_message = "La dirección debe incluir calle, colonia y ciudad";
                        break;
                    }
                }
                
                // Validar formato de email más estricto
                if (!empty($email)) {
                    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $error_message = "El formato del email no es válido";
                        break;
                    }
                    // Verificar que tenga un dominio válido
                    $emailParts = explode('@', $email);
                    if (count($emailParts) !== 2 || strlen($emailParts[1]) < 3) {
                        $error_message = "El email debe tener un dominio válido";
                        break;
                    }
                    // Verificar que no tenga caracteres especiales problemáticos
                    if (preg_match('/[<>"\']/', $email)) {
                        $error_message = "El email contiene caracteres no permitidos";
                        break;
                    }
                }
                
                // Verificar si el email ya existe para otro usuario
                $stmt = $pdo->prepare("SELECT id_usuario FROM usuario WHERE correo = ? AND id_usuario != ?");
                $stmt->execute([$email, $user_id]);
                if ($stmt->fetch()) {
                    $error_message = "El email ya está en uso por otro usuario";
                    break;
                }
                
                // Separar el nombre completo en first_name y last_name
                $nameParts = explode(' ', $name, 2);
                $firstName = $nameParts[0];
                $lastName = isset($nameParts[1]) ? $nameParts[1] : '';
                
                try {
                    $stmt = $pdo->prepare("UPDATE usuario SET nombre = ?, apellido = ?, correo = ?, telefono = ?, direccion = ? WHERE id_usuario = ?");
                    if ($stmt->execute([$firstName, $lastName, $email, $phone, $address, $user_id])) {
                        $success_message = "Perfil actualizado correctamente";
                    } else {
                        $error_message = "Error al actualizar el perfil en la base de datos";
                    }
                } catch (Exception $e) {
                    $error_message = "Error en la base de datos: " . $e->getMessage();
                }
                break;
                
            case 'change_password':
                $current_password = $_POST['current_password'];
                $new_password = $_POST['new_password'];
                $confirm_password = $_POST['confirm_password'];
                
                // Validar datos
                if (empty($current_password)) {
                    $error_message = "La contraseña actual es requerida";
                    break;
                }
                
                if (empty($new_password)) {
                    $error_message = "La nueva contraseña es requerida";
                    break;
                }
                
                if (strlen($new_password) < 8) {
                    $error_message = "La nueva contraseña debe tener al menos 8 caracteres";
                    break;
                }
                
                if ($new_password !== $confirm_password) {
                    $error_message = "Las contraseñas no coinciden";
                    break;
                }
                
                try {
                    if ($current_password === $user['hash_contraseña']) {
                        $hashed_password = $new_password;
                        $stmt = $pdo->prepare("UPDATE usuario SET hash_contraseña = ? WHERE id_usuario = ?");
                        if ($stmt->execute([$hashed_password, $user_id])) {
                            $success_message = "Contraseña cambiada correctamente";
                        } else {
                            $error_message = "Error al actualizar la contraseña en la base de datos";
                        }
                    } else {
                        $error_message = "La contraseña actual es incorrecta";
                    }
                } catch (Exception $e) {
                    $error_message = "Error en la base de datos: " . $e->getMessage();
                }
                break;
                
            case 'cancel_subscription':
                $subscription_id = $_POST['subscription_id'];
                $stmt = $pdo->prepare("UPDATE suscripcion_usuario SET estado = 'cancelada' WHERE id_suscripcion = ? AND id_usuario = ?");
                if ($stmt->execute([$subscription_id, $user_id])) {
                    $success_message = "Suscripción cancelada correctamente";
                } else {
                    $error_message = "Error al cancelar la suscripción";
                }
                break;
        }
        
        // Recargar datos del usuario
        $stmt = $pdo->prepare("SELECT * FROM usuario WHERE id_usuario = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
    } catch (Exception $e) {
        $error_message = "Error al procesar la solicitud";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="style.css">
    <title>Mi Perfil - Fit 360</title>
    <style>
        .required {
            color: #dc3545;
            font-weight: bold;
        }
        
        .form-group label {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .form-group input:invalid {
            border-color: #dc3545;
        }
        
        .form-group input:valid {
            border-color: #28a745;
        }
        
        .form-group input:focus:invalid {
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
        }
        
        .form-group input:focus:valid {
            box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
        }
    </style>
</head>
<body>
    <header>
        <a href="index.php" class="logo">Fit <span>360</span></a>
        <div class='bx bx-menu' id="menu-icon"></div>
        <ul class="navbar">
            <li><a href="index.php#home">Inicio</a></li>
            <li><a href="index.php#services">Servicios</a></li>
            <li><a href="index.php#about">Acerca de Nosotros</a></li>
            <li><a href="index.php#plans">Suscripciones</a></li>
            <li><a href="index.php#review">Productos</a></li>
        </ul>
        
        <div class="top-btn">
            <a href="profile.php" class="username-link">
                <i class='bx bx-user'></i>
                <span><?= htmlspecialchars($_SESSION['username']) ?></span>
            </a>
            <a href="logout.php" class="nav-btn">Cerrar Sesión</a>
        </div>
    </header>

    <section class="profile-section">
        <div class="profile-container">
            <div class="profile-header">
                <h1>Mi Perfil</h1>
                <p>Gestiona tu información personal y configuraciones</p>
            </div>

            <?php if (isset($success_message)): ?>
                <div class="alert success">
                    <i class='bx bx-check-circle'></i>
                    <?= htmlspecialchars($success_message) ?>
                </div>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
                <div class="alert error">
                    <i class='bx bx-error-circle'></i>
                    <?= htmlspecialchars($error_message) ?>
                </div>
            <?php endif; ?>

            <div class="profile-content">
                <!-- Información Personal -->
                <div class="profile-card">
                    <div class="card-header">
                        <h2><i class='bx bx-user-circle'></i> Información Personal</h2>
                    </div>
                    <form method="POST" class="profile-form" id="personal-form">
                        <input type="hidden" name="action" value="update_profile">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Nombre Completo</label>
                                <input type="text" name="name" value="<?= htmlspecialchars(($user['nombre'] ?? '') . ' ' . ($user['apellido'] ?? '')) ?>" 
                                       required placeholder="Solo letras y espacios" 
                                       pattern="[a-zA-ZáéíóúÁÉÍÓÚñÑ ]+" 
                                       title="Solo se permiten letras y espacios">
                            </div>
                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" name="email" value="<?= htmlspecialchars($user['correo'] ?? '') ?>" 
                                       required placeholder="ejemplo@dominio.com"
                                       title="Ingresa un email válido">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Teléfono <span class="required">*</span></label>
                                <input type="tel" name="phone" value="<?= htmlspecialchars($user['telefono'] ?? '') ?>" 
                                       placeholder="10 dígitos" 
                                       pattern="[0-9]{10}" 
                                       maxlength="10"
                                       required
                                       title="Debe tener exactamente 10 dígitos">
                            </div>
                            <div class="form-group">
                                <label>Dirección <span class="required">*</span></label>
                                <input type="text" name="address" value="<?= htmlspecialchars($user['direccion'] ?? '') ?>" 
                                       placeholder="Calle, número, colonia, ciudad"
                                       minlength="10"
                                       required
                                       title="Incluye calle, colonia y ciudad (mínimo 10 caracteres)">
                            </div>
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn">Guardar Cambios</button>
                        </div>
                    </form>
                </div>

                <!-- Cambiar Contraseña -->
                <div class="profile-card">
                    <div class="card-header">
                        <h2><i class='bx bx-lock'></i> Cambiar Contraseña</h2>
                    </div>
                    <form method="POST" class="profile-form">
                        <input type="hidden" name="action" value="change_password">
                        <div class="form-group">
                            <label>Contraseña Actual</label>
                            <input type="password" name="current_password" id="current_password" required>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Nueva Contraseña</label>
                                <input type="password" name="new_password" id="new_password" required>
                            </div>
                            <div class="form-group">
                                <label>Confirmar Nueva Contraseña</label>
                                <input type="password" name="confirm_password" id="confirm_password" required>
                            </div>
                        </div>
                        <label style="display:flex;align-items:center;gap:8px;margin-top:8px;font-size:1.3rem;">
                            <input type="checkbox" id="show-new-password"> Mostrar contraseña
                        </label>
                        <div class="form-actions">
                            <button type="submit" class="btn">Cambiar Contraseña</button>
                        </div>
                    </form>
                </div>

                <!-- Métodos de Pago -->
                <div class="profile-card">
                    <div class="card-header">
                        <h2><i class='bx bx-credit-card'></i> Métodos de Pago</h2>
                        <button class="add-btn" onclick="showAddPayment()">
                            <i class='bx bx-plus'></i> Agregar
                        </button>
                    </div>
                    <div class="payment-methods" id="paymentMethodsContainer">
                        <?php if (!empty($payment_methods)): ?>
                            <?php foreach ($payment_methods as $method): ?>
                            <div class="payment-method" data-id="<?= $method['id_metodo_pago'] ?>">
                                <div class="payment-info">
                                    <i class='bx bx-credit-card'></i>
                                    <div>
                                        <?php 
                                        // Formatear número de tarjeta para mostrar solo últimos 4 dígitos
                                        $card_number = preg_replace('/\s/', '', $method['numero_tarjeta']);
                                        $last_four = substr($card_number, -4);
                                        $formatted_card = '**** **** **** ' . $last_four;
                                        ?>
                                        <h4><?= htmlspecialchars($method['tipo_tarjeta']) ?> terminada en <?= htmlspecialchars($formatted_card) ?></h4>
                                        <p>Expira: <?= htmlspecialchars($method['fecha_vencimiento']) ?></p>
                                        <?php if ($method['es_predeterminado']): ?>
                                            <span class="default-badge">Predeterminada</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="payment-actions">
                                    <?php if (!$method['es_predeterminado']): ?>
                                        <button class="default-btn" onclick="setDefaultPayment(<?= $method['id_metodo_pago'] ?>)">
                                            <i class='bx bx-star'></i> Predeterminada
                                        </button>
                                    <?php endif; ?>
                                    <button class="remove-btn" onclick="removePayment(<?= $method['id_metodo_pago'] ?>)">
                                        <i class='bx bx-trash'></i>
                                    </button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class='bx bx-credit-card'></i>
                                <p>No tienes métodos de pago registrados</p>
                                <button class="btn" onclick="showAddPayment()">Agregar Método de Pago</button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Suscripciones Activas -->
                <?php if (!empty($subscriptions)): ?>
                <div class="profile-card">
                    <div class="card-header">
                        <h2><i class='bx bx-calendar-check'></i> Suscripción Activa</h2>
                    </div>
                    <div class="subscriptions-list">
                        <?php foreach ($subscriptions as $subscription): ?>
                        <div class="subscription-item">
                            <div class="subscription-info">
                                <h4><?= htmlspecialchars($subscription['plan_name']) ?></h4>
                                <p>$<?= number_format($subscription['precio'], 2) ?> MXN/mes</p>
                                <p>Estado: <span class="status active">Activa</span></p>
                                <p>Próximo pago: <?= date('d/m/Y', strtotime($subscription['fecha_fin'])) ?></p>
                            </div>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="cancel_subscription">
                                <input type="hidden" name="subscription_id" value="<?= $subscription['id_suscripcion'] ?>">
                                <button type="submit" class="cancel-btn" onclick="return confirm('¿Estás seguro de que quieres cancelar esta suscripción?')">
                                    <i class='bx bx-x'></i> Cancelar
                                </button>
                            </form>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Historial de Compras -->
                <div class="profile-card">
                    <div class="card-header">
                        <h2><i class='bx bx-history'></i> Historial de Compras</h2>
                    </div>
                    <div class="orders-list">
                        <?php if (!empty($orders)): ?>
                            <?php foreach ($orders as $order): ?>
                            <div class="order-item">
                                <div class="order-header">
                                    <div class="order-number">
                                        <h4>Orden #<?= $order['id_pedido'] ?></h4>
                                        <p class="order-date"><?= date('d/m/Y H:i', strtotime($order['fecha_creacion'])) ?></p>
                                    </div>
                                    <div class="order-status">
                                        <span class="status <?= $order['estado'] ?>"><?= ucfirst($order['estado']) ?></span>
                                    </div>
                                </div>
                                <div class="order-content">
                                    <div class="order-products-grid">
                                        <?php 
                                        $productNames = explode(', ', $order['product_names']);
                                        $quantities = explode(', ', $order['quantities']);
                                        $images = explode(', ', $order['images']);
                                        for ($i = 0; $i < count($productNames); $i++):
                                            if (!empty($productNames[$i])):
                                        ?>
                                        <div class="product-item-grid">
                                            <img src="<?= !empty($images[$i]) ? $images[$i] : 'assets/image1.png' ?>" alt="<?= htmlspecialchars($productNames[$i]) ?>">
                                            <div class="product-info-grid">
                                                <h5><?= htmlspecialchars($productNames[$i]) ?></h5>
                                                <p>Cantidad: <?= $quantities[$i] ?? 1 ?></p>
                                            </div>
                                        </div>
                                        <?php 
                                            endif;
                                        endfor; 
                                        ?>
                                    </div>
                                    <div class="order-summary">
                                        <p class="order-payment-method"><strong>Método de pago:</strong> <?= htmlspecialchars($order['metodo_pago']) ?></p>
                                        <p class="order-total-large"><strong>Total:</strong> $<?= number_format($order['monto_total'], 2) ?> MXN</p>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class='bx bx-package'></i>
                                <p>No tienes compras registradas</p>
                                <a href="products.php" class="btn">Ver Productos</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Modal para agregar método de pago -->
    <div id="paymentModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Agregar Método de Pago</h3>
                <button class="close-modal" onclick="closePaymentModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form class="payment-form" id="addPaymentForm">
                    <div class="form-group">
                        <label>Número de Tarjeta</label>
                        <div class="input-group">
                            <i class='bx bx-credit-card'></i>
                            <input type="text" id="cardNumber" name="numero_tarjeta" placeholder="1234 5678 9012 3456" maxlength="19" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Fecha de Expiración</label>
                            <div class="input-group">
                                <i class='bx bx-calendar'></i>
                                <input type="text" id="expiryDate" name="fecha_vencimiento" placeholder="MM/YY" maxlength="5" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>CVV</label>
                            <div class="input-group">
                                <i class='bx bx-lock'></i>
                                <input type="text" id="cvv" name="cvv" placeholder="123" maxlength="4" required>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Nombre en la Tarjeta</label>
                        <div class="input-group">
                            <i class='bx bx-user'></i>
                            <input type="text" id="cardholderName" name="nombre_titular" placeholder="Nombre Apellido" required pattern="^[A-Za-zÁÉÍÓÚáéíóúÑñ ]+$" title="El nombre no puede empezar con espacios ni contener números">
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn">Agregar Tarjeta</button>
                        <button type="button" class="btn-secondary" onclick="closePaymentModal()">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Checkout Modal -->
    <div id="checkoutModal" class="checkout-modal">
        <div class="checkout-modal-content">
            <div class="checkout-header">
                <h2><i class='bx bx-credit-card'></i> Finalizar Compra</h2>
                <span class="close-checkout" onclick="closeCheckout()">&times;</span>
            </div>
            
            <div class="checkout-body">
                <div class="checkout-items" id="checkout-items">
                    <!-- Checkout items will be dynamically added here -->
                </div>
                
                <div class="checkout-total">
                    <span>Total a Pagar:</span>
                    <span id="checkout-total">$0.00 MXN</span>
                </div>
                
                <form id="payment-form" class="payment-form">
                    <!-- Opción de método de pago existente -->
                    <div class="form-group">
                        <label>Método de Pago</label>
                        <div class="payment-method-selector">
                            <label class="radio-option">
                                <input type="radio" name="payment_option" value="existing">
                                <span>Usar método existente</span>
                            </label>
                            <label class="radio-option">
                                <input type="radio" name="payment_option" value="new" checked>
                                <span>Nuevo método de pago</span>
                            </label>
                        </div>
                        
                        <div id="existing-payment-methods" class="existing-methods" style="display: none;">
                            <select id="selected-payment-method" name="selected_payment_method">
                                <option value="">Selecciona un método de pago</option>
                            </select>
                            
                            <!-- Campo CVV para método existente -->
                            <div class="form-group" style="margin-top: 15px;">
                                <label for="existing-cvv">CVV</label>
                                <div class="input-group">
                                    <i class='bx bx-lock'></i>
                                    <input type="text" id="existing-cvv" name="existing_cvv" placeholder="123" maxlength="4">
                                </div>
                                <small style="color: #666; font-size: 12px;">Ingresa el código de seguridad de tu tarjeta</small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Nuevo método de pago -->
                    <div id="new-payment-form" class="new-payment-form">
                        <div class="form-group">
                            <label for="card-name">Nombre en la Tarjeta</label>
                            <div class="input-group">
                                <i class='bx bx-user'></i>
                                <input type="text" id="card-name" name="nombre_titular" required pattern="^[A-Za-zÁÉÍÓÚáéíóúÑñ ]+$" title="El nombre no puede empezar con espacios ni contener números">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="card-number">Número de Tarjeta</label>
                            <div class="input-group">
                                <i class='bx bx-credit-card'></i>
                                <input type="text" id="card-number" name="numero_tarjeta" placeholder="1234 5678 9012 3456" maxlength="19" required>
                            </div>
                            <div class="card-type-indicator" id="card-type"></div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="card-expiry">Fecha de Vencimiento</label>
                                <div class="input-group">
                                    <i class='bx bx-calendar'></i>
                                    <input type="text" id="card-expiry" name="fecha_vencimiento" placeholder="MM/YY" maxlength="5" required>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="card-cvv">CVV</label>
                                <div class="input-group">
                                    <i class='bx bx-lock'></i>
                                    <input type="text" id="card-cvv" name="cvv" placeholder="123" maxlength="4" required>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn">Confirmar Pago</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function toggleEdit(formId) {
            const form = document.getElementById(formId + '-form');
            const inputs = form.querySelectorAll('input, textarea, select');
            inputs.forEach(input => {
                input.disabled = !input.disabled;
            });
        }

        function showAddPayment() {
            document.getElementById('paymentModal').style.display = 'flex';
            
            // Limpiar estilos de validación previos
            const inputs = document.querySelectorAll('#addPaymentForm input');
            inputs.forEach(input => {
                input.style.borderColor = '';
                input.style.boxShadow = '';
            });
            
            // Remover indicador de tipo de tarjeta previo
            const cardTypeIndicator = document.getElementById('card-type-indicator');
            if (cardTypeIndicator) {
                cardTypeIndicator.remove();
            }
            
            // Limpiar formulario
            document.getElementById('addPaymentForm').reset();
        }

        function closePaymentModal() {
            document.getElementById('paymentModal').style.display = 'none';
            
            // Limpiar estilos de validación
            const inputs = document.querySelectorAll('#addPaymentForm input');
            inputs.forEach(input => {
                input.style.borderColor = '';
                input.style.boxShadow = '';
            });
            
            // Remover indicador de tipo de tarjeta
            const cardTypeIndicator = document.getElementById('card-type-indicator');
            if (cardTypeIndicator) {
                cardTypeIndicator.remove();
            }
            
            // Limpiar formulario
            document.getElementById('addPaymentForm').reset();
        }

        // Card type detection and formatting functions
        function detectCardType(number) {
            const patterns = {
                visa: /^4/,
                mastercard: /^5[1-5]/,
                amex: /^3[47]/,
                discover: /^6(?:011|5)/
            };
            
            for (const [type, pattern] of Object.entries(patterns)) {
                if (pattern.test(number)) {
                    return type;
                }
            }
            return 'unknown';
        }

        function validateCardNumber(number) {
            // Luhn algorithm
            let sum = 0;
            let isEven = false;
            
            for (let i = number.length - 1; i >= 0; i--) {
                let digit = parseInt(number[i]);
                
                if (isEven) {
                    digit *= 2;
                    if (digit > 9) {
                        digit -= 9;
                    }
                }
                
                sum += digit;
                isEven = !isEven;
            }
            
            return sum % 10 === 0;
        }

        function validateExpiryDate(expiry) {
            const [month, year] = expiry.split('/');
            const currentDate = new Date();
            const currentYear = currentDate.getFullYear() % 100;
            const currentMonth = currentDate.getMonth() + 1;
            
            const expMonth = parseInt(month);
            const expYear = parseInt(year);
            
            if (expYear < currentYear || (expYear === currentYear && expMonth < currentMonth)) {
                return false;
            }
            
            return expMonth >= 1 && expMonth <= 12;
        }

        function validateCVV(cvv, cardType) {
            const cvvLength = cvv.length;
            
            if (cardType === 'amex') {
                return cvvLength === 4;
            } else {
                return cvvLength === 3;
            }
        }

        // Formatear número de tarjeta
        document.getElementById('card-number').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\s/g, '').replace(/\D/g, '');
            let formattedValue = '';
            for (let i = 0; i < value.length; i++) {
                if (i > 0 && i % 4 === 0) {
                    formattedValue += ' ';
                }
                formattedValue += value[i];
            }
            e.target.value = formattedValue;
            
            // Detectar tipo de tarjeta
            const cardType = detectCardType(value);
            const cardTypeElement = document.getElementById('card-type');
            if (cardTypeElement) {
                cardTypeElement.textContent = cardType;
                cardTypeElement.className = 'card-type-indicator ' + cardType.toLowerCase();
            }
        });

        // Formatear fecha de expiración
        document.getElementById('card-expiry').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length >= 2) {
                value = value.substring(0, 2) + '/' + value.substring(2, 4);
            }
            e.target.value = value;
        });

        // Solo números para CVV
        document.getElementById('card-cvv').addEventListener('input', function(e) {
            e.target.value = e.target.value.replace(/\D/g, '');
        });

        // Validación para el formulario de agregar método de pago
        // Formatear número de tarjeta en el modal de agregar método de pago
        document.getElementById('cardNumber').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\s/g, '').replace(/\D/g, '');
            let formattedValue = '';
            for (let i = 0; i < value.length; i++) {
                if (i > 0 && i % 4 === 0) {
                    formattedValue += ' ';
                }
                formattedValue += value[i];
            }
            e.target.value = formattedValue;
            
            // Validar número de tarjeta
            const cardNumber = value;
            const isValidCard = validateCardNumber(cardNumber);
            const cardType = detectCardType(cardNumber);
            
            // Mostrar indicador de tipo de tarjeta
            let cardTypeIndicator = document.getElementById('card-type-indicator');
            if (!cardTypeIndicator) {
                cardTypeIndicator = document.createElement('div');
                cardTypeIndicator.id = 'card-type-indicator';
                cardTypeIndicator.className = 'card-type-indicator';
                e.target.parentNode.appendChild(cardTypeIndicator);
            }
            
            if (cardNumber.length > 0) {
                cardTypeIndicator.textContent = cardType;
                cardTypeIndicator.className = 'card-type-indicator ' + cardType.toLowerCase();
                
                if (cardNumber.length >= 13 && cardNumber.length <= 19) {
                    if (isValidCard) {
                        e.target.style.borderColor = '#28a745';
                        cardTypeIndicator.style.color = '#28a745';
                    } else {
                        e.target.style.borderColor = '#dc3545';
                        cardTypeIndicator.style.color = '#dc3545';
                    }
                } else {
                    e.target.style.borderColor = '';
                    cardTypeIndicator.style.color = '';
                }
            } else {
                cardTypeIndicator.textContent = '';
                e.target.style.borderColor = '';
            }
        });

        // Formatear fecha de expiración en el modal de agregar método de pago
        document.getElementById('expiryDate').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length >= 2) {
                value = value.substring(0, 2) + '/' + value.substring(2, 4);
            }
            e.target.value = value;
            
            // Validar fecha de expiración
            if (value.length === 5) {
                const isValidExpiry = validateExpiryDate(value);
                if (isValidExpiry) {
                    e.target.style.borderColor = '#28a745';
                } else {
                    e.target.style.borderColor = '#dc3545';
                }
            } else {
                e.target.style.borderColor = '';
            }
        });

        // Validar CVV en el modal de agregar método de pago
        document.getElementById('cvv').addEventListener('input', function(e) {
            e.target.value = e.target.value.replace(/\D/g, '');
            
            // Obtener tipo de tarjeta para validar CVV
            const cardNumber = document.getElementById('cardNumber').value.replace(/\s/g, '');
            const cardType = detectCardType(cardNumber);
            const cvv = e.target.value;
            
            if (cvv.length > 0) {
                const isValidCVV = validateCVV(cvv, cardType);
                if (isValidCVV) {
                    e.target.style.borderColor = '#28a745';
                } else {
                    e.target.style.borderColor = '#dc3545';
                }
            } else {
                e.target.style.borderColor = '';
            }
        });

        // Manejar envío del formulario de pago
        document.getElementById('addPaymentForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Validar campos antes de enviar
            const cardNumber = document.getElementById('cardNumber').value.replace(/\s/g, '');
            const expiryDate = document.getElementById('expiryDate').value;
            const cvv = document.getElementById('cvv').value;
            const cardholderName = document.getElementById('cardholderName').value.trim();
            
            // Validar número de tarjeta
            if (!validateCardNumber(cardNumber)) {
                alert('Por favor ingresa un número de tarjeta válido');
                document.getElementById('cardNumber').focus();
                return;
            }
            
            // Validar fecha de expiración
            if (!validateExpiryDate(expiryDate)) {
                alert('Por favor ingresa una fecha de expiración válida (MM/YY)');
                document.getElementById('expiryDate').focus();
                return;
            }
            
            // Validar CVV
            const cardType = detectCardType(cardNumber);
            if (!validateCVV(cvv, cardType)) {
                const expectedLength = cardType === 'amex' ? 4 : 3;
                alert(`Por favor ingresa un CVV válido (${expectedLength} dígitos)`);
                document.getElementById('cvv').focus();
                return;
            }
            
            // Validar nombre del titular
            if (cardholderName.length < 2 || /^[0-9]|\s/.test(cardholderName) || /[0-9]/.test(cardholderName)) {
                alert('Por favor ingresa un nombre válido (sin espacios al inicio ni números)');
                document.getElementById('cardholderName').focus();
                return;
            }
            
            const formData = new FormData(this);
            
            fetch('add_payment_method.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    closePaymentModal();
                    loadPaymentMethods();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al agregar método de pago');
            });
        });

        function loadPaymentMethods() {
            fetch('get_payment_methods.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const container = document.getElementById('paymentMethodsContainer');
                    
                    if (data.payment_methods.length === 0) {
                        container.innerHTML = `
                            <div class="empty-state">
                                <i class='bx bx-credit-card'></i>
                                <p>No tienes métodos de pago registrados</p>
                                <button class="btn" onclick="showAddPayment()">Agregar Método de Pago</button>
                            </div>
                        `;
                    } else {
                        // Mapear propiedades a los nombres esperados por el JS
                        const mappedMethods = data.payment_methods.map(method => ({
                            payment_method_id: method.id_metodo_pago,
                            card_type: method.tipo_tarjeta,
                            card_number: method.numero_tarjeta,
                            expiry_date: method.fecha_vencimiento,
                            cardholder_name: method.nombre_titular,
                            is_default: !!method.es_predeterminado
                        }));
                        container.innerHTML = mappedMethods.map(method => `
                            <div class="payment-method" data-id="${method.payment_method_id}">
                                <div class="payment-info">
                                    <i class='bx bx-credit-card'></i>
                                    <div>
                                        <h4>${method.card_type} terminada en ${method.card_number}</h4>
                                        <p>Expira: ${method.expiry_date}</p>
                                        ${method.is_default ? '<span class="default-badge">Predeterminada</span>' : ''}
                                    </div>
                                </div>
                                <div class="payment-actions">
                                    ${!method.is_default ? `
                                        <button class="default-btn" onclick="setDefaultPayment(${method.payment_method_id})">
                                            <i class='bx bx-star'></i> Predeterminada
                                        </button>
                                    ` : ''}
                                    <button class="remove-btn" onclick="removePayment(${method.payment_method_id})">
                                        <i class='bx bx-trash'></i>
                                    </button>
                                </div>
                            </div>
                        `).join('');
                    }
                } else {
                    console.error('Error:', data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        }

        function removePayment(id) {
            if (confirm('¿Estás seguro de que quieres eliminar este método de pago?')) {
                const formData = new FormData();
                formData.append('payment_method_id', id);
                
                fetch('delete_payment_method.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        loadPaymentMethods();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al eliminar método de pago');
                });
            }
        }

        function setDefaultPayment(id) {
            const formData = new FormData();
            formData.append('payment_method_id', id);
            
            fetch('set_default_payment_method.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    loadPaymentMethods();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al establecer método predeterminado');
            });
        }

        // Cerrar modal al hacer clic fuera
        window.onclick = function(event) {
            const modal = document.getElementById('paymentModal');
            if (event.target === modal) {
                closePaymentModal();
            }
        }

        // Validaciones para prevenir espacios al inicio en inputs de información personal
        document.addEventListener('DOMContentLoaded', function() {
            // Configurar validaciones para inputs de información personal
            const personalInputs = [
                'input[name="name"]',
                'input[name="email"]',
                'input[name="phone"]',
                'input[name="address"]'
            ];
            
            personalInputs.forEach(selector => {
                const input = document.querySelector(selector);
                if (input) {
                    // Prevenir espacios al inicio al escribir
                    input.addEventListener('input', function(e) {
                        if (this.value.startsWith(' ')) {
                            this.value = this.value.replace(/^\s+/, '');
                        }
                        
                        // Validaciones específicas por tipo de campo
                        const fieldName = this.getAttribute('name');
                        
                        if (fieldName === 'name') {
                            // Solo permitir letras y espacios para el nombre
                            this.value = this.value.replace(/[0-9]/g, '');
                            if (this.value.length > 0 && !/^[a-zA-ZáéíóúÁÉÍÓÚñÑ ]+$/.test(this.value)) {
                                this.style.borderColor = '#dc3545';
                                this.title = 'El nombre solo puede contener letras y espacios';
                            } else {
                                this.style.borderColor = '#28a745';
                                this.title = '';
                            }
                        }
                        
                        if (fieldName === 'phone') {
                            // Solo permitir números y máximo 10 dígitos
                            this.value = this.value.replace(/[^0-9]/g, '');
                            if (this.value.length > 10) {
                                this.value = this.value.substring(0, 10);
                            }
                            if (this.value.length === 0) {
                                this.style.borderColor = '#dc3545';
                                this.title = 'El teléfono es obligatorio';
                            } else if (this.value.length > 0 && this.value.length !== 10) {
                                this.style.borderColor = '#ffc107';
                                this.title = 'El teléfono debe tener exactamente 10 dígitos';
                            } else if (this.value.length === 10) {
                                this.style.borderColor = '#28a745';
                                this.title = '';
                            } else {
                                this.style.borderColor = '';
                                this.title = '';
                            }
                        }
                        
                        if (fieldName === 'email') {
                            // Validar formato de email
                            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                            if (this.value.length > 0 && !emailRegex.test(this.value)) {
                                this.style.borderColor = '#dc3545';
                                this.title = 'Ingresa un email válido (ejemplo@dominio.com)';
                            } else if (this.value.length > 0 && emailRegex.test(this.value)) {
                                this.style.borderColor = '#28a745';
                                this.title = '';
                            } else {
                                this.style.borderColor = '';
                                this.title = '';
                            }
                        }
                        
                        if (fieldName === 'address') {
                            // Validar dirección
                            if (this.value.length === 0) {
                                this.style.borderColor = '#dc3545';
                                this.title = 'La dirección es obligatoria';
                            } else if (this.value.length > 0 && this.value.length < 10) {
                                this.style.borderColor = '#ffc107';
                                this.title = 'La dirección debe ser más específica';
                            } else if (this.value.length >= 10 && !/(calle|avenida|blvd|colonia|ciudad|municipio)/i.test(this.value)) {
                                this.style.borderColor = '#ffc107';
                                this.title = 'Incluye calle, colonia y ciudad';
                            } else if (this.value.length >= 10 && /(calle|avenida|blvd|colonia|ciudad|municipio)/i.test(this.value)) {
                                this.style.borderColor = '#28a745';
                                this.title = '';
                            } else {
                                this.style.borderColor = '';
                                this.title = '';
                            }
                        }
                    });
                    
                    // Prevenir espacios al inicio al pegar
                    input.addEventListener('paste', function(e) {
                        setTimeout(() => {
                            if (this.value.startsWith(' ')) {
                                this.value = this.value.replace(/^\s+/, '');
                            }
                        }, 0);
                    });
                    
                    // Validar antes de enviar el formulario
                    input.addEventListener('blur', function() {
                        if (this.value.startsWith(' ')) {
                            this.value = this.value.replace(/^\s+/, '');
                        }
                    });
                }
            });
            
            // Validación del formulario de información personal
            const personalForm = document.getElementById('personal-form');
            if (personalForm) {
                personalForm.addEventListener('submit', function(e) {
                    const nameInput = this.querySelector('input[name="name"]');
                    const emailInput = this.querySelector('input[name="email"]');
                    const phoneInput = this.querySelector('input[name="phone"]');
                    const addressInput = this.querySelector('input[name="address"]');
                    
                    // Verificar espacios al inicio
                    if (nameInput && nameInput.value.startsWith(' ')) {
                        e.preventDefault();
                        alert('El nombre no puede empezar con espacios en blanco');
                        nameInput.focus();
                        return false;
                    }
                    
                    if (emailInput && emailInput.value.startsWith(' ')) {
                        e.preventDefault();
                        alert('El email no puede empezar con espacios en blanco');
                        emailInput.focus();
                        return false;
                    }
                    
                    if (phoneInput && phoneInput.value.startsWith(' ')) {
                        e.preventDefault();
                        alert('El teléfono no puede empezar con espacios en blanco');
                        phoneInput.focus();
                        return false;
                    }
                    
                    if (addressInput && addressInput.value.startsWith(' ')) {
                        e.preventDefault();
                        alert('La dirección no puede empezar con espacios en blanco');
                        addressInput.focus();
                        return false;
                    }
                    
                    // Validaciones específicas de formato
                    if (nameInput && nameInput.value.length > 0) {
                        if (!/^[a-zA-ZáéíóúÁÉÍÓÚñÑ ]+$/.test(nameInput.value)) {
                            e.preventDefault();
                            alert('El nombre solo puede contener letras y espacios, no números');
                            nameInput.focus();
                            return false;
                        }
                    }
                    
                    if (!phoneInput || phoneInput.value.length === 0) {
                        e.preventDefault();
                        alert('El teléfono es obligatorio');
                        phoneInput.focus();
                        return false;
                    } else if (!/^\d{10}$/.test(phoneInput.value)) {
                        e.preventDefault();
                        alert('El teléfono debe tener exactamente 10 dígitos');
                        phoneInput.focus();
                        return false;
                    }
                    
                    if (emailInput && emailInput.value.length > 0) {
                        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                        if (!emailRegex.test(emailInput.value)) {
                            e.preventDefault();
                            alert('Ingresa un email válido (ejemplo@dominio.com)');
                            emailInput.focus();
                            return false;
                        }
                    }
                    
                    if (!addressInput || addressInput.value.length === 0) {
                        e.preventDefault();
                        alert('La dirección es obligatoria');
                        addressInput.focus();
                        return false;
                    } else if (addressInput.value.length < 10) {
                        e.preventDefault();
                        alert('La dirección debe ser más específica (mínimo 10 caracteres)');
                        addressInput.focus();
                        return false;
                    } else if (!/(calle|avenida|blvd|colonia|ciudad|municipio)/i.test(addressInput.value)) {
                        e.preventDefault();
                        alert('La dirección debe incluir calle, colonia y ciudad');
                        addressInput.focus();
                        return false;
                    }
                });
            }
            
            // Configuración para mostrar/ocultar contraseñas
            const showNewPassword = document.getElementById('show-new-password');
            const newPassword = document.getElementById('new_password');
            const confirmPassword = document.getElementById('confirm_password');
            const currentPassword = document.getElementById('current_password');
            if (showNewPassword && newPassword && confirmPassword && currentPassword) {
                showNewPassword.addEventListener('change', function() {
                    const type = this.checked ? 'text' : 'password';
                    newPassword.type = type;
                    confirmPassword.type = type;
                    currentPassword.type = type;
                });
            }
            
            // Validaciones para campos de contraseña
            const passwordInputs = [
                'input[name="current_password"]',
                'input[name="new_password"]',
                'input[name="confirm_password"]'
            ];
            
            passwordInputs.forEach(selector => {
                const input = document.querySelector(selector);
                if (input) {
                    // Prevenir espacios al inicio al escribir
                    input.addEventListener('input', function(e) {
                        if (this.value.startsWith(' ')) {
                            this.value = this.value.replace(/^\s+/, '');
                        }
                    });
                    
                    // Prevenir espacios al inicio al pegar
                    input.addEventListener('paste', function(e) {
                        setTimeout(() => {
                            if (this.value.startsWith(' ')) {
                                this.value = this.value.replace(/^\s+/, '');
                            }
                        }, 0);
                    });
                    
                    // Validar antes de enviar el formulario
                    input.addEventListener('blur', function() {
                        if (this.value.startsWith(' ')) {
                            this.value = this.value.replace(/^\s+/, '');
                        }
                    });
                }
            });
            
            // Validación del formulario de cambio de contraseña
            const passwordForm = document.querySelector('form[action="change_password"]') || 
                               document.querySelector('form input[name="action"][value="change_password"]').closest('form');
            if (passwordForm) {
                passwordForm.addEventListener('submit', function(e) {
                    const currentPasswordInput = this.querySelector('input[name="current_password"]');
                    const newPasswordInput = this.querySelector('input[name="new_password"]');
                    const confirmPasswordInput = this.querySelector('input[name="confirm_password"]');
                    
                    // Verificar espacios al inicio
                    if (currentPasswordInput && currentPasswordInput.value.startsWith(' ')) {
                        e.preventDefault();
                        alert('La contraseña actual no puede empezar con espacios en blanco');
                        currentPasswordInput.focus();
                        return false;
                    }
                    
                    if (newPasswordInput && newPasswordInput.value.startsWith(' ')) {
                        e.preventDefault();
                        alert('La nueva contraseña no puede empezar con espacios en blanco');
                        newPasswordInput.focus();
                        return false;
                    }
                    
                    if (confirmPasswordInput && confirmPasswordInput.value.startsWith(' ')) {
                        e.preventDefault();
                        alert('La confirmación de contraseña no puede empezar con espacios en blanco');
                        confirmPasswordInput.focus();
                        return false;
                    }
                });
            }
        });
    </script>
</body>
</html>     