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
    <title>Iniciar Sesión - Sistema Bancario</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <form id="loginForm">
        <h1>Iniciar Sesión</h1>
        <label for="nombre_usuario">Nombre de Usuario:</label>
        <input type="text" id="nombre_usuario" name="nombre_usuario" required>
        <label for="password">Contraseña:</label>
        <input type="password" id="password" name="password" required>
        <button type="submit">Ingresar</button>
        <p id="response"></p>
        <p>¿No tienes una cuenta? <a href="registrarse.php">Regístrate aquí</a>.</p>
    </form>

    <script>
        $(document).ready(function () {
            $('#loginForm').on('submit', function (e) {
                e.preventDefault();

                const nombreUsuario = $('#nombre_usuario').val();
                const password = $('#password').val();

                if (!nombreUsuario || !password) {
                    alert('Por favor, completa todos los campos.');
                    return;
                }

                $.ajax({
                    url: 'controller_login.php', // Archivo que procesará el inicio de sesión
                    type: 'POST',
                    data: $(this).serialize() + '&action=login', // Enviamos los datos junto con la acción
                    success: function (data) {
                        $('#response').html(data); // Mostrar la respuesta en el <p id="response">
                        if (data.includes('Redirigiendo...')) {
                            window.location.href = 'cuenta_bancaria.php'; // Redirigir si el inicio de sesión es exitoso
                        }
                    }
                });
            });
        });
    </script>
</body>
</html>
