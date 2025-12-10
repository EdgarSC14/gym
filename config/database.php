<?php
$host = 'localhost';
$dbname = 'fit360_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;port=3306;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    // No mostrar errores directamente, dejar que los archivos que usan esto manejen los errores
    throw new Exception("Error de conexión a la base de datos: " . $e->getMessage());
}

// Function to start session
function startSession() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
}

// Function to get database connection
function getConnection() {
    global $pdo;
    return $pdo;
}

// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}
?> 