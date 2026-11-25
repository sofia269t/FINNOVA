<?php
require_once __DIR__ . "/php/sesion.php";
require_once __DIR__ . "/php/conexion.php";

if (!isset($_SESSION["usuario_id"]) || !filter_var($_SESSION["usuario_id"], FILTER_VALIDATE_INT)) {
    header("Location: login.php");
    exit;
}

$usuarioId = (int) $_SESSION["usuario_id"];
$fechaMes = new DateTimeImmutable("first day of this month");
$inicioMes = $fechaMes->format("Y-m-d");
$inicioMesSiguiente = $fechaMes->modify("first day of next month")->format("Y-m-d");
$nombresMeses = [
    1 => "enero", 2 => "febrero", 3 => "marzo", 4 => "abril",
    5 => "mayo", 6 => "junio", 7 => "julio", 8 => "agosto",
    9 => "septiembre", 10 => "octubre", 11 => "noviembre", 12 => "diciembre"
];
$mesActual = $nombresMeses[(int) $fechaMes->format("n")] . " " . $fechaMes->format("Y");
$sql = "SELECT
            COALESCE((SELECT SUM(i.ingMonto) FROM ingreso i
                      WHERE i.ingUsuario = ? AND i.ingFecha >= ? AND i.ingFecha < ?), 0) AS total_ingresos,
            COALESCE((SELECT SUM(g.gasMonto) FROM gastos g
                      WHERE g.gasUsuario = ? AND g.gasFecha >= ? AND g.gasFecha < ?), 0) AS total_gastos,
            COALESCE((SELECT SUM(g.gasMonto)
                      FROM gastos g
                      WHERE g.gasUsuario = ? AND g.gasFecha >= ? AND g.gasFecha < ?
                        AND g.gasCategoria IN ('Alimentación', 'Transporte', 'Servicios', 'Salud', 'Vivienda', 'Educación')), 0) AS gastos_prioritarios,
            COALESCE((SELECT SUM(a.ahorMonto) FROM ahorro a
                      WHERE a.ahorUsuario = ? AND a.ahorFecha >= ? AND a.ahorFecha < ?), 0) AS ahorros_registrados,
            COALESCE((SELECT SUM(ap.apmMonto)
                      FROM aporte_meta ap
                      INNER JOIN meta m ON m.idMeta = ap.apmMeta
                      WHERE m.metUsuario = ? AND ap.apmFecha >= ? AND ap.apmFecha < ?), 0) AS aportes_metas";
$stmt = $conexion->prepare($sql);
if (!$stmt) {
    error_log("No fue posible preparar el resumen de presupuesto: " . $conexion->error);
    http_response_code(500);
    exit("No fue posible cargar el presupuesto.");
}
$stmt->bind_param(
    "issississississ",
    $usuarioId, $inicioMes, $inicioMesSiguiente,
    $usuarioId, $inicioMes, $inicioMesSiguiente,
    $usuarioId, $inicioMes, $inicioMesSiguiente,
    $usuarioId, $inicioMes, $inicioMesSiguiente,
    $usuarioId, $inicioMes, $inicioMesSiguiente
);
if (!$stmt->execute()) {
    error_log("No fue posible consultar el resumen de presupuesto: " . $stmt->error);
    http_response_code(500);
    exit("No fue posible cargar el presupuesto.");
}
$resumen = $stmt->get_result()->fetch_assoc();
$stmt->close();
$conexion->close();

$totalIngresos = (float) $resumen["total_ingresos"];
$totalGastos = (float) $resumen["total_gastos"];
$gastosPrioritarios = (float) $resumen["gastos_prioritarios"];
$gastosVarios = max(0, $totalGastos - $gastosPrioritarios);
$totalAhorros = (float) $resumen["ahorros_registrados"] + (float) $resumen["aportes_metas"];
$totalMetas = (float) $resumen["aportes_metas"];
$totalRestante = $totalIngresos - $totalGastos - $totalAhorros;
$porcentajeUso = $totalIngresos > 0 ? min(100, ($totalGastos / $totalIngresos) * 100) : 0;
$porcentajeUsoTexto = number_format($porcentajeUso, 1, ",", ".");
$usuarioNombre = htmlspecialchars($_SESSION["usuario_nombre"] ?? "Usuario", ENT_QUOTES, "UTF-8");
$dinero = static fn(float $valor): string => "$" . number_format($valor, 2, ",", ".");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Presupuesto</title>
    <link rel="stylesheet" href="Estilos/presupuesto.css">
