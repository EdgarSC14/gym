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
            case 'edit':
                $plan_id = intval($_POST['plan_id']);
                $name = trim($_POST['name']);
                $price = floatval($_POST['price']);
                $duration_days = intval($_POST['duration_days']);
                $description = trim($_POST['description']);
                $benefits = trim($_POST['benefits']);
                
                // Validar campos obligatorios
                if (empty($description)) {
                    $error = 'La descripción es obligatoria';
                } elseif (empty($benefits)) {
                    $error = 'Los beneficios son obligatorios';
                } else {
                    try {
                        $stmt = $pdo->prepare("UPDATE plan_suscripcion SET nombre = ?, precio = ?, duracion_dias = ?, descripcion = ?, beneficios = ? WHERE id_plan = ?");
                        $stmt->execute([$name, $price, $duration_days, $description, $benefits, $plan_id]);
                        $message = 'Plan de suscripción actualizado exitosamente';
                    } catch (Exception $e) {
                        $error = 'Error al actualizar el plan: ' . $e->getMessage();
                    }
                }
                break;
                
            case 'delete':
                $plan_id = intval($_POST['plan_id']);
                try {
                    $stmt = $pdo->prepare("UPDATE plan_suscripcion SET esta_activo = 0 WHERE id_plan = ?");
                    $stmt->execute([$plan_id]);
                    $message = 'Plan de suscripción eliminado exitosamente';
                } catch (Exception $e) {
                    $error = 'Error al eliminar el plan: ' . $e->getMessage();
                }
                break;
        }
    }
}

// Obtener planes de suscripción
$plans = $pdo->query("SELECT * FROM plan_suscripcion WHERE esta_activo = 1 ORDER BY precio ASC")->fetchAll();

// Obtener suscripciones activas
$subscriptions = $pdo->query("
    SELECT us.*, u.nombre, u.apellido, u.correo, sp.nombre as plan_name, sp.precio as plan_precio
    FROM suscripcion_usuario us
    JOIN usuario u ON us.id_usuario = u.id_usuario
    JOIN plan_suscripcion sp ON us.id_plan = sp.id_plan
    WHERE us.estado = 'activa'
    ORDER BY us.fecha_creacion DESC
")->fetchAll();

// Obtener plan para editar
$edit_plan = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM plan_suscripcion WHERE id_plan = ?");
    $stmt->execute([$_GET['edit']]);
    $edit_plan = $stmt->fetch();
}

