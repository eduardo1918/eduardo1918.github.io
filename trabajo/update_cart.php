<?php
require_once 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: carrito.php');
    exit;
}

$conn = getConnection();

$id_carrito = isset($_POST['id_carrito']) ? (int)$_POST['id_carrito'] : 0;
$action = isset($_POST['action']) ? $_POST['action'] : '';

if (!$id_carrito || !$action) {
    header('Location: carrito.php');
    exit;
}

try {
    switch ($action) {
        case 'increase':
            $max_stock = isset($_POST['max_stock']) ? (int)$_POST['max_stock'] : 0;
            
            // Obtener cantidad actual
            $stmt = $conn->prepare("SELECT cantidad FROM carrito WHERE id_carrito = :id");
            $stmt->execute(['id' => $id_carrito]);
            $current = $stmt->fetch();
            
            if ($current && $current['cantidad'] < $max_stock) {
                $nueva_cantidad = $current['cantidad'] + 1;
                $update = $conn->prepare("UPDATE carrito SET cantidad = :cantidad WHERE id_carrito = :id");
                $update->execute(['cantidad' => $nueva_cantidad, 'id' => $id_carrito]);
                header('Location: carrito.php?updated=success');
            } else {
                header('Location: carrito.php?updated=stock');
            }
            break;
            
        case 'decrease':
            // Obtener cantidad actual
            $stmt = $conn->prepare("SELECT cantidad FROM carrito WHERE id_carrito = :id");
            $stmt->execute(['id' => $id_carrito]);
            $current = $stmt->fetch();
            
            if ($current && $current['cantidad'] > 1) {
                $nueva_cantidad = $current['cantidad'] - 1;
                $update = $conn->prepare("UPDATE carrito SET cantidad = :cantidad WHERE id_carrito = :id");
                $update->execute(['cantidad' => $nueva_cantidad, 'id' => $id_carrito]);
            } else {
                // Si es 1, eliminar el item
                $delete = $conn->prepare("DELETE FROM carrito WHERE id_carrito = :id");
                $delete->execute(['id' => $id_carrito]);
            }
            header('Location: carrito.php?updated=success');
            break;
            
        case 'remove':
            $delete = $conn->prepare("DELETE FROM carrito WHERE id_carrito = :id");
            $delete->execute(['id' => $id_carrito]);
            header('Location: carrito.php?removed=success');
            break;
            
        default:
            header('Location: carrito.php');
            break;
    }
} catch (Exception $e) {
    header('Location: carrito.php?error=general');
}

exit;
?>