</head>
<body>
    <header>
        <a href="pag.principal.php"><img class="logo" src="Estilos/img/logo_img.png" alt="Logo"></a>
        <a href="pag.principal.php"><img class="logo-nombre" src="Estilos/img/Logo.png" alt="Finnova Logo"></a>
        <h1>Presupuesto</h1>
        <div class="user-icon">
                    <p><?= $usuarioNombre ?></p>
                    <a href="perfil.php">
                        <img  src="Estilos/img/user-icon.png" alt="User Icon">
                    </a>
        </div>
        <div class="sub-menu">
            <ul>
                <li><a href="perfil.php">Perfil</a></li>
                <li><a href="php/logout.php">Cerrar Sesión</a></li>
            </ul>
        </div>
    </header>
    
    <main>
        <div class="menu">
            <ul>
                <li><a href="ingresos.html">Ingresos</a></li>
                <li><a href="gastos.html">Gastos</a></li>
                <li><a href="ahorros.php">Ahorros</a></li>
                <li><a href="metas.php">Metas</a></li>
                <li><a href="presupuesto.php" class="activo">Presupuesto</a></li>
                <li><a href="reportes/finanzas.php">Reportes</a></li>
                <li><label for="total-restante"> Total restante:</label>
                    <input type="text" id="total-restante" name="total-restante" value="<?= htmlspecialchars($dinero($totalRestante), ENT_QUOTES, "UTF-8") ?>" readonly>
                
                </li>
            </ul>
        </div>

        <section class="inicio" aria-labelledby="titulo-resumen">
            <div class="inicio-cabecera">
                <div>
                    <p class="etiqueta">Resumen financiero</p>
                    <h2 id="titulo-resumen">Tu presupuesto</h2>
                    <p class="descripcion">Resumen de ingresos, gastos y ahorros de <?= htmlspecialchars($mesActual, ENT_QUOTES, "UTF-8") ?>.</p>
                </div>
                <a href="graficas.html" class="btn-graficas">Ver resumen y gráficas</a>
            </div>

            <div class="resumen-principal">
                <div class="dato dato-ingresos">
                    <label for="ingresos">Ingresos del mes</label>
                    <input type="text" id="ingresos" name="ingresos" value="<?= htmlspecialchars($dinero($totalIngresos), ENT_QUOTES, "UTF-8") ?>" readonly>
                </div>
                <div class="progreso">
                    <div class="progreso-cabecera">
                        <span>Uso del presupuesto</span>
                        <strong><?= $porcentajeUsoTexto ?>%</strong>
                    </div>
                    <progress id="file" value="<?= number_format($porcentajeUso, 1, ".", "") ?>" max="100"><?= $porcentajeUsoTexto ?>%</progress>
                    <p>Se ha utilizado <?= $porcentajeUsoTexto ?>% de los ingresos en gastos.</p>
                </div>
                <div class="dato dato-limite">
                    <label for="limite">Límite de referencia (ingresos del mes)</label>
                    <input type="text" id="limite" name="limite" value="<?= htmlspecialchars($dinero($totalIngresos), ENT_QUOTES, "UTF-8") ?>" readonly>
                </div>
            </div>

            <div class="resumen-metricas">
                <div class="dato">
                    <label for="gastos">Total gastado este mes</label>
                    <input type="text" id="gastos" name="gastos" value="<?= htmlspecialchars($dinero($totalGastos), ENT_QUOTES, "UTF-8") ?>" readonly>
                </div>
                <div class="dato dato-restante">
                    <label for="restante">Saldo restante del mes</label>
                    <input type="text" id="restante" name="restante" value="<?= htmlspecialchars($dinero($totalRestante), ENT_QUOTES, "UTF-8") ?>" readonly>
                </div>
            </div>
        </section>
        <section class="resumen-card gastos-prioritarios">
            <div class="card-titulo">
                <span class="card-icon">★</span>
                <h2>Gastos prioritarios</h2>
            </div>
            <label for="Total-prioritarios">Total gastos prioritarios</label>
            <input type="text" id="Total-prioritarios" name="Total-prioritarios" value="<?= htmlspecialchars($dinero($gastosPrioritarios), ENT_QUOTES, "UTF-8") ?>" readonly>
            <a href="gastos.html">Ver Detalles</a>
        </section>
        <section class="resumen-card gastos-varios">
            <div class="card-titulo">
                <span class="card-icon">◈</span>
                <h2>Gastos varios</h2>
            </div>
            <label for="Total-varios">Total gastos varios</label>
            <input type="text" id="Total-varios" name="Total-varios" value="<?= htmlspecialchars($dinero($gastosVarios), ENT_QUOTES, "UTF-8") ?>" readonly>
            <a href="gastos.html">Ver Detalles</a>
        </section>
        <section class="resumen-card resumen-metas">
            <div class="card-titulo">
                <span class="card-icon">◎</span>
                <h2>Metas</h2>
            </div>
            <label for="Total-metas">Aportes a metas este mes</label>
            <input type="text" id="Total-metas" name="Total-metas" value="<?= htmlspecialchars($dinero($totalMetas), ENT_QUOTES, "UTF-8") ?>" readonly>
            <a href="metas.php">Ver Metas</a>
        </section>
        <section class="resumen-card resumen-ahorros">
            <div class="card-titulo">
                <span class="card-icon">▣</span>
                <h2>Ahorros</h2>
            </div>
            <label for="Total-ahorros">Ahorros y aportes del mes</label>
            <input type="text" id="Total-ahorros" name="Total-ahorros" value="<?= htmlspecialchars($dinero($totalAhorros), ENT_QUOTES, "UTF-8") ?>" readonly>
            <a href="ahorros.php">Ver Ahorros</a>
        </section>
    </main>
    <footer>
        <p> &copy; 2026 Finnova. Todos los derechos reservados.</p>
    </footer>
</body>
</html>