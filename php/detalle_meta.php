<?php
require_once __DIR__ . "/sesion.php";
require_once __DIR__ . "/conexion.php";

if (!isset($_SESSION["usuario_id"])) {
    header("Location: ../login.php");
    exit;
}

$idMeta = filter_input(INPUT_GET, "idMeta", FILTER_VALIDATE_INT);
if (!$idMeta || $idMeta < 1) {
    http_response_code(400);
    exit("La meta solicitada no es válida.");
}

$sql = "SELECT
            m.idMeta,
            m.metNombre,
            m.metFechaInicio,
            m.metFechaFin,
            m.metMontoObjetivo,
            m.metEstado,
            m.metImagen,
            COALESCE(SUM(a.apmMonto), 0) AS total_ahorrado,
            GREATEST(m.metMontoObjetivo - COALESCE(SUM(a.apmMonto), 0), 0) AS dinero_restante,
            ROUND(LEAST(COALESCE(SUM(a.apmMonto), 0) / NULLIF(m.metMontoObjetivo, 0) * 100, 100), 2) AS porcentaje_progreso
        FROM meta m
        LEFT JOIN aporte_meta a ON a.apmMeta = m.idMeta
        WHERE m.idMeta = ? AND m.metUsuario = ?
        GROUP BY m.idMeta, m.metNombre, m.metFechaInicio, m.metFechaFin,
                 m.metMontoObjetivo, m.metEstado, m.metImagen";

$stmt = $conexion->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    exit("No fue posible consultar el detalle de la meta.");
}
$usuarioId = (int) $_SESSION["usuario_id"];
$stmt->bind_param("ii", $idMeta, $usuarioId);
if (!$stmt->execute()) {
    http_response_code(500);
    exit("No fue posible cargar el detalle de la meta.");
}
$resultado = $stmt->get_result();
$meta = $resultado->fetch_assoc();
$stmt->close();

if (!$meta) {
    $conexion->close();
    http_response_code(404);
    exit("No se encontró la meta solicitada.");
}

$sqlAportes = "SELECT apmMonto, apmFecha, apmDescripcion
               FROM aporte_meta
               WHERE apmMeta = ?
               ORDER BY apmFecha DESC, idAporte DESC";
$stmtAportes = $conexion->prepare($sqlAportes);
if (!$stmtAportes) {
    $conexion->close();
    http_response_code(500);
    exit("No fue posible consultar el historial de aportes.");
}
$stmtAportes->bind_param("i", $idMeta);
if (!$stmtAportes->execute()) {
    $stmtAportes->close();
    $conexion->close();
    http_response_code(500);
    exit("No fue posible cargar el historial de aportes.");
}
$aportes = $stmtAportes->get_result();
$stmtAportes->close();
$conexion->close();

$usuarioNombre = htmlspecialchars($_SESSION["usuario_nombre"] ?? "Usuario", ENT_QUOTES, "UTF-8");
$mensajeAporte = $_SESSION["mensaje_aporte"] ?? "";
unset($_SESSION["mensaje_aporte"]);
$nombreMeta = htmlspecialchars($meta["metNombre"], ENT_QUOTES, "UTF-8");
$estadoMeta = htmlspecialchars($meta["metEstado"], ENT_QUOTES, "UTF-8");
$imagenMeta = trim((string) ($meta["metImagen"] ?? ""));
$porcentaje = number_format((float) $meta["porcentaje_progreso"], 2, ".", "");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $nombreMeta ?> - Detalle de Meta</title>
    <link rel="stylesheet" href="../Estilos/detalle_meta.css">
