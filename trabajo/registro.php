<?php
require_once 'config/database.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = isset($_POST['usuario']) ? trim($_POST['usuario']) : '';
    $contrasena = isset($_POST['contrasena']) ? $_POST['contrasena'] : '';
    $confirmar = isset($_POST['confirmar']) ? $_POST['confirmar'] : '';
    $nombre = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $telefono = isset($_POST['telefono']) ? trim($_POST['telefono']) : '';
    
    if (empty($usuario) || empty($contrasena) || empty($nombre) || empty($email)) {
        $error = 'Por favor completa todos los campos obligatorios';
    } elseif ($contrasena !== $confirmar) {
        $error = 'Las contraseñas no coinciden';
    } elseif (strlen($contrasena) < 6) {
        $error = 'La contraseña debe tener al menos 6 caracteres';
    } else {
        $conn = getConnection();
        
        // Verificar si el usuario ya existe
        $check = $conn->prepare("SELECT id_usuario FROM usuarios WHERE usuario = :usuario");
        $check->execute(['usuario' => $usuario]);
        
        if ($check->fetch()) {
            $error = 'El nombre de usuario ya está en uso';
        } else {
            // Insertar nuevo usuario
            $hash = password_hash($contrasena, PASSWORD_DEFAULT);
            
            $stmt = $conn->prepare("INSERT INTO usuarios (usuario, contrasena, nombre_completo, email, telefono) 
                                   VALUES (:usuario, :contrasena, :nombre, :email, :telefono)");
            
            if ($stmt->execute([
                'usuario' => $usuario,
                'contrasena' => $hash,
                'nombre' => $nombre,
                'email' => $email,
                'telefono' => $telefono
            ])) {
                $success = '¡Registro exitoso! Ahora puedes iniciar sesión';
            } else {
                $error = 'Error al registrar el usuario';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Registrarse - FireStore</title>
  <link rel="stylesheet" href="css/login.css" />
  
  <!-- Bootstrap -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

  <div class="contenedor-login">
    <div class="tarjeta-login">
      <h2>🔥 Registrarse 🔥</h2>
      
      <?php if($error): ?>
      <div class="alert alert-danger" role="alert">
        <?php echo htmlspecialchars($error); ?>
      </div>
      <?php endif; ?>
      
      <?php if($success): ?>
      <div class="alert alert-success" role="alert">
        <?php echo htmlspecialchars($success); ?>
        <br><a href="login.php" class="btn btn-sm btn-success mt-2">Iniciar sesión</a>
      </div>
      <?php endif; ?>
      
      <form method="POST" action="registro.php">
        <label for="usuario">Usuario *</label>
        <input type="text" id="usuario" name="usuario" placeholder="Elige un nombre de usuario" required>
        
        <label for="nombre">Nombre Completo *</label>
        <input type="text" id="nombre" name="nombre" placeholder="Tu nombre completo" required>
        
        <label for="email">Email *</label>
        <input type="email" id="email" name="email" placeholder="tu@email.com" required>
        
        <label for="telefono">Teléfono</label>
        <input type="tel" id="telefono" name="telefono" placeholder="+52 55 1234 5678">
        
        <label for="contrasena">Contraseña *</label>
        <input type="password" id="contrasena" name="contrasena" placeholder="Mínimo 6 caracteres" required>
        
        <label for="confirmar">Confirmar Contraseña *</label>
        <input type="password" id="confirmar" name="confirmar" placeholder="Repite tu contraseña" required>

        <button type="submit" class="boton-login">Registrarse</button>
      </form>

      <p class="registro">¿Ya tienes cuenta? <a href="login.php">Inicia sesión aquí</a></p>
      <p class="text-center mt-2">
        <a href="productos.php" style="color: #666; font-size: 0.9rem;">← Volver a la tienda</a>
      </p>
    </div>
  </div>
</body>
</html>