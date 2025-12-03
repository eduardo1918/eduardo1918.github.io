<?php
require_once 'config/database.php';

$conn = getConnection();

// Obtener solo productos con ofertas activas
$sql = "SELECT p.*, c.nombre as categoria_nombre,
        o.valor_descuento, o.tipo_descuento, o.fecha_fin,
        CASE 
            WHEN o.tipo_descuento = 'porcentaje' THEN p.precio_base * (1 - o.valor_descuento/100)
            WHEN o.tipo_descuento = 'cantidad' THEN p.precio_base - o.valor_descuento
            ELSE p.precio_base 
        END as precio_final,
        DATEDIFF(o.fecha_fin, CURDATE()) as dias_restantes
        FROM productos p
        INNER JOIN ofertas o ON p.id_producto = o.id_producto
        LEFT JOIN categorias c ON p.id_categoria = c.id_categoria
        WHERE p.activo = 1 
        AND o.activo = 1 
        AND CURDATE() BETWEEN o.fecha_inicio AND o.fecha_fin
        ORDER BY o.valor_descuento DESC";

$stmt = $conn->query($sql);
$productos_oferta = $stmt->fetchAll();

// Contar items en carrito
$cart_count_sql = "SELECT SUM(cantidad) as total FROM carrito WHERE session_id = :session_id";
$stmt_cart = $conn->prepare($cart_count_sql);
$stmt_cart->execute(['session_id' => $_SESSION['cart_id']]);
$cart_count = $stmt_cart->fetch()['total'] ?? 0;

