<?php
session_start();
require_once '../config/database.php';

// Verificar si el usuario está logueado y es admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'administrador') {
    header('Location: ../login.php');
    exit();
}

$pdo = getConnection();
$message = '';
$error = '';

// Procesar formularios
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $username = trim($_POST['username']);
                $email = trim($_POST['email']);
                $password = $_POST['password'];
                $first_name = trim($_POST['first_name']);
                $last_name = trim($_POST['last_name']);
                $phone = trim($_POST['phone']);
                $address = trim($_POST['address']);
                $role = $_POST['role'];
                
                // Validaciones personalizadas
                if (preg_match('/\d/', $first_name)) {
                    $error = 'El nombre no puede contener números';
                    break;
                }
                if (!empty($last_name) && preg_match('/\d/', $last_name)) {
                    $error = 'El apellido no puede contener números';
                    break;
                }
                if (!empty($phone) && preg_match('/[^\d\s\-\+]/', $phone)) {
                    $error = 'El teléfono no puede contener letras';
                    break;
                }
                if (strlen($password) < 8) {
                    $error = 'La contraseña debe tener al menos 8 caracteres';
                    break;
                }
                
                // Verificar si el usuario ya existe
                $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM usuario WHERE correo = ? OR nombre_usuario = ?");
                $stmt->execute([$email, $username]);
                if ($stmt->fetch()['count'] > 0) {
                    $error = 'El usuario o email ya existe';
                } else {
                    $password_hash = $password;
                    try {
                        $stmt = $pdo->prepare("INSERT INTO usuario (nombre_usuario, correo, hash_contraseña, nombre, apellido, telefono, direccion, rol) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                        $stmt->execute([$username, $email, $password_hash, $first_name, $last_name, $phone, $address, $role]);
                        $message = 'Usuario agregado exitosamente';
                    } catch (Exception $e) {
                        $error = 'Error al agregar el usuario: ' . $e->getMessage();
                    }
                }
                break;
                
            case 'edit':
                $user_id = intval($_POST['user_id']);
                $username = trim($_POST['username']);
                $email = trim($_POST['email']);
                $first_name = trim($_POST['first_name']);
                $last_name = trim($_POST['last_name']);
                $phone = trim($_POST['phone']);
                $address = trim($_POST['address']);
                $role = $_POST['role'];
                
                // Validaciones personalizadas
                if (preg_match('/\d/', $first_name)) {
                    $error = 'El nombre no puede contener números';
                    break;
                }
                if (!empty($last_name) && preg_match('/\d/', $last_name)) {
                    $error = 'El apellido no puede contener números';
                    break;
                }
                if (!empty($phone) && preg_match('/[^\d\s\-\+]/', $phone)) {
                    $error = 'El teléfono no puede contener letras';
                    break;
                }
                // Validar contraseña solo si se proporciona una nueva
                if (!empty($_POST['password']) && strlen($_POST['password']) < 8) {
                    $error = 'La contraseña debe tener al menos 8 caracteres';
                    break;
                }
                
                // Verificar si el email o username ya existe en otro usuario
                $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM usuario WHERE (correo = ? OR nombre_usuario = ?) AND id_usuario != ?");
                $stmt->execute([$email, $username, $user_id]);
                if ($stmt->fetch()['count'] > 0) {
                    $error = 'El usuario o email ya existe';
                } else {
                    try {
                        $stmt = $pdo->prepare("UPDATE usuario SET nombre_usuario = ?, correo = ?, nombre = ?, apellido = ?, telefono = ?, direccion = ?, rol = ? WHERE id_usuario = ?");
                        $stmt->execute([$username, $email, $first_name, $last_name, $phone, $address, $role, $user_id]);
                        
                        // Actualizar contraseña si se proporcionó una nueva
                        if (!empty($_POST['password'])) {
                            $password_hash = $_POST['password'];
                            $stmt = $pdo->prepare("UPDATE usuario SET hash_contraseña = ? WHERE id_usuario = ?");
                            $stmt->execute([$password_hash, $user_id]);
                        }
                        
                        $message = 'Usuario actualizado exitosamente';
                    } catch (Exception $e) {
                        $error = 'Error al actualizar el usuario: ' . $e->getMessage();
                    }
                }
                break;
                
            case 'delete':
                $user_id = intval($_POST['user_id']);
                try {
                    // Iniciar transacción para asegurar consistencia
                    // NOTA: Esta función elimina COMPLETAMENTE el usuario y todos sus datos relacionados
                    // de la base de datos. No es una eliminación lógica (soft delete).
                    $pdo->beginTransaction();
                    
                    // 1. Eliminar métodos de pago del usuario
                    $stmt = $pdo->prepare("DELETE FROM metodo_pago_usuario WHERE id_usuario = ?");
                    $stmt->execute([$user_id]);
                    
                    // 2. Eliminar reseñas de productos del usuario
                    $stmt = $pdo->prepare("DELETE FROM reseña_producto WHERE id_usuario = ?");
                    $stmt->execute([$user_id]);
                    
                    // 3. Eliminar suscripciones del usuario
                    $stmt = $pdo->prepare("DELETE FROM suscripcion_usuario WHERE id_usuario = ?");
                    $stmt->execute([$user_id]);
                    
                    // 4. Obtener IDs de pedidos del usuario para eliminar pagos relacionados
                    $stmt = $pdo->prepare("SELECT id_pedido FROM pedido WHERE id_usuario = ?");
                    $stmt->execute([$user_id]);
                    $pedidos = $stmt->fetchAll(PDO::FETCH_COLUMN);
                    
                    // 5. Eliminar pagos relacionados a pedidos del usuario
                    if (!empty($pedidos)) {
                        $placeholders = str_repeat('?,', count($pedidos) - 1) . '?';
                        $stmt = $pdo->prepare("DELETE FROM pago WHERE id_pedido IN ($placeholders)");
                        $stmt->execute($pedidos);
                    }
                    
                    // 6. Obtener IDs de suscripciones del usuario para eliminar pagos relacionados
                    $stmt = $pdo->prepare("SELECT id_suscripcion FROM suscripcion_usuario WHERE id_usuario = ?");
                    $stmt->execute([$user_id]);
                    $suscripciones = $stmt->fetchAll(PDO::FETCH_COLUMN);
                    
                    // 7. Eliminar pagos relacionados a suscripciones del usuario
                    if (!empty($suscripciones)) {
                        $placeholders = str_repeat('?,', count($suscripciones) - 1) . '?';
                        $stmt = $pdo->prepare("DELETE FROM pago WHERE id_suscripcion IN ($placeholders)");
                        $stmt->execute($suscripciones);
                    }
                    
                    // 8. Eliminar items de pedido relacionados a pedidos del usuario
                    if (!empty($pedidos)) {
                        $placeholders = str_repeat('?,', count($pedidos) - 1) . '?';
                        $stmt = $pdo->prepare("DELETE FROM item_pedido WHERE id_pedido IN ($placeholders)");
                        $stmt->execute($pedidos);
                    }
                    
                    // 9. Eliminar pedidos del usuario
                    $stmt = $pdo->prepare("DELETE FROM pedido WHERE id_usuario = ?");
                    $stmt->execute([$user_id]);
                    
                    // 10. Finalmente, eliminar el usuario
                    $stmt = $pdo->prepare("DELETE FROM usuario WHERE id_usuario = ?");
                    $stmt->execute([$user_id]);
                    
                    // Confirmar transacción
                    $pdo->commit();
                    $message = 'Usuario eliminado correctamente';
                } catch (Exception $e) {
                    // Revertir transacción en caso de error
                    $pdo->rollBack();
                    $error = 'Error al eliminar el usuario: ' . $e->getMessage();
                }
                break;
        }
    }
}

