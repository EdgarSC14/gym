<?php
require_once 'config/database.php';
startSession();

// Obtener servicios desde la base de datos
$pdo = getConnection();
$services = $pdo->query("SELECT * FROM servicio WHERE esta_activo = 1 LIMIT 6")->fetchAll();

// Obtener planes de suscripción
$plans = $pdo->query("SELECT * FROM plan_suscripcion WHERE esta_activo = 1")->fetchAll();

// Obtener productos destacados
$featuredProducts = $pdo->query("SELECT * FROM producto WHERE es_destacado = 1 AND esta_activo = 1 LIMIT 6")->fetchAll();

// Obtener todos los productos para el carrito
$allProducts = $pdo->query("SELECT p.*, pc.nombre as category_name FROM producto p LEFT JOIN categoria_producto pc ON p.id_categoria = pc.id_categoria WHERE p.esta_activo = 1")->fetchAll();

// Obtener productos por categoría
$categories = $pdo->query("SELECT * FROM categoria_producto WHERE esta_activo = 1")->fetchAll();

// Obtener la suscripción activa del usuario
$user_subscription_plan_id = null;
if (isLoggedIn()) {
    $stmt = $pdo->prepare("SELECT id_plan FROM suscripcion_usuario WHERE id_usuario = ? AND estado = 'activa' ORDER BY fecha_inicio DESC LIMIT 1");
    $stmt->execute([$_SESSION['user_id']]);
    $user_subscription_plan_id = $stmt->fetchColumn();
}

