<?php
require_once 'conexion.php';
require_once 'Usuario.php';
session_start();

// Verificar si el usuario está autenticado
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// Nombre del usuario
$nombre_usuario = $_SESSION['nombre_usuario'] ?? "Invitado";

// Lista de tipos de cuenta permitidos
$tipos_cuenta_permitidos = ["Ahorro", "Cuenta Corriente"];

class CuentaBancaria {
    private $pdo;
    private $usuario_id;

    public function __construct($usuario_id) {
        $conexion = new Conexion();
        $this->pdo = $conexion->conectar();
        $this->pdo->exec("SET NAMES 'utf8mb4'");
        $this->usuario_id = $usuario_id;
    }

    // Obtener todas las cuentas bancarias del usuario
    public function obtenerCuentas() {
        $sql = "SELECT id, tipo, saldo, fecha_creacion FROM cuentas_bancarias WHERE usuario_id = :usuario_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['usuario_id' => $this->usuario_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Crear una nueva cuenta bancaria
    public function crearCuenta($tipo) {
        global $tipos_cuenta_permitidos;
        if (!in_array($tipo, $tipos_cuenta_permitidos)) {
            throw new Exception("Tipo de cuenta no válido.");
        }
        $sql = "INSERT INTO cuentas_bancarias (usuario_id, tipo, saldo, fecha_creacion) VALUES (:usuario_id, :tipo, 0.00, NOW())";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['usuario_id' => $this->usuario_id, 'tipo' => $tipo]);
    }

    // Registrar una transacción
    public function registrarTransaccion($cuenta_id, $tipo_transaccion, $monto) {
        if ($monto <= 0) {
            throw new Exception("El monto debe ser mayor a cero.");
        }

        if (!in_array($tipo_transaccion, ['deposito', 'retiro'])) {
            throw new Exception("Tipo de transacción no válido.");
        }

        if ($tipo_transaccion === 'retiro') {
            $sql = "SELECT saldo FROM cuentas_bancarias WHERE id = :cuenta_id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['cuenta_id' => $cuenta_id]);
            $cuenta = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$cuenta) {
                throw new Exception("La cuenta bancaria no existe.");
            }

            if ($cuenta['saldo'] < $monto) {
                throw new Exception("Saldo insuficiente para realizar el retiro.");
            }
        }

        $sql = "INSERT INTO transacciones (cuenta_id, tipo_transaccion, monto, fecha) 
                VALUES (:cuenta_id, :tipo_transaccion, :monto, NOW())";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'cuenta_id' => $cuenta_id,
            'tipo_transaccion' => $tipo_transaccion,
            'monto' => $monto
        ]);

        $sql = ($tipo_transaccion === 'deposito') 
            ? "UPDATE cuentas_bancarias SET saldo = saldo + :monto WHERE id = :cuenta_id"
            : "UPDATE cuentas_bancarias SET saldo = saldo - :monto WHERE id = :cuenta_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['monto' => $monto, 'cuenta_id' => $cuenta_id]);
    }

    // Eliminar una cuenta bancaria
    public function eliminarCuenta($cuenta_id) {
        $sql = "DELETE FROM cuentas_bancarias WHERE id = :cuenta_id AND usuario_id = :usuario_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'cuenta_id' => $cuenta_id,
            'usuario_id' => $this->usuario_id
        ]);
    }

    // Obtener transacciones de una cuenta
    public function obtenerTransacciones($cuenta_id) {
        $sql = "SELECT id, tipo_transaccion, monto, fecha FROM transacciones WHERE cuenta_id = :cuenta_id ORDER BY fecha DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['cuenta_id' => $cuenta_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

$cuentaBancaria = new CuentaBancaria($_SESSION['user_id']);

// Manejar solicitudes POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $accion = $_POST['accion'] ?? null;
        $cuenta_id = $_POST['cuenta_id'] ?? null;
        $tipo = $_POST['tipo'] ?? null;
        $monto = (float) ($_POST['monto'] ?? 0);
        $tipo_transaccion = $_POST['tipo_transaccion'] ?? null;

        if ($accion === 'crear') {
            $cuentaBancaria->crearCuenta($tipo);
        } elseif ($accion === 'transaccion') {
            $cuentaBancaria->registrarTransaccion($cuenta_id, $tipo_transaccion, $monto);
        } elseif ($accion === 'eliminar') {
            $cuentaBancaria->eliminarCuenta($cuenta_id);
        }
    } catch (Exception $e) {
        echo "<script>alert('Error: {$e->getMessage()}');</script>";
    }
}

// Obtener todas las cuentas del usuario
$cuentas = $cuentaBancaria->obtenerCuentas();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Cuentas Bancarias</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <h1>Gestión de Cuentas Bancarias</h1>
        <h2>Bienvenido, <?= htmlspecialchars($nombre_usuario) ?></h2>
    </header>
    <main>
        <!-- Formulario para crear una nueva cuenta -->
        <section>
            <h3>Crear Nueva Cuenta</h3>
            <form method="POST">
                <label for="tipo">Tipo de Cuenta:</label>
                <select id="tipo" name="tipo" required>
                    <?php foreach ($tipos_cuenta_permitidos as $tipo): ?>
                        <option value="<?= $tipo ?>"><?= $tipo ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" name="accion" value="crear">Crear Cuenta</button>
            </form>
        </section>

        <!-- Lista de cuentas bancarias -->
        <section>
            <h3>Mis Cuentas</h3>
            <table border="1">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Tipo</th>
                        <th>Saldo</th>
                        <th>Fecha de Creación</th>
                        <th>Transacciones</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cuentas as $cuenta): ?>
                        <tr>
                            <td><?= $cuenta['id'] ?></td>
                            <td><?= htmlspecialchars($cuenta['tipo']) ?></td>
                            <td>S/. <?= number_format($cuenta['saldo'], 2) ?></td>
                            <td><?= $cuenta['fecha_creacion'] ?></td>
                            <td>
                                <form method="POST">
                                    <input type="hidden" name="cuenta_id" value="<?= $cuenta['id'] ?>">
                                    <select name="tipo_transaccion" required>
                                        <option value="deposito">Depósito</option>
                                        <option value="retiro">Retiro</option>
                                    </select>
                                    <input type="number" step="0.01" name="monto" placeholder="Monto" min="0.01" required>
                                    <button type="submit" name="accion" value="transaccion">Registrar</button>
                                </form>
                            </td>
                            <td>
                                <form method="POST">
                                    <input type="hidden" name="cuenta_id" value="<?= $cuenta['id'] ?>">
                                    <button type="submit" name="accion" value="eliminar" onclick="return confirm('¿Estás seguro de que deseas eliminar esta cuenta?')">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>

        <!-- Transacciones -->
        <section>
            <h3>Transacciones Recientes</h3>
            <table border="1">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Tipo</th>
                        <th>Monto</th>
                        <th>Fecha</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cuentas as $cuenta): ?>
                        <?php $transacciones = $cuentaBancaria->obtenerTransacciones($cuenta['id']); ?>
                        <?php foreach ($transacciones as $transaccion): ?>
                            <tr>
                                <td><?= $transaccion['id'] ?></td>
                                <td><?= htmlspecialchars($transaccion['tipo_transaccion']) ?></td>
                                <td>S/. <?= number_format($transaccion['monto'], 2) ?></td>
                                <td><?= $transaccion['fecha'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>
    </main>
    <a href="logout.php" class="logout">Cerrar Sesión</a>

</body>
</html>