// Obtener usuarios
$users = $pdo->query("
    SELECT u.*, 
           COUNT(us.id_suscripcion) as active_subscriptions,
           COUNT(o.id_pedido) as total_orders
    FROM usuario u
    LEFT JOIN suscripcion_usuario us ON u.id_usuario = us.id_usuario AND us.estado = 'activa'
    LEFT JOIN pedido o ON u.id_usuario = o.id_usuario
    GROUP BY u.id_usuario
    ORDER BY u.fecha_creacion DESC
")->fetchAll();

// Obtener usuario para editar
$edit_user = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM usuario WHERE id_usuario = ?");
    $stmt->execute([$_GET['edit']]);
    $edit_user = $stmt->fetch();
}

// Estadísticas de usuarios
$total_users = $pdo->query("SELECT COUNT(*) as total FROM usuario WHERE rol = 'usuario'")->fetch()['total'];
$active_subscriptions = $pdo->query("SELECT COUNT(*) as total FROM suscripcion_usuario WHERE estado = 'activa'")->fetch()['total'];
$new_users_month = $pdo->query("
    SELECT COUNT(*) as total 
    FROM usuario 
    WHERE rol = 'usuario' 
    AND fecha_creacion >= DATE_SUB(NOW(), INTERVAL 30 DAY)
")->fetch()['total'];
$premium_users = $pdo->query("
    SELECT COUNT(DISTINCT us.id_usuario) as total 
    FROM suscripcion_usuario us 
    JOIN plan_suscripcion sp ON us.id_plan = sp.id_plan 
    WHERE us.estado = 'activa' 
    AND sp.nombre IN ('PRO', 'PREMIUM')
")->fetch()['total'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios - Fit 360</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="admin-style.css">
</head>
<body>
    <div class="admin-container">
        <!-- Botón de menú móvil -->
        <button class="menu-toggle">
            <i class="fas fa-bars"></i>
        </button>

        <!-- Sidebar -->
        <div class="admin-sidebar">
            <div class="sidebar-header">
                <h2><i class="fas fa-dumbbell"></i> Fit 360</h2>
                <p>Panel de Administración</p>
            </div>
            
            <ul class="sidebar-menu">
                <li>
                    <a href="index.php">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="active">
                    <a href="users.php">
                        <i class="fas fa-users"></i>
                        <span>Usuarios</span>
                    </a>
                </li>
                <li>
                    <a href="products.php">
                        <i class="fas fa-box"></i>
                        <span>Productos</span>
                    </a>
                </li>
                <li>
                    <a href="services.php">
                        <i class="fas fa-dumbbell"></i>
                        <span>Servicios</span>
                    </a>
                </li>
                <li>
                    <a href="subscriptions.php">
                        <i class="fas fa-credit-card"></i>
                        <span>Suscripciones</span>
                    </a>
                </li>
                <li>
                    <a href="sales.php">
                        <i class="fas fa-chart-line"></i>
                        <span>Estadísticas</span>
                    </a>
                </li>
                <li>
                    <a href="../logout.php">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Cerrar Sesión</span>
                    </a>
                </li>
            </ul>
        </div>

        <!-- Contenido principal -->
        <div class="admin-main">
            <div class="admin-header">
                <h1><i class="fas fa-users"></i> Gestión de Usuarios</h1>
                <div class="user-info">
                    <i class="fas fa-user"></i>
                    Bienvenido, <?php echo htmlspecialchars($_SESSION['username']); ?>
                </div>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <!-- Estadísticas de usuarios -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($total_users); ?></h3>
                        <p>Total de Usuarios</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-credit-card"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($active_subscriptions); ?></h3>
                        <p>Suscripciones Activas</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($new_users_month); ?></h3>
                        <p>Nuevos este Mes</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-crown"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($premium_users); ?></h3>
                        <p>Usuarios Premium</p>
                    </div>
                </div>
            </div>

            <!-- Formulario para agregar/editar usuario -->
            <div class="admin-form">
                <h2><i class="fas fa-user-plus"></i> <?php echo $edit_user ? 'Editar Usuario' : 'Agregar Nuevo Usuario'; ?></h2>
                
                <form method="POST" action="">
                    <input type="hidden" name="action" value="<?php echo $edit_user ? 'edit' : 'add'; ?>">
                    <?php if ($edit_user): ?>
                        <input type="hidden" name="user_id" value="<?php echo $edit_user['id_usuario']; ?>">
                    <?php endif; ?>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="username">Nombre de Usuario *</label>
                            <input type="text" id="username" name="username" value="<?php echo $edit_user ? htmlspecialchars($edit_user['nombre_usuario']) : ''; ?>" required minlength="3" pattern="[a-zA-Z0-9_]+" title="Solo letras, números y guiones bajos">
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email *</label>
                            <input type="email" id="email" name="email" value="<?php echo $edit_user ? htmlspecialchars($edit_user['correo']) : ''; ?>" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="first_name">Nombre *</label>
                            <input type="text" id="first_name" name="first_name" value="<?php echo $edit_user ? htmlspecialchars($edit_user['nombre']) : ''; ?>" required minlength="2" pattern="[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+" title="Solo letras y espacios">
                        </div>
                        
                        <div class="form-group">
                            <label for="last_name">Apellido</label>
                            <input type="text" id="last_name" name="last_name" value="<?php echo $edit_user ? htmlspecialchars($edit_user['apellido']) : ''; ?>" minlength="2" pattern="[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+" title="Solo letras y espacios">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="phone">Teléfono</label>
                            <input type="tel" id="phone" name="phone" value="<?php echo $edit_user ? htmlspecialchars($edit_user['telefono']) : ''; ?>" pattern="[\d\s\-\+]+" title="Solo números, espacios, guiones y +" minlength="7">
                        </div>
                        
                        <div class="form-group">
                            <label for="role">Rol *</label>
                            <select id="role" name="role" required>
                                <option value="">Seleccionar rol</option>
                                <option value="usuario" <?php echo ($edit_user && $edit_user['rol'] == 'usuario') ? 'selected' : ''; ?>>Usuario</option>
                                <option value="administrador" <?php echo ($edit_user && $edit_user['rol'] == 'administrador') ? 'selected' : ''; ?>>Administrador</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="address">Dirección</label>
                        <textarea id="address" name="address" rows="3" minlength="10" title="Mínimo 10 caracteres"><?php echo $edit_user ? htmlspecialchars($edit_user['direccion']) : ''; ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="password"><?php echo $edit_user ? 'Nueva Contraseña (dejar en blanco para mantener la actual)' : 'Contraseña *'; ?></label>
                        <input type="password" id="password" name="password" <?php echo $edit_user ? 'minlength="8"' : 'required minlength="8"'; ?> title="Mínimo 8 caracteres">
                        <div class="show-password-container">
                            <input type="checkbox" id="show-password" style="margin-right: 8px;">
                            <label for="show-password" style="font-size:1.3rem; cursor:pointer;">Mostrar contraseña</label>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn">
                            <i class="fas fa-save"></i>
                            <?php echo $edit_user ? 'Actualizar Usuario' : 'Agregar Usuario'; ?>
                        </button>
                        <?php if ($edit_user): ?>
                            <a href="users.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i>
                                Cancelar
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Lista de usuarios -->
            <div class="admin-table">
                <h2><i class="fas fa-list"></i> Lista de Usuarios</h2>
                
                <div class="search-container">
                    <input type="text" class="search-input" placeholder="Buscar usuarios..." data-target=".user-row">
                </div>

                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Usuario</th>
                            <th>Email</th>
                            <th>Nombre</th>
                            <th>Rol</th>
                            <th>Suscripciones</th>
                            <th>Pedidos</th>
                            <th>Fecha Registro</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr class="user-row">
                                <td><?php echo $user['id_usuario']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($user['nombre_usuario']); ?></strong>
                                    <?php if ($user['rol'] == 'administrador'): ?>
                                        <i class="fas fa-crown" style="color: var(--accent-color); margin-left: 5px;"></i>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($user['correo']); ?></td>
                                <td><?php echo htmlspecialchars($user['nombre'] . ' ' . $user['apellido']); ?></td>
                                <td>
                                    <span class="<?php echo $user['rol'] == 'administrador' ? 'text-warning' : 'text-info'; ?>">
                                        <?php echo ucfirst($user['rol']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="<?php echo $user['active_subscriptions'] > 0 ? 'text-success' : 'text-muted'; ?>">
                                        <?php echo $user['active_subscriptions']; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="<?php echo $user['total_orders'] > 0 ? 'text-success' : 'text-muted'; ?>">
                                        <?php echo $user['total_orders']; ?>
                                    </span>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($user['fecha_creacion'])); ?></td>
                                <td class="table-actions">
                                    <a href="?edit=<?php echo $user['id_usuario']; ?>" class="btn btn-secondary" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <?php if ($user['id_usuario'] != $_SESSION['user_id']): ?>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('⚠️ ADVERTENCIA: Esta acción eliminará completamente al usuario y todos sus datos relacionados (pedidos, suscripciones, métodos de pago, reseñas) de la base de datos. Esta acción es IRREVERSIBLE. ¿Estás seguro de que quieres continuar?')">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id_usuario']; ?>">
                                            <button type="submit" class="btn btn-danger" title="Eliminar completamente">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="admin-script.js"></script>
</body>
</html> 