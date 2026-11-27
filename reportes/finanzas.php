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
$tipoEntrada = $_GET["tipo"] ?? "";
$tipoSinSeleccion = !is_string($tipoEntrada) || trim($tipoEntrada) === "";
$tipo = $tipoSinSeleccion ? "todos" : trim($tipoEntrada);
if (!isset($tipos[$tipo])) {
    $tipo = "todos";
    $tipoSinSeleccion = true;
}
$tiposGrafico = [
    "columnas" => "Columnas",
    "barras" => "Barras",
    "pastel" => "Pastel"
];
$tipoGrafico = $_GET["grafico"] ?? "columnas";
if (!is_string($tipoGrafico) || !isset($tiposGrafico[$tipoGrafico])) {
    $tipoGrafico = "columnas";
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

$etiquetasGrafico = [
    "Ingreso" => "Ingresos",
    "Gasto" => "Gastos",
    "Ahorro" => "Ahorros generales",
    "Aporte a meta" => "Aportes a metas"
];
$meses = [
    1 => "Enero", 2 => "Febrero", 3 => "Marzo", 4 => "Abril",
    5 => "Mayo", 6 => "Junio", 7 => "Julio", 8 => "Agosto",
    9 => "Septiembre", 10 => "Octubre", 11 => "Noviembre", 12 => "Diciembre"
];
$agruparPorMes = static function (array $filas) use ($meses): array {
    $montosPorMes = [];
    foreach ($filas as $fila) {
        if (!is_string($fila["fecha"]) || $fila["fecha"] === "") {
            continue;
        }
        $timestamp = strtotime($fila["fecha"]);
        if ($timestamp === false) {
            continue;
        }

        $periodo = date("Y-m", $timestamp);
        if (!isset($montosPorMes[$periodo])) {
            $mes = (int) date("n", $timestamp);
            $montosPorMes[$periodo] = [
                "etiqueta" => $meses[$mes] . " " . date("Y", $timestamp),
                "monto" => 0.0
            ];
        }
        $montosPorMes[$periodo]["monto"] += (float) $fila["monto"];
    }

    ksort($montosPorMes);
    return array_values($montosPorMes);
};
$graficos = [];
if ($tipo === "todos") {
    $datosPorCategoria = [];
    foreach ($totales as $tipoRegistro => $monto) {
        $datosPorCategoria[] = [
            "etiqueta" => $etiquetasGrafico[$tipoRegistro],
            "monto" => $monto
        ];
    }
    $graficos[] = [
        "titulo" => "Movimientos por categoría",
        "datos" => $datosPorCategoria
    ];
    $graficos[] = [
        "titulo" => "Movimientos generales por mes",
        "datos" => $agruparPorMes($registrosTodos)
    ];
} else {
    $graficos[] = [
        "titulo" => $tipos[$tipo] . " por mes",
        "datos" => $agruparPorMes($registros)
    ];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes - FINNOVA</title>
    <link rel="stylesheet" href="../Estilos/finanzas.css?v=3">
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
        <p>Selecciona un menú y pulsa consultar para ver sus gráficas. Si no eliges uno, se muestran gráficos generales de tus movimientos.</p>

        <form class="filtros" method="get" action="finanzas.php">
            <label for="desde">Desde
                <input type="date" id="desde" name="desde" value="<?= htmlspecialchars($desde, ENT_QUOTES, "UTF-8") ?>">
            </label>
            <label for="hasta">Hasta
                <input type="date" id="hasta" name="hasta" value="<?= htmlspecialchars($hasta, ENT_QUOTES, "UTF-8") ?>">
            </label>
            <label for="tipo">Menú de movimientos
                <select id="tipo" name="tipo">
                    <option value="" <?= $tipoSinSeleccion ? "selected" : "" ?>>
                        Gráficas generales
                    </option>
                    <?php foreach ($tipos as $valor => $etiqueta): ?>
                        <option value="<?= htmlspecialchars($valor, ENT_QUOTES, "UTF-8") ?>" <?= !$tipoSinSeleccion && $tipo === $valor ? "selected" : "" ?>>
                            <?= htmlspecialchars($etiqueta, ENT_QUOTES, "UTF-8") ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label for="grafico">Tipo de gráfica
                <select id="grafico" name="grafico">
                    <?php foreach ($tiposGrafico as $valor => $etiqueta): ?>
                        <option value="<?= htmlspecialchars($valor, ENT_QUOTES, "UTF-8") ?>" <?= $tipoGrafico === $valor ? "selected" : "" ?>>
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
            <button type="submit">Consultar y mostrar gráficas</button>
        </form>

        <section class="resumen" aria-label="Resumen del periodo">
            <article><span>Ingresos</span><strong><?= htmlspecialchars($dinero($totales["Ingreso"]), ENT_QUOTES, "UTF-8") ?></strong></article>
            <article><span>Gastos</span><strong><?= htmlspecialchars($dinero($totales["Gasto"]), ENT_QUOTES, "UTF-8") ?></strong></article>
            <article><span>Ahorros generales</span><strong><?= htmlspecialchars($dinero($totales["Ahorro"]), ENT_QUOTES, "UTF-8") ?></strong></article>
            <article><span>Aportes a metas</span><strong><?= htmlspecialchars($dinero($totales["Aporte a meta"]), ENT_QUOTES, "UTF-8") ?></strong></article>
            <article><span>Saldo del periodo</span><strong><?= htmlspecialchars($dinero($saldoPeriodo), ENT_QUOTES, "UTF-8") ?></strong></article>
        </section>

        <section class="graficos" aria-labelledby="titulo-graficos">
            <div class="graficos-encabezado">
                <h2 id="titulo-graficos">Gráficas de movimientos</h2>
                <p><?= $tipo === "todos"
                    ? "Resumen general por categoría y evolución mensual."
                    : htmlspecialchars($tipos[$tipo], ENT_QUOTES, "UTF-8") . " para el periodo consultado." ?></p>
            </div>
            <div class="graficos-lista">
                <?php foreach ($graficos as $indiceGrafico => $grafico):
                    $datos = $grafico["datos"];
                    $maximo = $datos === [] ? 0.0 : max(array_column($datos, "monto"));
                    $totalGrafico = array_sum(array_column($datos, "monto"));
                    $coloresGrafico = ["#19a38c", "#dc5656", "#1769aa", "#8b65c2", "#d18b24", "#4897a8"];
                    if ($tipoGrafico === "barras") {
                        $anchoGrafico = 900;
                        $altoGrafico = max(320, count($datos) * 62 + 70);
                        $margenIzquierdo = 230;
                        $margenDerecho = 125;
                        $margenSuperior = 34;
                        $altoTrazado = $altoGrafico - $margenSuperior - 28;
                        $anchoTrazado = $anchoGrafico - $margenIzquierdo - $margenDerecho;
                    } elseif ($tipoGrafico === "pastel") {
                        $anchoGrafico = 760;
                        $altoGrafico = max(340, count($datos) * 32 + 48);
                    } else {
                        $anchoGrafico = max(700, count($datos) * 88 + 100);
                        $altoGrafico = 340;
                        $margenIzquierdo = 82;
                        $margenDerecho = 22;
                        $margenSuperior = 20;
                        $altoTrazado = 238;
                        $anchoTrazado = $anchoGrafico - $margenIzquierdo - $margenDerecho;
                    }
                ?>
                    <article class="tarjeta-grafico">
                        <h3><?= htmlspecialchars($grafico["titulo"], ENT_QUOTES, "UTF-8") ?></h3>
                        <?php if ($datos === [] || $maximo <= 0): ?>
                            <p class="grafico-vacio">No hay movimientos con monto para graficar en este periodo.</p>
                        <?php elseif ($tipoGrafico === "columnas"): ?>
                            <div class="grafico-desplazable">
                                <svg class="grafico-svg" width="<?= $anchoGrafico ?>" height="<?= $altoGrafico ?>"
                                     viewBox="0 0 <?= $anchoGrafico ?> <?= $altoGrafico ?>"
                                     role="img" aria-labelledby="titulo-grafico-<?= $indiceGrafico ?>">
                                    <title id="titulo-grafico-<?= $indiceGrafico ?>">
                                        <?= htmlspecialchars($grafico["titulo"], ENT_QUOTES, "UTF-8") ?>
                                    </title>
                                    <?php for ($marca = 0; $marca <= 4; $marca++):
                                        $valorMarca = $maximo * $marca / 4;
                                        $posicionY = $margenSuperior + $altoTrazado * (4 - $marca) / 4;
                                    ?>
                                        <line class="grafico-cuadricula"
                                              x1="<?= $margenIzquierdo ?>" y1="<?= number_format($posicionY, 2, ".", "") ?>"
                                              x2="<?= $margenIzquierdo + $anchoTrazado ?>" y2="<?= number_format($posicionY, 2, ".", "") ?>"></line>
                                        <text class="grafico-eje-y"
                                              x="<?= $margenIzquierdo - 10 ?>" y="<?= number_format($posicionY + 4, 2, ".", "") ?>"
                                              text-anchor="end">$<?= number_format($valorMarca, 0, ",", ".") ?></text>
                                    <?php endfor; ?>
                                    <line class="grafico-eje"
                                          x1="<?= $margenIzquierdo ?>" y1="<?= $margenSuperior ?>"
                                          x2="<?= $margenIzquierdo ?>" y2="<?= $margenSuperior + $altoTrazado ?>"></line>
                                    <line class="grafico-eje"
                                          x1="<?= $margenIzquierdo ?>" y1="<?= $margenSuperior + $altoTrazado ?>"
                                          x2="<?= $margenIzquierdo + $anchoTrazado ?>" y2="<?= $margenSuperior + $altoTrazado ?>"></line>
                                    <?php foreach ($datos as $indiceDato => $dato):
                                        $espacio = $anchoTrazado / count($datos);
                                        $anchoBarra = min(54, $espacio * 0.58);
                                        $altoBarra = ((float) $dato["monto"] / $maximo) * $altoTrazado;
                                        $centroX = $margenIzquierdo + ($indiceDato * $espacio) + ($espacio / 2);
                                        $x = $centroX - ($anchoBarra / 2);
                                        $y = $margenSuperior + $altoTrazado - $altoBarra;
                                        $lineasEtiqueta = explode(" ", $dato["etiqueta"], 2);
                                        $colorBarra = $tipo === "todos"
                                            ? $coloresGrafico[$indiceDato % count($coloresGrafico)]
                                            : $coloresGrafico[0];
                                    ?>
                                        <g>
                                            <title><?= htmlspecialchars(
                                                $dato["etiqueta"] . ": " . $dinero((float) $dato["monto"]),
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ) ?></title>
                                            <rect class="grafico-barra"
                                                  x="<?= number_format($x, 2, ".", "") ?>"
                                                  y="<?= number_format($y, 2, ".", "") ?>"
                                                  width="<?= number_format($anchoBarra, 2, ".", "") ?>"
                                                  height="<?= number_format($altoBarra, 2, ".", "") ?>"
                                                  rx="5" fill="<?= $colorBarra ?>"></rect>
                                            <text class="grafico-etiqueta-eje-x"
                                                  x="<?= number_format($centroX, 2, ".", "") ?>"
                                                  y="<?= $margenSuperior + $altoTrazado + 22 ?>"
                                                  text-anchor="middle">
                                                <tspan x="<?= number_format($centroX, 2, ".", "") ?>">
                                                    <?= htmlspecialchars($lineasEtiqueta[0], ENT_QUOTES, "UTF-8") ?>
                                                </tspan>
                                                <?php if (isset($lineasEtiqueta[1])): ?>
                                                    <tspan x="<?= number_format($centroX, 2, ".", "") ?>" dy="14">
                                                        <?= htmlspecialchars($lineasEtiqueta[1], ENT_QUOTES, "UTF-8") ?>
                                                    </tspan>
                                                <?php endif; ?>
                                            </text>
                                        </g>
                                    <?php endforeach; ?>
                                </svg>
                            </div>
                        <?php elseif ($tipoGrafico === "barras"): ?>
                            <div class="grafico-desplazable">
                                <svg class="grafico-svg" width="<?= $anchoGrafico ?>" height="<?= $altoGrafico ?>"
                                     viewBox="0 0 <?= $anchoGrafico ?> <?= $altoGrafico ?>"
                                     role="img" aria-labelledby="titulo-grafico-<?= $indiceGrafico ?>">
                                    <title id="titulo-grafico-<?= $indiceGrafico ?>">
                                        <?= htmlspecialchars($grafico["titulo"], ENT_QUOTES, "UTF-8") ?>
                                    </title>
                                    <?php for ($marca = 0; $marca <= 4; $marca++):
                                        $valorMarca = $maximo * $marca / 4;
                                        $posicionX = $margenIzquierdo + $anchoTrazado * $marca / 4;
                                    ?>
                                        <line class="grafico-cuadricula"
                                              x1="<?= number_format($posicionX, 2, ".", "") ?>" y1="<?= $margenSuperior ?>"
                                              x2="<?= number_format($posicionX, 2, ".", "") ?>" y2="<?= $margenSuperior + $altoTrazado ?>"></line>
                                        <text class="grafico-eje-y"
                                              x="<?= number_format($posicionX, 2, ".", "") ?>" y="<?= $margenSuperior - 10 ?>"
                                              text-anchor="middle">$<?= number_format($valorMarca, 0, ",", ".") ?></text>
                                    <?php endfor; ?>
                                    <?php foreach ($datos as $indiceDato => $dato):
                                        $altoFila = $altoTrazado / count($datos);
                                        $centroY = $margenSuperior + ($indiceDato * $altoFila) + ($altoFila / 2);
                                        $anchoBarra = ((float) $dato["monto"] / $maximo) * $anchoTrazado;
                                        $colorBarra = $tipo === "todos"
                                            ? $coloresGrafico[$indiceDato % count($coloresGrafico)]
                                            : $coloresGrafico[0];
                                    ?>
                                        <g>
                                            <title><?= htmlspecialchars(
                                                $dato["etiqueta"] . ": " . $dinero((float) $dato["monto"]),
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ) ?></title>
                                            <text class="grafico-etiqueta-barra"
                                                  x="<?= $margenIzquierdo - 12 ?>"
                                                  y="<?= number_format($centroY + 4, 2, ".", "") ?>"
                                                  text-anchor="end">
                                                <?= htmlspecialchars($dato["etiqueta"], ENT_QUOTES, "UTF-8") ?>
                                            </text>
                                            <rect class="grafico-barra"
                                                  x="<?= $margenIzquierdo ?>"
                                                  y="<?= number_format($centroY - min(17, $altoFila * 0.3), 2, ".", "") ?>"
                                                  width="<?= number_format($anchoBarra, 2, ".", "") ?>"
                                                  height="<?= number_format(min(34, $altoFila * 0.6), 2, ".", "") ?>"
                                                  rx="5" fill="<?= $colorBarra ?>"></rect>
                                            <text class="grafico-eje-y"
                                                  x="<?= number_format($margenIzquierdo + $anchoBarra + 8, 2, ".", "") ?>"
                                                  y="<?= number_format($centroY + 4, 2, ".", "") ?>">
                                                $<?= number_format((float) $dato["monto"], 0, ",", ".") ?>
                                            </text>
                                        </g>
                                    <?php endforeach; ?>
                                </svg>
                            </div>
                        <?php else: ?>
                            <div class="grafico-desplazable">
                                <?php
                                $centroX = 175;
                                $centroY = $altoGrafico / 2;
                                $radio = min(118, ($altoGrafico - 48) / 2);
                                $leyendaX = 340;
                                ?>
                                <svg class="grafico-svg" width="<?= $anchoGrafico ?>" height="<?= $altoGrafico ?>"
                                     viewBox="0 0 <?= $anchoGrafico ?> <?= $altoGrafico ?>"
                                     role="img" aria-labelledby="titulo-grafico-<?= $indiceGrafico ?>">
                                    <title id="titulo-grafico-<?= $indiceGrafico ?>">
                                        <?= htmlspecialchars($grafico["titulo"], ENT_QUOTES, "UTF-8") ?>
                                    </title>
                                    <?php $anguloInicio = -90; ?>
                                    <?php foreach ($datos as $indiceDato => $dato):
                                        $montoDato = (float) $dato["monto"];
                                        $porcion = $totalGrafico > 0 ? $montoDato / $totalGrafico : 0;
                                        $anguloFin = $anguloInicio + (360 * $porcion);
                                        $colorSector = $coloresGrafico[$indiceDato % count($coloresGrafico)];
                                        if ($montoDato > 0 && $porcion >= 0.999999):
                                    ?>
                                        <circle cx="<?= $centroX ?>" cy="<?= number_format($centroY, 2, ".", "") ?>"
                                                r="<?= number_format($radio, 2, ".", "") ?>" fill="<?= $colorSector ?>">
                                            <title><?= htmlspecialchars(
                                                $dato["etiqueta"] . ": " . $dinero($montoDato) . " (100%)",
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ) ?></title>
                                        </circle>
                                    <?php elseif ($montoDato > 0):
                                        $inicioX = $centroX + ($radio * cos(deg2rad($anguloInicio)));
                                        $inicioY = $centroY + ($radio * sin(deg2rad($anguloInicio)));
                                        $finX = $centroX + ($radio * cos(deg2rad($anguloFin)));
                                        $finY = $centroY + ($radio * sin(deg2rad($anguloFin)));
                                        $arcoGrande = $porcion > 0.5 ? 1 : 0;
                                    ?>
                                        <path class="grafico-sector"
                                              d="M <?= $centroX ?> <?= number_format($centroY, 2, ".", "") ?>
                                                 L <?= number_format($inicioX, 2, ".", "") ?> <?= number_format($inicioY, 2, ".", "") ?>
                                                 A <?= number_format($radio, 2, ".", "") ?> <?= number_format($radio, 2, ".", "") ?>
                                                 0 <?= $arcoGrande ?> 1 <?= number_format($finX, 2, ".", "") ?> <?= number_format($finY, 2, ".", "") ?> Z"
                                              fill="<?= $colorSector ?>">
                                            <title><?= htmlspecialchars(
                                                $dato["etiqueta"] . ": " . $dinero($montoDato) . " (" . number_format($porcion * 100, 1, ",", ".") . "%)",
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ) ?></title>
                                        </path>
                                    <?php endif; ?>
                                    <?php $anguloInicio = $anguloFin; endforeach; ?>
                                    <?php foreach ($datos as $indiceDato => $dato):
                                        $porcion = $totalGrafico > 0 ? (float) $dato["monto"] / $totalGrafico : 0;
                                        $posicionLeyenda = 34 + ($indiceDato * 30);
                                        $colorSector = $coloresGrafico[$indiceDato % count($coloresGrafico)];
                                    ?>
                                        <rect x="<?= $leyendaX ?>" y="<?= $posicionLeyenda - 12 ?>"
                                              width="14" height="14" rx="3" fill="<?= $colorSector ?>"></rect>
                                        <text class="grafico-leyenda"
                                              x="<?= $leyendaX + 24 ?>" y="<?= $posicionLeyenda ?>">
                                            <?= htmlspecialchars($dato["etiqueta"], ENT_QUOTES, "UTF-8") ?>:
                                            <?= htmlspecialchars($dinero((float) $dato["monto"]), ENT_QUOTES, "UTF-8") ?>
                                            (<?= number_format($porcion * 100, 1, ",", ".") ?>%)
                                        </text>
                                    <?php endforeach; ?>
                                </svg>
                            </div>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
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