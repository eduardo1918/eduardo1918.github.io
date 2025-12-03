<?php
/**
 * Archivo de configuración de base de datos - FireStore
 * Guarda este archivo en: config/database.php
 */

// Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_USER', 'root');              // Cambia esto por tu usuario
define('DB_PASS', '');                  // Cambia esto por tu contraseña
define('DB_NAME', 'firestore_db');
define('DB_CHARSET', 'utf8mb4');

// Crear conexión
function getConnection() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        return $pdo;
    } catch (PDOException $e) {
        die("Error de conexión: " . $e->getMessage());
    }
}

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Generar ID de sesión único para el carrito
if (!isset($_SESSION['cart_id'])) {
    $_SESSION['cart_id'] = session_id();
}
?>