<?php
require_once __DIR__ . "/../php/sesion.php";
require_once __DIR__ . "/../php/conexion.php";
require_once __DIR__ . "/funciones_reportes.php";

if (!isset($_SESSION["usuario_id"]) || !filter_var($_SESSION["usuario_id"], FILTER_VALIDATE_INT)) {
    header("Location: ../login.php");
    exit;
}

$tipos = [
    "todos" => "Todo el sistema",
    "ingresos" => "Ingresos",
    "gastos" => "Gastos",
    "ahorros" => "Ahorros generales",
    "aportes" => "Aportes a metas",
    "metas" => "Metas"
];
$tipo = $_GET["tipo"] ?? "todos";
if (!is_string($tipo) || !isset($tipos[$tipo])) {
    $tipo = "todos";
}
$tipoTieneFiltroMeta = in_array($tipo, ["aportes", "metas"], true);

$filtrosMeta = ["estado" => null, "meta_id" => null];
$metasUsuario = [];
try {
    $rango = obtenerRangoReporte($_GET);
    $registrosTodos = obtenerRegistrosReporte(
        $conexion,
        (int) $_SESSION["usuario_id"],
        $rango["desde"],
        $rango["hasta"],
        "todos"
    );
    if ($tipoTieneFiltroMeta) {
        $filtrosMeta = obtenerFiltrosMetaReporte($_GET);
        $metasUsuario = obtenerMetasReporte($conexion, (int) $_SESSION["usuario_id"]);
        $registrosConFiltroMeta = obtenerRegistrosReporte(
            $conexion,
            (int) $_SESSION["usuario_id"],
            $rango["desde"],
            $rango["hasta"],
            $tipo,
            $filtrosMeta["estado"],
            $filtrosMeta["meta_id"]
        );
    }
} catch (InvalidArgumentException $error) {
    http_response_code(400);
    exit(htmlspecialchars($error->getMessage(), ENT_QUOTES, "UTF-8"));
} catch (RuntimeException $error) {
    http_response_code(500);
    exit("No fue posible cargar los reportes.");
}
$conexion->close();

$tiposPorRegistro = [
    "ingresos" => "Ingreso",
    "gastos" => "Gasto",
    "ahorros" => "Ahorro",
    "aportes" => "Aporte a meta",
    "metas" => "Aporte a meta"
];
$registros = $tipo === "todos"
    ? $registrosTodos
    : ($tipoTieneFiltroMeta
        ? $registrosConFiltroMeta
        : array_values(array_filter($registrosTodos, static function (array $fila) use ($tipo, $tiposPorRegistro): bool {
            return $fila["tipo"] === $tiposPorRegistro[$tipo];
        })));

$totales = [
    "Ingreso" => 0.0,
    "Gasto" => 0.0,
    "Ahorro" => 0.0,
    "Aporte a meta" => 0.0
];
foreach ($registrosTodos as $fila) {
    $totales[$fila["tipo"]] += (float) $fila["monto"];
}
$saldoPeriodo = $totales["Ingreso"] - $totales["Gasto"] - $totales["Ahorro"] - $totales["Aporte a meta"];
$usuarioNombre = htmlspecialchars($_SESSION["usuario_nombre"] ?? "Usuario", ENT_QUOTES, "UTF-8");
$desde = $rango["desde"] ?? "";
$hasta = $rango["hasta"] ?? "";
$dinero = static fn(float $valor): string => "$" . number_format($valor, 2, ",", ".");
$parametrosExportacion = http_build_query([
    "desde" => $desde,
    "hasta" => $hasta,
    "tipo" => $tipo,
    "estado" => $filtrosMeta["estado"] ?? "",
    "meta_id" => $filtrosMeta["meta_id"] ?? ""
]);
$estadosMeta = [
    "Pendiente" => "Pendiente",
    "En progreso" => "En progreso",
    "Por finalizar" => "Por finalizar",
    "Terminada" => "Finalizada",
    "Cancelada" => "Cancelada",
    "Vencida" => "Vencida"
];
$estadosActuales = array_unique(array_map(static function (array $meta) use ($estadosMeta): string {
    return $estadosMeta[$meta["metEstado"]] ?? $meta["metEstado"];
}, $metasUsuario));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes - FINNOVA</title>
    <link rel="stylesheet" href="../Estilos/finanzas.css">
</head>
<body>
<header>
    <a href="../pag.principal.php"><img class="logo" src="../Estilos/img/logo_img.png" alt="Logo"></a>
    <a href="../pag.principal.php"><img class="logo-nombre" src="../Estilos/img/Logo.png" alt="Finnova Logo"></a>
    <h1>Finanzas</h1>
    <div class="user-icon">
        <p><?= $usuarioNombre ?></p>
        <a href="../perfil.php" aria-label="Ver perfil">
            <img src="../Estilos/img/user-icon.png" alt="">
        </a>
    </div>
</header>

<nav class="menu" aria-label="Navegación principal">
    <ul>
        <li><a href="../pag.principal.php">Inicio</a></li>
        <li><a href="../ingresos.html">Ingresos</a></li>
        <li><a href="../gastos.html">Gastos</a></li>
        <li><a href="../ahorros.php">Ahorros</a></li>
        <li><a href="../metas.php">Metas</a></li>
        <li><a href="../presupuesto.php">Presupuesto</a></li>
        <li><a href="finanzas.php" aria-current="page">Reportes</a></li>
        <li><label for="total-restante"> Total restante:</label>
            <input type="text" id="total-restante" name="total-restante" value="$0.00" readonly>
                
        </li>
    </ul>
</nav>

