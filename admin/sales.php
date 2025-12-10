<?php
session_start();
require_once '../config/database.php';

// Verificar si el usuario está logueado y es admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'administrador') {
    header('Location: ../login.php');
    exit();
}

$pdo = getConnection();

// Estadísticas generales
$total_sales = $pdo->query("SELECT COUNT(*) as total FROM pago WHERE estado = 'completado'")->fetch()['total'];
$total_revenue = $pdo->query("SELECT SUM(monto) as total FROM pago WHERE estado = 'completado'")->fetch()['total'] ?? 0;
$monthly_revenue = $pdo->query("SELECT SUM(monto) as total FROM pago WHERE estado = 'completado' AND MONTH(fecha_pago) = MONTH(CURDATE())")->fetch()['total'] ?? 0;
$pending_payments = $pdo->query("SELECT COUNT(*) as total FROM pago WHERE estado = 'pendiente'")->fetch()['total'];

// Ventas por mes (últimos 6 meses)
$monthly_sales = $pdo->query("
    SELECT 
        DATE_FORMAT(fecha_pago, '%Y-%m') as month,
        COUNT(*) as sales_count,
        SUM(monto) as total_amount
    FROM pago 
    WHERE estado = 'completado' 
    AND fecha_pago >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(fecha_pago, '%Y-%m')
    ORDER BY month DESC
")->fetchAll();

// Productos más vendidos
$top_products = $pdo->query("
    SELECT 
        p.nombre,
        COUNT(oi.id_item_pedido) as sales_count,
        SUM(oi.cantidad) as total_quantity,
        SUM(oi.cantidad * oi.precio_unitario) as total_revenue
    FROM producto p
    JOIN item_pedido oi ON p.id_producto = oi.id_producto
    JOIN pedido o ON oi.id_pedido = o.id_pedido
    WHERE o.estado = 'pagado'
    GROUP BY p.id_producto
    ORDER BY total_quantity DESC
    LIMIT 10
")->fetchAll();

// Servicios más populares
$top_services = $pdo->query("
    SELECT 
        s.nombre,
        COUNT(oi.id_item_pedido) as bookings_count,
        SUM(oi.cantidad * oi.precio_unitario) as total_revenue
    FROM servicio s
    JOIN item_pedido oi ON s.id_servicio = oi.id_servicio
    JOIN pedido o ON oi.id_pedido = o.id_pedido
    WHERE o.estado = 'pagado'
    GROUP BY s.id_servicio
    ORDER BY bookings_count DESC
    LIMIT 10
")->fetchAll();

// Ventas recientes
$recent_sales = $pdo->query("
    SELECT 
        p.id_pago,
        p.monto,
        p.fecha_pago,
        p.estado,
        u.nombre,
        u.apellido,
        u.correo
    FROM pago p
    LEFT JOIN pedido o ON p.id_pedido = o.id_pedido
    LEFT JOIN usuario u ON o.id_usuario = u.id_usuario
    ORDER BY p.fecha_pago DESC
    LIMIT 20
")->fetchAll();

// Usuarios registrados por mes (últimos 6 meses)
$monthly_users = $pdo->query("
    SELECT DATE_FORMAT(fecha_creacion, '%Y-%m') as month, COUNT(*) as user_count
    FROM usuario
    WHERE fecha_creacion >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(fecha_creacion, '%Y-%m')
    ORDER BY month DESC
")->fetchAll();

// Suscripciones activas por mes (últimos 6 meses)
$monthly_subs = $pdo->query("
    SELECT DATE_FORMAT(fecha_inicio, '%Y-%m') as month, COUNT(*) as subs_count
    FROM suscripcion_usuario
    WHERE estado = 'activa' AND fecha_inicio >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(fecha_inicio, '%Y-%m')
    ORDER BY month DESC
")->fetchAll();

// Ventas de productos por mes (últimos 6 meses)
$monthly_product_sales = $pdo->query("
    SELECT DATE_FORMAT(o.fecha_creacion, '%Y-%m') as month, SUM(oi.cantidad) as total_quantity
    FROM item_pedido oi
    JOIN pedido o ON oi.id_pedido = o.id_pedido
    WHERE o.estado = 'pagado' AND o.fecha_creacion >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(o.fecha_creacion, '%Y-%m')
    ORDER BY month DESC
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ventas y Estadísticas - Fit 360</title>
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
                <li>
                    <a href="subscriptions.php">
                        <i class="fas fa-credit-card"></i>
                        <span>Suscripciones</span>
                    </a>
                </li>
                <li class="active">
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
                <h1><i class="fas fa-chart-line"></i> Ventas y Estadísticas</h1>
                <div class="user-info">
                    <button id="generateGeneralPDF" class="btn btn-primary" style="margin-right: 15px;">
                        <i class="fas fa-file-pdf"></i> Reporte General
                    </button>
                    <i class="fas fa-user"></i>
                    Bienvenido, <?php echo htmlspecialchars($_SESSION['username']); ?>
                </div>
            </div>

            <!-- Estadísticas Principales -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($total_sales); ?></h3>
                        <p>Total de Ventas</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="stat-info">
                        <h3>$<?php echo number_format($total_revenue, 2); ?></h3>
                        <p>Ingresos Totales</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-calendar"></i>
                    </div>
                    <div class="stat-info">
                        <h3>$<?php echo number_format($monthly_revenue, 2); ?></h3>
                        <p>Ingresos del Mes</p>
                    </div>
                </div>
            </div>

            <!-- Gráfico de Ventas Mensuales -->
            <div class="admin-table">
                <h2><i class="fas fa-chart-bar"></i> Ventas Mensuales</h2>
                <div style="padding: 20px;">
                    <label for="monthFilter" style="font-weight:600;">Filtrar por mes:</label>
                    <select id="monthFilter" style="margin-left:10px; padding:5px 10px; border-radius:5px;">
                        <option value="all">Todos</option>
                        <?php foreach(array_reverse($monthly_sales) as $item): 
                            $parts = explode('-', $item['month']);
                            $monthNum = intval($parts[1]);
                            $year = $parts[0];
                            $monthName = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'][$monthNum-1];
                            $label = $monthName . ' ' . $year;
                        ?>
                            <option value="<?= $item['month'] ?>"><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                    <canvas id="monthlyChart" width="400" height="200"></canvas>
                </div>
            </div>

            <!-- Gráfica de Usuarios Registrados -->
            <div class="admin-table">
                <h2><i class="fas fa-users"></i> Usuarios Registrados</h2>
                <div style="padding: 20px;">
                    <label for="usersMonthFilter" style="font-weight:600;">Filtrar por mes:</label>
                    <select id="usersMonthFilter" style="margin-left:10px; padding:5px 10px; border-radius:5px;">
                        <option value="all">Todos</option>
                        <?php foreach(array_reverse($monthly_users) as $item): 
                            $parts = explode('-', $item['month']);
                            $monthNum = intval($parts[1]);
                            $year = $parts[0];
                            $monthName = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'][$monthNum-1];
                            $label = $monthName . ' ' . $year;
                        ?>
                            <option value="<?= $item['month'] ?>"><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                    <canvas id="usersChart" width="400" height="200"></canvas>
                </div>
            </div>

            <!-- Gráfica de Suscripciones Activas -->
            <div class="admin-table">
                <h2><i class="fas fa-credit-card"></i> Suscripciones Activas</h2>
                <div style="padding: 20px;">
                    <label for="subsMonthFilter" style="font-weight:600;">Filtrar por mes:</label>
                    <select id="subsMonthFilter" style="margin-left:10px; padding:5px 10px; border-radius:5px;">
                        <option value="all">Todos</option>
                        <?php foreach(array_reverse($monthly_subs) as $item): 
                            $parts = explode('-', $item['month']);
                            $monthNum = intval($parts[1]);
                            $year = $parts[0];
                            $monthName = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'][$monthNum-1];
                            $label = $monthName . ' ' . $year;
                        ?>
                            <option value="<?= $item['month'] ?>"><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                    <canvas id="subsChart" width="400" height="200"></canvas>
                </div>
            </div>

            <!-- Gráfica de Ventas de Productos -->
            <div class="admin-table">
                <h2><i class="fas fa-box"></i> Ventas de Productos</h2>
                <div style="padding: 20px;">
                    <label for="productSalesMonthFilter" style="font-weight:600;">Filtrar por mes:</label>
                    <select id="productSalesMonthFilter" style="margin-left:10px; padding:5px 10px; border-radius:5px;">
                        <option value="all">Todos</option>
                        <?php foreach(array_reverse($monthly_product_sales) as $item): 
                            $parts = explode('-', $item['month']);
                            $monthNum = intval($parts[1]);
                            $year = $parts[0];
                            $monthName = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'][$monthNum-1];
                            $label = $monthName . ' ' . $year;
                        ?>
                            <option value="<?= $item['month'] ?>"><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                    <canvas id="productSalesChart" width="400" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>

    <script src="admin-script.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script>
        const monthlySalesData = <?php echo json_encode(array_reverse($monthly_sales)); ?>;
        const monthNames = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
        const labels = monthlySalesData.map(item => {
            const [year, month] = item.month.split('-');
            return monthNames[parseInt(month, 10) - 1] + ' ' + year;
        });
        const data = monthlySalesData.map(item => parseFloat(item.total_amount));

        const ctx = document.getElementById('monthlyChart').getContext('2d');
        let chart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Ventas ($)',
                    data: data,
                    backgroundColor: 'rgba(75, 192, 192, 0.8)',
                    borderColor: 'rgb(75, 192, 192)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: 'Ventas Mensuales'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        document.getElementById('monthFilter').addEventListener('change', function() {
            const selected = this.value;
            if (selected === 'all') {
                chart.data.labels = labels;
                chart.data.datasets[0].data = data;
            } else {
                const idx = monthlySalesData.findIndex(item => item.month === selected);
                if (idx !== -1) {
                    chart.data.labels = [labels[idx]];
                    chart.data.datasets[0].data = [data[idx]];
                }
            }
            chart.update();
        });

        // Usuarios registrados por mes
        const monthlyUsersData = <?php echo json_encode(array_reverse($monthly_users)); ?>;
        const usersLabels = monthlyUsersData.map(item => {
            const [year, month] = item.month.split('-');
            return monthNames[parseInt(month, 10) - 1] + ' ' + year;
        });
        const usersData = monthlyUsersData.map(item => parseInt(item.user_count));
        const usersCtx = document.getElementById('usersChart').getContext('2d');
        let usersChart = new Chart(usersCtx, {
            type: 'bar',
            data: {
                labels: usersLabels,
                datasets: [{
                    label: 'Usuarios Registrados',
                    data: usersData,
                    backgroundColor: 'rgba(54, 162, 235, 0.7)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                plugins: { title: { display: true, text: 'Usuarios Registrados por Mes' } },
                scales: { y: { beginAtZero: true } }
            }
        });

        // Suscripciones activas por mes
        const monthlySubsData = <?php echo json_encode(array_reverse($monthly_subs)); ?>;
        const subsLabels = monthlySubsData.map(item => {
            const [year, month] = item.month.split('-');
            return monthNames[parseInt(month, 10) - 1] + ' ' + year;
        });
        const subsData = monthlySubsData.map(item => parseInt(item.subs_count));
        const subsCtx = document.getElementById('subsChart').getContext('2d');
        let subsChart = new Chart(subsCtx, {
            type: 'bar',
            data: {
                labels: subsLabels,
                datasets: [{
                    label: 'Suscripciones Activas',
                    data: subsData,
                    backgroundColor: 'rgba(255, 99, 132, 0.7)',
                    borderColor: 'rgba(255, 99, 132, 1)',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                plugins: { title: { display: true, text: 'Suscripciones Activas por Mes' } },
                scales: { y: { beginAtZero: true } }
            }
        });

        // Ventas de productos por mes
        const monthlyProductSalesData = <?php echo json_encode(array_reverse($monthly_product_sales)); ?>;
        const productSalesLabels = monthlyProductSalesData.map(item => {
            const [year, month] = item.month.split('-');
            return monthNames[parseInt(month, 10) - 1] + ' ' + year;
        });
        const productSalesData = monthlyProductSalesData.map(item => parseInt(item.total_quantity));
        const productSalesCtx = document.getElementById('productSalesChart').getContext('2d');
        let productSalesChart = new Chart(productSalesCtx, {
            type: 'bar',
            data: {
                labels: productSalesLabels,
                datasets: [{
                    label: 'Ventas de Productos',
                    data: productSalesData,
                    backgroundColor: 'rgba(255, 206, 86, 0.7)',
                    borderColor: 'rgba(255, 206, 86, 1)',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                plugins: { title: { display: true, text: 'Ventas de Productos por Mes' } },
                scales: { y: { beginAtZero: true } }
            }
        });

        // Filtro de mes para usuarios registrados
        const usersMonthFilter = document.getElementById('usersMonthFilter');
        usersMonthFilter.addEventListener('change', function() {
            const selected = this.value;
            if (selected === 'all') {
                usersChart.data.labels = usersLabels;
                usersChart.data.datasets[0].data = usersData;
            } else {
                const idx = monthlyUsersData.findIndex(item => item.month === selected);
                if (idx !== -1) {
                    usersChart.data.labels = [usersLabels[idx]];
                    usersChart.data.datasets[0].data = [usersData[idx]];
                }
            }
            usersChart.update();
        });
        
        // Filtro de mes para suscripciones activas
        const subsMonthFilter = document.getElementById('subsMonthFilter');
        subsMonthFilter.addEventListener('change', function() {
            const selected = this.value;
            if (selected === 'all') {
                subsChart.data.labels = subsLabels;
                subsChart.data.datasets[0].data = subsData;
            } else {
                const idx = monthlySubsData.findIndex(item => item.month === selected);
                if (idx !== -1) {
                    subsChart.data.labels = [subsLabels[idx]];
                    subsChart.data.datasets[0].data = [subsData[idx]];
                }
            }
            subsChart.update();
        });
        
        // Filtro de mes para ventas de productos
        const productSalesMonthFilter = document.getElementById('productSalesMonthFilter');
        productSalesMonthFilter.addEventListener('change', function() {
            const selected = this.value;
            if (selected === 'all') {
                productSalesChart.data.labels = productSalesLabels;
                productSalesChart.data.datasets[0].data = productSalesData;
            } else {
                const idx = monthlyProductSalesData.findIndex(item => item.month === selected);
                if (idx !== -1) {
                    productSalesChart.data.labels = [productSalesLabels[idx]];
                    productSalesChart.data.datasets[0].data = [productSalesData[idx]];
                }
            }
            productSalesChart.update();
        });

        // Función de notificación
        function showNotification(message, type = 'info') {
            // Crear elemento de notificación
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 15px 20px;
                border-radius: 5px;
                color: white;
                font-weight: bold;
                z-index: 10000;
                max-width: 300px;
                box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            `;
            
            // Configurar color según tipo
            switch(type) {
                case 'success':
                    notification.style.backgroundColor = '#28a745';
                    break;
                case 'error':
                    notification.style.backgroundColor = '#dc3545';
                    break;
                case 'warning':
                    notification.style.backgroundColor = '#ffc107';
                    notification.style.color = '#212529';
                    break;
                default:
                    notification.style.backgroundColor = '#17a2b8';
            }
            
            notification.textContent = message;
            document.body.appendChild(notification);
            
            // Remover después de 3 segundos
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 3000);
        }

        // Función para generar PDF General con todas las secciones
        document.getElementById('generateGeneralPDF').addEventListener('click', function() {
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generando PDF...';
            this.disabled = true;
            
            // Verificar si hay datos
            if ((!monthlySalesData || monthlySalesData.length === 0) && 
                (!monthlyUsersData || monthlyUsersData.length === 0) && 
                (!monthlySubsData || monthlySubsData.length === 0) && 
                (!monthlyProductSalesData || monthlyProductSalesData.length === 0)) {
                this.innerHTML = '<i class="fas fa-file-pdf"></i> Reporte General';
                this.disabled = false;
                if (typeof showNotification === 'function') {
                    showNotification('No hay datos disponibles para generar el reporte general', 'warning');
                }
                return;
            }
            
            // Esperar un momento para asegurar que las gráficas estén renderizadas
            setTimeout(() => {
                // Capturar todas las gráficas como imágenes
                const salesChartImage = document.getElementById('monthlyChart').toDataURL('image/png');
                const usersChartImage = document.getElementById('usersChart').toDataURL('image/png');
                const subsChartImage = document.getElementById('subsChart').toDataURL('image/png');
                const productSalesChartImage = document.getElementById('productSalesChart').toDataURL('image/png');
                
                // Generar contenido para cada sección (solo gráficas, sin tablas)
                let salesSection = '';
                if (monthlySalesData && monthlySalesData.length > 0) {
                    salesSection = `
                        <div style="margin-bottom: 40px;">
                            <h2 style="color: #FF1E00; font-size: 18px; border-bottom: 1px solid #ddd; padding-bottom: 5px;">Ventas Mensuales</h2>
                            <div style="text-align: center; margin: 20px 0;">
                                <img src="${salesChartImage}" style="max-width: 100%; height: auto; border: 1px solid #ddd;" />
                            </div>
                        </div>
                    `;
                }
                
                let usersSection = '';
                if (monthlyUsersData && monthlyUsersData.length > 0) {
                    usersSection = `
                        <div style="margin-bottom: 40px;">
                            <h2 style="color: #FF1E00; font-size: 18px; border-bottom: 1px solid #ddd; padding-bottom: 5px;">Usuarios Registrados</h2>
                            <div style="text-align: center; margin: 20px 0;">
                                <img src="${usersChartImage}" style="max-width: 100%; height: auto; border: 1px solid #ddd;" />
                            </div>
                        </div>
                    `;
                }
                
                let subsSection = '';
                if (monthlySubsData && monthlySubsData.length > 0) {
                    subsSection = `
                        <div style="margin-bottom: 40px;">
                            <h2 style="color: #FF1E00; font-size: 18px; border-bottom: 1px solid #ddd; padding-bottom: 5px;">Suscripciones Activas</h2>
                            <div style="text-align: center; margin: 20px 0;">
                                <img src="${subsChartImage}" style="max-width: 100%; height: auto; border: 1px solid #ddd;" />
                            </div>
                        </div>
                    `;
                }
                
                let productSalesSection = '';
                if (monthlyProductSalesData && monthlyProductSalesData.length > 0) {
                    productSalesSection = `
                        <div style="margin-bottom: 40px;">
                            <h2 style="color: #FF1E00; font-size: 18px; border-bottom: 1px solid #ddd; padding-bottom: 5px;">Ventas de Productos</h2>
                            <div style="text-align: center; margin: 20px 0;">
                                <img src="${productSalesChartImage}" style="max-width: 100%; height: auto; border: 1px solid #ddd;" />
                            </div>
                        </div>
                    `;
                }
                
                const pdfContent = `
                    <div style="font-family: Arial, sans-serif; padding: 20px;">
                        <div style="text-align: center; border-bottom: 2px solid #FF1E00; padding-bottom: 20px; margin-bottom: 30px;">
                            <h1 style="color: #FF1E00; font-size: 28px; margin: 0;">Fit 360 - Reporte General de Estadísticas</h1>
                            <p style="font-size: 16px; margin: 5px 0;">Fecha del Reporte: ${new Date().toLocaleString('es-ES')}</p>
                            <p style="font-size: 16px; margin: 5px 0;">Generado por: <?php echo htmlspecialchars($_SESSION['username']); ?></p>
                        </div>
                        
                        <div style="margin-bottom: 30px;">
                            <h2 style="color: #FF1E00; font-size: 20px; border-bottom: 1px solid #ddd; padding-bottom: 5px;">Resumen Ejecutivo</h2>
                            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin: 20px 0;">
                                <div style="background: #f9f9f9; padding: 15px; border-left: 4px solid #FF1E00;">
                                    <div style="font-weight: bold; color: #666; font-size: 12px;">Total de Ventas</div>
                                    <div style="font-size: 18px; font-weight: bold; color: #333;"><?php echo number_format($total_sales); ?></div>
                                </div>
                                <div style="background: #f9f9f9; padding: 15px; border-left: 4px solid #FF1E00;">
                                    <div style="font-weight: bold; color: #666; font-size: 12px;">Ingresos Totales</div>
                                    <div style="font-size: 18px; font-weight: bold; color: #333;">$<?php echo number_format($total_revenue, 2); ?></div>
                                </div>
                                <div style="background: #f9f9f9; padding: 15px; border-left: 4px solid #FF1E00;">
                                    <div style="font-weight: bold; color: #666; font-size: 12px;">Ingresos del Mes</div>
                                    <div style="font-size: 18px; font-weight: bold; color: #333;">$<?php echo number_format($monthly_revenue, 2); ?></div>
                                </div>
                            </div>
                        </div>
                        
                        ${salesSection}
                        ${usersSection}
                        ${subsSection}
                        ${productSalesSection}
                    </div>
                `;
                
                generatePDF(pdfContent, 'reporte_general_fit360', this);
            }, 500); // Esperar 500ms para asegurar que las gráficas estén renderizadas
        });

        // Función auxiliar para generar PDF
        function generatePDF(content, filename, button) {
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = content;
            document.body.appendChild(tempDiv);
            
            const opt = {
                margin: 1,
                filename: filename + '_' + new Date().toISOString().slice(0, 19).replace(/:/g, '-') + '.pdf',
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2 },
                jsPDF: { unit: 'in', format: 'a4', orientation: 'portrait' }
            };
            
            html2pdf().set(opt).from(tempDiv).save().then(() => {
                document.body.removeChild(tempDiv);
                button.innerHTML = '<i class="fas fa-file-pdf"></i> Reporte General';
                button.disabled = false;
                if (typeof showNotification === 'function') {
                    showNotification('PDF generado exitosamente', 'success');
                }
            }).catch(err => {
                console.error('Error generando PDF:', err);
                document.body.removeChild(tempDiv);
                button.innerHTML = '<i class="fas fa-file-pdf"></i> Reporte General';
                button.disabled = false;
                if (typeof showNotification === 'function') {
                    showNotification('Error al generar el PDF', 'error');
                }
            });
        }
    </script>
</body>
</html> 