// Estadísticas de suscripciones
$total_subscriptions = $pdo->query("SELECT COUNT(*) as total FROM suscripcion_usuario WHERE estado = 'activa'")->fetch()['total'];
$total_revenue = $pdo->query("
    SELECT SUM(sp.precio) as total 
    FROM suscripcion_usuario us 
    JOIN plan_suscripcion sp ON us.id_plan = sp.id_plan 
    WHERE us.estado = 'activa'
")->fetch()['total'] ?? 0;
$expiring_soon = $pdo->query("
    SELECT COUNT(*) as total 
    FROM suscripcion_usuario 
    WHERE estado = 'activa' 
    AND fecha_fin BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
")->fetch()['total'];
$total_plans = $pdo->query("SELECT COUNT(*) as total FROM plan_suscripcion WHERE esta_activo = 1")->fetch()['total'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Suscripciones - Fit 360</title>
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
                <li>
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
                <li class="active">
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
                <h1><i class="fas fa-credit-card"></i> Gestión de Suscripciones</h1>
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

            <!-- Estadísticas -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-credit-card"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($total_subscriptions); ?></h3>
                        <p>Suscripciones Activas</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="stat-info">
                        <h3>$<?php echo number_format($total_revenue, 2); ?> MXN</h3>
                        <p>Ingresos Totales</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($expiring_soon); ?></h3>
                        <p>Expiran Pronto</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-list"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($total_plans); ?></h3>
                        <p>Planes Disponibles</p>
                    </div>
                </div>
            </div>

            <!-- Formulario para editar plan -->
            <?php if ($edit_plan): ?>
            <div class="admin-form">
                <h2><i class="fas fa-edit"></i> Editar Plan</h2>
                
                <form method="POST" action="">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="plan_id" value="<?php echo $edit_plan['id_plan']; ?>">

                    <div class="form-row">
                        <div class="form-group">
                            <label for="name">Nombre del Plan *</label>
                            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($edit_plan['nombre']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="price">Precio *</label>
                            <input type="number" id="price" name="price" step="0.01" min="0" value="<?php echo $edit_plan['precio']; ?>" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="duration_days">Duración (días) *</label>
                            <input type="number" id="duration_days" name="duration_days" min="1" value="<?php echo $edit_plan['duracion_dias']; ?>" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="description">Descripción *</label>
                        <textarea id="description" name="description" rows="3" required><?php echo htmlspecialchars($edit_plan['descripcion']); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="benefits">Beneficios *</label>
                        <textarea id="benefits" name="benefits" rows="4" placeholder="Lista los beneficios del plan, uno por línea" required><?php echo htmlspecialchars($edit_plan['beneficios']); ?></textarea>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn">
                            <i class="fas fa-save"></i>
                            Actualizar Plan
                        </button>
                        <a href="subscriptions.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i>
                            Cancelar
                        </a>
                    </div>
                </form>
            </div>
            <?php endif; ?>

            <!-- Lista de planes -->
            <div class="admin-table">
                <h2><i class="fas fa-list"></i> Planes de Suscripción</h2>
                
                <div class="search-container">
                    <input type="text" class="search-input" placeholder="Buscar planes..." data-target=".plan-row">
                </div>

                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Precio</th>
                            <th>Duración</th>
                            <th>Descripción</th>
                            <th>Beneficios</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($plans as $plan): ?>
                            <tr class="plan-row">
                                <td><?php echo $plan['id_plan']; ?></td>
                                <td><strong><?php echo htmlspecialchars($plan['nombre']); ?></strong></td>
                                <td><strong>$<?php echo number_format($plan['precio'], 2); ?> MXN</strong></td>
                                <td><?php echo $plan['duracion_dias']; ?> días</td>
                                <td><?php echo htmlspecialchars(substr($plan['descripcion'], 0, 50)) . (strlen($plan['descripcion']) > 50 ? '...' : ''); ?></td>
                                <td><?php echo htmlspecialchars(substr($plan['beneficios'], 0, 50)) . (strlen($plan['beneficios']) > 50 ? '...' : ''); ?></td>
                                <td class="table-actions">
                                    <a href="?edit=<?php echo $plan['id_plan']; ?>" class="btn btn-secondary" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('¿Estás seguro de que quieres eliminar este plan?')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="plan_id" value="<?php echo $plan['id_plan']; ?>">
                                        <button type="submit" class="btn btn-danger" title="Eliminar">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Lista de suscripciones activas -->
            <div class="admin-table">
                <h2><i class="fas fa-users"></i> Suscripciones Activas</h2>
                
                <div class="search-container">
                    <input type="text" class="search-input" placeholder="Buscar suscripciones..." data-target=".subscription-row">
                </div>

                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Usuario</th>
                            <th>Plan</th>
                            <th>Precio</th>
                            <th>Fecha Inicio</th>
                            <th>Fecha Fin</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($subscriptions as $subscription): ?>
                            <tr class="subscription-row">
                                <td><?php echo $subscription['id_suscripcion']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($subscription['nombre'] . ' ' . $subscription['apellido']); ?></strong>
                                    <br><small><?php echo htmlspecialchars($subscription['correo']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($subscription['plan_name']); ?></td>
                                <td>$<?php echo number_format($subscription['plan_precio'], 2); ?> MXN</td>
                                <td><?php echo date('d/m/Y', strtotime($subscription['fecha_inicio'])); ?></td>
                                <td>
                                    <?php 
                                    $end_date = strtotime($subscription['fecha_fin']);
                                    $days_until_expiry = ceil(($end_date - time()) / (60 * 60 * 24));
                                    $class = $days_until_expiry <= 7 ? 'text-danger' : ($days_until_expiry <= 30 ? 'text-warning' : 'text-success');
                                    ?>
                                    <span class="<?php echo $class; ?>">
                                        <?php echo date('d/m/Y', $end_date); ?>
                                        <?php if ($days_until_expiry <= 7): ?>
                                            <br><small>(Expira en <?php echo $days_until_expiry; ?> días)</small>
                                        <?php endif; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="text-success">Activa</span>
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