<?php
require_once 'config/database.php';

$conn = getConnection();

// Obtener items del carrito
$sql = "SELECT c.*, p.nombre, p.descripcion, p.imagen_principal, p.precio_base, p.stock,
        o.valor_descuento, o.tipo_descuento,
        CASE 
            WHEN o.tipo_descuento = 'porcentaje' THEN p.precio_base * (1 - o.valor_descuento/100)
            WHEN o.tipo_descuento = 'cantidad' THEN p.precio_base - o.valor_descuento
            ELSE p.precio_base 
        END as precio_final
        FROM carrito c
        INNER JOIN productos p ON c.id_producto = p.id_producto
        LEFT JOIN ofertas o ON p.id_producto = o.id_producto 
            AND o.activo = 1 
            AND CURDATE() BETWEEN o.fecha_inicio AND o.fecha_fin
        WHERE c.session_id = :session_id
        ORDER BY c.fecha_agregado DESC";

$stmt = $conn->prepare($sql);
$stmt->execute(['session_id' => $_SESSION['cart_id']]);
$items = $stmt->fetchAll();

// Calcular totales
$subtotal = 0;
$descuento_total = 0;
foreach($items as $item) {
    $precio_original = $item['precio_base'] * $item['cantidad'];
    $precio_con_descuento = $item['precio_final'] * $item['cantidad'];
    $subtotal += $precio_con_descuento;
    $descuento_total += ($precio_original - $precio_con_descuento);
}

$envio = $subtotal >= 1000 ? 0 : 150;
$total = $subtotal + $envio;

