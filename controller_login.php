<?php
session_start();
require_once 'conexion.php';

class SistemaBancario {
    private $pdo;

    public function __construct() {
        $conexion = new Conexion();
        $this->pdo = $conexion->conectar();
        $this->pdo->exec("SET NAMES 'utf8mb4'");
    }

    /*************************************/
    /*          USUARIOS                */
    /*************************************/

    // Registrar un nuevo usuario
    public function registrarUsuario($nombre, $apellido_paterno, $apellido_materno, $email, $telefono, $dni, $nombre_usuario, $clave) {
        try {
            // Validar si el usuario ya existe
            $sql = "SELECT email, dni, nombre_usuario FROM usuarios 
                    WHERE email = :email OR dni = :dni OR nombre_usuario = :nombre_usuario";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'email' => $email,
                'dni' => $dni,
                'nombre_usuario' => $nombre_usuario
            ]);

            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($resultado) {
                if ($resultado['email'] === $email) {
                    return "El correo electrónico ya está registrado.";
                }
                if ($resultado['dni'] === $dni) {
                    return "El DNI ya está registrado.";
                }
                if ($resultado['nombre_usuario'] === $nombre_usuario) {
                    return "El nombre de usuario ya está registrado.";
                }
            }

            // Insertar el usuario si no existen duplicados
            $sql = "INSERT INTO usuarios (nombre, apellido_paterno, apellido_materno, email, telefono, dni, nombre_usuario, clave)
                    VALUES (:nombre, :apellido_paterno, :apellido_materno, :email, :telefono, :dni, :nombre_usuario, :clave)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'nombre' => $nombre,
                'apellido_paterno' => $apellido_paterno,
                'apellido_materno' => $apellido_materno,
                'email' => $email,
                'telefono' => $telefono,
                'dni' => $dni,
                'nombre_usuario' => $nombre_usuario,
                'clave' => password_hash($clave, PASSWORD_BCRYPT)
            ]);
            return "Usuario registrado exitosamente.";
        } catch (PDOException $e) {
            return "Error al registrar el usuario: " . $e->getMessage();
        }
    }

    // Verificar credenciales de inicio de sesión
    public function verificarCredenciales($nombre_usuario, $clave) {
        try {
            $sql = "SELECT id, nombre, clave FROM usuarios WHERE nombre_usuario = :nombre_usuario";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['nombre_usuario' => $nombre_usuario]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($usuario && password_verify($clave, $usuario['clave'])) {
                // Guardar datos del usuario en la sesión
                $_SESSION['user_id'] = $usuario['id'];
                $_SESSION['nombre_usuario'] = $usuario['nombre'];

                return ["status" => true, "message" => "Inicio de sesión exitoso."];
            }
            return ["status" => false, "message" => "Nombre de usuario o contraseña incorrectos."];
        } catch (PDOException $e) {
            return ["status" => false, "message" => "Error al verificar credenciales: " . $e->getMessage()];
        }
    }

    /*************************************/
    /*      CUENTAS BANCARIAS           */
    /*************************************/

    // Crear una nueva cuenta bancaria
    public function crearCuentaBancaria($usuario_id, $tipo) {
        try {
            $sql = "INSERT INTO cuentas_bancarias (usuario_id, tipo, saldo) VALUES (:usuario_id, :tipo, 0.00)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['usuario_id' => $usuario_id, 'tipo' => $tipo]);
            return "Cuenta bancaria creada exitosamente.";
        } catch (PDOException $e) {
            return "Error al crear la cuenta bancaria: " . $e->getMessage();
        }
    }

    // Obtener cuentas bancarias de un usuario
    public function obtenerCuentasBancarias($usuario_id) {
        try {
            $sql = "SELECT * FROM cuentas_bancarias WHERE usuario_id = :usuario_id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['usuario_id' => $usuario_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return "Error al obtener cuentas bancarias: " . $e->getMessage();
        }
    }

    /*************************************/
    /*          TRANSACCIONES           */
    /*************************************/

    // Registrar una nueva transacción
    public function registrarTransaccion($cuenta_id, $tipo_transaccion, $monto) {
        try {
            $sql = "INSERT INTO transacciones (cuenta_id, tipo_transaccion, monto)
                    VALUES (:cuenta_id, :tipo_transaccion, :monto)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'cuenta_id' => $cuenta_id,
                'tipo_transaccion' => $tipo_transaccion,
                'monto' => $monto
            ]);
            // Actualizar saldo de la cuenta bancaria
            $this->actualizarSaldoCuenta($cuenta_id, $tipo_transaccion, $monto);
            return "Transacción registrada exitosamente.";
        } catch (PDOException $e) {
            return "Error al registrar la transacción: " . $e->getMessage();
        }
    }

    // Actualizar saldo de una cuenta bancaria
    private function actualizarSaldoCuenta($cuenta_id, $tipo_transaccion, $monto) {
        $sql = "UPDATE cuentas_bancarias SET saldo = saldo + :monto WHERE id = :id";
        if ($tipo_transaccion === 'retiro') {
            $sql = "UPDATE cuentas_bancarias SET saldo = saldo - :monto WHERE id = :id";
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['monto' => $monto, 'id' => $cuenta_id]);
    }

    // Obtener transacciones de una cuenta bancaria
    public function obtenerTransacciones($cuenta_id) {
        try {
            $sql = "SELECT * FROM transacciones WHERE cuenta_id = :cuenta_id ORDER BY fecha DESC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['cuenta_id' => $cuenta_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return "Error al obtener transacciones: " . $e->getMessage();
        }
    }
}

