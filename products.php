<?php
require_once 'config/database.php';
startSession();

// Obtener categorías y productos desde la base de datos
$pdo = getConnection();

// Obtener categorías
$categories = $pdo->query("SELECT * FROM categoria_producto WHERE esta_activo = 1")->fetchAll();

// Obtener todos los productos activos
$products = $pdo->query("SELECT p.*, c.nombre as nombre_categoria FROM producto p 
                        LEFT JOIN categoria_producto c ON p.id_categoria = c.id_categoria 
                        WHERE p.esta_activo = 1 
                        ORDER BY p.es_destacado DESC, p.nombre ASC")->fetchAll();

// Filtrar por categoría si se especifica
$selectedCategory = $_GET['category'] ?? null;
if ($selectedCategory) {
    $products = $pdo->prepare("SELECT p.*, c.nombre as nombre_categoria FROM producto p 
                              LEFT JOIN categoria_producto c ON p.id_categoria = c.id_categoria 
                              WHERE p.esta_activo = 1 AND p.id_categoria = ? 
                              ORDER BY p.es_destacado DESC, p.nombre ASC");
    $products->execute([$selectedCategory]);
    $products = $products->fetchAll();
}

// Convertir productos a JSON para JavaScript
$productsJson = json_encode($products);

// Obtener la dirección del usuario si está logueado
$user_address = null;
if (isLoggedIn()) {
    $stmt = $pdo->prepare("SELECT direccion FROM usuario WHERE id_usuario = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user_address = $stmt->fetchColumn();
}

// Obtener métodos de pago del usuario
$user_payment_methods = [];
if (isLoggedIn()) {
    $stmt = $pdo->prepare("
        SELECT id_metodo_pago, tipo_tarjeta, numero_tarjeta, fecha_vencimiento, nombre_titular, es_predeterminado 
        FROM metodo_pago_usuario 
        WHERE id_usuario = ? AND esta_activo = 1 
        ORDER BY es_predeterminado DESC, fecha_creacion DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $user_payment_methods = $stmt->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="style.css">
    <title>Fit 360 - Catálogo de Productos</title>
</head>
<body>
    <header>
        <a href="index.php#home" class="logo">Fit <span>360</span></a>
        <div class='bx bx-menu' id="menu-icon"></div>
        <ul class="navbar">
            <li><a href="index.php#home">Inicio</a></li>
            <li><a href="index.php#services">Servicios</a></li>
            <li><a href="index.php#about">Acerca de Nosotros</a></li>
            <li><a href="index.php#plans">Suscripciones</a></li>
            <li><a href="index.php#review">Productos</a></li>
        </ul>
        
        <!-- Carrito de compras: mover aquí, justo después del navbar y antes de top-btn -->
        <div class="nav-cart">
            <i class='bx bx-cart' id="cart-icon" onclick="toggleCart()"></i>
            <span class="cart-count" id="cart-count">0</span>
        </div>
        
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

    <section class="products-section">
        <div class="products-container">
            <h1 class="products-heading">Catálogo <span>Completo</span></h1>
            <p class="products-subtitle">Descubre nuestra amplia gama de productos para optimizar tu rendimiento físico</p>
            
            <!-- Filtros por categoría -->
            <div class="category-filters">
                <a href="products.php" class="filter-btn <?= !$selectedCategory ? 'active' : '' ?>">Todos</a>
                <?php foreach($categories as $category): ?>
                    <a href="products.php?category=<?= $category['id_categoria'] ?>" 
                       class="filter-btn <?= $selectedCategory == $category['id_categoria'] ? 'active' : '' ?>">
                        <?= htmlspecialchars($category['nombre']) ?>
                    </a>
                <?php endforeach; ?>
            </div>
            
            <div class="products-grid">
                <?php foreach($products as $product): ?>
                <div class="product-card" onclick="showProductDetails(<?= $product['id_producto'] ?>)">
                    <div class="product-image">
                        <img src="<?= $product['url_imagen'] ?: 'assets/image1.png' ?>" alt="<?= htmlspecialchars($product['nombre']) ?>">
                        <?php if($product['es_destacado']): ?>
                            <div class="product-badge">Destacado</div>
                        <?php endif; ?>
                    </div>
                    <div class="product-info">
                        <h3><?= htmlspecialchars($product['nombre']) ?></h3>
                        <div class="product-category"><?= htmlspecialchars($product['nombre_categoria']) ?></div>
                        <div class="product-rating">
                            <?php 
                            $rating = $product['calificacion'] ?? 0;
                            $fullStars = floor($rating);
                            $hasHalfStar = ($rating - $fullStars) >= 0.5;
                            
                            for($i = 1; $i <= 5; $i++): 
                                if ($i <= $fullStars): ?>
                                    <i class='bx bxs-star filled'></i>
                                <?php elseif ($i == $fullStars + 1 && $hasHalfStar): ?>
                                    <i class='bx bxs-star-half filled'></i>
                                <?php else: ?>
                                    <i class='bx bx-star'></i>
                                <?php endif; ?>
                            <?php endfor; ?>
                            <span>(<?= number_format($rating, 1) ?>/5)</span>
                        </div>
                        <div class="product-price">
                            <span class="current-price">$<?= number_format($product['precio'], 2) ?> MXN</span>
                            <?php if($product['precio_anterior']): ?>
                                <span class="old-price">$<?= number_format($product['precio_anterior'], 2) ?> MXN</span>
                            <?php endif; ?>
                        </div>
                        <div class="stock-info">
                            <span class="stock-label">Stock:</span>
                            <span class="stock-amount <?= $product['cantidad_stock'] > 0 ? 'in-stock' : 'out-of-stock' ?>">
                                <?= $product['cantidad_stock'] > 0 ? $product['cantidad_stock'] . ' disponibles' : 'Agotado' ?>
                            </span>
                        </div>
                        <p><?= htmlspecialchars($product['descripcion']) ?></p>
                        <button class="btn add-to-cart-btn" onclick="event.stopPropagation(); addToCart(<?= $product['id_producto'] ?>, 'product', this)" <?= $product['cantidad_stock'] <= 0 ? 'disabled style="background:#ccc;cursor:not-allowed;"' : '' ?>>
                            <?= $product['cantidad_stock'] <= 0 ? 'Sin Stock' : 'Agregar al Carrito' ?>
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Product Modal -->
    <div id="productModal" class="product-modal">
        <div class="product-modal-content">
            <div class="product-modal-header">
                <button class="close-modal">&times;</button>
            </div>
            <div class="product-modal-body">
                <img id="modalProductImage" class="product-modal-image" src="" alt="Product Image">
                
                <div class="product-modal-info">
                    <h2 id="modalProductName" class="product-modal-name"></h2>
                    <div id="modalProductRating" class="product-modal-rating"></div>
                    <div class="product-modal-price">
                        <span id="modalProductPrice"></span>
                        <span id="modalOldPrice" class="product-modal-old-price"></span>
                    </div>
                    <p id="modalProductDescription" class="product-modal-description"></p>
                </div>
                
                <div class="product-comments" id="modalProductComments">
                    <h3>Comentarios de Clientes</h3>
                    <div class="comments-list" id="commentsList">
                        <!-- Comments will be dynamically loaded here -->
                    </div>
                    <div class="add-comment">
                        <h4>Agregar Comentario</h4>
                        <div class="comment-form">
                            <div class="rating-input">
                                <span>Calificación:</span>
                                <div class="stars">
                                    <i class='bx bx-star' data-rating="1"></i>
                                    <i class='bx bx-star' data-rating="2"></i>
                                    <i class='bx bx-star' data-rating="3"></i>
                                    <i class='bx bx-star' data-rating="4"></i>
                                    <i class='bx bx-star' data-rating="5"></i>
                                </div>
                            </div>
                            <textarea id="commentText" placeholder="Escribe tu comentario aquí..." rows="3"></textarea>
                            <button class="btn" onclick="addComment()">Publicar Comentario</button>
                        </div>
                    </div>
                </div>
                
                <div class="product-actions">
                    <a href="#" class="btn" id="modalBuyButton">Comprar Ahora</a>
                    <button class="btn-secondary" onclick="closeProductModal()">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Shopping Cart Modal -->
    <div id="cartModal" class="cart-modal">
        <div class="cart-modal-content">
            <div class="cart-header">
                <h2><i class='bx bx-cart'></i> Carrito de Compras</h2>
                <span class="close-cart" onclick="toggleCart()">&times;</span>
            </div>
            
            <div class="cart-body">
                <div id="cart-items" class="cart-items">
                    <!-- Cart items will be dynamically added here -->
                </div>
                
                <div class="cart-summary">
                    <div class="cart-total">
                        <span>Subtotal:</span>
                        <span id="cart-subtotal">$0.00 MXN</span>
                    </div>
                    <div class="cart-total">
                        <span>Envío:</span>
                        <span id="cart-shipping">$0.00 MXN</span>
                    </div>
                    <div class="cart-total">
                        <span>Total:</span>
                        <span id="cart-total">$0.00 MXN</span>
                    </div>
                </div>
                
                <div class="cart-actions">
                    <button class="btn" onclick="proceedToCheckout()">Proceder al Pago</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Checkout Modal -->
    <div id="checkoutModal" class="checkout-modal">
        <div class="checkout-modal-content">
            <div class="checkout-header">
                <h2><i class='bx bx-credit-card'></i> Finalizar Compra</h2>
                <span class="close-checkout" onclick="closeCheckout()">&times;</span>
            </div>
            
            <div class="checkout-body">
                <div class="checkout-items" id="checkout-items">
                    <!-- Checkout items will be dynamically added here -->
                </div>
                
                <div class="checkout-total">
                    <span>Total a Pagar:</span>
                    <span id="checkout-total">$0.00 MXN</span>
                </div>
                
                <form id="payment-form" class="payment-form">
                    <!-- Opción de método de pago existente -->
                    <?php if (!empty($user_payment_methods)): ?>
                    <div class="form-group">
                        <label>Método de Pago</label>
                        <div class="payment-method-selector">
                            <label class="radio-option">
                                <input type="radio" name="payment_option" value="existing" checked>
                                <span>Elegir método de pago</span>
                            </label>
                        </div>
                        
                        <div id="existing-payment-methods" class="existing-methods">
                            <select id="selected-payment-method" name="selected_payment_method">
                                <?php foreach($user_payment_methods as $method): ?>
                                    <option value="<?= $method['id_metodo_pago'] ?>" <?= $method['es_predeterminado'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($method['tipo_tarjeta']) ?> - ****<?= substr(preg_replace('/\s/', '', $method['numero_tarjeta']), -4) ?>
                                        <?= $method['es_predeterminado'] ? ' (Predeterminado)' : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            
                            <!-- Campo CVV para método existente -->
                            <div class="form-group" style="margin-top: 15px;">
                                <label for="existing-cvv">CVV</label>
                                <input type="text" id="existing-cvv" name="existing_cvv" placeholder="123" maxlength="4">
                                <small style="color: #666; font-size: 12px;">Ingresa el código de seguridad de tu tarjeta</small>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <button type="submit" class="btn">Confirmar Pago</button>
                </form>
            </div>
        </div>
    </div>

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
        // Product modal functions
        function showProductDetails(productId) {
            const product = productData[productId];
            if (!product) {
                console.error('Product not found:', productId);
                return;
            }

            currentProductId = productId;

            document.getElementById('modalProductName').textContent = product.name;
            // Mostrar rating
            const rating = product.rating || 0;
            let stars = '';
            const fullStars = Math.floor(rating);
            const hasHalfStar = (rating - fullStars) >= 0.5;
            for(let i = 1; i <= 5; i++) {
                if (i <= fullStars) {
                    stars += "<i class='bx bxs-star filled'></i>";
                } else if (i === fullStars + 1 && hasHalfStar) {
                    stars += "<i class='bx bxs-star-half filled'></i>";
                } else {
                    stars += "<i class='bx bx-star'></i>";
                }
            }
            stars += `<span>(${parseFloat(rating).toFixed(1)}/5)</span>`;
            document.getElementById('modalProductRating').innerHTML = stars;
            
            document.getElementById('modalProductPrice').textContent = `$${product.price.toFixed(2)} MXN`;
            
            const oldPriceElement = document.getElementById('modalOldPrice');
            if (product.oldPrice) {
                oldPriceElement.textContent = `$${product.oldPrice.toFixed(2)} MXN`;
                oldPriceElement.style.display = 'inline';
            } else {
                oldPriceElement.style.display = 'none';
            }
            
            document.getElementById('modalProductDescription').textContent = product.description;
            document.getElementById('modalProductImage').src = product.image;
            document.getElementById('modalBuyButton').onclick = () => addToCart(productId, 'product', document.getElementById('modalBuyButton'));

            // Load comments
            loadComments(productId);

            // Reset comment form
            document.getElementById('commentText').value = '';
            selectedRating = 0;
            updateStarDisplay();

            document.getElementById('productModal').style.display = 'flex';
        }

        function closeProductModal() {
            document.getElementById('productModal').style.display = 'none';
        }

        // Comments functionality
        function loadComments(productId) {
            const commentsList = document.getElementById('commentsList');
            commentsList.innerHTML = '<p>Cargando comentarios...</p>';
            
            fetch(`get_comments.php?id_producto=${productId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        if (data.comments.length === 0) {
                            commentsList.innerHTML = '<p>No hay comentarios aún. ¡Sé el primero en comentar!</p>';
                        } else {
                            commentsList.innerHTML = data.comments.map(comment => `
                                <div class="comment-item">
                                    <div class="comment-header">
                                        <div class="comment-user">
                                            <i class='bx bx-user-circle'></i>
                                            <span>${comment.user}</span>
                                        </div>
                                        <div class="comment-rating">
                                            ${generateStars(comment.rating)}
                                        </div>
                                        <div class="comment-date">
                                            ${formatDate(comment.date)}
                                        </div>
                                    </div>
                                    <div class="comment-text">
                                        ${comment.text}
                                    </div>
                                </div>
                            `).join('');
                        }
                    } else {
                        commentsList.innerHTML = '<p>Error al cargar comentarios</p>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    commentsList.innerHTML = '<p>Error al cargar comentarios</p>';
                });
        }

        function generateStars(rating) {
            let stars = '';
            for (let i = 1; i <= 5; i++) {
                if (i <= rating) {
                    stars += '<i class="bx bxs-star"></i>';
                } else {
                    stars += '<i class="bx bx-star"></i>';
                }
            }
            return stars;
        }

        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('es-ES', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
        }

        function addComment() {
            if (!currentProductId) return;
            
            // Verificar si el usuario está logueado
            if (!isLoggedIn) {
                alert('Debes iniciar sesión para agregar comentarios.');
                window.location.href = 'login.php';
                return;
            }
            
            const commentText = document.getElementById('commentText').value.trim();
            if (!commentText) {
                alert('Por favor, escribe un comentario');
                return;
            }
            
            if (selectedRating === 0) {
                alert('Por favor, selecciona una calificación');
                return;
            }

            // Deshabilitar botón mientras se procesa
            const submitBtn = document.querySelector('.comment-form .btn');
            const originalText = submitBtn.textContent;
            submitBtn.textContent = 'Publicando...';
            submitBtn.disabled = true;

            // Enviar comentario al servidor
            fetch('add_comment.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    id_producto: currentProductId,
                    rating: selectedRating,
                    comment: commentText
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Recargar comentarios
                    loadComments(currentProductId);
                    
                    // Reset form
                    document.getElementById('commentText').value = '';
                    selectedRating = 0;
                    updateStarDisplay();
                    
                    alert('¡Comentario agregado exitosamente!');
                } else {
                    alert(data.message || 'Error al agregar comentario');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al agregar comentario');
            })
            .finally(() => {
                // Restaurar botón
                submitBtn.textContent = originalText;
                submitBtn.disabled = false;
            });
        }

        function updateStarDisplay() {
            const stars = document.querySelectorAll('.stars i');
            stars.forEach((star, index) => {
                if (index < selectedRating) {
                    star.classList.remove('bx-star');
                    star.classList.add('bxs-star');
                } else {
                    star.classList.remove('bxs-star');
                    star.classList.add('bx-star');
                }
            });
        }

        // Event listeners
        document.addEventListener('DOMContentLoaded', function() {
            // Close modal with X button
            document.querySelector('.close-modal').addEventListener('click', closeProductModal);

            // Payment form submission
            document.getElementById('payment-form').addEventListener('submit', function(e) {
                e.preventDefault();
                processPaymentWithExistingMethod();
            });

            // Star rating functionality
            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('bx') && e.target.dataset.rating) {
                    selectedRating = parseInt(e.target.dataset.rating);
                    updateStarDisplay();
                }
            });

            // Formateo del CVV para método existente
            const existingCvv = document.getElementById('existing-cvv');
            if (existingCvv) {
                existingCvv.addEventListener('input', function() {
                    this.value = this.value.replace(/\D/g, '');
                });
            }
        });

        function processPaymentWithExistingMethod() {
            console.log('Iniciando processPaymentWithExistingMethod'); // Debug log
            
            // Evitar ejecuciones múltiples
            if (isProcessingPayment) {
                console.log('processPaymentWithExistingMethod ya en ejecución, ignorando...');
                return;
            }
            
            isProcessingPayment = true;
            
            const selectedMethod = document.getElementById('selected-payment-method').value;
            const existingCvv = document.getElementById('existing-cvv').value;
            
            // Validar que se haya seleccionado un método de pago
            if (!selectedMethod || selectedMethod === '') {
                alert('Por favor selecciona un método de pago');
                isProcessingPayment = false; // Reset del flag
                return;
            }
            
            // Validar CVV
            if (!existingCvv || existingCvv.length < 3 || existingCvv.length > 4) {
                alert('Por favor ingresa un CVV válido (3-4 dígitos)');
                isProcessingPayment = false; // Reset del flag
                return;
            }
            
            if (!/^\d{3,4}$/.test(existingCvv)) {
                alert('El CVV debe contener solo números');
                isProcessingPayment = false; // Reset del flag
                return;
            }
            
            // Procesar pago con método existente
            const submitBtn = document.querySelector('#payment-form button[type="submit"]');
            const originalText = submitBtn.textContent;
            
            submitBtn.textContent = 'Procesando...';
            submitBtn.disabled = true;

            // Calcular total
            const subtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
            const shipping = subtotal > 0 ? 1.00 : 0;
            const total = subtotal + shipping;

            // Enviar datos al servidor
            fetch('process_cart_purchase.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    cart_items: cart,
                    payment_method: 'método existente',
                    total_amount: total
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('¡Pago procesado exitosamente con tu método de pago guardado!');
                    clearCart();
                    closeCheckout();
                    location.reload(); // Refrescar la página para actualizar el stock
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al procesar el pago');
                isProcessingPayment = false; // Reset del flag en caso de error
            })
            .finally(() => {
                submitBtn.textContent = originalText;
                submitBtn.disabled = false;
            });
        }

        // Cart functionality
        let cart = [];
        let cartTotal = 0;
        let currentProductId = null;
        let selectedRating = 0;
        let isProcessingPayment = false;

        // Product data from PHP
        const productsData = <?= $productsJson ?>;
        // Convert products array to object for easy access
        const productData = {};
        productsData.forEach(product => {
            productData[product.id_producto] = {
                name: product.nombre,
                price: parseFloat(product.precio),
                oldPrice: product.precio_anterior ? parseFloat(product.precio_anterior) : null,
                description: product.descripcion,
                image: product.url_imagen || 'assets/image1.png',
                category: product.nombre_categoria,
                rating: product.calificacion || 5
            };
        });

        // Load cart from localStorage on page load
        function loadCartFromStorage() {
            const savedCart = localStorage.getItem('fit360_cart');
            if (savedCart) {
                try {
                    cart = JSON.parse(savedCart);
                    updateCartDisplay();
                } catch (e) {
                    console.error('Error loading cart from storage:', e);
                    cart = [];
                }
            }
        }

        // Save cart to localStorage
        function saveCartToStorage() {
            localStorage.setItem('fit360_cart', JSON.stringify(cart));
        }

        // Add to cart function
        function addToCart(productId, type = 'product', button) {
            // Verificar si el usuario está logueado
            const isLoggedIn = <?php echo isLoggedIn() ? 'true' : 'false'; ?>;
            if (!isLoggedIn) {
                alert('Debes iniciar sesión para agregar productos al carrito.');
                window.location.href = 'login.php';
                return;
            }

            if (type === 'product') {
                const product = productData[productId];
                if (!product) {
                    alert('Producto no encontrado');
                    return;
                }

                // Check if product is already in cart
                const existingItem = cart.find(item => item.id_producto === productId);
                if (existingItem) {
                    existingItem.quantity += 1;
                } else {
                    cart.push({
                        id_producto: productId,
                        name: product.name,
                        price: product.price,
                        image: product.image,
                        quantity: 1,
                        type: 'product'
                    });
                }
            }

            saveCartToStorage();
            updateCartDisplay();
            
            // Show success message
            const addToCartBtn = button;
            if (addToCartBtn) {
                const originalText = addToCartBtn.textContent;
                addToCartBtn.textContent = '¡Agregado!';
                addToCartBtn.style.background = '#28a745';
                setTimeout(() => {
                    addToCartBtn.textContent = originalText;
                    addToCartBtn.style.background = '';
                }, 1500);
            }
        }

        // Remove from cart function
        function removeFromCart(index) {
            cart.splice(index, 1);
            saveCartToStorage();
            updateCartDisplay();
        }

        // Update quantity function
        function updateQuantity(index, change) {
            const item = cart[index];
            item.quantity += change;
            
            if (item.quantity <= 0) {
                removeFromCart(index);
            } else {
                saveCartToStorage();
                updateCartDisplay();
            }
        }

        // Update cart display
        function updateCartDisplay() {
            const cartItems = document.getElementById('cart-items');
            const cartSubtotal = document.getElementById('cart-subtotal');
            const cartShipping = document.getElementById('cart-shipping');
            const cartTotal = document.getElementById('cart-total');
            const cartCount = document.getElementById('cart-count');

            if (!cartItems) return;

            if (cart.length === 0) {
                cartItems.innerHTML = '<p class="empty-cart">Tu carrito está vacío</p>';
                if (cartSubtotal) cartSubtotal.textContent = '$0.00 MXN';
                if (cartShipping) cartShipping.textContent = '$0.00 MXN';
                if (cartTotal) cartTotal.textContent = '$0.00 MXN';
                if (cartCount) cartCount.textContent = '0';
                return;
            }

            let subtotal = 0;
            cartItems.innerHTML = cart.map((item, index) => {
                const itemTotal = item.price * item.quantity;
                subtotal += itemTotal;
                
                return `
                    <div class="cart-item">
                        <img src="${item.image}" alt="${item.name}">
                        <div class="cart-item-info">
                            <h4>${item.name}</h4>
                            <p>$${item.price.toFixed(2)} MXN</p>
                            <div class="quantity-controls">
                                <button onclick="updateQuantity(${index}, -1)">-</button>
                                <span>${item.quantity}</span>
                                <button onclick="updateQuantity(${index}, 1)">+</button>
                            </div>
                        </div>
                        <div class="cart-item-total">
                            <span>$${itemTotal.toFixed(2)} MXN</span>
                            <button onclick="removeFromCart(${index})" class="remove-item">&times;</button>
                        </div>
                    </div>
                `;
            }).join('');

            const shipping = subtotal > 0 ? 1.00 : 0;
            const total = subtotal + shipping;

            if (cartSubtotal) cartSubtotal.textContent = `$${subtotal.toFixed(2)} MXN`;
            if (cartShipping) cartShipping.textContent = `$${shipping.toFixed(2)} MXN`;
            if (cartTotal) cartTotal.textContent = `$${total.toFixed(2)} MXN`;
            if (cartCount) cartCount.textContent = cart.reduce((sum, item) => sum + item.quantity, 0).toString();
        }

        // Clear cart function
        function clearCart() {
            cart = [];
            saveCartToStorage();
            updateCartDisplay();
        }

        // Toggle cart modal
        function toggleCart() {
            const cartModal = document.getElementById('cartModal');
            if (cartModal.style.display === 'flex') {
                cartModal.style.display = 'none';
            } else {
                cartModal.style.display = 'flex';
            }
        }

        // Proceed to checkout
        function proceedToCheckout() {
            if (cart.length === 0) {
                alert('Tu carrito está vacío');
                return;
            }

            // Validar que no se exceda el stock
            for (let i = 0; i < cart.length; i++) {
                const item = cart[i];
                const product = productsData.find(p => p.id_producto === item.id_producto);
                if (product && item.quantity > product.cantidad_stock) {
                    alert('La cantidad de "' + product.nombre + '" en el carrito excede el stock disponible (' + product.cantidad_stock + '). Por favor ajusta la cantidad antes de continuar.');
                    return;
                }
            }

            // Verificar si el usuario está logueado
            const isLoggedIn = <?php echo isLoggedIn() ? 'true' : 'false'; ?>;
            if (!isLoggedIn) {
                alert('Debes iniciar sesión para proceder al pago.');
                window.location.href = 'login.php';
                return;
            }

            // Verificar si el usuario tiene una dirección registrada
            const userAddress = '<?php echo addslashes($user_address ?? ''); ?>';
            if (!userAddress || userAddress.trim() === '') {
                alert('Debes tener una dirección registrada para proceder al pago. Por favor, actualiza tu perfil.');
                window.location.href = 'profile.php';
                return;
            }

            // Verificar si el usuario tiene métodos de pago
            fetch('get_payment_methods.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (data.payment_methods.length === 0) {
                        alert('No tienes métodos de pago agregados. Por favor, agrega un método de pago en tu perfil.');
                        window.location.href = 'profile.php';
                        return;
                    }
                    
                    // Si tiene métodos de pago, mostrar el checkout
                    const cartModal = document.getElementById('cartModal');
                    const checkoutModal = document.getElementById('checkoutModal');
                    
                    cartModal.style.display = 'none';
                    checkoutModal.style.display = 'flex';
                    
                    updateCheckoutDisplay();
                } else {
                    alert('Error al verificar métodos de pago. Por favor, intenta de nuevo.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al verificar métodos de pago. Por favor, intenta de nuevo.');
            });
        }

        // Close checkout modal
        function closeCheckout() {
            document.getElementById('checkoutModal').style.display = 'none';
        }

        // Update checkout display
        function updateCheckoutDisplay() {
            const checkoutItems = document.getElementById('checkout-items');
            const checkoutTotal = document.getElementById('checkout-total');
            
            if (cart.length === 0) {
                checkoutItems.innerHTML = '<p>No hay productos en el carrito</p>';
                checkoutTotal.textContent = '$0.00 MXN';
                return;
            }
            
            const subtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
            const shipping = subtotal > 0 ? 1.00 : 0;
            const total = subtotal + shipping;
            
            checkoutItems.innerHTML = cart.map((item, index) => `
                <div class="checkout-item">
                    <img src="${item.image}" alt="${item.name}">
                    <div class="checkout-item-info">
                        <h4>${item.name}</h4>
                        <p>Cantidad: ${item.quantity}</p>
                        <span class="item-price">$${(item.price * item.quantity).toFixed(2)} MXN</span>
                    </div>
                </div>
            `).join('');
            
            checkoutTotal.textContent = `$${total.toFixed(2)} MXN`;
        }

        // Show cart notification
        function showCartNotification() {
            const cartCount = document.getElementById('cart-count');
            if (cartCount) {
                cartCount.style.animation = 'cartPulse 0.5s ease-in-out';
                setTimeout(() => {
                    cartCount.style.animation = '';
                }, 500);
            }
        }

        // Cargar carrito al iniciar
        document.addEventListener('DOMContentLoaded', function() {
            loadCartFromStorage();
        });
    </script>
</body>
</html> 