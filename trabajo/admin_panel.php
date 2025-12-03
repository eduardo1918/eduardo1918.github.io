<?php
require_once 'config/database.php';

// VERIFICAR QUE EL USUARIO SEA ADMIN
if (!isset($_SESSION['usuario']) || $_SESSION['usuario'] !== 'admin') {
    header('Location: login.php?error=unauthorized');
    exit;
}

$conn = getConnection();

// ESTADÍSTICAS
$stats = [];

// Total de productos
$stmt = $conn->query("SELECT COUNT(*) as total FROM productos WHERE activo = 1");
$stats['productos'] = $stmt->fetch()['total'];

// Total de categorías
$stmt = $conn->query("SELECT COUNT(*) as total FROM categorias WHERE activo = 1");
$stats['categorias'] = $stmt->fetch()['total'];

// Total de ofertas activas
$stmt = $conn->query("SELECT COUNT(*) as total FROM ofertas WHERE activo = 1 AND CURDATE() BETWEEN fecha_inicio AND fecha_fin");
$stats['ofertas'] = $stmt->fetch()['total'];

// Total de usuarios
$stmt = $conn->query("SELECT COUNT(*) as total FROM usuarios WHERE activo = 1");
$stats['usuarios'] = $stmt->fetch()['total'];

// Productos con bajo stock (menos de 20 unidades)
$stmt = $conn->query("SELECT * FROM productos WHERE stock < 20 AND activo = 1 ORDER BY stock ASC LIMIT 5");
$bajo_stock = $stmt->fetchAll();

// Productos más vendidos (simulado por ahora)
$stmt = $conn->query("SELECT * FROM productos WHERE activo = 1 ORDER BY stock DESC LIMIT 5");
$mas_vendidos = $stmt->fetchAll();

