<?php
require_once 'config/database.php';
startSession();

// Verificar si el usuario está logueado
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Obtener el ID del servicio desde la URL
$serviceId = $_GET['id'] ?? null;

if (!$serviceId) {
    header('Location: index.php#services');
    exit;
}

// Obtener datos del servicio desde la base de datos
$pdo = getConnection();
$stmt = $pdo->prepare("SELECT * FROM servicio WHERE id_servicio = ? AND esta_activo = 1");
$stmt->execute([$serviceId]);
$service = $stmt->fetch();

if (!$service) {
    header('Location: index.php#services');
    exit;
}

// Obtener imágenes de la galería
$stmt_gallery = $pdo->prepare("SELECT * FROM imagen_servicio WHERE id_servicio = ?");
$stmt_gallery->execute([$serviceId]);
$gallery_images = $stmt_gallery->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8"> 
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'> 
    <link rel="stylesheet" href="style.css">     
    <title>Fit 360 - <?= htmlspecialchars($service['nombre']) ?></title>
</head>
<body>
    <header>
        <a href="index.php#home" class="logo">Fit <span>360</span></a>
        <div class='bx bx-menu' id="menu-icon"></div>
        <ul class="navbar">
            <li><a href="index.php#home">Inicio</a></li>
            <li><a href="index.php#services">Servicios</a></li>
        </ul>
        
        <div class="top-btn">
            <?php if(isLoggedIn()): ?>
                <a href="profile.php" class="username-link">
                    <i class='bx bx-user'></i>
                    <span><?= htmlspecialchars($_SESSION['username']) ?></span>
                </a>
                <a href="logout.php" class="nav-btn">Cerrar Sesión</a>
            <?php else: ?>
                <a href="login.php" class="nav-btn">Iniciar Sesión</a>
            <?php endif; ?>
        </div>
    </header>

    <section class="service-detail-section">
        <div class="service-detail-container">
            <div class="service-main-content">
                <div class="service-info">
                    <h1><?= htmlspecialchars($service['nombre']) ?></h1>
                    
                    <div class="service-description">
                        <h2>Descripción del Servicio</h2>
                        <p><?= nl2br(htmlspecialchars($service['descripcion'])) ?></p>
                        
                        <?php if (!empty($service['beneficios'])): ?>
                        <div class="service-benefits">
                            <h3>Beneficios:</h3>
                            <ul class="service-features">
                                <?php 
                                $benefits = explode("\n", trim($service['beneficios']));
                                foreach($benefits as $benefit): 
                                    if(!empty(trim($benefit))): ?>
                                    <li><i class='bx bx-check'></i> <?= htmlspecialchars(trim($benefit)) ?></li>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php endif; ?>
                        
                        <div class="service-actions">
                            <a href="index.php#services" class="btn-secondary">Ver Más Servicios</a>
                        </div>
                    </div>
                </div>

                <?php if($service['url_video']): ?>
                <div class="service-video">
                    <iframe 
                        src="<?= htmlspecialchars($service['url_video']) ?>" 
                        frameborder="0" 
                        allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" 
                        allowfullscreen>
                    </iframe>
                </div>
                <?php endif; ?>
            </div>

            <?php if (!empty($gallery_images)): ?>
            <div class="service-gallery">
                <h2>Galería del Servicio</h2>
                <div class="gallery-grid">
                    <?php foreach($gallery_images as $img): ?>
                        <a href="<?= htmlspecialchars($img['url_imagen']) ?>" data-lightbox="service-gallery">
                            <img src="<?= htmlspecialchars($img['url_imagen']) ?>" alt="<?= htmlspecialchars($service['nombre']) ?>">
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Botón flotante de inicio -->
    <a href="index.php#home" class="home-floating-btn">
        <i class='bx bx-chevron-up'></i>
        <span>Inicio</span>
    </a>

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
        // Función para agregar al carrito
        function addToCart(itemId, type) {
            // Aquí puedes implementar la lógica del carrito
            alert('Servicio agregado al carrito');
        }
    </script>
</body>
</html>