$usuario_logueado = isset($_SESSION['usuario']) ? $_SESSION['usuario'] : null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Ofertas Especiales - FireStore</title>
  
  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  
  <!-- CSS Personalizado -->
  <link rel="stylesheet" href="css/styles.css">
  
  <style>
    .hero-offers {
      background: linear-gradient(145deg, #8b0000, #b71c1c, #d32f2f);
      color: white;
      padding: 60px 20px;
      margin-bottom: 40px;
      text-align: center;
    }
    
    .badge-offer {
      position: absolute;
      top: 10px;
      right: 10px;
      background: #dc2626;
      color: white;
      padding: 8px 15px;
      border-radius: 25px;
      font-size: 1rem;
      font-weight: bold;
      z-index: 10;
      box-shadow: 0 2px 8px rgba(220, 38, 38, 0.4);
    }
    
    .badge-countdown {
      position: absolute;
      bottom: 10px;
      left: 10px;
      background: rgba(0, 0, 0, 0.7);
      color: white;
      padding: 5px 12px;
      border-radius: 20px;
      font-size: 0.85rem;
      z-index: 10;
    }
    
    .price-original {
      text-decoration: line-through;
      color: #999;
      font-size: 0.9rem;
      display: block;
    }
    
    .savings-badge {
      background: #16a34a;
      color: white;
      padding: 3px 10px;
      border-radius: 15px;
      font-size: 0.85rem;
      display: inline-block;
      margin-top: 5px;
    }
    
    .badge-cart {
      position: absolute;
      top: -8px;
      right: -8px;
      background: #dc3545;
      color: white;
      border-radius: 50%;
      padding: 2px 7px;
      font-size: 0.75rem;
      font-weight: bold;
    }
    
    @keyframes pulse {
      0%, 100% { transform: scale(1); }
      50% { transform: scale(1.05); }
    }
    
    .flash-deal {
      animation: pulse 2s infinite;
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
      <a href="ofertas.php" style="color: #b71c1c; font-weight: bold;">Ofertas</a>
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
      
      <a href="carrito.php" class="position-relative" style="margin-left: 15px;">
        <i class="fas fa-shopping-cart"></i>
        <?php if($cart_count > 0): ?>
          <span class="badge-cart"><?php echo $cart_count; ?></span>
        <?php endif; ?>
      </a>
      
      <div class="menu-alternar" id="menu-alternar">☰</div>
    </div>
  </nav>

  <!-- Hero Section -->
  <section class="hero-offers">
    <h1 class="display-3 fw-bold mb-3">
      <i class="fas fa-fire flash-deal"></i> OFERTAS ESPECIALES
    </h1>
    <p class="lead mb-0">¡Aprovecha nuestros increíbles descuentos!</p>
    <p class="fs-5">Equipo de bomberos profesional con hasta 25% de descuento</p>
  </section>

  <!-- Productos en Oferta -->
  <section class="productos">
    <?php if(count($productos_oferta) > 0): ?>
    <h2 class="text-center mb-4">⚡ Ofertas Activas</h2>
    
    <div class="contenedor-tarjetas">
      <?php foreach($productos_oferta as $prod): ?>
      <div class="tarjeta position-relative">
        <span class="badge-offer">
          <?php 
          if($prod['tipo_descuento'] == 'porcentaje') {
              echo '-' . $prod['valor_descuento'] . '%';
          } else {
              echo '-$' . number_format($prod['valor_descuento'], 2);
          }
          ?>
        </span>
        
        <?php if($prod['dias_restantes'] <= 7): ?>
        <span class="badge-countdown">
          <i class="fas fa-clock"></i> 
          <?php echo $prod['dias_restantes']; ?> día<?php echo $prod['dias_restantes'] != 1 ? 's' : ''; ?> restantes
        </span>
        <?php endif; ?>
        
        <img src="<?php echo htmlspecialchars($prod['imagen_principal']); ?>" 
             alt="<?php echo htmlspecialchars($prod['nombre']); ?>">
        
        <div class="contenido-tarjeta">
          <h3><?php echo htmlspecialchars($prod['nombre']); ?></h3>
          <p><?php echo htmlspecialchars($prod['descripcion']); ?></p>
          
          <span class="price-original">$<?php echo number_format($prod['precio_base'], 2); ?> MXN</span>
          <span class="precio">$<?php echo number_format($prod['precio_final'], 2); ?> MXN</span>
          
          <span class="savings-badge">
            <i class="fas fa-piggy-bank"></i> 
            Ahorras $<?php echo number_format($prod['precio_base'] - $prod['precio_final'], 2); ?>
          </span>
          
          <p class="text-muted small mt-2 mb-3">
            <i class="fas fa-box"></i> Stock: <?php echo $prod['stock']; ?> unidades
          </p>
          
          <?php if($prod['stock'] > 0): ?>
            <form method="POST" action="add_to_cart.php">
              <input type="hidden" name="id_producto" value="<?php echo $prod['id_producto']; ?>">
              <input type="hidden" name="cantidad" value="1">
              <button type="submit" class="boton-comprar">
                <i class="fas fa-shopping-cart"></i> Aprovechar Oferta
              </button>
            </form>
          <?php else: ?>
            <button class="boton-comprar" disabled style="background: #999; cursor: not-allowed;">
              Agotado
            </button>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    
    <?php else: ?>
    <!-- No hay ofertas -->
    <div class="text-center py-5">
      <i class="fas fa-tags fa-5x text-muted mb-3"></i>
      <h3>No hay ofertas activas en este momento</h3>
      <p class="text-muted">¡Vuelve pronto para encontrar increíbles descuentos!</p>
      <a href="productos.php" class="btn btn-danger mt-3">
        <i class="fas fa-fire"></i> Ver Todos los Productos
      </a>
    </div>
    <?php endif; ?>
  </section>

  <!-- Beneficios -->
  <section class="container my-5">
    <div class="row g-4 text-center">
      <div class="col-md-3">
        <div class="p-4">
          <i class="fas fa-shipping-fast fa-3x mb-3" style="color: #b71c1c;"></i>
          <h5>Envío Gratis</h5>
          <p class="text-muted small">En compras mayores a $1,000</p>
        </div>
      </div>
      <div class="col-md-3">
        <div class="p-4">
          <i class="fas fa-undo fa-3x mb-3" style="color: #b71c1c;"></i>
          <h5>Devoluciones</h5>
          <p class="text-muted small">30 días para devolver</p>
        </div>
      </div>
      <div class="col-md-3">
        <div class="p-4">
          <i class="fas fa-shield-alt fa-3x mb-3" style="color: #b71c1c;"></i>
          <h5>Compra Segura</h5>
          <p class="text-muted small">100% protegido</p>
        </div>
      </div>
      <div class="col-md-3">
        <div class="p-4">
          <i class="fas fa-headset fa-3x mb-3" style="color: #b71c1c;"></i>
          <h5>Soporte 24/7</h5>
          <p class="text-muted small">Siempre disponibles</p>
        </div>
      </div>
    </div>
  </section>

  <!-- Footer -->
  <footer class="pie-pagina">
    <div class="contenedor-footer">
      <div class="info-footer">
        <h3>FireStore</h3>
        <p>Todo el equipo de bomberos a tu alcance.</p>
      </div>
      <div class="redes-footer">
        <h4>Síguenos</h4>
        <a href="#">Facebook</a>
        <a href="#">Instagram</a>
        <a href="#">Twitter</a>
      </div>
      <div class="contacto-footer">
        <h4>Contacto</h4>
        <p>Email: contacto@firestore.com</p>
        <p>Tel: +52 55 1234 5678</p>
      </div>
    </div>
    
    <div class="derechos">
      <p>&copy; 2025 FireStore. Todos los derechos reservados.</p>
    </div>
  </footer>

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