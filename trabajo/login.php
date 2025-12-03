<?php
require_once 'config/database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = isset($_POST['usuario']) ? trim($_POST['usuario']) : '';
    $contrasena = isset($_POST['contrasena']) ? $_POST['contrasena'] : '';
    
    if (!empty($usuario) && !empty($contrasena)) {
        $conn = getConnection();
        
        $stmt = $conn->prepare("SELECT * FROM usuarios WHERE usuario = :usuario AND activo = 1");
        $stmt->execute(['usuario' => $usuario]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($contrasena, $user['contrasena'])) {
            $_SESSION['usuario'] = $user['usuario'];
            $_SESSION['id_usuario'] = $user['id_usuario'];
            $_SESSION['nombre_completo'] = $user['nombre_completo'];
            
            header('Location: productos.php?login=success');
            exit;
        } else {
            $error = 'Usuario o contraseña incorrectos';
        }
    } else {
        $error = 'Por favor completa todos los campos';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Iniciar sesión - FireStore</title>
  <link rel="stylesheet" href="css/login.css" />
  
  <!-- Bootstrap para alertas -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

  <div class="contenedor-login">
    <div class="tarjeta-login">
      <h2>🔥 Iniciar sesión 🔥</h2>
      
      <?php if($error): ?>
      <div class="alert alert-danger" role="alert">
        <?php echo htmlspecialchars($error); ?>
      </div>
      <?php endif; ?>
      
      <form method="POST" action="login.php">
        <label for="usuario">Usuario</label>
        <input type="text" id="usuario" name="usuario" placeholder="Ingresa tu usuario" required>
        
        <label for="contrasena">Contraseña</label>
        <input type="password" id="contrasena" name="contrasena" placeholder="Ingresa tu contraseña" required>

        <button type="submit" class="boton-login">Entrar</button>
      </form>

      <p class="registro">¿No tienes cuenta? <a href="registro.php">Regístrate aquí</a></p>
      <p class="text-center mt-2">
        <a href="productos.php" style="color: #666; font-size: 0.9rem;">← Volver a la tienda</a>
      </p>
      
      <div class="mt-3 p-3" style="background: #f0f0f0; border-radius: 8px; font-size: 0.85rem;">
        <strong>Usuario de prueba:</strong><br>
        Usuario: <code>admin</code><br>
        Contraseña: <code>bombero123</code>
      </div>
    </div>
  </div>
</body>
</html>