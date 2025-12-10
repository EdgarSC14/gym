<?php
require_once 'config/database.php';

echo "<h1>Verificación de Base de Datos</h1>";

try {
    $pdo = getConnection();
    echo "<p style='color: green;'>✅ Conexión a la base de datos exitosa</p>";
    
    // Verificar si existe la tabla users
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color: green;'>✅ Tabla 'users' existe</p>";
    } else {
        echo "<p style='color: red;'>❌ Tabla 'users' NO existe</p>";
        exit;
    }
    
    // Verificar estructura de la tabla users
    echo "<h2>Estructura de la tabla 'users':</h2>";
    $stmt = $pdo->query("DESCRIBE users");
    $columns = $stmt->fetchAll();
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Llave</th><th>Default</th><th>Extra</th></tr>";
    
    $hasAddress = false;
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . $column['Field'] . "</td>";
        echo "<td>" . $column['Type'] . "</td>";
        echo "<td>" . $column['Null'] . "</td>";
        echo "<td>" . $column['Key'] . "</td>";
        echo "<td>" . $column['Default'] . "</td>";
        echo "<td>" . $column['Extra'] . "</td>";
        echo "</tr>";
        
        if ($column['Field'] === 'address') {
            $hasAddress = true;
        }
    }
    echo "</table>";
    
    // Verificar específicamente el campo address
    echo "<h2>Verificación del campo 'address':</h2>";
    if ($hasAddress) {
        echo "<p style='color: green;'>✅ Campo 'address' existe en la tabla 'users'</p>";
    } else {
        echo "<p style='color: red;'>❌ Campo 'address' NO existe en la tabla 'users'</p>";
        echo "<p>Necesitas ejecutar el siguiente SQL:</p>";
        echo "<pre style='background: #f4f4f4; padding: 10px; border-radius: 5px;'>";
        echo "ALTER TABLE `users` ADD COLUMN `address` TEXT DEFAULT NULL AFTER `phone`;";
        echo "</pre>";
        
        // Intentar agregar el campo automáticamente
        echo "<h3>Intentando agregar el campo automáticamente...</h3>";
        try {
            $pdo->exec("ALTER TABLE `users` ADD COLUMN `address` TEXT DEFAULT NULL AFTER `phone`");
            echo "<p style='color: green;'>✅ Campo 'address' agregado exitosamente</p>";
            
            // Verificar nuevamente
            $stmt = $pdo->query("DESCRIBE users");
            $columns = $stmt->fetchAll();
            $hasAddress = false;
            foreach ($columns as $column) {
                if ($column['Field'] === 'address') {
                    $hasAddress = true;
                    break;
                }
            }
            
            if ($hasAddress) {
                echo "<p style='color: green;'>✅ Verificación: Campo 'address' ahora existe</p>";
            } else {
                echo "<p style='color: red;'>❌ Error: Campo 'address' no se agregó correctamente</p>";
            }
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Error al agregar el campo: " . $e->getMessage() . "</p>";
        }
    }
    
    // Mostrar algunos datos de ejemplo
    echo "<h2>Datos de ejemplo en la tabla 'users':</h2>";
    $stmt = $pdo->query("SELECT id_usuario, nombre_usuario, correo, nombre, apellido, telefono, direccion FROM usuario LIMIT 5");
    $users = $stmt->fetchAll();
    
    if (!empty($users)) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>Nombre</th><th>Apellido</th><th>Teléfono</th><th>Dirección</th></tr>";
        
        foreach ($users as $user) {
            echo "<tr>";
            echo "<td>" . $user['id_usuario'] . "</td>";
            echo "<td>" . htmlspecialchars($user['nombre_usuario']) . "</td>";
            echo "<td>" . htmlspecialchars($user['correo']) . "</td>";
            echo "<td>" . htmlspecialchars($user['nombre']) . "</td>";
            echo "<td>" . htmlspecialchars($user['apellido']) . "</td>";
            echo "<td>" . htmlspecialchars($user['telefono'] ?? 'NULL') . "</td>";
            echo "<td>" . htmlspecialchars($user['direccion'] ?? 'NULL') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No hay usuarios en la tabla</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error de conexión: " . $e->getMessage() . "</p>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
table { margin: 10px 0; }
th { background: #f0f0f0; padding: 8px; }
td { padding: 8px; }
pre { font-family: monospace; }
</style> 