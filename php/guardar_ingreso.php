<?php
require_once __DIR__ . "/sesion.php";

function mostrar_error_ingreso(int $codigo, string $mensaje): void
{
    http_response_code($codigo);
    header("Content-Type: text/html; charset=utf-8");
    $mensajeSeguro = htmlspecialchars($mensaje, ENT_QUOTES, "UTF-8");
    echo "<!DOCTYPE html>
<html lang=\"es\">
<head>
    <meta charset=\"UTF-8\">
    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
    <title>Error al guardar el ingreso</title>
</head>
<body>
    <main>
        <h1>No se pudo guardar el ingreso</h1>
        <p>{$mensajeSeguro}</p>
        <a href=\"formulario_ingreso.php\">Volver al formulario</a>
    </main>
</body>
</html>";
    exit;
}

if (!isset($_SESSION["usuario_id"])) {
    header("Location: ../login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ingresos.php");
    exit;
}

$nombreEntrada = $_POST["nombre"] ?? "";
$montoEntrada = $_POST["monto"] ?? "";
$frecuenciaEntrada = $_POST["frecuencia"] ?? "";
$descripcionEntrada = $_POST["descripcion"] ?? "";

if (!is_string($nombreEntrada) || !is_string($montoEntrada)
    || !is_string($frecuenciaEntrada) || !is_string($descripcionEntrada)) {
    mostrar_error_ingreso(400, "Los datos enviados no son válidos.");
}

$nombre = trim($nombreEntrada);
$frecuencia = $frecuenciaEntrada;
$descripcion = isset($_POST["activar-descripcion"]) ? trim($descripcionEntrada) : "";
$recordatorios = isset($_POST["recordatorios"]) ? 1 : 0;

if ((isset($_POST["activar-descripcion"]) && $_POST["activar-descripcion"] !== "1")
    || (isset($_POST["recordatorios"]) && $_POST["recordatorios"] !== "1")) {
    mostrar_error_ingreso(400, "Las opciones seleccionadas no son válidas.");
}

if ($nombre === "" || strlen($nombre) > 100) {
    mostrar_error_ingreso(400, "El nombre del ingreso es obligatorio y no debe superar 100 caracteres.");
}

if (!is_numeric($montoEntrada) || !is_finite((float) $montoEntrada)
    || (float) $montoEntrada <= 0 || (float) $montoEntrada > 9999999999.99) {
    mostrar_error_ingreso(400, "Ingresa un monto válido mayor que cero.");
}

if (!in_array($frecuencia, ["Mensual", "Quincenal", "Semanal"], true)) {
    mostrar_error_ingreso(400, "Selecciona una frecuencia válida.");
}

if (strlen($descripcion) > 200) {
    mostrar_error_ingreso(400, "La descripción no debe superar 200 caracteres.");
}

require_once __DIR__ . "/conexion.php";
$monto = (float) $montoEntrada;
$usuarioId = (int) $_SESSION["usuario_id"];
$stmt = $conexion->prepare(
    "INSERT INTO ingreso (ingMonto, ingFecha, ingNombre, ingDescripcion, ingFrecuencia, ingRecordatorio, ingUsuario)
     VALUES (?, CURRENT_DATE(), ?, ?, ?, ?, ?)"
);

if (!$stmt) {
    error_log("No se pudo preparar el registro de ingreso: " . $conexion->error);
    mostrar_error_ingreso(500, "No fue posible guardar el ingreso.");
}

$stmt->bind_param(
    "dsssii",
    $monto,
    $nombre,
    $descripcion,
    $frecuencia,
    $recordatorios,
    $usuarioId
);

if (!$stmt->execute()) {
    error_log("No se pudo guardar el ingreso: " . $stmt->error);
    $stmt->close();
    $conexion->close();
    mostrar_error_ingreso(500, "No fue posible guardar el ingreso.");
}

$stmt->close();
$conexion->close();
header("Location: ingresos.php", true, 303);
exit;
