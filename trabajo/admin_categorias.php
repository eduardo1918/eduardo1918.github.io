<?php
require_once 'config/database.php';

if (!isset($_SESSION['usuario']) || $_SESSION['usuario'] !== 'admin') {
    header('Location: login.php?error=unauthorized');
    exit;
}

$conn = getConnection();
$mensaje = '';

// PROCESAR ACCIONES
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $nombre = trim($_POST['nombre']);
        $descripcion = trim($_POST['descripcion']);
        $imagen = trim($_POST['imagen']);
        
        if (!empty($nombre)) {
            $stmt = $conn->prepare("INSERT INTO categorias (nombre, descripcion, imagen) VALUES (?, ?, ?)");
            if ($stmt->execute([$nombre, $descripcion, $imagen])) {
                $mensaje = "✅ Categoría agregada";
            }
        }
    }
    elseif ($action === 'edit') {
        $id = (int)$_POST['id_categoria'];
        $nombre = trim($_POST['nombre']);
        $descripcion = trim($_POST['descripcion']);
        $imagen = trim($_POST['imagen']);
        $activo = isset($_POST['activo']) ? 1 : 0;
        
        $stmt = $conn->prepare("UPDATE categorias SET nombre=?, descripcion=?, imagen=?, activo=? WHERE id_categoria=?");
        if ($stmt->execute([$nombre, $descripcion, $imagen, $activo, $id])) {
            $mensaje = "✅ Categoría actualizada";
        }
    }
}

// OBTENER CATEGORÍAS
$categorias = $conn->query("SELECT * FROM categorias ORDER BY id_categoria")->fetchAll();

// SI SE ESTÁ EDITANDO
$cat_editar = null;
if (isset($_GET['edit'])) {
    $id_edit = (int)$_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM categorias WHERE id_categoria=?");
    $stmt->execute([$id_edit]);
    $cat_editar = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Gestión de Categorías - FireStore Admin</title>
  
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  
  <style>
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
      background: #f5f5f5;
      min-height: 100vh;
    }
    .card-admin {
      background: white;
      border-radius: 12px;
      padding: 25px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      margin-bottom: 20px;
    }
    .btn-admin {
      background: #b71c1c;
      color: white;
      border: none;
      padding: 10px 20px;
      border-radius: 8px;
    }
    .btn-admin:hover {
      background: #8b0000;
      color: white;
    }
  </style>
</head>
<body>

  <!-- SIDEBAR -->
  <div class="sidebar">
    <div class="logo">
      <i class="fas fa-fire"></i> FireStore Admin
    </div>
    <a href="admin_panel.php"><i class="fas fa-chart-line"></i> Dashboard</a>
    <a href="admin_productos.php"><i class="fas fa-box"></i> Productos</a>
    <a href="admin_ofertas.php"><i class="fas fa-tags"></i> Ofertas</a>
    <a href="admin_usuarios.php"><i class="fas fa-users"></i> Usuarios</a>
    <a href="admin_categorias.php" class="active"><i class="fas fa-list"></i> Categorías</a>
    <hr style="border-color: rgba(255,255,255,0.2); margin: 20px;">
    <a href="productos.php"><i class="fas fa-store"></i> Ver Tienda</a>
    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a>
  </div>

  <!-- MAIN CONTENT -->
  <div class="main-content">
    <h1 class="mb-4"><i class="fas fa-list"></i> Gestión de Categorías</h1>

    <?php if($mensaje): ?>
    <div class="alert alert-success alert-dismissible fade show">
      <?php echo $mensaje; ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- FORMULARIO -->
    <div class="card-admin">
      <h4 class="mb-3">
        <?php echo $cat_editar ? '<i class="fas fa-edit"></i> Editar Categoría' : '<i class="fas fa-plus"></i> Nueva Categoría'; ?>
      </h4>
      
      <form method="POST">
        <input type="hidden" name="action" value="<?php echo $cat_editar ? 'edit' : 'add'; ?>">
        <?php if($cat_editar): ?>
        <input type="hidden" name="id_categoria" value="<?php echo $cat_editar['id_categoria']; ?>">
        <?php endif; ?>
        
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label">Nombre *</label>
            <input type="text" class="form-control" name="nombre" value="<?php echo $cat_editar['nombre'] ?? ''; ?>" required>
          </div>
          
          <div class="col-md-4">
            <label class="form-label">Imagen (ruta)</label>
            <input type="text" class="form-control" name="imagen" value="<?php echo $cat_editar['imagen'] ?? 'imagenes/'; ?>" placeholder="imagenes/categoria.png">
          </div>
          
          <div class="col-md-4">
            <?php if($cat_editar): ?>
            <label class="form-label">Estado</label><br>
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" name="activo" id="activo" <?php echo $cat_editar['activo'] ? 'checked' : ''; ?>>
              <label class="form-check-label" for="activo">Activa</label>
            </div>
            <?php endif; ?>
          </div>
          
          <div class="col-md-12">
            <label class="form-label">Descripción</label>
            <textarea class="form-control" name="descripcion" rows="2"><?php echo $cat_editar['descripcion'] ?? ''; ?></textarea>
          </div>
        </div>
        
        <div class="mt-3">
          <button type="submit" class="btn btn-admin">
            <i class="fas fa-save"></i> <?php echo $cat_editar ? 'Actualizar' : 'Agregar'; ?>
          </button>
          <?php if($cat_editar): ?>
          <a href="admin_categorias.php" class="btn btn-secondary">
            <i class="fas fa-times"></i> Cancelar
          </a>
          <?php endif; ?>
        </div>
      </form>
    </div>

    <!-- LISTA -->
    <div class="card-admin">
      <h4 class="mb-3"><i class="fas fa-list"></i> Todas las Categorías (<?php echo count($categorias); ?>)</h4>
      
      <div class="table-responsive">
        <table class="table table-hover">
          <thead>
            <tr>
              <th>ID</th>
              <th>Nombre</th>
              <th>Descripción</th>
              <th>Imagen</th>
              <th>Estado</th>
              <th>Acción</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($categorias as $cat): ?>
            <tr>
              <td><?php echo $cat['id_categoria']; ?></td>
              <td><strong><?php echo htmlspecialchars($cat['nombre']); ?></strong></td>
              <td><?php echo htmlspecialchars($cat['descripcion']); ?></td>
              <td><small class="text-muted"><?php echo htmlspecialchars($cat['imagen']); ?></small></td>
              <td>
                <?php if($cat['activo']): ?>
                  <span class="badge bg-success">Activa</span>
                <?php else: ?>
                  <span class="badge bg-secondary">Inactiva</span>
                <?php endif; ?>
              </td>
              <td>
                <a href="admin_categorias.php?edit=<?php echo $cat['id_categoria']; ?>" class="btn btn-sm btn-primary">
                  <i class="fas fa-edit"></i> Editar
                </a>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>