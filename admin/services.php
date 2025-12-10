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
                $service_id = intval($_POST['service_id']);
                $name = trim($_POST['name']);
                $description = trim($_POST['description']);
                $benefits = trim($_POST['benefits']);
                $duration_minutes = intval($_POST['duration_minutes']);
                
                // Validar campos obligatorios
                if (empty($description)) {
                    $error = 'La descripción es obligatoria';
                } elseif (empty($benefits)) {
                    $error = 'Los beneficios son obligatorios';
                } else {
                    // Procesar nueva imagen principal si se subió
                    $image_url = $_POST['current_image'];
                    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
                        $upload_dir = '../assets/services/';
                        if (!is_dir($upload_dir)) {
                            mkdir($upload_dir, 0777, true);
                        }
                        
                        $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                        $file_name = uniqid() . '.' . $file_extension;
                        $upload_path = $upload_dir . $file_name;
                        
                        if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                            $image_url = 'assets/services/' . $file_name;
                        }
                    }
                    
                    // Procesar nuevo video si se subió
                    $video_url = $_POST['current_video'];
                    if (isset($_FILES['video']) && $_FILES['video']['error'] == 0) {
                        $upload_dir = '../assets/videos/';
                        if (!is_dir($upload_dir)) {
                            mkdir($upload_dir, 0777, true);
                        }
                        
                        $file_extension = pathinfo($_FILES['video']['name'], PATHINFO_EXTENSION);
                        $file_name = uniqid() . '.' . $file_extension;
                        $upload_path = $upload_dir . $file_name;
                        
                        if (move_uploaded_file($_FILES['video']['tmp_name'], $upload_path)) {
                            $video_url = 'assets/videos/' . $file_name;
                        }
                    }
                    
                    try {
                        $stmt = $pdo->prepare("UPDATE servicio SET nombre = ?, descripcion = ?, beneficios = ?, duracion_minutos = ?, url_imagen = ?, url_video = ? WHERE id_servicio = ?");
                        $stmt->execute([$name, $description, $benefits, $duration_minutes, $image_url, $video_url, $service_id]);
                        
                        // Procesar nuevas imágenes de galería
                        if (isset($_FILES['gallery_images']) && !empty($_FILES['gallery_images']['name'][0])) {
                            $gallery_files = $_FILES['gallery_images'];
                            $upload_dir = '../assets/services/';
                            
                            for ($i = 0; $i < count($gallery_files['name']); $i++) {
                                if ($gallery_files['error'][$i] === UPLOAD_ERR_OK) {
                                    $file_tmp_name = $gallery_files['tmp_name'][$i];
                                    $file_extension = pathinfo($gallery_files['name'][$i], PATHINFO_EXTENSION);
                                    $file_name = uniqid() . '_gallery_' . $i . '.' . $file_extension;
                                    $upload_path = $upload_dir . $file_name;
                                    
                                    if (move_uploaded_file($file_tmp_name, $upload_path)) {
                                        $gallery_image_url = 'assets/services/' . $file_name;
                                        $stmt_gallery = $pdo->prepare("INSERT INTO imagen_servicio (id_servicio, url_imagen) VALUES (?, ?)");
                                        $stmt_gallery->execute([$service_id, $gallery_image_url]);
                                    }
                                }
                            }
                        }
                        
                        $message = 'Servicio actualizado exitosamente';
                    } catch (Exception $e) {
                        $error = 'Error al actualizar el servicio: ' . $e->getMessage();
                    }
                }
                break;
                
            case 'delete':
                $service_id = intval($_POST['service_id']);
                try {
                    $stmt = $pdo->prepare("UPDATE servicio SET esta_activo = 0 WHERE id_servicio = ?");
                    $stmt->execute([$service_id]);
                    $message = 'Servicio eliminado exitosamente';
                } catch (Exception $e) {
                    $error = 'Error al eliminar el servicio: ' . $e->getMessage();
                }
                break;
        }
    }
}

// Manejar eliminación de imagen de galería
if (isset($_GET['delete_gallery_image']) && isset($_GET['edit'])) {
    $image_id = intval($_GET['delete_gallery_image']);
    $service_id = intval($_GET['edit']);
    
    try {
        // Obtener la URL de la imagen antes de eliminarla
        $stmt = $pdo->prepare("SELECT url_imagen FROM imagen_servicio WHERE id_imagen = ? AND id_servicio = ?");
        $stmt->execute([$image_id, $service_id]);
        $image = $stmt->fetch();
        
        if ($image) {
            // Eliminar el archivo físico
            $file_path = '../' . $image['url_imagen'];
            if (file_exists($file_path)) {
                unlink($file_path);
            }
            
            // Eliminar de la base de datos
            $stmt = $pdo->prepare("DELETE FROM imagen_servicio WHERE id_imagen = ?");
            $stmt->execute([$image_id]);
            
            $message = 'Imagen de galería eliminada exitosamente';
        }
    } catch (Exception $e) {
        $error = 'Error al eliminar la imagen: ' . $e->getMessage();
    }
    
    // Redirigir de vuelta al formulario de edición
    header("Location: services.php?edit=" . $service_id);
    exit;
}