<main>
        <h1>Reportes financieros</h1>
        <p>Elige una categoría para descargar solo ese reporte. “Todo el sistema” incluye las categorías en un único CSV, identificadas por tipo. CSV no admite hojas separadas. Si dejas las fechas vacías, se incluirá todo el historial.</p>

        <form class="filtros" method="get" action="finanzas.php">
            <label for="desde">Desde
                <input type="date" id="desde" name="desde" value="<?= htmlspecialchars($desde, ENT_QUOTES, "UTF-8") ?>">
            </label>
            <label for="hasta">Hasta
                <input type="date" id="hasta" name="hasta" value="<?= htmlspecialchars($hasta, ENT_QUOTES, "UTF-8") ?>">
            </label>
            <label for="tipo">Reporte
                <select id="tipo" name="tipo">
                    <?php foreach ($tipos as $valor => $etiqueta): ?>
                        <option value="<?= htmlspecialchars($valor, ENT_QUOTES, "UTF-8") ?>" <?= $tipo === $valor ? "selected" : "" ?>>
                            <?= htmlspecialchars($etiqueta, ENT_QUOTES, "UTF-8") ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <?php if ($tipoTieneFiltroMeta): ?>
                <label for="estado">Estado de la meta
                    <select id="estado" name="estado" onchange="this.form.submit()">
                        <option value="">Todos los estados</option>
                        <?php foreach ($estadosMeta as $valor => $etiqueta): ?>
                            <option value="<?= htmlspecialchars($valor, ENT_QUOTES, "UTF-8") ?>" <?= $filtrosMeta["estado"] === $valor ? "selected" : "" ?>>
                                <?= htmlspecialchars($etiqueta, ENT_QUOTES, "UTF-8") ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label for="meta_id">Meta específica
                    <select id="meta_id" name="meta_id" onchange="this.form.submit()">
                        <option value="">Todas las metas</option>
                        <?php foreach ($metasUsuario as $meta): ?>
                            <option value="<?= (int) $meta["idMeta"] ?>" <?= $filtrosMeta["meta_id"] === (int) $meta["idMeta"] ? "selected" : "" ?>>
                                #<?= (int) $meta["idMeta"] ?> - <?= htmlspecialchars($meta["metNombre"], ENT_QUOTES, "UTF-8") ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
            <?php endif; ?>
            <button type="submit">Consultar</button>
        </form>

        <section class="resumen" aria-label="Resumen del periodo">
            <article><span>Ingresos</span><strong><?= htmlspecialchars($dinero($totales["Ingreso"]), ENT_QUOTES, "UTF-8") ?></strong></article>
            <article><span>Gastos</span><strong><?= htmlspecialchars($dinero($totales["Gasto"]), ENT_QUOTES, "UTF-8") ?></strong></article>
            <article><span>Ahorros generales</span><strong><?= htmlspecialchars($dinero($totales["Ahorro"]), ENT_QUOTES, "UTF-8") ?></strong></article>
            <article><span>Aportes a metas</span><strong><?= htmlspecialchars($dinero($totales["Aporte a meta"]), ENT_QUOTES, "UTF-8") ?></strong></article>
            <article><span>Saldo del periodo</span><strong><?= htmlspecialchars($dinero($saldoPeriodo), ENT_QUOTES, "UTF-8") ?></strong></article>
        </section>

        <div class="acciones">
            <a href="exportar_finanzas.php?<?= htmlspecialchars($parametrosExportacion, ENT_QUOTES, "UTF-8") ?>">Descargar CSV: <?= htmlspecialchars($tipos[$tipo], ENT_QUOTES, "UTF-8") ?></a>
        </div>

        <h2><?= htmlspecialchars($tipos[$tipo], ENT_QUOTES, "UTF-8") ?></h2>
        <div class="tabla-contenedor">
            <table>
                <thead>
                    <tr><th>Fecha</th><th>Tipo</th><th>Detalle</th><th>Monto</th><th>Estado</th></tr>
                </thead>
                <tbody>
                    <?php if ($registros === []): ?>
                        <tr>
                            <td colspan="5">
                                <?php if ($tipoTieneFiltroMeta && $filtrosMeta["meta_id"] !== null): ?>
                                    La meta seleccionada no tiene aportes para el estado y las fechas indicadas.
                                <?php elseif ($tipoTieneFiltroMeta && $filtrosMeta["estado"] !== null): ?>
                                    No hay aportes registrados para metas en estado <?= htmlspecialchars($estadosMeta[$filtrosMeta["estado"]] ?? $filtrosMeta["estado"], ENT_QUOTES, "UTF-8") ?>.
                                    Estados actuales de tus metas: <?= htmlspecialchars($estadosActuales === [] ? "ninguno" : implode(", ", $estadosActuales), ENT_QUOTES, "UTF-8") ?>.
                                <?php elseif ($tipoTieneFiltroMeta): ?>
                                    Aún no hay aportes registrados en tus metas.
                                <?php else: ?>
                                    No hay registros para los filtros seleccionados.
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php else: foreach ($registros as $fila): ?>
                        <tr>
                            <td><?= $fila["fecha"] === null ? "—" : htmlspecialchars(date("d/m/Y", strtotime($fila["fecha"])), ENT_QUOTES, "UTF-8") ?></td>
                            <td><?= htmlspecialchars($fila["tipo"], ENT_QUOTES, "UTF-8") ?></td>
                            <td><?= htmlspecialchars($fila["detalle"], ENT_QUOTES, "UTF-8") ?></td>
                            <td><?= htmlspecialchars($dinero((float) $fila["monto"]), ENT_QUOTES, "UTF-8") ?></td>
                            <td><?= htmlspecialchars($fila["estado"] !== "" ? $fila["estado"] : "—", ENT_QUOTES, "UTF-8") ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>