// Obtener métodos de pago del usuario si está logueado
$user_payment_methods = [];
$user_address = null;
if (isLoggedIn()) {
    $stmt = $pdo->prepare("SELECT * FROM metodo_pago_usuario WHERE id_usuario = ? AND esta_activo = 1 ORDER BY es_predeterminado DESC");
    $stmt->execute([$_SESSION['user_id']]);
    $user_payment_methods = $stmt->fetchAll();
    
    // Obtener la dirección del usuario
    $stmt = $pdo->prepare("SELECT direccion FROM usuario WHERE id_usuario = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user_address = $stmt->fetchColumn();
}

// Lógica de servicios permitidos
$allowed_services = [];
if ($user_subscription_plan_id) {
    if ($user_subscription_plan_id == 1) { // BÁSICO
        $allowed_services = [1, 2];
    } elseif ($user_subscription_plan_id == 2) { // PRO
        $allowed_services = [1, 2, 3, 4];
    } elseif ($user_subscription_plan_id == 3) { // PREMIUM
        $allowed_services = [1, 2, 3, 4, 5, 6];
    }
}

// Convertir productos destacados a JSON para JavaScript
$featuredProductsJson = json_encode($featuredProducts);

// Convertir todos los productos a JSON para el carrito
$productsJson = json_encode($allProducts);
?>
<!DOCTYPE html> 
<html lang="en"> 
<head> 
    <meta charset="UTF-8"> 
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'> 
    <link rel="stylesheet" href="style.css"> 
    <title>Fit 360</title> 
</head> 
<body> 

    <header> 

        <a href="#home" class="logo">Fit <span>360</span></a> 
        
        <div class='bx bx-menu' id="menu-icon"></div>

        <ul class="navbar"> 
            <li><a href="#home">Inicio</a></li> 
            <li><a href="#services">Servicios</a></li> 
            <li><a href="#about">Acerca de Nosotros</a></li> 
            <li><a href="#plans">Suscripciones</a></li> 
            <li><a href="#review">Productos</a></li> 
        </ul> 
        
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
    
    <!-- home Section Code-->    

    <section class="home" id="home"> 
        <div class="home-content"> 
            
            <h3>Construye</h3> 
            <h1>tus sueños</h1> 
            <h3><span class="multiple-text"></span></h3> 
            
        
            <?php if(!isLoggedIn()): ?>
                <a href="login.php" class="nav-btn">Iniciar Sesión</a> 
            <?php endif; ?>
        
        </div>
        
        <div class="home-img"> 
            <img src="https://images.unsplash.com/photo-1517836357463-d25dfeac3438?auto=format&fit=crop&w=600&q=80" alt="Home Image"> 
        </div>

    </section>


    <!-- Services Section Code-->    
    
    <section class="services" id="services"> 
        <h2 class="heading">Nuestros <Span>Servicios</span></h2> 
        
        <div class="services-content"> 
            <?php foreach($services as $service): 
                $is_locked = false;
                $service_class = 'service-box';
                
                if (!isLoggedIn()) {
                    // Si no está logueado, todos los servicios están bloqueados
                    $is_locked = true;
                    $service_class = 'service-box locked';
                    $service_link = 'login.php';
                } else {
                    // Si está logueado, verificar según su plan
                    $is_locked = !in_array($service['id_servicio'], $allowed_services);
                    $service_class = $is_locked ? 'service-box locked logged-in' : 'service-box';
                    $service_link = $is_locked ? 'javascript:void(0);' : 'service-details.php?id=' . $service['id_servicio'];
                }
            ?>
            <div class="<?php echo $service_class; ?>">
                <a href="<?php echo $service_link; ?>">
                    <img src="<?php echo htmlspecialchars($service['url_imagen'] ?: 'assets/image1.png'); ?>" alt="Servicio">
                    <div class="service-overlay">
                        <h3><?php echo htmlspecialchars($service['nombre']); ?></h3>
                        <?php if ($is_locked): ?>
                            <?php if (!isLoggedIn()): ?>
                                <p class="lock-message">Inicia sesión para acceder</p>
                            <?php else: ?>
                                <p class="lock-message"><a href="#plans" style="color:inherit;text-decoration:none;cursor:pointer;">Mejora tu plan para acceder</a></p>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>

    </section>

    <!-- About Section Code-->    
    
    <section class="about" id="about"> 
        <div class="about-img"> 
            <img src="https://images.unsplash.com/photo-1506744038136-46273834b3fb?auto=format&fit=crop&w=600&q=80" alt="Acerca de Nosotros"> 
        </div> 

        <div class="about-content"> 
            <h2 class="heading">Porque <span>Elegirnos?</span></h2> 
            
            <div class="benefits-list">

                
                <div class="benefit-item">
                    <i class='bx bx-group'></i>
                    <div>
                        <h4>Comunidad Motivadora</h4>
                        <p>Únete a una comunidad de personas con objetivos similares que te inspirarán a alcanzar tus metas.</p>
                    </div>
                </div>
                
                
                <div class="benefit-item">
                    <i class='bx bx-target-lock'></i>
                    <div>
                        <h4>Resultados Garantizados</h4>
                        <p>Con nuestros programas personalizados y seguimiento profesional, verás resultados reales.</p>
                    </div>
                </div>
            </div>

            <a href="#plans" class="btn">Ver Nuestros Planes</a>
        
        </div>

    </section>

    <!-- precios Section Code-->    

    <section class="plans" id="plans">
        <h2 class="heading">Nuestras <Span>Suscripciones</span></h2> 
            
        <div class="plans-content">
            <?php foreach($plans as $plan): ?>
            <div class="box"> 
                <h3><?= htmlspecialchars($plan['nombre']) ?></h3> 
                <h2><span>$<?= number_format($plan['precio'], 2) ?> MXN/Mes</span></h2> 
                <ul> 
                    <?php 
                    $benefits = explode("\n", $plan['beneficios']);
                    foreach($benefits as $benefit): 
                        if(trim($benefit)): ?>
                        <li><?= htmlspecialchars(trim($benefit)) ?></li> 
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ul> 
                <a href="subscription-checkout.php?plan=<?= $plan['id_plan'] ?>"> 
                    Suscribirse 
                    <i class='bx bx-right-arrow-alt'></i> 
                </a>
            </div>
            <?php endforeach; ?>
        </div>        
    </section>

    <!-- review Section Code-->

    <section class="review" id="review" > 
        <div class="review-box"> 
            <h2 class="heading">Productos <span>Destacados</span></h2> 
            
            <div class="wrapper">
                <?php foreach($featuredProducts as $product): ?>
                <div class="review-item" onclick="showProductDetails(<?= $product['id_producto'] ?>)"> 
                    <img src="<?= $product['url_imagen'] ?: 'assets/image1.png' ?>" alt="<?= htmlspecialchars($product['nombre']) ?>"> 
                    <h2><?= htmlspecialchars($product['nombre']) ?></h2> 
                    <div class="rating">
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
                        <span class="rating-text">(<?= number_format($rating, 1) ?>/5)</span>
                    </div>
                    <div class="price">
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
                    <button class="add-to-cart" onclick="event.stopPropagation(); addToCart(<?= $product['id_producto'] ?>, 'product', this)" <?= $product['cantidad_stock'] <= 0 ? 'disabled style="background:#ccc;cursor:not-allowed;"' : '' ?>>
                        <?= $product['cantidad_stock'] <= 0 ? 'Sin Stock' : 'Agregar al Carrito' ?>
                    </button>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="view-more-container">
                <a href="products.php" class="view-more-btn">Ver Más Productos <i class='bx bx-right-arrow-alt'></i></a>
            </div>
        </div>
    </section>

    <!-- Footer Section Code-->

    <footer class="footer"> 
        <div class="social"> 
            <a href="https://facebook.com/fit360gym" target="_blank"><i class='bx bxl-facebook'></i></a> 
            <a href="https://instagram.com/fit360gym" target="_blank"><i class='bx bxl-instagram' ></i></a> 
        </div> 
        
        <p class="copyright"> 
            &copy; Fit 360 2025 | Todos los derechos reservados. 
        </p> 
    </footer>

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
                                        <?= htmlspecialchars($method['tipo_tarjeta']) ?> - ****<?= substr($method['numero_tarjeta'], -4) ?>
                                        <?= $method['es_predeterminado'] ? ' (Predeterminado)' : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            
                            <!-- Campo CVV para método existente -->
                            <div class="form-group" style="margin-top: 15px;">
                                <label for="existing-cvv">CVV</label>
                                <div class="input-group">
                                    <i class='bx bx-lock'></i>
                                    <input type="text" id="existing-cvv" name="existing_cvv" placeholder="123" maxlength="4" required>
                                </div>
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

    <script src="https://unpkg.com/typed.js@2.0.16/dist/typed.umd.js"></script>
    <script>
        // Cart functionality
        let cart = [];
        let cartTotal = 0;
        let currentProductId = null;
        let selectedRating = 0;

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
            const isLoggedIn = <?php echo isLoggedIn() ? 'true' : 'false'; ?>;
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
            // Close modals when clicking outside
            document.getElementById('productModal').addEventListener('click', function(e) {
                if (e.target === this) {
                    closeProductModal();
                }
            });

            document.getElementById('cartModal').addEventListener('click', function(e) {
                if (e.target === this) {
                    toggleCart();
                }
            });

            document.getElementById('checkoutModal').addEventListener('click', function(e) {
                if (e.target === this) {
                    closeCheckout();
                }
            });

            // Close modal with X button
            document.querySelector('.close-modal').addEventListener('click', closeProductModal);

            // Payment form submission
            document.getElementById('payment-form').addEventListener('submit', function(e) {
                e.preventDefault();
                processPayment();
            });

            // Star rating functionality
            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('bx') && e.target.dataset.rating) {
                    selectedRating = parseInt(e.target.dataset.rating);
                    updateStarDisplay();
                }
            });

            // Load cart when page loads
            loadCartFromStorage();
        });

        function processPaymentWithExistingMethod() {
            const selectedMethod = document.getElementById('selected-payment-method').value;
            const existingCvv = document.getElementById('existing-cvv').value;
            
            // Validar CVV
            if (!existingCvv || existingCvv.length < 3 || existingCvv.length > 4) {
                alert('Por favor ingresa un CVV válido (3-4 dígitos)');
                return;
            }
            
            if (!/^\d{3,4}$/.test(existingCvv)) {
                alert('El CVV debe contener solo números');
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
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al procesar el pago');
            })
            .finally(() => {
                submitBtn.textContent = originalText;
                submitBtn.disabled = false;
            });
        }

        function processPayment() {
            // Validar datos de la tarjeta antes de enviar
            const cardNumber = document.getElementById('card-number').value.replace(/\s/g, '');
            const cardExpiry = document.getElementById('card-expiry').value;
            const cardCvv = document.getElementById('card-cvv').value;
            const cardName = document.getElementById('card-name').value.trim();
            
            // Validaciones básicas
            if (!cardName || cardName.length < 3) {
                alert('Por favor ingresa un nombre válido');
                return;
            }
            
            if (!cardNumber || cardNumber.length < 13 || cardNumber.length > 19) {
                alert('Por favor ingresa un número de tarjeta válido');
                return;
            }
            
            if (!validateCardNumber(cardNumber)) {
                alert('Número de tarjeta inválido');
                return;
            }
            
            if (!validateExpiryDate(cardExpiry)) {
                alert('Fecha de vencimiento inválida');
                return;
            }
            
            const cardType = detectCardType(cardNumber);
            if (!validateCVV(cardCvv, cardType)) {
                const expectedLength = cardType === 'Amex' ? 4 : 3;
                alert(`CVV inválido. Las tarjetas ${cardType} requieren ${expectedLength} dígitos`);
                return;
            }
            
            // Procesar pago con nuevo método
            const submitBtn = document.querySelector('#payment-form button[type="submit"]');
            const originalText = submitBtn.textContent;
            
            submitBtn.textContent = 'Procesando...';
            submitBtn.disabled = true;

            // Calcular total
            const subtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
            const shipping = subtotal > 0 ? 1.00 : 0;
            const total = subtotal + shipping;

            // Enviar datos al servidor incluyendo información de la tarjeta
            fetch('process_cart_purchase.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    cart_items: cart,
                    payment_method: 'nueva tarjeta',
                    total_amount: total,
                    card_data: {
                        card_number: cardNumber,
                        card_expiry: cardExpiry,
                        card_cvv: cardCvv,
                        card_name: cardName,
                        card_type: cardType
                    }
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('¡Pago procesado exitosamente! Tu pedido ha sido confirmado.');
                    clearCart();
                    closeCheckout();
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al procesar el pago');
            })
            .finally(() => {
                submitBtn.textContent = originalText;
                submitBtn.disabled = false;
            });
        }

        // Typed.js initialization
        let typed = new Typed('.multiple-text', {
            strings: ['Tu Cuerpo', 'Tu Mente', 'Tu Salud'],
            typeSpeed: 100,
            backSpeed: 100,
            backDelay: 1000,
            loop: true
        });

        // Validaciones de tarjeta de crédito
        function formatCardNumber(input) {
            let value = input.value.replace(/\s/g, '').replace(/\D/g, '');
            let formattedValue = '';
            for (let i = 0; i < value.length; i++) {
                if (i > 0 && i % 4 === 0) {
                    formattedValue += ' ';
                }
                formattedValue += value[i];
            }
            input.value = formattedValue;
            
            // Detectar tipo de tarjeta
            const cardType = detectCardType(value);
            const cardTypeElement = document.getElementById('card-type');
            if (cardTypeElement) {
                cardTypeElement.textContent = cardType;
                cardTypeElement.className = 'card-type-indicator ' + cardType.toLowerCase();
            }
        }

        function formatExpiryDate(input) {
            let value = input.value.replace(/\D/g, '');
            if (value.length >= 2) {
                value = value.substring(0, 2) + '/' + value.substring(2, 4);
            }
            input.value = value;
        }

        function formatCVV(input) {
            input.value = input.value.replace(/\D/g, '');
        }

        function detectCardType(number) {
            const patterns = {
                visa: /^4/,
                mastercard: /^5[1-5]/,
                amex: /^3[47]/,
                discover: /^6(?:011|5)/
            };
            
            for (const [type, pattern] of Object.entries(patterns)) {
                if (pattern.test(number)) {
                    return type.charAt(0).toUpperCase() + type.slice(1);
                }
            }
            return '';
        }

        function validateCardNumber(number) {
            // Algoritmo de Luhn
            let sum = 0;
            let isEven = false;
            
            for (let i = number.length - 1; i >= 0; i--) {
                let digit = parseInt(number[i]);
                
                if (isEven) {
                    digit *= 2;
                    if (digit > 9) {
                        digit -= 9;
                    }
                }
                
                sum += digit;
                isEven = !isEven;
            }
            
            return sum % 10 === 0;
        }

        function validateExpiryDate(expiry) {
            const [month, year] = expiry.split('/');
            const currentDate = new Date();
            const currentYear = currentDate.getFullYear() % 100;
            const currentMonth = currentDate.getMonth() + 1;
            
            const expMonth = parseInt(month);
            const expYear = parseInt(year);
            
            if (expMonth < 1 || expMonth > 12) return false;
            if (expYear < currentYear || (expYear === currentYear && expMonth < currentMonth)) return false;
            
            return true;
        }

        function validateCVV(cvv, cardType) {
            const length = cardType === 'Amex' ? 4 : 3;
            return /^\d{3,4}$/.test(cvv) && cvv.length === length;
        }

        // Event listeners para formateo y validación
        document.addEventListener('DOMContentLoaded', function() {
            // Load cart from localStorage
            loadCartFromStorage();
            
            // Formateo de campos de tarjeta
            const cardNumber = document.getElementById('card-number');
            const cardExpiry = document.getElementById('card-expiry');
            const cardCvv = document.getElementById('card-cvv');
            const existingCvv = document.getElementById('existing-cvv');
            
            if (cardNumber) {
                cardNumber.addEventListener('input', function() {
                    formatCardNumber(this);
                });
            }
            
            if (cardExpiry) {
                cardExpiry.addEventListener('input', function() {
                    formatExpiryDate(this);
                });
            }
            
            if (cardCvv) {
                cardCvv.addEventListener('input', function() {
                    formatCVV(this);
                });
            }
            
            // Formateo del CVV para método existente
            if (existingCvv) {
                existingCvv.addEventListener('input', function() {
                    formatCVV(this);
                });
            }
            
            // Configurar estado inicial para método existente
            if (existingCvv) {
                existingCvv.required = true;
                existingCvv.disabled = false;
            }
            
            // Validación del formulario de pago
            const paymentForm = document.getElementById('payment-form');
            if (paymentForm) {
                paymentForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    processPaymentWithExistingMethod();
                });
            }
        });
    </script>

</body> 
</html>