// Manejar eliminación de imagen principal
if (isset($_GET['delete_main_image']) && isset($_GET['edit'])) {
    $service_id = intval($_GET['edit']);
    try {
        // Obtener la URL de la imagen antes de eliminarla
        $stmt = $pdo->prepare("SELECT url_imagen FROM servicio WHERE id_servicio = ?");
        $stmt->execute([$service_id]);
        $service = $stmt->fetch();
        if ($service && $service['url_imagen']) {
            $file_path = '../' . $service['url_imagen'];
            if (file_exists($file_path)) {
                unlink($file_path);
            }
            // Eliminar la referencia en la base de datos
            $stmt = $pdo->prepare("UPDATE servicio SET url_imagen = NULL WHERE id_servicio = ?");
            $stmt->execute([$service_id]);
            $message = 'Imagen principal eliminada exitosamente';
        }
    } catch (Exception $e) {
        $error = 'Error al eliminar la imagen principal: ' . $e->getMessage();
    }
    header("Location: services.php?edit=" . $service_id);
    exit;
}

// Manejar eliminación de video principal
if (isset($_GET['delete_video']) && isset($_GET['edit'])) {
    $service_id = intval($_GET['edit']);
    try {
        // Obtener la URL del video antes de eliminarlo
        $stmt = $pdo->prepare("SELECT url_video FROM servicio WHERE id_servicio = ?");
        $stmt->execute([$service_id]);
        $service = $stmt->fetch();
        if ($service && $service['url_video']) {
            $file_path = '../' . $service['url_video'];
            if (file_exists($file_path)) {
                unlink($file_path);
            }
            // Eliminar la referencia en la base de datos
            $stmt = $pdo->prepare("UPDATE servicio SET url_video = NULL WHERE id_servicio = ?");
            $stmt->execute([$service_id]);
            $message = 'Video eliminado exitosamente';
        }
    } catch (Exception $e) {
        $error = 'Error al eliminar el video: ' . $e->getMessage();
    }
    header("Location: services.php?edit=" . $service_id);
    exit;
}

// Obtener servicios
$services = $pdo->query("SELECT * FROM servicio WHERE esta_activo = 1 ORDER BY fecha_creacion DESC")->fetchAll();

// Obtener servicio para editar
$edit_service = null;
$gallery_images = [];
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM servicio WHERE id_servicio = ?");
    $stmt->execute([$_GET['edit']]);
    $edit_service = $stmt->fetch();

    if ($edit_service) {
        $stmt_gallery = $pdo->prepare("SELECT * FROM imagen_servicio WHERE id_servicio = ?");
        $stmt_gallery->execute([$edit_service['id_servicio']]);
        $gallery_images = $stmt_gallery->fetchAll();
    }
}

