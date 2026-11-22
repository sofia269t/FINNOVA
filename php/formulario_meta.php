<?php
require_once __DIR__ . "/sesion.php";
require_once __DIR__ . "/conexion.php";

if (!isset($_SESSION["usuario_id"])) {
    header("Location: ../login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../metas.php");
    exit;
}

$idMeta = filter_input(INPUT_POST, "idMeta", FILTER_VALIDATE_INT);
$usuarioId = (int) $_SESSION["usuario_id"];

function mostrarErrorAporte(string $mensaje, ?int $idMeta = null): void
{
    $_SESSION["mensaje_aporte"] = $mensaje;
    if ($idMeta !== null && $idMeta > 0) {
        header("Location: detalle_meta.php?idMeta=" . $idMeta);
    } else {
        header("Location: ../metas.php");
    }
    exit;
}

if (!$idMeta || $idMeta < 1) {
    http_response_code(400);
    exit("No se pudo identificar una meta válida.");
}

$montoEnviado = $_POST["monto"] ?? "";
if (!is_string($montoEnviado)) {
    mostrarErrorAporte("Ingresa un monto válido.", $idMeta);
}
$montoTexto = trim($montoEnviado);
if (!preg_match('/^\d{1,10}(?:\.\d{1,2})?$/D', $montoTexto) || (float) $montoTexto <= 0) {
    mostrarErrorAporte("Ingresa un monto válido mayor que cero.", $idMeta);
}
$monto = (float) $montoTexto;

$descripcionEnviada = $_POST["descripcion"] ?? "";
if (!is_string($descripcionEnviada)) {
    mostrarErrorAporte("La descripción enviada no es válida.", $idMeta);
}
$descripcion = trim($descripcionEnviada);
if (preg_match('/^.{0,150}$/us', $descripcion) !== 1) {
    mostrarErrorAporte("La descripción no puede superar 150 caracteres.", $idMeta);
}
$descripcionAporte = $descripcion === "" ? null : $descripcion;
$fechaAporte = null;

$consultaMeta = $conexion->prepare(
    "SELECT metEstado FROM meta WHERE idMeta = ? AND metUsuario = ? LIMIT 1"
);
if (!$consultaMeta) {
    error_log("No fue posible preparar la validación de la meta: " . $conexion->error);
    mostrarErrorAporte("No fue posible validar la meta. Inténtalo nuevamente.", $idMeta);
}
$consultaMeta->bind_param("ii", $idMeta, $usuarioId);
if (!$consultaMeta->execute()) {
    error_log("No fue posible validar la meta: " . $consultaMeta->error);
    mostrarErrorAporte("No fue posible validar la meta. Inténtalo nuevamente.", $idMeta);
}
$resultadoMeta = $consultaMeta->get_result();
$meta = $resultadoMeta->fetch_assoc();
$consultaMeta->close();

if (!$meta) {
    http_response_code(404);
    exit("La meta no existe o no pertenece a tu cuenta.");
}
if (in_array($meta["metEstado"], ["Terminada", "Cancelada", "Vencida"], true)) {
    mostrarErrorAporte("No se pueden registrar aportes a una meta " . strtolower($meta["metEstado"]) . ".", $idMeta);
}

try {
    $stmt = $conexion->prepare("CALL sp_registrar_aporte_meta(?, ?, ?, ?, ?)");
    if (!$stmt) {
        error_log("No fue posible preparar el registro del aporte: " . $conexion->error);
        mostrarErrorAporte("No fue posible registrar el aporte. Inténtalo nuevamente.", $idMeta);
    }
    $stmt->bind_param("iidss", $idMeta, $usuarioId, $monto, $fechaAporte, $descripcionAporte);
    if (!$stmt->execute()) {
        error_log("No fue posible registrar el aporte: " . $stmt->error);
        $stmt->close();
        mostrarErrorAporte("No fue posible registrar el aporte. Inténtalo nuevamente.", $idMeta);
    }
    $resultadoAporte = $stmt->get_result();
    if ($resultadoAporte) {
        $resultadoAporte->free();
    }
    while ($stmt->more_results() && $stmt->next_result()) {
        $resultadoAporte = $stmt->get_result();
        if ($resultadoAporte) {
            $resultadoAporte->free();
        }
    }
    $stmt->close();
    $conexion->close();
} catch (mysqli_sql_exception $error) {
    error_log("Error al registrar un aporte de meta: " . $error->getMessage());
    mostrarErrorAporte("No fue posible registrar el aporte. Verifica que la meta permita nuevos aportes.", $idMeta);
}

$_SESSION["mensaje_aporte"] = "El aporte se registró correctamente.";
header("Location: detalle_meta.php?idMeta=" . $idMeta);
exit;
