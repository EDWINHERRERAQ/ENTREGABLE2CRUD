<?php
session_start();

// Verificar si ya hay un usuario autenticado
if (isset($_SESSION['user_id'])) {
    header("Location: cuenta_bancaria.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Usuario - Sistema Bancario</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <form id="registerForm">
        <h1>Registro de Usuario</h1>
        <label for="nombre">Nombre:</label>
        <input type="text" id="nombre" name="nombre" required>

        <label for="apellido_paterno">Apellido Paterno:</label>
        <input type="text" id="apellido_paterno" name="apellido_paterno" required>

        <label for="apellido_materno">Apellido Materno:</label>
        <input type="text" id="apellido_materno" name="apellido_materno" required>

        <label for="email">Correo Electrónico:</label>
        <input type="email" id="email" name="email" required>

        <label for="telefono">Teléfono:</label>
        <input type="text" id="telefono" name="telefono" required>

        <label for="dni">DNI:</label>
        <input type="text" id="dni" name="dni" required>

        <label for="nombre_usuario">Nombre de Usuario:</label>
        <input type="text" id="nombre_usuario" name="nombre_usuario" required>

        <label for="password">Contraseña:</label>
        <input type="password" id="password" name="password" required>

        <button type="submit">Registrarse</button>
        <p id="response"></p>
        <p>¿Ya tienes una cuenta? <a href="index.php">Inicia sesión aquí</a>.</p>
    </form>

    <script>
        $(document).ready(function () {
            $('#registerForm').on('submit', function (e) {
                e.preventDefault();
                $.ajax({
                    url: 'controller_login.php', // Archivo que procesará el registro
                    type: 'POST',
                    data: $(this).serialize() + '&action=register', // Enviar los datos junto con la acción
                    success: function (data) {
                        $('#response').html(data); // Mostrar la respuesta en el <p id="response">
                        if (data.includes('Registro exitoso')) {
                            window.location.href = 'index.php'; // Redirigir al login si el registro fue exitoso
                        }
                    }
                });
            });
        });
    </script>
</body>
</html>
