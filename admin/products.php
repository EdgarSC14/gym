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
                $name = trim($_POST['name']);
                $description = trim($_POST['description']);
                $price = floatval($_POST['price']);
                $old_price = !empty($_POST['old_price']) ? floatval($_POST['old_price']) : null;
                $stock_quantity = intval($_POST['stock_quantity']);
                $category_id = intval($_POST['category_id']);
                $is_featured = isset($_POST['is_featured']) ? 1 : 0;
                
                // Procesar imagen
                $image_url = null;
                if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
                    $upload_dir = '../assets/products/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                    $file_name = uniqid() . '.' . $file_extension;
                    $upload_path = $upload_dir . $file_name;
                    
                    if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                        $image_url = 'assets/products/' . $file_name;
                    }
                }
                
                try {
                    $stmt = $pdo->prepare("INSERT INTO producto (nombre, descripcion, precio, precio_anterior, cantidad_stock, id_categoria, url_imagen, es_destacado) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$name, $description, $price, $old_price, $stock_quantity, $category_id, $image_url, $is_featured]);
                    $message = 'Producto agregado exitosamente';
                } catch (Exception $e) {
                    $error = 'Error al agregar el producto: ' . $e->getMessage();
                }
                break;
                
            case 'edit':
                $product_id = intval($_POST['product_id']);
                $name = trim($_POST['name']);
                $description = trim($_POST['description']);
                $price = floatval($_POST['price']);
                $old_price = !empty($_POST['old_price']) ? floatval($_POST['old_price']) : null;
                $stock_quantity = intval($_POST['stock_quantity']);
                $category_id = intval($_POST['category_id']);
                $is_featured = isset($_POST['is_featured']) ? 1 : 0;
                
                // Procesar nueva imagen si se subió
                $image_url = $_POST['current_image'];
                if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
                    $upload_dir = '../assets/products/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                    $file_name = uniqid() . '.' . $file_extension;
                    $upload_path = $upload_dir . $file_name;
                    
                    if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                        $image_url = 'assets/products/' . $file_name;
                    }
                }
                
                try {
                    $stmt = $pdo->prepare("UPDATE producto SET nombre = ?, descripcion = ?, precio = ?, precio_anterior = ?, cantidad_stock = ?, id_categoria = ?, url_imagen = ?, es_destacado = ? WHERE id_producto = ?");
                    $stmt->execute([$name, $description, $price, $old_price, $stock_quantity, $category_id, $image_url, $is_featured, $product_id]);
                    $message = 'Producto actualizado exitosamente';
                } catch (Exception $e) {
                    $error = 'Error al actualizar el producto: ' . $e->getMessage();
                }
                break;
                
            case 'delete':
                $product_id = intval($_POST['product_id']);
                try {
                    $stmt = $pdo->prepare("UPDATE producto SET esta_activo = 0 WHERE id_producto = ?");
                    $stmt->execute([$product_id]);
                    $message = 'Producto eliminado exitosamente';
                } catch (Exception $e) {
                    $error = 'Error al eliminar el producto: ' . $e->getMessage();
                }
                break;
        }
    }
}

// Manejar eliminación de imagen del producto
if (isset($_GET['delete_image']) && isset($_GET['edit'])) {
    $product_id = intval($_GET['edit']);
    try {
        // Obtener la URL de la imagen antes de eliminarla
        $stmt = $pdo->prepare("SELECT url_imagen FROM producto WHERE id_producto = ?");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch();
        if ($product && $product['url_imagen']) {
            $file_path = '../' . $product['url_imagen'];
            if (file_exists($file_path)) {
                unlink($file_path);
            }
            // Eliminar la referencia en la base de datos
            $stmt = $pdo->prepare("UPDATE producto SET url_imagen = NULL WHERE id_producto = ?");
            $stmt->execute([$product_id]);
            $message = 'Imagen del producto eliminada exitosamente';
        }
    } catch (Exception $e) {
        $error = 'Error al eliminar la imagen del producto: ' . $e->getMessage();
    }
    header("Location: products.php?edit=" . $product_id);
    exit;
}

// Obtener categorías
$categories = $pdo->query("SELECT * FROM categoria_producto WHERE esta_activo = 1")->fetchAll();

// Obtener productos
$products = $pdo->query("
    SELECT p.*, pc.nombre as category_name 
    FROM producto p 
    LEFT JOIN categoria_producto pc ON p.id_categoria = pc.id_categoria 
    WHERE p.esta_activo = 1 
    ORDER BY p.fecha_creacion DESC
")->fetchAll();

// Obtener producto para editar
$edit_product = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM producto WHERE id_producto = ?");
    $stmt->execute([$_GET['edit']]);
    $edit_product = $stmt->fetch();
}

