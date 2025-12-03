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
        // AGREGAR OFERTA
        $id_producto = (int)$_POST['id_producto'];
        $tipo = $_POST['tipo_descuento'];
        $valor = (float)$_POST['valor_descuento'];
        $fecha_inicio = $_POST['fecha_inicio'];
        $fecha_fin = $_POST['fecha_fin'];
        
        if ($id_producto > 0 && $valor > 0) {
            $stmt = $conn->prepare("INSERT INTO ofertas (id_producto, tipo_descuento, valor_descuento, fecha_inicio, fecha_fin, activo) VALUES (?, ?, ?, ?, ?, 1)");
            if ($stmt->execute([$id_producto, $tipo, $valor, $fecha_inicio, $fecha_fin])) {
                $mensaje = "✅ Oferta creada exitosamente";
            } else {
                $error = "❌ Error al crear oferta";
            }
        }
    }
    elseif ($action === 'edit') {
        // EDITAR OFERTA
        $id = (int)$_POST['id_oferta'];
        $tipo = $_POST['tipo_descuento'];
        $valor = (float)$_POST['valor_descuento'];
        $fecha_inicio = $_POST['fecha_inicio'];
        $fecha_fin = $_POST['fecha_fin'];
        $activo = isset($_POST['activo']) ? 1 : 0;
        
        $stmt = $conn->prepare("UPDATE ofertas SET tipo_descuento=?, valor_descuento=?, fecha_inicio=?, fecha_fin=?, activo=? WHERE id_oferta=?");
        if ($stmt->execute([$tipo, $valor, $fecha_inicio, $fecha_fin, $activo, $id])) {
            $mensaje = "✅ Oferta actualizada exitosamente";
        } else {
            $error = "❌ Error al actualizar oferta";
        }
    }
    elseif ($action === 'delete') {
        // ELIMINAR OFERTA
        $id = (int)$_POST['id_oferta'];
        $stmt = $conn->prepare("DELETE FROM ofertas WHERE id_oferta=?");
        if ($stmt->execute([$id])) {
            $mensaje = "✅ Oferta eliminada exitosamente";
        } else {
            $error = "❌ Error al eliminar oferta";
        }
    }
}

// OBTENER OFERTAS
$ofertas = $conn->query("SELECT o.*, p.nombre as producto_nombre, p.precio_base FROM ofertas o INNER JOIN productos p ON o.id_producto = p.id_producto ORDER BY o.id_oferta DESC")->fetchAll();

// OBTENER PRODUCTOS SIN OFERTA ACTIVA
$productos_disponibles = $conn->query("SELECT p.* FROM productos p WHERE p.activo=1 AND p.id_producto NOT IN (SELECT id_producto FROM ofertas WHERE activo=1 AND CURDATE() BETWEEN fecha_inicio AND fecha_fin)")->fetchAll();