// Verificar usuario logueado
$usuario_logueado = isset($_SESSION['usuario']) ? $_SESSION['usuario'] : null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Carrito de Compras - FireStore</title>
  
  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  
  <!-- CSS Personalizado -->
  <link rel="stylesheet" href="css/styles.css">
  
  <style>
    .cart-container {
      max-width: 1200px;
      margin: 40px auto;
      padding: 20px;
    }
    
    .cart-item {
      background: white;
      border-radius: 12px;
      padding: 20px;
      margin-bottom: 15px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    
    .cart-img {
      width: 100px;
      height: 100px;
      object-fit: contain;
      border-radius: 8px;
      background: #f3f3f3;
      padding: 5px;
    }
    
    .quantity-control {
      display: flex;
      align-items: center;
      gap: 10px;
    }
    
    .quantity-btn {
      width: 32px;
      height: 32px;
      border: 1px solid #ddd;
      background: white;
      border-radius: 50%;
      cursor: pointer;
      transition: all 0.3s;
    }
    
    .quantity-btn:hover {
      background: #b71c1c;
      color: white;
      border-color: #b71c1c;
    }
    
    .summary-card {
      background: white;
      border-radius: 12px;
      padding: 25px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      position: sticky;
      top: 20px;
    }
    
    .btn-checkout {
      background: #b71c1c;
      color: white;
      border: none;
      padding: 12px;
      border-radius: 30px;
      font-weight: bold;
      width: 100%;
      transition: all 0.3s;
    }
    
    .btn-checkout:hover {
      background: #8b0000;
      transform: translateY(-2px);
    }
    
    .empty-cart {
      text-align: center;
      padding: 60px 20px;
      background: white;
      border-radius: 15px;
    }
  </style>
</head>
<body>

  <!-- Navbar -->
  <nav>
    <div class="logo">
      FireStore 
      <img src="imagenes/logo.png" height="30px" width="30px" alt="Logo">
    </div>

    <div class="menu" id="menu">
      <a href="productos.php">Productos</a>
      <a href="ofertas.php">Ofertas</a>
      <a href="productos.php#categorias">Categorías</a>
    </div>

    <div class="derecha">
      <?php if($usuario_logueado): ?>
        <span style="margin-right: 15px; color: #666;">
          <i class="fas fa-user"></i> <?php echo htmlspecialchars($usuario_logueado); ?>
        </span>
        <a href="logout.php" style="color: #dc3545;">Cerrar sesión</a>
      <?php else: ?>
        <a href="registro.php">Registrarse</a>
        <a href="login.php" class="boton-login">Iniciar sesión</a>
      <?php endif; ?>
      
      <a href="carrito.php" style="margin-left: 15px; color: #b71c1c; font-weight: bold;">
        <i class="fas fa-shopping-cart"></i> Carrito
      </a>
      
      <div class="menu-alternar" id="menu-alternar">☰</div>
    </div>
  </nav>

  <!-- Contenido del Carrito -->
  <div class="cart-container">
    <h1 class="mb-4"><i class="fas fa-shopping-cart"></i> Mi Carrito</h1>
    
    <?php if(count($items) > 0): ?>
    <div class="row">
      <!-- Items del carrito -->
      <div class="col-lg-8">
        <?php foreach($items as $item): ?>
        <div class="cart-item">
          <div class="row align-items-center">
            <div class="col-md-2 text-center">
              <img src="<?php echo htmlspecialchars($item['imagen_principal']); ?>" 
                   class="cart-img" 
                   alt="<?php echo htmlspecialchars($item['nombre']); ?>">
            </div>
            
            <div class="col-md-4">
              <h5 class="mb-1"><?php echo htmlspecialchars($item['nombre']); ?></h5>
              <p class="text-muted small mb-0"><?php echo htmlspecialchars(substr($item['descripcion'], 0, 60)) . '...'; ?></p>
            </div>
            
            <div class="col-md-3">
              <div class="quantity-control">
                <form method="POST" action="update_cart.php" class="d-inline">
                  <input type="hidden" name="id_carrito" value="<?php echo $item['id_carrito']; ?>">
                  <input type="hidden" name="action" value="decrease">
                  <button type="submit" class="quantity-btn">-</button>
                </form>
                
                <span class="fw-bold"><?php echo $item['cantidad']; ?></span>
                
                <form method="POST" action="update_cart.php" class="d-inline">
                  <input type="hidden" name="id_carrito" value="<?php echo $item['id_carrito']; ?>">
                  <input type="hidden" name="action" value="increase">
                  <input type="hidden" name="max_stock" value="<?php echo $item['stock']; ?>">
                  <button type="submit" class="quantity-btn">+</button>
                </form>
              </div>
            </div>
            
            <div class="col-md-2 text-end">
              <?php if($item['valor_descuento']): ?>
                <small class="text-decoration-line-through text-muted d-block">
                  $<?php echo number_format($item['precio_base'] * $item['cantidad'], 2); ?>
                </small>
              <?php endif; ?>
              <h5 class="mb-0" style="color: #b71c1c;">
                $<?php echo number_format($item['precio_final'] * $item['cantidad'], 2); ?>
              </h5>
            </div>
            
            <div class="col-md-1 text-end">
              <form method="POST" action="update_cart.php">
                <input type="hidden" name="id_carrito" value="<?php echo $item['id_carrito']; ?>">
                <input type="hidden" name="action" value="remove">
                <button type="submit" class="btn btn-sm btn-danger">
                  <i class="fas fa-trash"></i>
                </button>
              </form>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
        
        <div class="mt-3">
          <a href="productos.php" class="btn btn-outline-danger">
            <i class="fas fa-arrow-left"></i> Seguir Comprando
          </a>
        </div>
      </div>
      
      <!-- Resumen -->
      <div class="col-lg-4">
        <div class="summary-card">
          <h4 class="mb-4">Resumen del Pedido</h4>
          
          <div class="d-flex justify-content-between mb-2">
            <span>Subtotal:</span>
            <strong>$<?php echo number_format($subtotal, 2); ?> MXN</strong>
          </div>
          
          <?php if($descuento_total > 0): ?>
          <div class="d-flex justify-content-between mb-2 text-success">
            <span>Descuento:</span>
            <strong>-$<?php echo number_format($descuento_total, 2); ?> MXN</strong>
          </div>
          <?php endif; ?>
          
          <div class="d-flex justify-content-between mb-2">
            <span>Envío:</span>
            <strong><?php echo $envio > 0 ? '$' . number_format($envio, 2) . ' MXN' : 'GRATIS'; ?></strong>
          </div>
          
          <?php if($envio > 0): ?>
          <small class="text-muted d-block mb-3">
            <i class="fas fa-info-circle"></i> Envío gratis en compras mayores a $1,000
          </small>
          <?php endif; ?>
          
          <hr>
          
          <div class="d-flex justify-content-between mb-4">
            <h5>Total:</h5>
            <h5 style="color: #b71c1c;">$<?php echo number_format($total, 2); ?> MXN</h5>
          </div>
          
          <button class="btn-checkout" onclick="alert('¡Gracias por tu compra! Esta es una demostración.')">
            <i class="fas fa-lock"></i> PROCEDER AL PAGO
          </button>
          
          <div class="mt-3 text-center">
            <small class="text-muted">
              <i class="fas fa-shield-alt"></i> Compra 100% segura
            </small>
          </div>
        </div>
      </div>
    </div>
    
    <?php else: ?>
    <!-- Carrito Vacío -->
    <div class="empty-cart">
      <i class="fas fa-shopping-cart fa-5x text-muted mb-3"></i>
      <h3>Tu carrito está vacío</h3>
      <p class="text-muted">¡Agrega productos para comenzar tu compra!</p>
      <a href="productos.php" class="btn btn-danger mt-3">
        <i class="fas fa-fire"></i> Ver Productos
      </a>
    </div>
    <?php endif; ?>
  </div>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  
  <script>
    // Menú responsive
    document.getElementById('menu-alternar').addEventListener('click', function() {
      document.getElementById('menu').classList.toggle('activo');
    });
  </script>
</body>
</html>