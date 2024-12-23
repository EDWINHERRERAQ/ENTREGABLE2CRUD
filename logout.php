<?php
session_start();
session_unset(); // Eliminar todas las variables de sesión
session_destroy(); // Destruir la sesión activa

// Redirigir al formulario de registro
header("Location: index.php");
exit;
?>
