<?php
require_once 'config/database.php';

// VERIFICAR ADMIN
if (!isset($_SESSION['usuario']) || $_SESSION['usuario'] !== 'admin') {
    header('Location: login.php?error=unauthorized');
    exit;
}

$conn = getConnection();
$mensaje = '';
$error = '';

// PROCESAR ACCIONES
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        // AGREGAR PRODUCTO
        $nombre = trim($_POST['nombre']);
        $descripcion = trim($_POST['descripcion']);
        $precio = (float)$_POST['precio'];
        $categoria = (int)$_POST['categoria'];
        $imagen = trim($_POST['imagen']);
        $stock = (int)$_POST['stock'];
        
        if (!empty($nombre) && $precio > 0 && $categoria > 0) {
            $stmt = $conn->prepare("INSERT INTO productos (nombre, descripcion, precio_base, id_categoria, imagen_principal, stock) VALUES (?, ?, ?, ?, ?, ?)");
            if ($stmt->execute([$nombre, $descripcion, $precio, $categoria, $imagen, $stock])) {
                $mensaje = "✅ Producto agregado exitosamente";
            } else {
                $error = "❌ Error al agregar producto";
            }
        }
    }
    elseif ($action === 'edit') {
        // EDITAR PRODUCTO
        $id = (int)$_POST['id_producto'];
        $nombre = trim($_POST['nombre']);
        $descripcion = trim($_POST['descripcion']);
        $precio = (float)$_POST['precio'];
        $categoria = (int)$_POST['categoria'];
        $imagen = trim($_POST['imagen']);
        $stock = (int)$_POST['stock'];
        $activo = isset($_POST['activo']) ? 1 : 0;
        
        $stmt = $conn->prepare("UPDATE productos SET nombre=?, descripcion=?, precio_base=?, id_categoria=?, imagen_principal=?, stock=?, activo=? WHERE id_producto=?");
        if ($stmt->execute([$nombre, $descripcion, $precio, $categoria, $imagen, $stock, $activo, $id])) {
            $mensaje = "✅ Producto actualizado exitosamente";
        } else {
            $error = "❌ Error al actualizar producto";
        }
    }
    elseif ($action === 'delete') {
        // ELIMINAR PRODUCTO (desactivar)
        $id = (int)$_POST['id_producto'];
        $stmt = $conn->prepare("UPDATE productos SET activo=0 WHERE id_producto=?");
        if ($stmt->execute([$id])) {
            $mensaje = "✅ Producto eliminado exitosamente";
        } else {
            $error = "❌ Error al eliminar producto";
        }
    }
}

// OBTENER PRODUCTOS
$productos = $conn->query("SELECT p.*, c.nombre as categoria_nombre FROM productos p LEFT JOIN categorias c ON p.id_categoria = c.id_categoria ORDER BY p.id_producto DESC")->fetchAll();

// OBTENER CATEGORÍAS
$categorias = $conn->query("SELECT * FROM categorias WHERE activo=1")->fetchAll();

