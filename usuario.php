<?php
require_once 'conexion.php';

class Usuario {
    private $pdo;

    // Constructor para inicializar la conexión
    public function __construct() {
        $conexion = new Conexion();
        $this->pdo = $conexion->conectar();
        $this->pdo->exec("SET NAMES 'utf8mb4'");
    }

    // Registrar un nuevo usuario
    public function registrarUsuario($nombre, $apellido_paterno, $apellido_materno, $email, $telefono, $dni, $nombre_usuario, $clave) {
        try {
            // Validar si el usuario ya existe (por email, DNI o nombre de usuario)
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
                    return ["status" => false, "message" => "El correo electrónico ya está registrado."];
                }
                if ($resultado['dni'] === $dni) {
                    return ["status" => false, "message" => "El DNI ya está registrado."];
                }
                if ($resultado['nombre_usuario'] === $nombre_usuario) {
                    return ["status" => false, "message" => "El nombre de usuario ya está registrado."];
                }
            }

            // Insertar el nuevo usuario
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
                'clave' => password_hash($clave, PASSWORD_BCRYPT) // Encriptar contraseña
            ]);

            return ["status" => true, "message" => "Usuario registrado exitosamente."];
        } catch (PDOException $e) {
            // Capturar errores de duplicado o problemas de SQL
            if ($e->getCode() === '23000') { // Código de error para entradas duplicadas
                return ["status" => false, "message" => "El correo electrónico, DNI o nombre de usuario ya está registrado."];
            }
            return ["status" => false, "message" => "Error al registrar el usuario: " . $e->getMessage()];
        }
    }

    // Verificar credenciales de inicio de sesión
    public function verificarCredenciales($nombre_usuario, $clave) {
        try {
            $sql = "SELECT * FROM usuarios WHERE nombre_usuario = :nombre_usuario";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['nombre_usuario' => $nombre_usuario]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($usuario && password_verify($clave, $usuario['clave'])) {
                return ["status" => true, "user" => $usuario]; // Devuelve los datos del usuario
            }
            return ["status" => false, "message" => "Nombre de usuario o contraseña incorrectos."];
        } catch (PDOException $e) {
            return ["status" => false, "message" => "Error al verificar las credenciales: " . $e->getMessage()];
        }
    }

    // Obtener datos del usuario por ID
    public function obtenerUsuarioPorId($id) {
        try {
            $sql = "SELECT * FROM usuarios WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['id' => $id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return ["status" => false, "message" => "Error al obtener el usuario: " . $e->getMessage()];
        }
    }

    // Editar datos de usuario
    public function editarUsuario($id, $nombre, $apellido_paterno, $apellido_materno, $email, $telefono, $dni) {
        try {
            $sql = "UPDATE usuarios SET nombre = :nombre, apellido_paterno = :apellido_paterno, apellido_materno = :apellido_materno,
                    email = :email, telefono = :telefono, dni = :dni WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'id' => $id,
                'nombre' => $nombre,
                'apellido_paterno' => $apellido_paterno,
                'apellido_materno' => $apellido_materno,
                'email' => $email,
                'telefono' => $telefono,
                'dni' => $dni
            ]);
            return ["status" => true, "message" => "Usuario actualizado exitosamente."];
        } catch (PDOException $e) {
            return ["status" => false, "message" => "Error al actualizar el usuario: " . $e->getMessage()];
        }
    }

    // Eliminar usuario
    public function eliminarUsuario($id) {
        try {
            $sql = "DELETE FROM usuarios WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['id' => $id]);
            return ["status" => true, "message" => "Usuario eliminado exitosamente."];
        } catch (PDOException $e) {
            return ["status" => false, "message" => "Error al eliminar el usuario: " . $e->getMessage()];
        }
    }

    // Listar todos los usuarios
    public function listarUsuarios() {
        try {
            $sql = "SELECT * FROM usuarios";
            $stmt = $this->pdo->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return ["status" => false, "message" => "Error al listar los usuarios: " . $e->getMessage()];
        }
    }
}
?>
