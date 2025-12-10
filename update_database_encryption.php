<?php
require_once 'config/database.php';

echo "Actualizando base de datos para remover encriptación...\n";

try {
    $pdo = getConnection();
    
    // Limpiar datos encriptados de métodos de pago
    echo "Limpiando métodos de pago encriptados...\n";
    $stmt = $pdo->prepare("UPDATE metodo_pago_usuario SET numero_tarjeta = '', cvv = '' WHERE numero_tarjeta LIKE '$2y$%'");
    $stmt->execute();
    echo "Métodos de pago actualizados.\n";
    
    // Limpiar contraseñas encriptadas de usuarios (mantener solo las básicas)
    echo "Limpiando contraseñas encriptadas...\n";
    $stmt = $pdo->prepare("UPDATE usuario SET hash_contraseña = 'password123' WHERE hash_contraseña LIKE '$2y$%'");
    $stmt->execute();
    echo "Contraseñas actualizadas.\n";
    
    echo "Base de datos actualizada exitosamente.\n";
    echo "Nota: Los métodos de pago existentes han sido limpiados y las contraseñas se han establecido como 'password123'.\n";
    echo "Los usuarios deberán cambiar sus contraseñas al iniciar sesión.\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?> 