<?php
require_once 'config/database.php';
startSession();

$error = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if(empty($email) || empty($password)) {
        $error = 'Todos los campos son obligatorios';
    } else {
        $pdo = getConnection();
        $stmt = $pdo->prepare('SELECT * FROM usuario WHERE correo = ? AND esta_activo = 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if($user && $password === $user['hash_contraseña']) {
            $_SESSION['user_id'] = $user['id_usuario'];
            $_SESSION['username'] = $user['nombre_usuario'];
            $_SESSION['role'] = $user['rol'];
            
            // Redirigir según el rol
            if ($user['rol'] === 'administrador') {
                header('Location: admin/index.php');
            } else {
            header('Location: index.php');
            }
            exit;
        } else {
            $error = 'Credenciales incorrectas';
        }
    }
}
?>


<!-- login.html -->
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="style.css">
    <title>Fit 360 - Iniciar Sesión</title>
</head>
<body>
    <header>
        <a href="index.php#home" class="logo">Fit <span>360</span></a>
        <div class='bx bx-menu' id="menu-icon"></div>
        <ul class="navbar">
            <li><a href="index.php#home">Inicio</a></li>
        </ul>
        <div class="top-btn">
            <a href="register.php" class="nav-btn">Registrarse</a>
        </div>
    </header>

    <section class="auth-section">
        <div class="auth-container">
            <div class="auth-content">
                <h2>Iniciar Sesión</h2>
                
                <?php if($error): ?>
                    <div class="error-message"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                
                <form class="auth-form" method="POST" action="">
                    <div class="form-group">
                        <label>Correo Electrónico</label>
                        <div class="input-group">
                            <i class='bx bx-envelope'></i>
                            <input type="email" name="email" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Contraseña</label>
                        <div class="input-group">
                            <i class='bx bx-lock-alt'></i>
                            <input type="password" name="password" id="login-password" required>
                        </div>
                        <label class="show-password-label" style="display:flex;align-items:center;gap:8px;margin-top:8px;font-size:1.3rem;">
                            <input type="checkbox" id="show-login-password"> Mostrar contraseña
                        </label>
                    </div>

                    <button type="submit" class="btn">Acceder</button>
                    
                    <div class="auth-links">
                        <a href="register.php">¿No tienes cuenta? Regístrate</a>
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
    document.getElementById('show-login-password').addEventListener('change', function() {
        const pwd = document.getElementById('login-password');
        pwd.type = this.checked ? 'text' : 'password';
    });

    // Validación en tiempo real para correo y contraseña
    document.addEventListener('DOMContentLoaded', function() {
        const emailInput = document.querySelector('input[name="email"]');
        const passwordInput = document.querySelector('input[name="password"]');

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
                if (input.type === 'password') {
                    // Para contraseña: solo válida si tiene más de 8 caracteres
                    if (input.value.length >= 8 && input.value.trim() !== '') {
                        input.classList.add('valid');
                    } else {
                        input.classList.remove('valid');
                    }
                } else {
                    // Para otros campos (email): validación normal
                    if (input.checkValidity() && input.value.trim() !== '') {
                        input.classList.add('valid');
                    } else {
                        input.classList.remove('valid');
                    }
                }
                input.setCustomValidity('');
            }
        }

        if (emailInput) {
            emailInput.addEventListener('input', function() {
                validateNoLeadingSpace(this);
            });
            emailInput.addEventListener('blur', function() {
                validateNoLeadingSpace(this);
            });
        }
        if (passwordInput) {
            passwordInput.addEventListener('input', function() {
                validateNoLeadingSpace(this);
            });
            passwordInput.addEventListener('blur', function() {
                validateNoLeadingSpace(this);
            });
        }
    });
    </script>
</body>
</html>