// SI SE ESTÁ EDITANDO, OBTENER EL PRODUCTO
$producto_editar = null;
if (isset($_GET['edit'])) {
    $id_edit = (int)$_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM productos WHERE id_producto=?");
    $stmt->execute([$id_edit]);
    $producto_editar = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Gestión de Productos - FireStore Admin</title>
  
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
    
    .product-img-small {
      width: 60px;
      height: 60px;
      object-fit: contain;
      background: #f5f5f5;
      border-radius: 8px;
      padding: 5px;
    }
  </style>
</head>
<body>

  <!-- SIDEBAR -->
  <div class="sidebar">
    <div class="logo">
      <i class="fas fa-fire"></i> FireStore Admin
    </div>
    
    <a href="admin_panel.php">
      <i class="fas fa-chart-line"></i> Dashboard
    </a>
    <a href="admin_productos.php" class="active">
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
    <h1 class="mb-4"><i class="fas fa-box"></i> Gestión de Productos</h1>

    <?php if($mensaje): ?>
    <div class="alert alert-success alert-dismissible fade show">
      <?php echo $mensaje; ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <?php if($error): ?>
    <div class="alert alert-danger alert-dismissible fade show">
      <?php echo $error; ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- FORMULARIO AGREGAR/EDITAR -->
    <div class="card-admin">
      <h4 class="mb-3">
        <?php echo $producto_editar ? '<i class="fas fa-edit"></i> Editar Producto' : '<i class="fas fa-plus"></i> Agregar Nuevo Producto'; ?>
      </h4>
      
      <form method="POST">
        <input type="hidden" name="action" value="<?php echo $producto_editar ? 'edit' : 'add'; ?>">
        <?php if($producto_editar): ?>
        <input type="hidden" name="id_producto" value="<?php echo $producto_editar['id_producto']; ?>">
        <?php endif; ?>
        
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Nombre del Producto *</label>
            <input type="text" class="form-control" name="nombre" value="<?php echo $producto_editar['nombre'] ?? ''; ?>" required>
          </div>
          
          <div class="col-md-3">
            <label class="form-label">Precio (MXN) *</label>
            <input type="number" step="0.01" class="form-control" name="precio" value="<?php echo $producto_editar['precio_base'] ?? ''; ?>" required>
          </div>
          
          <div class="col-md-3">
            <label class="form-label">Stock *</label>
            <input type="number" class="form-control" name="stock" value="<?php echo $producto_editar['stock'] ?? 0; ?>" required>
          </div>
          
          <div class="col-md-12">
            <label class="form-label">Descripción</label>
            <textarea class="form-control" name="descripcion" rows="2"><?php echo $producto_editar['descripcion'] ?? ''; ?></textarea>
          </div>
          
          <div class="col-md-6">
            <label class="form-label">Categoría *</label>
            <select class="form-select" name="categoria" required>
              <option value="">Seleccionar...</option>
              <?php foreach($categorias as $cat): ?>
              <option value="<?php echo $cat['id_categoria']; ?>" 
                      <?php echo ($producto_editar && $producto_editar['id_categoria'] == $cat['id_categoria']) ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($cat['nombre']); ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>
          
          <div class="col-md-6">
            <label class="form-label">Imagen (ruta)</label>
            <input type="text" class="form-control" name="imagen" value="<?php echo $producto_editar['imagen_principal'] ?? 'imagenes/'; ?>" placeholder="imagenes/producto.png">
          </div>
          
          <?php if($producto_editar): ?>
          <div class="col-md-12">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" name="activo" id="activo" <?php echo $producto_editar['activo'] ? 'checked' : ''; ?>>
              <label class="form-check-label" for="activo">
                Producto activo (visible en la tienda)
              </label>
            </div>
          </div>
          <?php endif; ?>
        </div>
        
        <div class="mt-3">
          <button type="submit" class="btn btn-admin">
            <i class="fas fa-save"></i> <?php echo $producto_editar ? 'Actualizar' : 'Agregar'; ?> Producto
          </button>
          <?php if($producto_editar): ?>
          <a href="admin_productos.php" class="btn btn-secondary">
            <i class="fas fa-times"></i> Cancelar
          </a>
          <?php endif; ?>
        </div>
      </form>
    </div>

    <!-- LISTA DE PRODUCTOS -->
    <div class="card-admin">
      <h4 class="mb-3"><i class="fas fa-list"></i> Todos los Productos (<?php echo count($productos); ?>)</h4>
      
      <div class="table-responsive">
        <table class="table table-hover">
          <thead>
            <tr>
              <th>ID</th>
              <th>Imagen</th>
              <th>Nombre</th>
              <th>Categoría</th>
              <th>Precio</th>
              <th>Stock</th>
              <th>Estado</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($productos as $prod): ?>
            <tr>
              <td><?php echo $prod['id_producto']; ?></td>
              <td>
                <img src="<?php echo htmlspecialchars($prod['imagen_principal']); ?>" class="product-img-small" alt="">
              </td>
              <td><?php echo htmlspecialchars($prod['nombre']); ?></td>
              <td><?php echo htmlspecialchars($prod['categoria_nombre']); ?></td>
              <td><strong>$<?php echo number_format($prod['precio_base'], 2); ?></strong></td>
              <td>
                <?php if($prod['stock'] < 20): ?>
                  <span class="badge bg-danger"><?php echo $prod['stock']; ?> unidades</span>
                <?php else: ?>
                  <span class="badge bg-success"><?php echo $prod['stock']; ?> unidades</span>
                <?php endif; ?>
              </td>
              <td>
                <?php if($prod['activo']): ?>
                  <span class="badge bg-success">Activo</span>
                <?php else: ?>
                  <span class="badge bg-secondary">Inactivo</span>
                <?php endif; ?>
              </td>
              <td>
                <a href="admin_productos.php?edit=<?php echo $prod['id_producto']; ?>" class="btn btn-sm btn-primary">
                  <i class="fas fa-edit"></i>
                </a>
                <form method="POST" class="d-inline" onsubmit="return confirm('¿Eliminar este producto?');">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id_producto" value="<?php echo $prod['id_producto']; ?>">
                  <button type="submit" class="btn btn-sm btn-danger">
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

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>