</head>
<body>
    <header>
        <a href="../pag.principal.php"><img class="logo" src="../Estilos/img/logo_img.png" alt="Logo"></a>
        <a href="../pag.principal.php"><img class="logo-nombre" src="../Estilos/img/Logo.png" alt="Finnova Logo"></a>
        <h1>Detalle de Meta</h1>
        <div class="user-icon">
            <p><?= $usuarioNombre ?></p>
            <a href="../perfil.php"><img src="../Estilos/img/user-icon.png" alt="Perfil de usuario"></a>
        </div>
        <div class="sub-menu">
            <ul>
                <li><a href="../perfil.php">Perfil</a></li>
                <li><a href="logout.php">Cerrar Sesión</a></li>
            </ul>
        </div>
    </header>

    <main>
        <div class="menu">
            <ul>
                <li><a href="../ingresos.html">Ingresos</a></li>
                <li><a href="../gastos.html">Gastos</a></li>
                <li><a href="../ahorros.php">Ahorros</a></li>
                <li><a href="../metas.php" class="activo">Metas</a></li>
                <li><a href="../presupuesto.php">Presupuesto</a></li>
                <li><a href="../reportes/finanzas.php">Reportes</a></li>
            </ul>
        </div>

        <section class="informacion_meta">
            <h2><?= $nombreMeta ?></h2>
            <?php if ($mensajeAporte !== ""): ?>
                <p role="status"><?= htmlspecialchars($mensajeAporte, ENT_QUOTES, "UTF-8") ?></p>
            <?php endif; ?>
            <?php if ($imagenMeta !== ""): ?>
                <img src="<?= htmlspecialchars($imagenMeta, ENT_QUOTES, "UTF-8") ?>" alt="Imagen de <?= $nombreMeta ?>" style="max-width: 240px; height: auto;">
            <?php endif; ?>
            <p>Estado: <?= $estadoMeta ?></p>
            <p>Fecha de inicio: <?= htmlspecialchars(date("d/m/Y", strtotime($meta["metFechaInicio"])), ENT_QUOTES, "UTF-8") ?></p>
            <p>Fecha límite: <?= htmlspecialchars(date("d/m/Y", strtotime($meta["metFechaFin"])), ENT_QUOTES, "UTF-8") ?></p>
            <p>Valor objetivo: $<?= number_format((float) $meta["metMontoObjetivo"], 2, ",", ".") ?></p>
            <p>Total ahorrado: $<?= number_format((float) $meta["total_ahorrado"], 2, ",", ".") ?></p>
            <p>Dinero restante: $<?= number_format((float) $meta["dinero_restante"], 2, ",", ".") ?></p>
            <label for="progreso-meta">Progreso: <?= $porcentaje ?>%</label>
            <progress id="progreso-meta" value="<?= $porcentaje ?>" max="100"><?= $porcentaje ?>%</progress>
            <p><a href="../formulario_meta.html?idMeta=<?= (int) $meta["idMeta"] ?>">Registrar un aporte</a></p>
            <p><a href="editar_meta.php?idMeta=<?= (int) $meta["idMeta"] ?>">Editar meta</a></p>
            <p><a href="../metas.php">Volver a mis metas</a></p>

            <section class="historial-meta" aria-labelledby="titulo-historial-meta">
                <h2 id="titulo-historial-meta">Historial de aportes</h2>
                <?php if ($aportes->num_rows === 0): ?>
                    <p class="sin-aportes">Todavía no hay aportes registrados para esta meta.</p>
                <?php else: ?>
                    <div class="tabla-historial-meta">
                        <table>
                            <thead>
                                <tr>
                                    <th scope="col">Fecha</th>
                                    <th scope="col">Monto</th>
                                    <th scope="col">Descripción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($aporte = $aportes->fetch_assoc()):
                                    $descripcion = trim((string) ($aporte["apmDescripcion"] ?? ""));
                                    $descripcion = $descripcion !== "" ? $descripcion : "Sin descripción";
                                ?>
                                    <tr>
                                        <td><?= htmlspecialchars(date("d/m/Y H:i", strtotime($aporte["apmFecha"])), ENT_QUOTES, "UTF-8") ?></td>
                                        <td>$<?= number_format((float) $aporte["apmMonto"], 2, ",", ".") ?></td>
                                        <td><?= htmlspecialchars($descripcion, ENT_QUOTES, "UTF-8") ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </section>
        </section>
    </main>
</body>
</html>
