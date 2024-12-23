<?php
require_once 'conexion.php';

$conexion = new Conexion();

try {
    $pdo = $conexion->conectar();
    echo "Conexión exitosa a la base de datos.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
