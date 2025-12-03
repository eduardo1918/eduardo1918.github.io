<?php
require_once 'config/database.php';

$conn = getConnection();

// Obtener categorías
$stmt_cat = $conn->query("SELECT * FROM categorias WHERE activo = 1");
$categorias = $stmt_cat->fetchAll();

// Obtener filtro de categoría
$categoria_filtro = isset($_GET['categoria']) ? $_GET['categoria'] : null;

// Obtener productos con ofertas
$sql = "SELECT p.*, c.nombre as categoria_nombre, 
        o.valor_descuento, o.tipo_descuento,
        CASE 
            WHEN o.tipo_descuento = 'porcentaje' THEN p.precio_base * (1 - o.valor_descuento/100)
            WHEN o.tipo_descuento = 'cantidad' THEN p.precio_base - o.valor_descuento
            ELSE p.precio_base 
        END as precio_final,
        o.id_oferta
        FROM productos p
        LEFT JOIN categorias c ON p.id_categoria = c.id_categoria
        LEFT JOIN ofertas o ON p.id_producto = o.id_producto 
            AND o.activo = 1 
            AND CURDATE() BETWEEN o.fecha_inicio AND o.fecha_fin
        WHERE p.activo = 1";

if ($categoria_filtro) {
    $sql .= " AND p.id_categoria = :categoria";
}

$sql .= " ORDER BY p.id_producto ASC";

$stmt = $conn->prepare($sql);
if ($categoria_filtro) {
    $stmt->bindParam(':categoria', $categoria_filtro);
}
$stmt->execute();
$productos = $stmt->fetchAll();

// Contar items en carrito
$cart_count_sql = "SELECT SUM(cantidad) as total FROM carrito WHERE session_id = :session_id";
$stmt_cart = $conn->prepare($cart_count_sql);
$stmt_cart->execute(['session_id' => $_SESSION['cart_id']]);
$cart_count = $stmt_cart->fetch()['total'] ?? 0;

// Verificar si el usuario está logueado
$usuario_logueado = isset($_SESSION['usuario']) ? $_SESSION['usuario'] : null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>FireStore - Equipamiento de Bomberos</title>
  
  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  
  <!-- CSS Personalizado -->
  <link rel="stylesheet" href="css/styles.css">
  
  <style>
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
    
    .badge-offer {
      position: absolute;
      top: 10px;
      right: 10px;
      background: #dc2626;
      color: white;
      padding: 5px 12px;
      border-radius: 20px;
      font-size: 0.85rem;
      font-weight: bold;
      z-index: 10;
    }
    
    .price-original {
      text-decoration: line-through;
      color: #999;
      font-size: 0.9rem;
      display: block;
    }
    
    .categoria-btn {
      background: white;
      border: 2px solid #ddd;
      padding: 10px 20px;
      border-radius: 8px;
      margin: 5px;
      cursor: pointer;
      transition: all 0.3s;
    }
    
    .categoria-btn:hover, .categoria-btn.active {
      background: #b71c1c;
      color: white;
      border-color: #b71c1c;
    }
  </style>
</head>
<body>

  <!-- Barra de navegación -->
  <nav>
    <div class="logo">
      FireStore 
      <img src="imagenes/logo.png" height="30px" width="30px" alt="Logo de FireStore">
    </div>

    <div class="menu" id="menu">
      <a href="productos.php">Productos</a>
      <a href="ofertas.php">Ofertas</a>
      <a href="#categorias">Categorías</a>
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

  <!-- Sección de Categorías -->
  <section id="categorias" class="container text-center my-4">
    <h3 class="mb-3">Filtrar por Categoría</h3>
    <div>
      <a href="productos.php" class="categoria-btn <?php echo !$categoria_filtro ? 'active' : ''; ?>">
        Todas
      </a>
      <?php foreach($categorias as $cat): ?>
        <a href="productos.php?categoria=<?php echo $cat['id_categoria']; ?>" 
           class="categoria-btn <?php echo $categoria_filtro == $cat['id_categoria'] ? 'active' : ''; ?>">
          <?php echo htmlspecialchars($cat['nombre']); ?>
        </a>
      <?php endforeach; ?>
    </div>
  </section>

  <!-- Sección de productos -->
  <section class="productos">
    <h2>
      <?php echo $categoria_filtro ? 'Productos Filtrados' : 'Nuestros Productos'; ?>
    </h2>

    <div class="contenedor-tarjetas">
      <?php foreach($productos as $prod): ?>
      <div class="tarjeta position-relative">
        <?php if($prod['id_oferta']): ?>
        <span class="badge-offer">
          <?php 
          if($prod['tipo_descuento'] == 'porcentaje') {
              echo '-' . $prod['valor_descuento'] . '%';
          } else {
              echo '-$' . number_format($prod['valor_descuento'], 2);
          }
          ?>
        </span>
        <?php endif; ?>
        
        <img src="<?php echo htmlspecialchars($prod['imagen_principal']); ?>" 
             alt="<?php echo htmlspecialchars($prod['nombre']); ?>">
        
        <div class="contenido-tarjeta">
          <h3><?php echo htmlspecialchars($prod['nombre']); ?></h3>
          <p><?php echo htmlspecialchars($prod['descripcion']); ?></p>
          
          <?php if($prod['id_oferta']): ?>
            <span class="price-original">$<?php echo number_format($prod['precio_base'], 2); ?> MXN</span>
            <span class="precio">$<?php echo number_format($prod['precio_final'], 2); ?> MXN</span>
          <?php else: ?>
            <span class="precio">$<?php echo number_format($prod['precio_base'], 2); ?> MXN</span>
          <?php endif; ?>
          
          <p class="text-muted small mt-2">
            <i class="fas fa-box"></i> Stock: <?php echo $prod['stock']; ?> unidades
          </p>
          
          <?php if($prod['stock'] > 0): ?>
            <form method="POST" action="add_to_cart.php" class="d-inline">
              <input type="hidden" name="id_producto" value="<?php echo $prod['id_producto']; ?>">
              <input type="hidden" name="cantidad" value="1">
              <button type="submit" class="boton-comprar">
                <i class="fas fa-shopping-cart"></i> Comprar
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

    <!-- Contenedor de mapa dentro del footer -->
    <div class="contenedor-mapa-footer">
      <iframe 
        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3440.2843117442935!2d-107.91905370006008!3d30.428041262148675!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x86dcac4fcecf127b%3A0x831d4a328ec0316!2sHeroico%20Cuerpo%20De%20Bomberos!5e0!3m2!1ses-419!2smx!4v1761185509846!5m2!1ses-419!2smx" 
        width="100%" 
        height="300" 
        style="border:0;" 
        allowfullscreen="" 
        loading="lazy" 
        referrerpolicy="no-referrer-when-downgrade">
      </iframe>
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
    
    // Alerta de producto agregado
    <?php if(isset($_GET['added']) && $_GET['added'] == 'success'): ?>
      alert('¡Producto agregado al carrito exitosamente!');
    <?php endif; ?>
  </script>
</body>
</html>