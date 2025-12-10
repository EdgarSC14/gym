<?php
require_once 'config/database.php';
startSession();

$error = '';
$success = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    $firstName = trim($_POST['first_name']);
    $lastName = trim($_POST['last_name']);
    $username = trim($_POST['username']);

    // Validaciones
    if(empty($email) || empty($password) || empty($firstName) || empty($username)) {
        $error = 'Todos los campos obligatorios deben estar completos';
    } elseif($email !== trim($email) || $firstName !== trim($firstName) || $lastName !== trim($lastName) || $username !== trim($username)) {
        $error = 'No se permiten espacios en blanco al principio de los campos';
    } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'El correo electrónico no es válido';
    } elseif(preg_match('/[0-9]/', $firstName)) {
        $error = 'El nombre no puede contener números';
    } elseif(!empty($lastName) && preg_match('/[0-9]/', $lastName)) {
        $error = 'El apellido no puede contener números';
    } elseif(strlen($password) < 8) {
        $error = 'La contraseña debe tener al menos 8 caracteres';
    } elseif($password !== $confirmPassword) {
        $error = 'Las contraseñas no coinciden';
    } else {
        $pdo = getConnection();
        
        // Verificar si el email ya existe
        $stmt = $pdo->prepare('SELECT id_usuario FROM usuario WHERE correo = ?');
        $stmt->execute([$email]);
        
        if($stmt->rowCount() > 0) {
            $error = 'El correo ya está registrado';
        } else {
            // Verificar si el username ya existe
            $stmt = $pdo->prepare('SELECT id_usuario FROM usuario WHERE nombre_usuario = ?');
            $stmt->execute([$username]);
            
            if($stmt->rowCount() > 0) {
                $error = 'El nombre de usuario ya está en uso';
            } else {
                // Almacenar contraseña sin encriptar
                $hashedPassword = $password;
                
                // Insertar usuario
                $stmt = $pdo->prepare('INSERT INTO usuario (nombre_usuario, correo, hash_contraseña, nombre, apellido) VALUES (?, ?, ?, ?, ?)');
                if($stmt->execute([$username, $email, $hashedPassword, $firstName, $lastName])) {
                    $_SESSION['user_id'] = $pdo->lastInsertId();
                    $_SESSION['username'] = $username;
                    $_SESSION['role'] = 'user';
                    $success = 'Usuario registrado exitosamente';
                    header('Location: index.php');
                    exit;
                } else {
                    $error = 'Error al registrar el usuario';
                }
            }
        }
    }
}
?>


<!-- register.html -->
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="style.css">
    <title>Fit 360 - Registro</title>
</head>
<body>
    <header>
        <a href="index.php#home" class="logo">Fit <span>360</span></a>
        <div class='bx bx-menu' id="menu-icon"></div>
        <ul class="navbar">
            <li><a href="index.php#home">Inicio</a></li>
        </ul>
        <div class="top-btn">
            <a href="login.php" class="nav-btn">Iniciar Sesión</a>
        </div>
    </header>

    <section class="auth-section">
        <div class="auth-container">
            <div class="auth-content">
                <h2>Crear Cuenta</h2>
                
                <?php if($error): ?>
                    <div class="error-message"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                
                <?php if($success): ?>
                    <div class="success-message"><?= htmlspecialchars($success) ?></div>
                <?php endif; ?>
                
                <form class="auth-form" method="POST" action="">
                    <div class="form-group">
                        <label>Nombre de Usuario *</label>
                        <div class="input-group">
                            <i class='bx bx-user'></i>
                            <input type="text" name="username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Nombre *</label>
                        <div class="input-group">
                            <i class='bx bx-user'></i>
                            <input type="text" name="first_name" value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Apellido</label>
                        <div class="input-group">
                            <i class='bx bx-user'></i>
                            <input type="text" name="last_name" value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Correo Electrónico *</label>
                        <div class="input-group">
                            <i class='bx bx-envelope'></i>
                            <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Contraseña *</label>
                        <div class="input-group">
                            <i class='bx bx-lock-alt'></i>
                            <input type="password" name="password" id="register-password" required>
                        </div>
                        <div class="password-requirements">
                            Mínimo 8 caracteres
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Confirmar Contraseña *</label>
                        <div class="input-group">
                            <i class='bx bx-lock-alt'></i>
                            <input type="password" name="confirm_password" id="register-confirm-password" required>
                        </div>
                    </div>

                    <label class="show-password-label" style="display:flex;align-items:center;gap:8px;margin-top:8px;font-size:1.3rem;">
                        <input type="checkbox" id="show-register-password"> Mostrar contraseña
                    </label>

                    <button type="submit" class="btn">Registrarse</button>
                    
                    <div class="auth-links">
                        <a href="login.php">¿Ya tienes cuenta? Inicia Sesión</a>
                    </div>
                </form>
            </div>
        </div>
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
    document.getElementById('show-register-password').addEventListener('change', function() {
        const pwd = document.getElementById('register-password');
        const cpwd = document.getElementById('register-confirm-password');
        const type = this.checked ? 'text' : 'password';
        pwd.type = type;
        cpwd.type = type;
    });

    // Validación en tiempo real para todos los campos
    document.addEventListener('DOMContentLoaded', function() {
        const inputs = document.querySelectorAll('.auth-form input[type="text"], .auth-form input[type="email"], .auth-form input[type="password"]');

        function validateNoLeadingSpace(input) {
            // Limpiar espacios al principio automáticamente
            input.value = input.value.replace(/^\s+/, '');
            // Validar visualmente
            if (/^\s/.test(input.value)) {
                input.classList.add('invalid');
                input.classList.remove('valid');
                input.setCustomValidity('No se permiten espacios en blanco al principio');
            } else {
                input.classList.remove('invalid');
                // Verificar si el input es válido según su tipo
                if (input.checkValidity() && input.value.trim() !== '') {
                    input.classList.add('valid');
                } else {
                    input.classList.remove('valid');
                }
                input.setCustomValidity('');
            }
        }

        inputs.forEach(input => {
            input.addEventListener('input', function() {
                validateNoLeadingSpace(this);
            });
            input.addEventListener('blur', function() {
                validateNoLeadingSpace(this);
            });
        });
    });
    </script>

</body>
</html>