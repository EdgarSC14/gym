<?php
require_once '../config/database.php';
startSession();

if (!isLoggedIn() || $_SESSION['role'] !== 'administrador') {
    header('Location: ../login.php');
    exit;
}

$pdo = getConnection();
$message = '';
$error = '';

// Procesar subida de archivos
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action']) && $_POST['action'] == 'upload') {
        $upload_type = $_POST['upload_type'];
        $upload_dir = '../assets/' . $upload_type . '/';
        
        // Crear directorio si no existe
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $uploaded_files = [];
        $errors = [];
        
        foreach ($_FILES['files']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['files']['error'][$key] == 0) {
                $file_name = $_FILES['files']['name'][$key];
                $file_size = $_FILES['files']['size'][$key];
                $file_type = $_FILES['files']['type'][$key];
                
                // Validar tipo de archivo
                $allowed_types = [];
                if ($upload_type == 'images') {
                    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                } elseif ($upload_type == 'videos') {
                    $allowed_types = ['video/mp4', 'video/avi', 'video/mov', 'video/wmv'];
                }
                
                if (!in_array($file_type, $allowed_types)) {
                    $errors[] = "Tipo de archivo no permitido: $file_name";
                    continue;
                }
                
                // Validar tamaño (10MB máximo)
                if ($file_size > 10 * 1024 * 1024) {
                    $errors[] = "Archivo demasiado grande: $file_name";
                    continue;
                }
                
                // Generar nombre único
                $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
                $unique_name = uniqid() . '_' . time() . '.' . $file_extension;
                $upload_path = $upload_dir . $unique_name;
                
                if (move_uploaded_file($tmp_name, $upload_path)) {
                    $uploaded_files[] = 'assets/' . $upload_type . '/' . $unique_name;
                } else {
                    $errors[] = "Error al subir: $file_name";
                }
            }
        }
        
        if (!empty($uploaded_files)) {
            $message = 'Archivos subidos exitosamente: ' . count($uploaded_files);
        }
        
        if (!empty($errors)) {
            $error = 'Errores: ' . implode(', ', $errors);
        }
    }
}

// Obtener archivos existentes
function getFiles($directory) {
    $files = [];
    $path = '../assets/' . $directory . '/';
    
    if (is_dir($path)) {
        $items = scandir($path);
        foreach ($items as $item) {
            if ($item != '.' && $item != '..' && !is_dir($path . $item)) {
                $files[] = [
                    'name' => $item,
                    'path' => 'assets/' . $directory . '/' . $item,
                    'size' => filesize($path . $item),
                    'modified' => filemtime($path . $item)
                ];
            }
        }
    }
    
    return $files;
}