/*************************************/
/*            MANEJAR POST          */
/*************************************/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sistema = new SistemaBancario();
    $action = $_POST['action'] ?? '';

    if ($action === 'register') {
        $resultado = $sistema->registrarUsuario(
            $_POST['nombre'],
            $_POST['apellido_paterno'],
            $_POST['apellido_materno'],
            $_POST['email'],
            $_POST['telefono'],
            $_POST['dni'],
            $_POST['nombre_usuario'],
            $_POST['password']
        );
        echo "<script>alert('$resultado'); window.location.href = 'registrarse.php';</script>";
    } elseif ($action === 'login') {
        $nombre_usuario = $_POST['nombre_usuario'];
        $clave = $_POST['password'];

        $resultado = $sistema->verificarCredenciales($nombre_usuario, $clave);

        if ($resultado['status']) {
            echo "<script>alert('{$resultado['message']}'); window.location.href = 'cuenta_bancaria.php';</script>";
        } else {
            echo "<script>alert('{$resultado['message']}'); window.history.back();</script>";
        }
    } elseif ($action === 'crear_cuenta') {
        if (!isset($_SESSION['user_id'])) {
            echo "<script>alert('Debes iniciar sesión primero.'); window.location.href = 'index.php';</script>";
            exit;
        }
        $usuario_id = $_SESSION['user_id'];
        $tipo = $_POST['tipo'];

        $mensaje = $sistema->crearCuentaBancaria($usuario_id, $tipo);
        echo "<script>alert('$mensaje'); window.location.href = 'cuenta_bancaria.php';</script>";
    } elseif ($action === 'registrar_transaccion') {
        if (!isset($_SESSION['user_id'])) {
            echo "<script>alert('Debes iniciar sesión primero.'); window.location.href = 'index.php';</script>";
            exit;
        }
        $cuenta_id = $_POST['cuenta_id'];
        $tipo_transaccion = $_POST['tipo_transaccion'];
        $monto = $_POST['monto'];

        $mensaje = $sistema->registrarTransaccion($cuenta_id, $tipo_transaccion, $monto);
        echo "<script>alert('$mensaje'); window.location.href = 'cuenta_bancaria.php';</script>";
    }
}
?>