// Estadísticas de productos
$total_products = $pdo->query("SELECT COUNT(*) as total FROM producto WHERE esta_activo = 1")->fetch()['total'];
$featured_products = $pdo->query("SELECT COUNT(*) as total FROM producto WHERE es_destacado = 1 AND esta_activo = 1")->fetch()['total'];
$low_stock = $pdo->query("SELECT COUNT(*) as total FROM producto WHERE cantidad_stock <= 10 AND esta_activo = 1")->fetch()['total'];
$total_value = $pdo->query("SELECT SUM(precio * cantidad_stock) as total FROM producto WHERE esta_activo = 1")->fetch()['total'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Productos - Fit 360</title>
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
                <li class="active">
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
                <h1><i class="fas fa-box"></i> Gestión de Productos</h1>
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

            <!-- Estadísticas de productos -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-box"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($total_products); ?></h3>
                        <p>Total de Productos</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-star"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($featured_products); ?></h3>
                        <p>Productos Destacados</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($low_stock); ?></h3>
                        <p>Stock Bajo</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="stat-info">
                        <h3>$<?php echo number_format($total_value, 2); ?> MXN</h3>
                        <p>Valor Total</p>
                    </div>
                </div>
            </div>

            <!-- Formulario para agregar/editar producto -->
            <div class="admin-form">
                <h2><i class="fas fa-plus"></i> <?php echo $edit_product ? 'Editar Producto' : 'Agregar Nuevo Producto'; ?></h2>
                
                <form method="POST" action="" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="<?php echo $edit_product ? 'edit' : 'add'; ?>">
                    <?php if ($edit_product): ?>
                        <input type="hidden" name="product_id" value="<?php echo $edit_product['id_producto']; ?>">
                        <input type="hidden" name="current_image" value="<?php echo htmlspecialchars($edit_product['url_imagen']); ?>">
                    <?php endif; ?>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="name">Nombre del Producto *</label>
                            <input type="text" id="name" name="name" value="<?php echo $edit_product ? htmlspecialchars($edit_product['nombre']) : ''; ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="category_id">Categoría *</label>
                            <select id="category_id" name="category_id" required>
                                <option value="">Seleccionar categoría</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id_categoria']; ?>" <?php echo ($edit_product && $edit_product['id_categoria'] == $category['id_categoria']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['nombre']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="description">Descripción</label>
                        <textarea id="description" name="description" rows="4"><?php echo $edit_product ? htmlspecialchars($edit_product['descripcion']) : ''; ?></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="price">Precio *</label>
                            <input type="number" id="price" name="price" step="0.01" min="0" value="<?php echo $edit_product ? $edit_product['precio'] : ''; ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="old_price">Precio Anterior</label>
                            <input type="number" id="old_price" name="old_price" step="0.01" min="0" value="<?php echo $edit_product ? $edit_product['precio_anterior'] : ''; ?>">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="stock_quantity">Cantidad en Stock *</label>
                            <input type="number" id="stock_quantity" name="stock_quantity" min="0" value="<?php echo $edit_product ? $edit_product['cantidad_stock'] : '0'; ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="image">Imagen del Producto</label>
                            <input type="file" id="image" name="image" accept="image/*">
                            <?php if ($edit_product && $edit_product['url_imagen']): ?>
                                <div class="current-image" style="position:relative;">
                                    <img src="../<?php echo htmlspecialchars($edit_product['url_imagen']); ?>" alt="Imagen actual" style="max-width: 100px; margin-top: 10px;">
                                    <a href="products.php?delete_image=1&edit=<?php echo $edit_product['id_producto']; ?>" class="delete-gallery-img" style="top:5px;right:5px;" onclick="return confirm('¿Eliminar la imagen del producto?')">&times;</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" id="is_featured" name="is_featured" value="1" <?php echo ($edit_product && $edit_product['es_destacado']) ? 'checked' : ''; ?>>
                            <span class="checkmark"></span>
                            Producto Destacado
                        </label>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn">
                            <i class="fas fa-save"></i>
                            <?php echo $edit_product ? 'Actualizar Producto' : 'Agregar Producto'; ?>
                        </button>
                        <?php if ($edit_product): ?>
                            <a href="products.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i>
                                Cancelar
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Lista de productos -->
            <div class="admin-table">
                <h2><i class="fas fa-list"></i> Lista de Productos</h2>
                
                <div class="search-container">
                    <input type="text" class="search-input" placeholder="Buscar productos..." data-target=".product-row">
                </div>

                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Imagen</th>
                            <th>Nombre</th>
                            <th>Categoría</th>
                            <th>Precio</th>
                            <th>Stock</th>
                            <th>Destacado</th>
                            <th>Fecha</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                            <tr class="product-row">
                                <td><?php echo $product['id_producto']; ?></td>
                                <td>
                                    <?php if ($product['url_imagen']): ?>
                                        <img src="../<?php echo htmlspecialchars($product['url_imagen']); ?>" alt="<?php echo htmlspecialchars($product['nombre']); ?>" style="width: 50px; height: 50px; object-fit: cover; border-radius: 5px;">
                                    <?php else: ?>
                                        <div style="width: 50px; height: 50px; background: #f0f0f0; border-radius: 5px; display: flex; align-items: center; justify-content: center;">
                                            <i class="fas fa-image" style="color: #ccc;"></i>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($product['nombre']); ?></strong>
                                    <?php if ($product['es_destacado']): ?>
                                        <i class="fas fa-star" style="color: var(--accent-color); margin-left: 5px;"></i>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($product['category_name']); ?></td>
                                <td>
                                    <strong>$<?php echo number_format($product['precio'], 2); ?> MXN</strong>
                                    <?php if ($product['precio_anterior']): ?>
                                        <br><small style="text-decoration: line-through; color: #999;">$<?php echo number_format($product['precio_anterior'], 2); ?> MXN</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="<?php echo $product['cantidad_stock'] <= 10 ? 'text-danger' : ($product['cantidad_stock'] <= 50 ? 'text-warning' : 'text-success'); ?>">
                                        <?php echo $product['cantidad_stock']; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($product['es_destacado']): ?>
                                        <i class="fas fa-star" style="color: var(--accent-color);"></i>
                                    <?php else: ?>
                                        <i class="fas fa-star" style="color: #ccc;"></i>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($product['fecha_creacion'])); ?></td>
                                <td class="table-actions">
                                    <a href="?edit=<?php echo $product['id_producto']; ?>" class="btn btn-secondary" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('¿Estás seguro de que quieres eliminar este producto?')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="product_id" value="<?php echo $product['id_producto']; ?>">
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