$images = getFiles('images');
$videos = getFiles('videos');
$products_images = getFiles('products');
$services_images = getFiles('services');
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="admin-style.css">
    <title>Gestión de Multimedia - Fit 360</title>
    <style>
        .media-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .media-item {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        
        .media-item:hover {
            transform: translateY(-5px);
        }
        
        .media-preview {
            width: 100%;
            height: 150px;
            object-fit: cover;
            background: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .media-info {
            padding: 15px;
        }
        
        .media-name {
            font-weight: 600;
            margin-bottom: 5px;
            word-break: break-all;
        }
        
        .media-size {
            font-size: 12px;
            color: #666;
        }
        
        .upload-area {
            border: 2px dashed #ddd;
            border-radius: 8px;
            padding: 40px;
            text-align: center;
            background: #f9f9f9;
            transition: border-color 0.3s ease;
        }
        
        .upload-area.dragover {
            border-color: #667eea;
            background: #f0f4ff;
        }
        
        .upload-icon {
            font-size: 48px;
            color: #ccc;
            margin-bottom: 15px;
        }
        
        .file-input {
            display: none;
        }
        
        .media-actions {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }
        
        .media-actions button {
            flex: 1;
            padding: 8px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
        }
        
        .copy-btn {
            background: #17a2b8;
            color: white;
        }
        
        .delete-btn {
            background: #dc3545;
            color: white;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <nav class="admin-sidebar">
            <div class="sidebar-header">
                <h2>Fit 360</h2>
                <p>Panel de Administración</p>
            </div>
            
            <ul class="sidebar-menu">
                <li><a href="index.php"><i class='bx bxs-dashboard'></i><span>Dashboard</span></a></li>
                <li><a href="users.php"><i class='bx bxs-user-detail'></i><span>Usuarios</span></a></li>
                <li><a href="products.php"><i class='bx bxs-shopping-bag'></i><span>Productos</span></a></li>
                <li><a href="services.php"><i class='bx bxs-dumbbell'></i><span>Servicios</span></a></li>
                <li><a href="subscriptions.php"><i class='bx bxs-credit-card'></i><span>Suscripciones</span></a></li>
                <li class="active"><a href="media.php"><i class='bx bxs-image'></i><span>Multimedia</span></a></li>
                <li><a href="sales.php"><i class='bx bxs-chart'></i><span>Estadísticas</span></a></li>
                <li><a href="../logout.php"><i class='bx bxs-log-out'></i><span>Cerrar Sesión</span></a></li>
            </ul>
        </nav>

        <main class="admin-main">
            <header class="admin-header">
                <h1>Gestión de Multimedia</h1>
                <div class="header-actions">
                    <button class="btn" onclick="showUploadForm()">
                        <i class='bx bxs-upload'></i>Subir Archivos
                    </button>
                </div>
            </header>

            <?php if ($message): ?>
                <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <!-- Formulario de Subida -->
            <div class="admin-form" id="uploadForm" style="display: none;">
                <h2>Subir Archivos</h2>
                
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="upload">
                    
                    <div class="form-group">
                        <label>Tipo de Archivo</label>
                        <select name="upload_type" required>
                            <option value="images">Imágenes Generales</option>
                            <option value="videos">Videos</option>
                            <option value="products">Imágenes de Productos</option>
                            <option value="services">Imágenes de Servicios</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Seleccionar Archivos</label>
                        <div class="upload-area" id="uploadArea">
                            <div class="upload-icon">
                                <i class='bx bxs-cloud-upload'></i>
                            </div>
                            <p>Arrastra archivos aquí o haz clic para seleccionar</p>
                            <input type="file" name="files[]" multiple class="file-input" id="fileInput" required>
                        </div>
                        <div id="fileList" style="margin-top: 15px;"></div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn">Subir Archivos</button>
                        <button type="button" class="btn btn-secondary" onclick="hideUploadForm()">Cancelar</button>
                    </div>
                </form>
            </div>

            <!-- Estadísticas -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon"><i class='bx bxs-image'></i></div>
                    <div class="stat-info">
                        <h3><?= count($images) ?></h3>
                        <p>Imágenes Generales</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class='bx bxs-video'></i></div>
                    <div class="stat-info">
                        <h3><?= count($videos) ?></h3>
                        <p>Videos</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class='bx bxs-shopping-bag'></i></div>
                    <div class="stat-info">
                        <h3><?= count($products_images) ?></h3>
                        <p>Imágenes de Productos</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class='bx bxs-dumbbell'></i></div>
                    <div class="stat-info">
                        <h3><?= count($services_images) ?></h3>
                        <p>Imágenes de Servicios</p>
                    </div>
                </div>
            </div>

            <!-- Imágenes Generales -->
            <div class="admin-table">
                <div class="table-header">
                    <h2>Imágenes Generales</h2>
                </div>
                <div class="media-grid">
                    <?php foreach ($images as $image): ?>
                        <div class="media-item">
                            <img src="../<?= $image['path'] ?>" alt="<?= $image['name'] ?>" class="media-preview">
                            <div class="media-info">
                                <div class="media-name"><?= htmlspecialchars($image['name']) ?></div>
                                <div class="media-size"><?= number_format($image['size'] / 1024, 1) ?> KB</div>
                                <div class="media-actions">
                                    <button class="copy-btn" onclick="copyPath('<?= $image['path'] ?>')">Copiar Ruta</button>
                                    <button class="delete-btn" onclick="deleteFile('<?= $image['path'] ?>')">Eliminar</button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Videos -->
            <div class="admin-table">
                <div class="table-header">
                    <h2>Videos</h2>
                </div>
                <div class="media-grid">
                    <?php foreach ($videos as $video): ?>
                        <div class="media-item">
                            <video class="media-preview" controls>
                                <source src="../<?= $video['path'] ?>" type="video/mp4">
                                Tu navegador no soporta el elemento video.
                            </video>
                            <div class="media-info">
                                <div class="media-name"><?= htmlspecialchars($video['name']) ?></div>
                                <div class="media-size"><?= number_format($video['size'] / 1024 / 1024, 1) ?> MB</div>
                                <div class="media-actions">
                                    <button class="copy-btn" onclick="copyPath('<?= $video['path'] ?>')">Copiar Ruta</button>
                                    <button class="delete-btn" onclick="deleteFile('<?= $video['path'] ?>')">Eliminar</button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Imágenes de Productos -->
            <div class="admin-table">
                <div class="table-header">
                    <h2>Imágenes de Productos</h2>
                </div>
                <div class="media-grid">
                    <?php foreach ($products_images as $image): ?>
                        <div class="media-item">
                            <img src="../<?= $image['path'] ?>" alt="<?= $image['name'] ?>" class="media-preview">
                            <div class="media-info">
                                <div class="media-name"><?= htmlspecialchars($image['name']) ?></div>
                                <div class="media-size"><?= number_format($image['size'] / 1024, 1) ?> KB</div>
                                <div class="media-actions">
                                    <button class="copy-btn" onclick="copyPath('<?= $image['path'] ?>')">Copiar Ruta</button>
                                    <button class="delete-btn" onclick="deleteFile('<?= $image['path'] ?>')">Eliminar</button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Imágenes de Servicios -->
            <div class="admin-table">
                <div class="table-header">
                    <h2>Imágenes de Servicios</h2>
                </div>
                <div class="media-grid">
                    <?php foreach ($services_images as $image): ?>
                        <div class="media-item">
                            <img src="../<?= $image['path'] ?>" alt="<?= $image['name'] ?>" class="media-preview">
                            <div class="media-info">
                                <div class="media-name"><?= htmlspecialchars($image['name']) ?></div>
                                <div class="media-size"><?= number_format($image['size'] / 1024, 1) ?> KB</div>
                                <div class="media-actions">
                                    <button class="copy-btn" onclick="copyPath('<?= $image['path'] ?>')">Copiar Ruta</button>
                                    <button class="delete-btn" onclick="deleteFile('<?= $image['path'] ?>')">Eliminar</button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </main>
    </div>

    <script src="admin-script.js"></script>
    <script>
        function showUploadForm() {
            document.getElementById('uploadForm').style.display = 'block';
        }
        
        function hideUploadForm() {
            document.getElementById('uploadForm').style.display = 'none';
        }
        
        function copyPath(path) {
            navigator.clipboard.writeText(path).then(() => {
                alert('Ruta copiada al portapapeles: ' + path);
            });
        }
        
        function deleteFile(path) {
            if (confirm('¿Estás seguro de que quieres eliminar este archivo?')) {
                // Implementar eliminación de archivo
                alert('Archivo eliminado: ' + path);
            }
        }
        
        // Drag and drop functionality
        const uploadArea = document.getElementById('uploadArea');
        const fileInput = document.getElementById('fileInput');
        const fileList = document.getElementById('fileList');
        
        uploadArea.addEventListener('click', () => {
            fileInput.click();
        });
        
        uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadArea.classList.add('dragover');
        });
        
        uploadArea.addEventListener('dragleave', () => {
            uploadArea.classList.remove('dragover');
        });
        
        uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
            fileInput.files = e.dataTransfer.files;
            updateFileList();
        });
        
        fileInput.addEventListener('change', updateFileList);
        
        function updateFileList() {
            fileList.innerHTML = '';
            for (let file of fileInput.files) {
                const div = document.createElement('div');
                div.textContent = `${file.name} (${(file.size / 1024 / 1024).toFixed(2)} MB)`;
                fileList.appendChild(div);
            }
        }
    </script>
</body>
</html> 