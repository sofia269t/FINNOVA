<?php
require_once __DIR__ . "/sesion.php";
require_once __DIR__ . "/conexion.php";

if (!isset($_SESSION["usuario_id"]) || !filter_var($_SESSION["usuario_id"], FILTER_VALIDATE_INT)) {
    header("Location: ../login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../crear_meta.html");
    exit;
}

function volverAlFormularioMeta(string $mensaje): void
{
    header("Location: ../crear_meta.html?error=" . rawurlencode($mensaje));
    exit;
}

$nombreEnviado = $_POST["metNombre"] ?? "";
$montoEnviado = $_POST["metMontoObjetivo"] ?? "";
$fechaInicio = $_POST["metFechaInicio"] ?? "";
$fechaFin = $_POST["metFechaFin"] ?? "";

if (!is_string($nombreEnviado) || !is_string($montoEnviado) ||
    !is_string($fechaInicio) || !is_string($fechaFin)) {
    volverAlFormularioMeta("Revisa los datos ingresados.");
}

$nombre = trim($nombreEnviado);
if ($nombre === "" || preg_match('/^.{1,100}$/us', $nombre) !== 1) {
    volverAlFormularioMeta("El nombre es obligatorio y no debe superar 100 caracteres.");
}

if (!preg_match('/^\d{1,10}(?:\.\d{1,2})?$/D', $montoEnviado) || (float) $montoEnviado <= 0) {
    volverAlFormularioMeta("Ingresa un monto objetivo válido mayor que cero.");
}
$monto = (float) $montoEnviado;

$validarFecha = static function (string $fecha): bool {
    if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/D', $fecha, $partes) !== 1) {
        return false;
    }

    return checkdate((int) $partes[2], (int) $partes[3], (int) $partes[1]);
};

if (!$validarFecha($fechaInicio) || !$validarFecha($fechaFin)) {
    volverAlFormularioMeta("Ingresa fechas válidas.");
}
if ($fechaFin <= $fechaInicio) {
    volverAlFormularioMeta("La fecha límite debe ser posterior a la fecha de inicio.");
}
if ($fechaFin < date("Y-m-d")) {
    volverAlFormularioMeta("La fecha límite no puede estar en el pasado.");
}

$estado = "Pendiente";
$imagen = null;
$usuarioId = (int) $_SESSION["usuario_id"];

try {
    $stmt = $conexion->prepare("CALL sp_crear_meta(?, ?, ?, ?, ?, ?, ?)");
    if (!$stmt) {
        error_log("No fue posible preparar el registro de la meta: " . $conexion->error);
        volverAlFormularioMeta("No fue posible crear la meta. Inténtalo nuevamente.");
    }

    $stmt->bind_param("ssdsssi", $fechaInicio, $fechaFin, $monto, $estado, $nombre, $imagen, $usuarioId);
    if (!$stmt->execute()) {
        error_log("No fue posible crear la meta: " . $stmt->error);
        $stmt->close();
        volverAlFormularioMeta("No fue posible crear la meta. Inténtalo nuevamente.");
    }

    $resultado = $stmt->get_result();
    $metaCreada = $resultado ? $resultado->fetch_assoc() : null;
    if ($resultado) {
        $resultado->free();
    }
    while ($stmt->more_results() && $stmt->next_result()) {
        $resultado = $stmt->get_result();
        if ($resultado) {
            $resultado->free();
        }
    }
    $stmt->close();
    $conexion->close();
} catch (mysqli_sql_exception $error) {
    error_log("Error al crear una meta: " . $error->getMessage());
    volverAlFormularioMeta("No fue posible crear la meta. Verifica los datos e inténtalo nuevamente.");
}

if (!$metaCreada || !isset($metaCreada["idMeta"])) {
    error_log("El procedimiento de creación de metas no devolvió el identificador de la meta.");
    volverAlFormularioMeta("No fue posible confirmar la creación de la meta.");
}

$_SESSION["mensaje_meta"] = "La meta se creó correctamente.";
header("Location: ../metas.php");
exit;