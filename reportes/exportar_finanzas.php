<?php
require_once __DIR__ . "/../php/sesion.php";
require_once __DIR__ . "/../php/conexion.php";
require_once __DIR__ . "/funciones_reportes.php";

if (!isset($_SESSION["usuario_id"]) || !filter_var($_SESSION["usuario_id"], FILTER_VALIDATE_INT)) {
    header("Location: ../login.php");
    exit;
}

$tipos = ["todos", "ingresos", "gastos", "ahorros", "aportes", "metas"];
$tipo = $_GET["tipo"] ?? "todos";
if (!is_string($tipo) || !in_array($tipo, $tipos, true)) {
    http_response_code(400);
    exit("El tipo de reporte no es válido.");
}
try {
    $rango = obtenerRangoReporte($_GET);
    $filtrosMeta = in_array($tipo, ["aportes", "metas"], true)
        ? obtenerFiltrosMetaReporte($_GET)
        : ["estado" => null, "meta_id" => null];
    $registros = obtenerRegistrosReporte(
        $conexion,
        (int) $_SESSION["usuario_id"],
        $rango["desde"],
        $rango["hasta"],
        $tipo,
        $filtrosMeta["estado"],
        $filtrosMeta["meta_id"]
    );
} catch (InvalidArgumentException $error) {
    http_response_code(400);
    exit(htmlspecialchars($error->getMessage(), ENT_QUOTES, "UTF-8"));
} catch (RuntimeException $error) {
    http_response_code(500);
    exit("No fue posible exportar el reporte.");
}
$conexion->close();

$nombreArchivo = "reporte_finnova_" . $tipo . "_" . date("Ymd_His") . ".csv";
header("Content-Type: text/csv; charset=UTF-8");
header("Content-Disposition: attachment; filename=\"" . $nombreArchivo . "\"");
header("X-Content-Type-Options: nosniff");
echo "\xEF\xBB\xBF";
$salida = fopen("php://output", "w");
fputcsv($salida, ["Fecha", "Tipo", "Detalle", "Monto", "Estado"], ";");
foreach ($registros as $fila) {
    fputcsv($salida, [
        $fila["fecha"],
        protegerValorCsv((string) $fila["tipo"]),
        protegerValorCsv((string) $fila["detalle"]),
        $fila["monto"],
        protegerValorCsv((string) $fila["estado"])
    ], ";");
}
fclose($salida);
