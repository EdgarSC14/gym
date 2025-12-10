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

// Obtener el ID del plan desde la URL
$planId = $_GET['plan'] ?? null;

if (!$planId) {
    header('Location: index.php#plans');
    exit;
}

// Obtener datos del plan desde la base de datos
$pdo = getConnection();
$stmt = $pdo->prepare("SELECT * FROM plan_suscripcion WHERE id_plan = ? AND esta_activo = 1");
$stmt->execute([$planId]);
$plan = $stmt->fetch();

if (!$plan) {
    header('Location: index.php#plans');
    exit;
}

// Obtener métodos de pago del usuario
$user_payment_methods = [];
$stmt = $pdo->prepare("
    SELECT id_metodo_pago, tipo_tarjeta, numero_tarjeta, fecha_vencimiento, nombre_titular, es_predeterminado 
    FROM metodo_pago_usuario 
    WHERE id_usuario = ? AND esta_activo = 1 
    ORDER BY es_predeterminado DESC, fecha_creacion DESC
");
$stmt->execute([$_SESSION['user_id']]);
$user_payment_methods = $stmt->fetchAll();

// Si no tiene métodos de pago, redirigir al perfil para agregar uno
if (empty($user_payment_methods)) {
    $_SESSION['error_message'] = 'Debes agregar al menos un método de pago para suscribirte.';
    header('Location: profile.php');
    exit;
}

// Verificar si el usuario ya tiene una suscripción activa
$stmt = $pdo->prepare("
    SELECT us.id_suscripcion, us.id_plan, us.fecha_inicio, us.fecha_fin, us.estado,
           sp.nombre as plan_name, sp.precio as plan_precio
    FROM suscripcion_usuario us
    JOIN plan_suscripcion sp ON us.id_plan = sp.id_plan
    WHERE us.id_usuario = ? AND us.estado = 'activa'
    ORDER BY us.fecha_creacion DESC
    LIMIT 1
");
$stmt->execute([$_SESSION['user_id']]);
$active_subscription = $stmt->fetch();

// Si el usuario ya tiene una suscripción activa, mostrar mensaje y redirigir
if ($active_subscription) {
    $error = 'Ya tienes una suscripción activa: ' . htmlspecialchars($active_subscription['plan_name']) . 
             '. Debes cancelar tu suscripción actual antes de suscribirte a un nuevo plan.';
}

// Procesar el formulario de suscripción
$error = '';
$success = '';

// Función para validar tarjetas reales usando algoritmo de Luhn
function validateLuhn($cardNumber) {
    $cardNumber = preg_replace('/\s/', '', $cardNumber);
    $sum = 0;
    $length = strlen($cardNumber);
    $parity = $length % 2;
    
    for ($i = 0; $i < $length; $i++) {
        $digit = $cardNumber[$i];
        if ($i % 2 == $parity) {
            $digit *= 2;
            if ($digit > 9) {
                $digit -= 9;
            }
        }
        $sum += $digit;
    }
    
    return ($sum % 10) == 0;
}

// Función para validar tipo de tarjeta
function getCardType($cardNumber) {
    $cardNumber = preg_replace('/\s/', '', $cardNumber);
    
    // Patrones para diferentes tipos de tarjeta con longitudes específicas
    $patterns = [
        'visa' => '/^4[0-9]{15}$/', // Exactamente 16 dígitos
        'mastercard' => '/^5[1-5][0-9]{14}$/', // Exactamente 16 dígitos
        'amex' => '/^3[47][0-9]{13}$/', // Exactamente 15 dígitos
        'discover' => '/^6(?:011|5[0-9]{2})[0-9]{12}$/', // Exactamente 16 dígitos
        'diners' => '/^3(?:0[0-5]|[68][0-9])[0-9]{11}$/', // Exactamente 14 dígitos
        'jcb' => '/^(?:2131|1800|35\d{3})\d{11}$/' // Exactamente 16 dígitos
    ];
    
    foreach ($patterns as $type => $pattern) {
        if (preg_match($pattern, $cardNumber)) {
            return $type;
        }
    }
    
    return 'unknown';
}

// Función para validar CVV según tipo de tarjeta
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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Verificar nuevamente si el usuario tiene una suscripción activa
        $stmt = $pdo->prepare("SELECT id_suscripcion FROM suscripcion_usuario WHERE id_usuario = ? AND estado = 'activa'");
        $stmt->execute([$_SESSION['user_id']]);
        if ($stmt->fetch()) {
            throw new Exception('Ya tienes una suscripción activa. Debes cancelarla antes de suscribirte a un nuevo plan.');
        }
        
        // Determinar el método de pago utilizado
        $paymentOption = $_POST['payment_option'] ?? 'new';
        $paymentMethod = 'tarjeta';
        
        if ($paymentOption === 'existing') {
            // Usar método de pago existente
            $selectedPaymentMethodId = $_POST['selected_payment_method'] ?? null;
            $existingCvv = $_POST['existing_cvv'] ?? '';
            
            if (!$selectedPaymentMethodId) {
                throw new Exception('Debes seleccionar un método de pago');
            }
            
            if (!$existingCvv || strlen($existingCvv) < 3 || strlen($existingCvv) > 4) {
                throw new Exception('CVV inválido');
            }
            
            // Verificar que el método de pago pertenece al usuario
            $stmt = $pdo->prepare("SELECT id_metodo_pago FROM metodo_pago_usuario WHERE id_metodo_pago = ? AND id_usuario = ? AND esta_activo = 1");
            $stmt->execute([$selectedPaymentMethodId, $_SESSION['user_id']]);
            if (!$stmt->fetch()) {
                throw new Exception('Método de pago inválido');
            }
            
            $paymentMethod = 'método existente';
        } else {
            // Validar nuevo método de pago
            $cardName = $_POST['card_name'] ?? '';
            $cardNumber = $_POST['card_number'] ?? '';
            $expiry = $_POST['expiry'] ?? '';
            $cvc = $_POST['cvc'] ?? '';
            
            if (strlen($cardName) < 3) {
                throw new Exception('Nombre en la tarjeta inválido');
            }
            
            $cardNumber = preg_replace('/\s/', '', $cardNumber);
            
            // Obtener tipo de tarjeta primero
            $cardType = getCardType($cardNumber);
            if ($cardType === 'unknown') {
                throw new Exception('Tipo de tarjeta no reconocido');
            }
            
            // Validar longitud según tipo de tarjeta
            $cardLength = strlen($cardNumber);
            $expectedLength = 0;
            
            switch ($cardType) {
                case 'amex':
                    $expectedLength = 15;
                    break;
                case 'diners':
                    $expectedLength = 14;
                    break;
                default: // visa, mastercard, discover, jcb
                    $expectedLength = 16;
                    break;
            }
            
            if ($cardLength !== $expectedLength) {
                throw new Exception("Número de tarjeta inválido. Las tarjetas $cardType requieren exactamente $expectedLength dígitos");
            }
            
            // Validar tarjeta usando algoritmo de Luhn
            if (!validateLuhn($cardNumber)) {
                throw new Exception('Número de tarjeta inválido');
            }
            
            if (!preg_match('/^\d{2}\/\d{2}$/', $expiry)) {
                throw new Exception('Fecha de vencimiento inválida');
            }
            
            // Validar CVV según tipo de tarjeta
            if (!validateCVV($cvc, $cardType)) {
                $expectedLength = ($cardType === 'amex') ? 4 : 3;
                throw new Exception("CVV inválido. Las tarjetas $cardType requieren $expectedLength dígitos");
            }
            
            $paymentMethod = 'nueva tarjeta';
        }
        
        // Crear la suscripción del usuario
        $startDate = date('Y-m-d');
        $endDate = date('Y-m-d', strtotime('+30 days'));
        
        $stmt = $pdo->prepare("INSERT INTO suscripcion_usuario (id_usuario, id_plan, fecha_inicio, fecha_fin, estado) VALUES (?, ?, ?, ?, 'activa')");
        $stmt->execute([$_SESSION['user_id'], $planId, $startDate, $endDate]);
        
        $subscriptionId = $pdo->lastInsertId();
        
        // Registrar el pago
        $stmt = $pdo->prepare("INSERT INTO pago (id_suscripcion, monto, metodo_pago, estado) VALUES (?, ?, ?, 'completado')");
        $stmt->execute([$subscriptionId, $plan['precio'], $paymentMethod]);
        
        $success = 'Suscripción activada exitosamente';
        
        // Redirigir después de un momento
        header("Refresh: 2; URL=index.php");
        
    } catch (Exception $e) {
        $error = $e->getMessage();
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
    <title>Fit 360 - Suscripción <?= htmlspecialchars($plan['nombre']) ?></title>
</head>
<body>
    <header>
        <a href="index.php#home" class="logo">Fit <span>360</span></a>
        <div class='bx bx-menu' id="menu-icon"></div>
        <ul class="navbar">
            <li><a href="index.php#home">Inicio</a></li>
            <li><a href="index.php#plans">Suscripciones</a></li>
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
        <?php if($error): ?>
            <div class="error-message">
                <?= htmlspecialchars($error) ?>
                <?php if($active_subscription): ?>
                    <div style="margin-top: 15px;">
                        <h4>Tu Suscripción Actual:</h4>
                        <div class="current-subscription-info">
                            <p><strong>Plan:</strong> <?= htmlspecialchars($active_subscription['plan_name']) ?></p>
                            <p><strong>Precio:</strong> $<?= number_format($active_subscription['plan_precio'], 2) ?> MXN/mes</p>
                            <p><strong>Fecha de inicio:</strong> <?= date('d/m/Y', strtotime($active_subscription['fecha_inicio'])) ?></p>
                            <p><strong>Fecha de vencimiento:</strong> <?= date('d/m/Y', strtotime($active_subscription['fecha_fin'])) ?></p>
                        </div>
                        <div style="margin-top: 20px;">
                            <a href="profile.php" class="btn">Ir a Mi Perfil para Cancelar</a>
                            <a href="index.php#plans" class="btn btn-secondary" style="margin-left: 10px;">Ver Otros Planes</a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <?php if($success): ?>
            <div class="success-message"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <div class="checkout-container">
            <div class="subscription-details">
                <h2>Suscripción <?= htmlspecialchars($plan['nombre']) ?></h2>
                
                <div class="price-container">
                    <span class="price">$<?= number_format($plan['precio'], 2) ?> MXN</span>
                    <span class="period">/mes</span>
                </div>
                
                <ul class="benefits-list">
                    <?php 
                    $benefits = explode("\n", $plan['beneficios']);
                    foreach($benefits as $benefit): 
                        if(trim($benefit)): ?>
                        <li><i class='bx bx-check'></i> <?= htmlspecialchars(trim($benefit)) ?></li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ul>
                
                <div class="subscription-info">
                    <h3>Detalles de la Suscripción</h3>
                    <p><?= htmlspecialchars($plan['descripcion']) ?></p>
                    <p>Renovación automática mensual. Puedes cancelar en cualquier momento sin penalización. Acceso inmediato a todos los beneficios <?= htmlspecialchars($plan['nombre']) ?>.</p>
                </div>
            </div>

            <div class="order-summary">
                <h3>Resumen del Pedido</h3>
                <div class="summary-details">
                    <div class="summary-item">
                        <span>Suscripción <?= htmlspecialchars($plan['nombre']) ?></span>
                        <span>$<?= number_format($plan['precio'], 2) ?> MXN</span>
                    </div>
                    <div class="summary-item total">
                        <span>Total</span>
                        <span>$<?= number_format($plan['precio'], 2) ?> MXN</span>
                    </div>
                </div>

                <?php if($active_subscription): ?>
                    <!-- Mensaje cuando ya tiene suscripción activa -->
                    <div class="subscription-warning" style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 20px; border-radius: 8px; margin: 20px 0;">
                        <div style="display: flex; align-items: center; margin-bottom: 15px;">
                            <i class='bx bx-info-circle' style="color: #856404; font-size: 24px; margin-right: 10px;"></i>
                            <h4 style="color: #856404; margin: 0;">Ya tienes una suscripción activa</h4>
                        </div>
                        <p style="color: #856404; margin-bottom: 15px;">
                            Para suscribirte a este plan, primero debes cancelar tu suscripción actual: 
                            <strong><?= htmlspecialchars($active_subscription['plan_name']) ?></strong>
                        </p>
                        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                            <a href="profile.php" class="btn" style="background: #dc3545; border-color: #dc3545;">
                                <i class='bx bx-user'></i> Ir a Mi Perfil para Cancelar
                            </a>
                            <a href="index.php#plans" class="btn btn-secondary">
                                <i class='bx bx-list-ul'></i> Ver Otros Planes
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Formulario de pago cuando no tiene suscripción activa -->
                    <form class="payment-form" method="POST" action="">
                        <!-- Opción de método de pago existente -->
                        <div class="form-group">
                            <label>Método de Pago</label>
                            <div class="payment-method-selector">
                                <!-- Solo dejar la opción de método existente -->
                                <label class="radio-option">
                                    <input type="radio" name="payment_option" value="existing" checked style="display:none;">
                                    <span>Usar método existente</span>
                                </label>
                            </div>
                            <div id="existing-payment-methods" class="existing-methods">
                                <select id="selected-payment-method" name="selected_payment_method" required>
                                    <?php foreach($user_payment_methods as $method): ?>
                                        <option value="<?= $method['id_metodo_pago'] ?>" <?= $method['es_predeterminado'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($method['tipo_tarjeta']) ?> - ****<?= substr(preg_replace('/\s/', '', $method['numero_tarjeta']), -4) ?><?= $method['es_predeterminado'] ? ' (Predeterminado)' : '' ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <!-- Campo CVV para método existente -->
                                <div class="form-group" style="margin-top: 15px;">
                                    <label for="existing-cvv">CVV</label>
                                    <div class="input-group">
                                        <i class='bx bx-lock'></i>
                                        <input type="text" id="existing-cvv" name="existing_cvv" placeholder="123" maxlength="4" required>
                                    </div>
                                    <small style="color: #666; font-size: 12px;">Ingresa el código de seguridad de tu tarjeta</small>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn">Activar Suscripción <?= htmlspecialchars($plan['nombre']) ?></button>
                        
                        <div class="security-info">
                            <i class='bx bx-shield-check'></i>
                            <span>Pago seguro con encriptación SSL</span>
                        </div>
                    </form>
                <?php endif; ?>
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

    <script>
        // Función para validar tarjetas usando algoritmo de Luhn
        function validateLuhn(cardNumber) {
            cardNumber = cardNumber.replace(/\s/g, '');
            let sum = 0;
            let length = cardNumber.length;
            let parity = length % 2;
            
            for (let i = 0; i < length; i++) {
                let digit = parseInt(cardNumber[i]);
                if (i % 2 == parity) {
                    digit *= 2;
                    if (digit > 9) {
                        digit -= 9;
                    }
                }
                sum += digit;
            }
            
            return (sum % 10) == 0;
        }

        // Función para obtener tipo de tarjeta
        function getCardType(cardNumber) {
            cardNumber = cardNumber.replace(/\s/g, '');
            
            const patterns = {
                'visa': '/^4[0-9]{15}$/', // Exactamente 16 dígitos
                'mastercard': '/^5[1-5][0-9]{14}$/', // Exactamente 16 dígitos
                'amex': '/^3[47][0-9]{13}$/', // Exactamente 15 dígitos
                'discover': '/^6(?:011|5[0-9]{2})[0-9]{12}$/', // Exactamente 16 dígitos
                'diners': '/^3(?:0[0-5]|[68][0-9])[0-9]{11}$/', // Exactamente 14 dígitos
                'jcb': '/^(?:2131|1800|35\d{3})\d{11}$/' // Exactamente 16 dígitos
            };
            
            for (let type in patterns) {
                if (patterns[type].test(cardNumber)) {
                    return type;
                }
            }
            
            return 'unknown';
        }

        // Función para validar CVV según tipo de tarjeta
        function validateCVV(cvv, cardType) {
            const cvvLength = cvv.length;
            
            switch (cardType) {
                case 'amex':
                    return cvvLength === 4;
                case 'visa':
                case 'mastercard':
                case 'discover':
                case 'diners':
                case 'jcb':
                    return cvvLength === 3;
                default:
                    return cvvLength >= 3 && cvvLength <= 4;
            }
        }

        // Card type detection and formatting functions (from profile.php)
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

        // Toggle entre método existente y nuevo
        const paymentOptions = document.querySelectorAll('input[name="payment_option"]');
        const existingMethods = document.getElementById('existing-payment-methods');
        const newPaymentForm = document.getElementById('new-payment-form');
        
        if (paymentOptions.length > 0) {
            paymentOptions.forEach(option => {
                option.addEventListener('change', function() {
                    if (this.value === 'existing') {
                        existingMethods.style.display = 'block';
                        newPaymentForm.style.display = 'none';
                        // Remover required de campos del nuevo método y deshabilitarlos
                        document.querySelectorAll('#new-payment-form input').forEach(input => {
                            input.removeAttribute('required');
                            input.disabled = true;
                        });
                        // Habilitar campo CVV existente
                        const existingCvv = document.getElementById('existing-cvv');
                        if (existingCvv) {
                            existingCvv.required = true;
                            existingCvv.disabled = false;
                        }
                    } else {
                        existingMethods.style.display = 'none';
                        newPaymentForm.style.display = 'block';
                        // Habilitar campos del nuevo método y agregar required
                        document.querySelectorAll('#new-payment-form input').forEach(input => {
                            input.required = true;
                            input.disabled = false;
                        });
                        // Deshabilitar campo CVV existente
                        const existingCvv = document.getElementById('existing-cvv');
                        if (existingCvv) {
                            existingCvv.removeAttribute('required');
                            existingCvv.disabled = true;
                        }
                    }
                });
            });
            
            // Configurar estado inicial
            const initialOption = document.querySelector('input[name="payment_option"]:checked');
            if (initialOption) {
                if (initialOption.value === 'existing') {
                    // Configurar estado inicial para método existente
                    document.querySelectorAll('#new-payment-form input').forEach(input => {
                        input.removeAttribute('required');
                        input.disabled = true;
                    });
                    const existingCvv = document.getElementById('existing-cvv');
                    if (existingCvv) {
                        existingCvv.required = true;
                        existingCvv.disabled = false;
                    }
                } else {
                    // Configurar estado inicial para nuevo método
                    const existingCvv = document.getElementById('existing-cvv');
                    if (existingCvv) {
                        existingCvv.removeAttribute('required');
                        existingCvv.disabled = true;
                    }
                }
            }
        }
        
        // Formateo del CVV para método existente
        const existingCvv = document.getElementById('existing-cvv');
        if (existingCvv) {
            existingCvv.addEventListener('input', function() {
                this.value = this.value.replace(/\D/g, '');
            });
        }

        // Formateo del número de tarjeta
        const cardNumber = document.getElementById('card-number');
        if (cardNumber) {
            cardNumber.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\s/g, '').replace(/\D/g, '');
                let formattedValue = '';
                for (let i = 0; i < value.length; i++) {
                    if (i > 0 && i % 4 === 0) {
                        formattedValue += ' ';
                    }
                    formattedValue += value[i];
                }
                e.target.value = formattedValue;
                
                // Validar número de tarjeta usando las funciones de profile.php
                const cardNumberValue = value;
                const isValidCard = validateCardNumber(cardNumberValue);
                const cardType = detectCardType(cardNumberValue);
                
                // Mostrar indicador de tipo de tarjeta
                let cardTypeIndicator = document.getElementById('card-type-indicator');
                if (!cardTypeIndicator) {
                    cardTypeIndicator = document.createElement('div');
                    cardTypeIndicator.id = 'card-type-indicator';
                    cardTypeIndicator.className = 'card-type-indicator';
                    e.target.parentNode.appendChild(cardTypeIndicator);
                }
                
                if (cardNumberValue.length > 0) {
                    cardTypeIndicator.textContent = cardType;
                    cardTypeIndicator.className = 'card-type-indicator ' + cardType.toLowerCase();
                    
                    if (cardNumberValue.length >= 13 && cardNumberValue.length <= 19) {
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
                
                // Actualizar placeholder del CVV según tipo de tarjeta
                const cardCvv = document.getElementById('card-cvv');
                if (cardCvv && value.length >= 13) {
                    cardCvv.placeholder = (cardType === 'amex') ? '1234' : '123';
                }
            });
            
            // Prevenir entrada de caracteres no numéricos
            cardNumber.addEventListener('keydown', function(e) {
                // Permitir teclas de navegación y edición
                const allowedKeys = [
                    'Backspace', 'Delete', 'Tab', 'Escape', 'Enter',
                    'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown',
                    'Home', 'End', 'PageUp', 'PageDown'
                ];
                
                if (allowedKeys.includes(e.key)) {
                    return;
                }
                
                // Permitir solo números
                if (!/^\d$/.test(e.key)) {
                    e.preventDefault();
                }
            });
        }

        // Formateo de fecha de expiración
        const cardExpiry = document.getElementById('card-expiry');
        if (cardExpiry) {
            cardExpiry.addEventListener('input', function(e) {
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
        }

        // Solo números para CVV y limitar longitud según tipo de tarjeta
        const cardCvv = document.getElementById('card-cvv');
        if (cardCvv) {
            cardCvv.addEventListener('input', function(e) {
                e.target.value = e.target.value.replace(/\D/g, '');
                
                // Obtener tipo de tarjeta para validar CVV
                const cardNumberValue = document.getElementById('card-number').value.replace(/\s/g, '');
                const cardType = detectCardType(cardNumberValue);
                let maxLength = (cardType === 'amex') ? 4 : 3;
                e.target.maxLength = maxLength;
                e.target.placeholder = (cardType === 'amex') ? '1234' : '123';
                
                // Limitar longitud
                if (e.target.value.length > maxLength) {
                    e.target.value = e.target.value.substring(0, maxLength);
                }
                
                // Validación visual
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
            // Prevenir pegado de más dígitos
            cardCvv.addEventListener('paste', function(e) {
                e.preventDefault();
                const pastedText = (e.clipboardData || window.clipboardData).getData('text');
                const cardNumberValue = document.getElementById('card-number').value.replace(/\s/g, '');
                const cardType = detectCardType(cardNumberValue);
                let maxLength = (cardType === 'amex') ? 4 : 3;
                const cleanValue = pastedText.replace(/\D/g, '').substring(0, maxLength);
                cardCvv.value = cleanValue;
            });
        }

        // Validación del formulario
        document.querySelector('.payment-form').addEventListener('submit', function(e) {
            const paymentOption = document.querySelector('input[name="payment_option"]:checked');
            
            if (paymentOption && paymentOption.value === 'existing') {
                // Validar CVV para método existente
                const existingCvv = document.getElementById('existing-cvv').value;
                if (!existingCvv || existingCvv.length < 3 || existingCvv.length > 4) {
                    e.preventDefault();
                    alert('Por favor ingresa un CVV válido (3-4 dígitos)');
                    return;
                }
                
                if (!/^\d{3,4}$/.test(existingCvv)) {
                    e.preventDefault();
                    alert('El CVV debe contener solo números');
                    return;
                }
            } else {
                // Validar nuevo método de pago
                const cardNumber = document.getElementById('card-number').value.replace(/\s/g, '');
                const cardExpiry = document.getElementById('card-expiry').value;
                const cardCvv = document.getElementById('card-cvv').value;
                const cardName = document.getElementById('card-name').value.trim();
                
                // Validar nombre
                if (cardName.length < 3) {
                    e.preventDefault();
                    alert('Por favor ingresa un nombre válido');
                    return;
                }
                
                // Validar número de tarjeta usando las funciones de profile.php
                if (!validateCardNumber(cardNumber)) {
                    e.preventDefault();
                    alert('Por favor ingresa un número de tarjeta válido');
                    return;
                }
                
                // Validar fecha de vencimiento
                if (!validateExpiryDate(cardExpiry)) {
                    e.preventDefault();
                    alert('Por favor ingresa una fecha de vencimiento válida (MM/YY)');
                    return;
                }
                
                // Obtener tipo de tarjeta y validar CVV
                const cardType = detectCardType(cardNumber);
                if (!validateCVV(cardCvv, cardType)) {
                    const expectedLength = (cardType === 'amex') ? 4 : 3;
                    e.preventDefault();
                    alert(`CVV inválido. Las tarjetas ${cardType} requieren ${expectedLength} dígitos`);
                    return;
                }
            }
        });
    </script>
</body>
</html>