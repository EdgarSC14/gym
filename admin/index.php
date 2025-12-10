<?php
session_start();
require_once '../config/database.php';

// Verificar si el usuario está logueado y es admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'administrador') {
    header('Location: ../login.php');
    exit();
}

// Obtener estadísticas
$stats = [];

// Total de usuarios
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM usuario");
$stmt->execute();
$stats['total_users'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Usuarios activos (con suscripción activa)
$stmt = $pdo->prepare("SELECT COUNT(DISTINCT us.id_usuario) as total FROM suscripcion_usuario us WHERE us.estado = 'activa'");
$stmt->execute();
$stats['active_subscriptions'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Total de ventas (pagos completados)
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM pago WHERE estado = 'completado'");
$stmt->execute();
$stats['total_sales'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Ingresos totales (pagos completados)
$stmt = $pdo->prepare("SELECT SUM(monto) as total FROM pago WHERE estado = 'completado'");
$stmt->execute();
$stats['total_revenue'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// Total de productos activos
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM producto WHERE esta_activo = 1");
$stmt->execute();
$stats['total_products'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Total de servicios activos
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM servicio WHERE esta_activo = 1");
$stmt->execute();
$stats['total_services'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Actividad reciente (usuarios, ventas, suscripciones, bajo stock)
$recent_users = $pdo->query("
    SELECT nombre_usuario as username, fecha_creacion as date, 'Nuevo usuario registrado' as action, NULL as extra FROM usuario ORDER BY fecha_creacion DESC LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);
$recent_sales = $pdo->query("
    SELECT CONCAT(u.nombre, ' ', u.apellido) as username, p.fecha_pago as date, 'Nueva venta realizada' as action, CONCAT('$', p.monto) as extra
    FROM pago p
    LEFT JOIN pedido o ON p.id_pedido = o.id_pedido
    LEFT JOIN usuario u ON o.id_usuario = u.id_usuario
    WHERE p.estado = 'completado'
    ORDER BY p.fecha_pago DESC LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);
$recent_subs = $pdo->query("
    SELECT CONCAT(u.nombre, ' ', u.apellido) as username, us.fecha_inicio as date, 'Nueva suscripción' as action, sp.nombre as extra
    FROM suscripcion_usuario us
    JOIN usuario u ON us.id_usuario = u.id_usuario
    JOIN plan_suscripcion sp ON us.id_plan = sp.id_plan
    WHERE us.estado = 'activa'
    ORDER BY us.fecha_inicio DESC LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);
$low_stock = $pdo->query("
    SELECT nombre as username, fecha_creacion as date, 'Producto con bajo stock' as action, CONCAT('Stock: ', cantidad_stock) as extra
    FROM producto
    WHERE cantidad_stock <= 10 AND esta_activo = 1
    ORDER BY fecha_creacion DESC LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);
$recent_activity = array_merge($recent_users, $recent_sales, $recent_subs, $low_stock);
// Ordenar por fecha descendente y limitar a 10
usort($recent_activity, function($a, $b) { return strtotime($b['date']) - strtotime($a['date']); });
$recent_activity = array_slice($recent_activity, 0, 10);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - Fit 360</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="admin-style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                <li class="active">
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
                <h1><i class="fas fa-tachometer-alt"></i> Dashboard</h1>
                <div class="user-info">
                    <i class="fas fa-user"></i>
                    Bienvenido, <?php echo htmlspecialchars($_SESSION['username']); ?>
                </div>
            </div>

            <div class="dashboard-content">
                <!-- Estadísticas -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo number_format($stats['total_users']); ?></h3>
                            <p>Total de Usuarios</p>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-credit-card"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo number_format($stats['active_subscriptions']); ?></h3>
                            <p>Suscripciones Activas</p>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo number_format($stats['total_sales']); ?></h3>
                            <p>Total de Ventas</p>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                        <div class="stat-info">
                            <h3>$<?php echo number_format($stats['total_revenue'], 2); ?></h3>
                            <p>Ingresos Totales</p>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-box"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo number_format($stats['total_products']); ?></h3>
                            <p>Productos</p>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-dumbbell"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo number_format($stats['total_services']); ?></h3>
                            <p>Servicios</p>
                        </div>
                    </div>
                </div>

                <!-- Gráficos y actividad reciente -->
                <div class="dashboard-grid">
                    <!-- Actividad reciente -->
                    <div class="recent-activity">
                        <h3><i class="fas fa-clock"></i> Actividad Reciente</h3>
                        <div class="activity-list">
                            <?php if (empty($recent_activity)): ?>
                                <p class="no-activity">No hay actividad reciente</p>
                            <?php else: ?>
                                <?php foreach ($recent_activity as $activity): ?>
                                    <div class="activity-item">
                                        <div class="activity-icon">
                                            <?php if (strpos($activity['action'], 'usuario') !== false): ?>
                                                <i class="fas fa-user-plus"></i>
                                            <?php elseif (strpos($activity['action'], 'venta') !== false): ?>
                                                <i class="fas fa-shopping-cart"></i>
                                            <?php elseif (strpos($activity['action'], 'suscrip') !== false): ?>
                                                <i class="fas fa-credit-card"></i>
                                            <?php elseif (strpos($activity['action'], 'stock') !== false): ?>
                                                <i class="fas fa-box"></i>
                                            <?php else: ?>
                                                <i class="fas fa-info-circle"></i>
                                            <?php endif; ?>
                                        </div>
                                        <div class="activity-content">
                                            <p><strong><?php echo htmlspecialchars($activity['username']); ?></strong></p>
                                            <small><?php echo htmlspecialchars($activity['action']); ?><?php if (!empty($activity['extra'])): ?> (<?php echo htmlspecialchars($activity['extra']); ?>)<?php endif; ?></small>
                                            <small class="activity-time"><?php echo date('d/m/Y H:i', strtotime($activity['date'])); ?></small>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="admin-script.js"></script>
</body>
</html> 