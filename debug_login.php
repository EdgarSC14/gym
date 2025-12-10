<?php
require_once 'config/database.php';
startSession();

echo "<h2>Debug de Login</h2>";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    echo "<p>Email ingresado: " . htmlspecialchars($email) . "</p>";
    echo "<p>Contraseña ingresada: " . htmlspecialchars($password) . "</p>";
    
    $pdo = getConnection();
    
    // Verificar si el usuario existe
    $stmt = $pdo->prepare('SELECT * FROM usuario WHERE correo = ? AND esta_activo = 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user) {
        echo "<p>✅ Usuario encontrado en la base de datos</p>";
        echo "<p>ID: " . $user['id_usuario'] . "</p>";
        echo "<p>Nombre: " . $user['nombre'] . " " . $user['apellido'] . "</p>";
        echo "<p>Rol: " . $user['rol'] . "</p>";
        echo "<p>Hash de contraseña en BD: " . $user['hash_contraseña'] . "</p>";
        echo "<p>Contraseña ingresada: " . $password . "</p>";
        
        if ($password === $user['hash_contraseña']) {
            echo "<p>✅ Contraseña correcta</p>";
            
            $_SESSION['user_id'] = $user['id_usuario'];
            $_SESSION['username'] = $user['nombre_usuario'];
            $_SESSION['role'] = $user['rol'];
            
            echo "<p>✅ Sesión iniciada correctamente</p>";
            echo "<p>user_id: " . $_SESSION['user_id'] . "</p>";
            echo "<p>username: " . $_SESSION['username'] . "</p>";
            echo "<p>role: " . $_SESSION['role'] . "</p>";
            
            if ($user['rol'] === 'administrador') {
                echo "<p>Redirigiendo a admin...</p>";
                header('Location: admin/index.php');
                exit;
            } else {
                echo "<p>Redirigiendo a index...</p>";
                header('Location: index.php');
                exit;
            }
        } else {
            echo "<p>❌ Contraseña incorrecta</p>";
        }
    } else {
        echo "<p>❌ Usuario no encontrado</p>";
        
        // Mostrar todos los usuarios para debug
        $all_users = $pdo->query('SELECT id_usuario, nombre_usuario, correo, rol FROM usuario WHERE esta_activo = 1')->fetchAll();
        echo "<h3>Usuarios disponibles:</h3>";
        echo "<ul>";
        foreach ($all_users as $u) {
            echo "<li>ID: " . $u['id_usuario'] . " - Usuario: " . $u['nombre_usuario'] . " - Email: " . $u['correo'] . " - Rol: " . $u['rol'] . "</li>";
        }
        echo "</ul>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Debug Login</title>
</head>
<body>
    <form method="POST">
        <h3>Prueba de Login</h3>
        <p>
            <label>Email:</label>
            <input type="email" name="email" required>
        </p>
        <p>
            <label>Contraseña:</label>
            <input type="password" name="password" required>
        </p>
        <button type="submit">Probar Login</button>
    </form>
    
    <p><a href="login.php">Volver al login normal</a></p>
</body>
</html> 