// Usuarios registrados recientemente
$stmt = $conn->query("SELECT * FROM usuarios ORDER BY fecha_registro DESC LIMIT 5");
$usuarios_recientes = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Panel de Administración - FireStore</title>
  
  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  
  <style>
    :root {
      --admin-primary: #b71c1c;
      --admin-secondary: #8b0000;
    }
    
    body {
      background: #f5f5f5;
    }
    
    .sidebar {
      background: linear-gradient(180deg, #8b0000, #b71c1c);
      min-height: 100vh;
      color: white;
      position: fixed;
      width: 250px;
      padding-top: 20px;
    }
    
    .sidebar .logo {
      text-align: center;
      padding: 20px;
      font-size: 1.5rem;
      font-weight: bold;
      border-bottom: 1px solid rgba(255,255,255,0.2);
      margin-bottom: 20px;
    }
    
    .sidebar a {
      color: white;
      text-decoration: none;
      padding: 15px 25px;
      display: block;
      transition: all 0.3s;
    }
    
    .sidebar a:hover, .sidebar a.active {
      background: rgba(255,255,255,0.2);
      border-left: 4px solid white;
    }
    
    .main-content {
      margin-left: 250px;
      padding: 30px;
    }
    
    .stat-card {
      background: white;
      border-radius: 12px;
      padding: 25px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      transition: transform 0.3s;
    }
    
    .stat-card:hover {
      transform: translateY(-5px);
    }
    
    .stat-card .icon {
      font-size: 3rem;
      margin-bottom: 15px;
    }
    
    .stat-card.red .icon { color: #b71c1c; }
    .stat-card.blue .icon { color: #1976d2; }
    .stat-card.green .icon { color: #388e3c; }
    .stat-card.orange .icon { color: #f57c00; }
    
    .stat-card h3 {
      font-size: 2.5rem;
      font-weight: bold;
      margin: 10px 0;
    }
    
    .data-table {
      background: white;
      border-radius: 12px;
      padding: 25px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      margin-top: 30px;
    }
    
    .btn-admin {
      background: var(--admin-primary);
      color: white;
      border: none;
      padding: 10px 20px;
      border-radius: 8px;
      transition: all 0.3s;
    }
    
    .btn-admin:hover {
      background: var(--admin-secondary);
      color: white;
    }
    
    .badge-stock-low {
      background: #dc3545;
      color: white;
      padding: 5px 10px;
      border-radius: 20px;
      font-size: 0.85rem;
    }
    
    .badge-stock-ok {
      background: #28a745;
      color: white;
      padding: 5px 10px;
      border-radius: 20px;
      font-size: 0.85rem;
    }
  </style>
</head>
<body>

  <!-- SIDEBAR -->
  <div class="sidebar">
    <div class="logo">
      <i class="fas fa-fire"></i> FireStore Admin
    </div>
    
    <a href="admin_panel.php" class="active">
      <i class="fas fa-chart-line"></i> Dashboard
    </a>
    <a href="admin_productos.php">
      <i class="fas fa-box"></i> Productos
    </a>
    <a href="admin_ofertas.php">
      <i class="fas fa-tags"></i> Ofertas
    </a>
    <a href="admin_usuarios.php">
      <i class="fas fa-users"></i> Usuarios
    </a>
    <a href="admin_categorias.php">
      <i class="fas fa-list"></i> Categorías
    </a>
    
    <hr style="border-color: rgba(255,255,255,0.2); margin: 20px;">
    
    <a href="productos.php">
      <i class="fas fa-store"></i> Ver Tienda
    </a>
    <a href="logout.php">
      <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
    </a>
  </div>

  <!-- MAIN CONTENT -->
  <div class="main-content">
    <!-- HEADER -->
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h1>Dashboard Administrativo</h1>
        <p class="text-muted">Bienvenido, <strong><?php echo htmlspecialchars($_SESSION['nombre_completo'] ?? 'Admin'); ?></strong></p>
      </div>
      <div>
        <span class="text-muted">
          <i class="fas fa-calendar"></i> <?php echo date('d/m/Y'); ?>
        </span>
      </div>
    </div>

    <!-- ESTADÍSTICAS -->
    <div class="row g-4 mb-4">
      <div class="col-md-3">
        <div class="stat-card red">
          <div class="icon"><i class="fas fa-box"></i></div>
          <h3><?php echo $stats['productos']; ?></h3>
          <p class="text-muted mb-0">Productos Activos</p>
        </div>
      </div>
      
      <div class="col-md-3">
        <div class="stat-card blue">
          <div class="icon"><i class="fas fa-list"></i></div>
          <h3><?php echo $stats['categorias']; ?></h3>
          <p class="text-muted mb-0">Categorías</p>
        </div>
      </div>
      
      <div class="col-md-3">
        <div class="stat-card green">
          <div class="icon"><i class="fas fa-tags"></i></div>
          <h3><?php echo $stats['ofertas']; ?></h3>
          <p class="text-muted mb-0">Ofertas Activas</p>
        </div>
      </div>
      
      <div class="col-md-3">
        <div class="stat-card orange">
          <div class="icon"><i class="fas fa-users"></i></div>
          <h3><?php echo $stats['usuarios']; ?></h3>
          <p class="text-muted mb-0">Usuarios Registrados</p>
        </div>
      </div>
    </div>

    <!-- ACCIONES RÁPIDAS -->
    <div class="row g-4 mb-4">
      <div class="col-12">
        <div class="data-table">
          <h4 class="mb-3"><i class="fas fa-bolt"></i> Acciones Rápidas</h4>
          <div class="row g-3">
            <div class="col-md-3">
              <a href="admin_productos.php?action=add" class="btn btn-admin w-100">
                <i class="fas fa-plus"></i> Nuevo Producto
              </a>
            </div>
            <div class="col-md-3">
              <a href="admin_ofertas.php?action=add" class="btn btn-admin w-100">
                <i class="fas fa-tag"></i> Nueva Oferta
              </a>
            </div>
            <div class="col-md-3">
              <a href="admin_categorias.php?action=add" class="btn btn-admin w-100">
                <i class="fas fa-list"></i> Nueva Categoría
              </a>
            </div>
            <div class="col-md-3">
              <a href="admin_usuarios.php" class="btn btn-outline-danger w-100">
                <i class="fas fa-users"></i> Ver Usuarios
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="row g-4">
      <!-- PRODUCTOS CON BAJO STOCK -->
      <div class="col-md-6">
        <div class="data-table">
          <h4 class="mb-3"><i class="fas fa-exclamation-triangle text-warning"></i> Stock Bajo</h4>
          <?php if(count($bajo_stock) > 0): ?>
          <div class="table-responsive">
            <table class="table table-hover">
              <thead>
                <tr>
                  <th>Producto</th>
                  <th>Stock</th>
                  <th>Acción</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach($bajo_stock as $prod): ?>
                <tr>
                  <td><?php echo htmlspecialchars($prod['nombre']); ?></td>
                  <td>
                    <span class="badge-stock-low">
                      <?php echo $prod['stock']; ?> unidades
                    </span>
                  </td>
                  <td>
                    <a href="admin_productos.php?edit=<?php echo $prod['id_producto']; ?>" class="btn btn-sm btn-outline-primary">
                      <i class="fas fa-edit"></i> Editar
                    </a>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <?php else: ?>
          <p class="text-muted">✅ Todos los productos tienen stock suficiente</p>
          <?php endif; ?>
        </div>
      </div>

      <!-- USUARIOS RECIENTES -->
      <div class="col-md-6">
        <div class="data-table">
          <h4 class="mb-3"><i class="fas fa-user-plus text-success"></i> Usuarios Recientes</h4>
          <?php if(count($usuarios_recientes) > 0): ?>
          <div class="table-responsive">
            <table class="table table-hover">
              <thead>
                <tr>
                  <th>Usuario</th>
                  <th>Email</th>
                  <th>Fecha</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach($usuarios_recientes as $user): ?>
                <tr>
                  <td>
                    <i class="fas fa-user-circle"></i> 
                    <?php echo htmlspecialchars($user['usuario']); ?>
                  </td>
                  <td><?php echo htmlspecialchars($user['email']); ?></td>
                  <td><?php echo date('d/m/Y', strtotime($user['fecha_registro'])); ?></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <?php else: ?>
          <p class="text-muted">No hay usuarios registrados</p>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>