// Estadísticas de servicios
$total_services = $pdo->query("SELECT COUNT(*) as total FROM servicio WHERE esta_activo = 1")->fetch()['total'];
$avg_duration = $pdo->query("SELECT AVG(duracion_minutos) as total FROM servicio WHERE esta_activo = 1")->fetch()['total'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Servicios - Fit 360</title>
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
                <li class="active">
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
                <h1><i class="fas fa-dumbbell"></i> Gestión de Servicios</h1>
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

            <!-- Estadísticas de servicios -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-dumbbell"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($total_services); ?></h3>
                        <p>Total de Servicios</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo round($avg_duration); ?> min</h3>
                        <p>Duración Promedio</p>
                    </div>
                </div>
            </div>

            <!-- Formulario para editar servicio -->
            <?php if ($edit_service): ?>
            <div class="admin-form">
                <h2><i class="fas fa-edit"></i> Editar Servicio</h2>
                
                <form method="POST" action="services.php" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="service_id" value="<?php echo $edit_service['id_servicio']; ?>">
                    <input type="hidden" name="current_image" value="<?php echo htmlspecialchars($edit_service['url_imagen']); ?>">
                    <input type="hidden" name="current_video" value="<?php echo htmlspecialchars($edit_service['url_video']); ?>">

                    <div class="form-row">
                        <div class="form-group">
                            <label for="name">Nombre del Servicio *</label>
                            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($edit_service['nombre']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="duration_minutes">Duración (minutos) *</label>
                            <input type="number" id="duration_minutes" name="duration_minutes" min="1" value="<?php echo $edit_service['duracion_minutos']; ?>" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="description">Descripción *</label>
                        <textarea id="description" name="description" rows="3" required><?php echo htmlspecialchars($edit_service['descripcion']); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="benefits">Beneficios (uno por línea) *</label>
                        <textarea id="benefits" name="benefits" rows="4" placeholder="Lista los beneficios del servicio, uno por línea" required><?php echo htmlspecialchars($edit_service['beneficios']); ?></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="image">Imagen Principal</label>
                            <input type="file" id="image" name="image" accept="image/*">
                            <?php if ($edit_service['url_imagen']): ?>
                                <div class="current-image" style="position:relative;">
                                    <img src="../<?php echo htmlspecialchars($edit_service['url_imagen']); ?>" alt="Imagen actual" style="max-width: 100px; margin-top: 10px;">
                                    <a href="services.php?delete_main_image=1&edit=<?php echo $edit_service['id_servicio']; ?>" class="delete-gallery-img" style="top:5px;right:5px;" onclick="return confirm('¿Eliminar la imagen principal?')">&times;</a>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="form-group">
                            <label for="video">Video</label>
                            <input type="file" id="video" name="video" accept="video/*">
                            <?php if ($edit_service['url_video']): ?>
                                <div class="current-video" style="position:relative;">
                                    <video src="../<?php echo htmlspecialchars($edit_service['url_video']); ?>" controls style="max-width: 100px; margin-top: 10px;"></video>
                                    <a href="services.php?delete_video=1&edit=<?php echo $edit_service['id_servicio']; ?>" class="delete-gallery-img" style="top:5px;right:5px;" onclick="return confirm('¿Eliminar el video?')">&times;</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="gallery_images">Imágenes de Galería (múltiples)</label>
                        <input type="file" id="gallery_images" name="gallery_images[]" accept="image/*" multiple>
                    </div>

                    <?php if (!empty($gallery_images)): ?>
                    <div class="form-group">
                        <label>Galería Actual</label>
                        <div class="current-gallery">
                            <?php foreach ($gallery_images as $img): ?>
                            <div class="gallery-item">
                                <img src="../<?php echo htmlspecialchars($img['url_imagen']); ?>" alt="Galería">
                                <a href="services.php?delete_gallery_image=<?php echo $img['id_imagen']; ?>&edit=<?php echo $edit_service['id_servicio']; ?>" class="delete-gallery-img" onclick="return confirm('¿Eliminar esta imagen?')">&times;</a>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="form-actions">
                        <button type="submit" class="btn">
                            <i class="fas fa-save"></i>
                            Actualizar Servicio
                        </button>
                        <a href="services.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i>
                            Cancelar
                        </a>
                    </div>
                </form>
            </div>
            <?php endif; ?>

            <!-- Lista de servicios -->
            <div class="admin-table">
                <h2><i class="fas fa-list"></i> Lista de Servicios</h2>
                
                <div class="search-container">
                    <input type="text" class="search-input" placeholder="Buscar servicios..." data-target=".service-row">
                </div>

                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Imagen</th>
                            <th>Video</th>
                            <th>Nombre</th>
                            <th>Descripción</th>
                            <th>Duración</th>
                            <th>Fecha</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($services as $service): ?>
                            <tr class="service-row">
                                <td><?php echo $service['id_servicio']; ?></td>
                                <td>
                                    <?php if ($service['url_imagen']): ?>
                                        <img src="../<?php echo htmlspecialchars($service['url_imagen']); ?>" alt="<?php echo htmlspecialchars($service['nombre']); ?>" style="width: 50px; height: 50px; object-fit: cover; border-radius: 5px;">
                                    <?php else: ?>
                                        <div style="width: 50px; height: 50px; background: #f0f0f0; border-radius: 5px; display: flex; align-items: center; justify-content: center;">
                                            <i class="fas fa-image" style="color: #ccc;"></i>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($service['url_video']): ?>
                                        <div style="width: 50px; height: 50px; background: #e3f2fd; border-radius: 5px; display: flex; align-items: center; justify-content: center; border: 2px solid #2196f3;">
                                            <i class="fas fa-video" style="color: #2196f3;"></i>
                                        </div>
                                    <?php else: ?>
                                        <div style="width: 50px; height: 50px; background: #f0f0f0; border-radius: 5px; display: flex; align-items: center; justify-content: center;">
                                            <i class="fas fa-video-slash" style="color: #ccc;"></i>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td><strong><?php echo htmlspecialchars($service['nombre']); ?></strong></td>
                                <td><?php echo htmlspecialchars(substr($service['descripcion'], 0, 50)) . (strlen($service['descripcion']) > 50 ? '...' : ''); ?></td>
                                <td><?php echo $service['duracion_minutos']; ?> min</td>
                                <td><?php echo date('d/m/Y', strtotime($service['fecha_creacion'])); ?></td>
                                <td class="table-actions">
                                    <a href="?edit=<?php echo $service['id_servicio']; ?>" class="btn btn-secondary" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('¿Estás seguro de que quieres eliminar este servicio?')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="service_id" value="<?php echo $service['id_servicio']; ?>">
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
        </div>
    </div>

    <script src="admin-script.js"></script>
</body>
</html> 