// SI SE ESTÁ EDITANDO
$oferta_editar = null;
if (isset($_GET['edit'])) {
    $id_edit = (int)$_GET['edit'];
    $stmt = $conn->prepare("SELECT o.*, p.nombre as producto_nombre FROM ofertas o INNER JOIN productos p ON o.id_producto = p.id_producto WHERE o.id_oferta=?");
    $stmt->execute([$id_edit]);
    $oferta_editar = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Gestión de Ofertas - FireStore Admin</title>
  
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
    
    <a href="admin_panel.php">
      <i class="fas fa-chart-line"></i> Dashboard
    </a>
    <a href="admin_productos.php">
      <i class="fas fa-box"></i> Productos
    </a>
    <a href="admin_ofertas.php" class="active">
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
    <h1 class="mb-4"><i class="fas fa-tags"></i> Gestión de Ofertas</h1>

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
        <?php echo $oferta_editar ? '<i class="fas fa-edit"></i> Editar Oferta' : '<i class="fas fa-plus"></i> Crear Nueva Oferta'; ?>
      </h4>
      
      <form method="POST">
        <input type="hidden" name="action" value="<?php echo $oferta_editar ? 'edit' : 'add'; ?>">
        <?php if($oferta_editar): ?>
        <input type="hidden" name="id_oferta" value="<?php echo $oferta_editar['id_oferta']; ?>">
        <?php endif; ?>
        
        <div class="row g-3">
          <?php if(!$oferta_editar): ?>
          <div class="col-md-6">
            <label class="form-label">Producto *</label>
            <select class="form-select" name="id_producto" required>
              <option value="">Seleccionar producto...</option>
              <?php foreach($productos_disponibles as $prod): ?>
              <option value="<?php echo $prod['id_producto']; ?>">
                <?php echo htmlspecialchars($prod['nombre']); ?> ($<?php echo number_format($prod['precio_base'], 2); ?>)
              </option>
              <?php endforeach; ?>
            </select>
            <small class="text-muted">Solo productos sin oferta activa</small>
          </div>
          <?php else: ?>
          <div class="col-md-6">
            <label class="form-label">Producto</label>
            <input type="text" class="form-control" value="<?php echo htmlspecialchars($oferta_editar['producto_nombre']); ?>" disabled>
          </div>
          <?php endif; ?>
          
          <div class="col-md-3">
            <label class="form-label">Tipo de Descuento *</label>
            <select class="form-select" name="tipo_descuento" id="tipoDescuento" required>
              <option value="porcentaje" <?php echo ($oferta_editar && $oferta_editar['tipo_descuento'] == 'porcentaje') ? 'selected' : ''; ?>>Porcentaje (%)</option>
              <option value="cantidad" <?php echo ($oferta_editar && $oferta_editar['tipo_descuento'] == 'cantidad') ? 'selected' : ''; ?>>Cantidad ($)</option>
            </select>
          </div>
          
          <div class="col-md-3">
            <label class="form-label">Valor del Descuento *</label>
            <input type="number" step="0.01" class="form-control" name="valor_descuento" id="valorDescuento" value="<?php echo $oferta_editar['valor_descuento'] ?? ''; ?>" required>
            <small class="text-muted" id="descuentoHelp">Ejemplo: 20 para 20%</small>
          </div>
          
          <div class="col-md-6">
            <label class="form-label">Fecha de Inicio *</label>
            <input type="date" class="form-control" name="fecha_inicio" value="<?php echo $oferta_editar['fecha_inicio'] ?? date('Y-m-d'); ?>" required>
          </div>
          
          <div class="col-md-6">
            <label class="form-label">Fecha de Fin *</label>
            <input type="date" class="form-control" name="fecha_fin" value="<?php echo $oferta_editar['fecha_fin'] ?? ''; ?>" required>
          </div>
          
          <?php if($oferta_editar): ?>
          <div class="col-md-12">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" name="activo" id="activo" <?php echo $oferta_editar['activo'] ? 'checked' : ''; ?>>
              <label class="form-check-label" for="activo">
                Oferta activa (visible en la tienda)
              </label>
            </div>
          </div>
          <?php endif; ?>
        </div>
        
        <div class="mt-3">
          <button type="submit" class="btn btn-admin">
            <i class="fas fa-save"></i> <?php echo $oferta_editar ? 'Actualizar' : 'Crear'; ?> Oferta
          </button>
          <?php if($oferta_editar): ?>
          <a href="admin_ofertas.php" class="btn btn-secondary">
            <i class="fas fa-times"></i> Cancelar
          </a>
          <?php endif; ?>
        </div>
      </form>
    </div>

    <!-- LISTA DE OFERTAS -->
    <div class="card-admin">
      <h4 class="mb-3"><i class="fas fa-list"></i> Todas las Ofertas (<?php echo count($ofertas); ?>)</h4>
      
      <div class="table-responsive">
        <table class="table table-hover">
          <thead>
            <tr>
              <th>ID</th>
              <th>Producto</th>
              <th>Precio Original</th>
              <th>Descuento</th>
              <th>Precio Final</th>
              <th>Vigencia</th>
              <th>Estado</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($ofertas as $oferta): 
              $precio_final = $oferta['tipo_descuento'] == 'porcentaje' 
                ? $oferta['precio_base'] * (1 - $oferta['valor_descuento']/100)
                : $oferta['precio_base'] - $oferta['valor_descuento'];
              
              $hoy = date('Y-m-d');
              $vigente = ($hoy >= $oferta['fecha_inicio'] && $hoy <= $oferta['fecha_fin'] && $oferta['activo']);
            ?>
            <tr>
              <td><?php echo $oferta['id_oferta']; ?></td>
              <td><?php echo htmlspecialchars($oferta['producto_nombre']); ?></td>
              <td>$<?php echo number_format($oferta['precio_base'], 2); ?></td>
              <td>
                <?php if($oferta['tipo_descuento'] == 'porcentaje'): ?>
                  <span class="badge bg-success">-<?php echo $oferta['valor_descuento']; ?>%</span>
                <?php else: ?>
                  <span class="badge bg-info">-$<?php echo number_format($oferta['valor_descuento'], 2); ?></span>
                <?php endif; ?>
              </td>
              <td><strong>$<?php echo number_format($precio_final, 2); ?></strong></td>
              <td>
                <small><?php echo date('d/m/Y', strtotime($oferta['fecha_inicio'])); ?></small><br>
                <small><?php echo date('d/m/Y', strtotime($oferta['fecha_fin'])); ?></small>
              </td>
              <td>
                <?php if($vigente): ?>
                  <span class="badge bg-success">Activa</span>
                <?php elseif(!$oferta['activo']): ?>
                  <span class="badge bg-secondary">Desactivada</span>
                <?php else: ?>
                  <span class="badge bg-warning">Expirada</span>
                <?php endif; ?>
              </td>
              <td>
                <a href="admin_ofertas.php?edit=<?php echo $oferta['id_oferta']; ?>" class="btn btn-sm btn-primary">
                  <i class="fas fa-edit"></i>
                </a>
                <form method="POST" class="d-inline" onsubmit="return confirm('¿Eliminar esta oferta?');">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id_oferta" value="<?php echo $oferta['id_oferta']; ?>">
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
  <script>
    // Actualizar texto de ayuda según tipo de descuento
    document.getElementById('tipoDescuento').addEventListener('change', function() {
      const helpText = document.getElementById('descuentoHelp');
      if(this.value === 'porcentaje') {
        helpText.textContent = 'Ejemplo: 20 para 20%';
      } else {
        helpText.textContent = 'Ejemplo: 150 para $150 MXN';
      }
    });
  </script>
</body>
</html>