<?php
require_once 'config/database.php';

function updateProductRatings() {
    $pdo = getConnection();
    
    try {
        // Obtener todos los productos que tienen comentarios
        $stmt = $pdo->prepare("
            SELECT DISTINCT p.id_producto, p.nombre
            FROM producto p
            INNER JOIN reseña_producto pr ON p.id_producto = pr.id_producto
            WHERE p.esta_activo = 1
        ");
        $stmt->execute();
        $products = $stmt->fetchAll();
        
        foreach ($products as $product) {
            // Calcular el rating promedio para cada producto
            $stmt = $pdo->prepare("
                SELECT AVG(calificacion) as avg_rating, COUNT(*) as total_reviews
                FROM reseña_producto 
                WHERE id_producto = ?
            ");
            $stmt->execute([$product['id_producto']]);
            $ratingData = $stmt->fetch();
            
            if ($ratingData && $ratingData['avg_rating'] !== null) {
                $avgRating = round($ratingData['avg_rating'], 2);
                
                // Actualizar el rating del producto
                $updateStmt = $pdo->prepare("
                    UPDATE producto 
                    SET calificacion = ? 
                    WHERE id_producto = ?
                ");
                $updateStmt->execute([$avgRating, $product['id_producto']]);
                
                echo "Producto '{$product['nombre']}' actualizado: Rating promedio = {$avgRating} ({$ratingData['total_reviews']} reseñas)\n";
            }
        }
        
        // Para productos sin comentarios, establecer rating en 0
        $stmt = $pdo->prepare("
            UPDATE producto p
            LEFT JOIN reseña_producto pr ON p.id_producto = pr.id_producto
            SET p.calificacion = 0
            WHERE pr.id_producto IS NULL AND p.esta_activo = 1
        ");
        $stmt->execute();
        
        echo "Actualización de calificaciones completada.\n";
        
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
}

// Ejecutar la actualización si se llama directamente
if (basename(__FILE__) == basename($_SERVER['SCRIPT_NAME'])) {
    updateProductRatings();
}
?> 