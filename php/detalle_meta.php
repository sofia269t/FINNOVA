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
$conexion->close();

if (!$meta) {
    http_response_code(404);
    exit("No se encontró la meta solicitada.");
}

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
    <link rel="stylesheet" href="../Estilos/presupuesto.css">
</head>
<body>
    <header>
        <a href="../pag.principal.php"><img class="logo" src="../Estilos/img/logo_img.png" alt="Logo"></a>
        <a href="../pag.principal.php"><img class="logo-nombre" src="../Estilos/img/Logo.png" alt="Finnova Logo"></a>
        <h1>Detalle de Meta</h1>
        <div class="user-icon">
            <p><?= $usuarioNombre ?></p>
            <a href="../perfil.html"><img src="../Estilos/img/user-icon.png" alt="Perfil de usuario"></a>
        </div>
        <div class="sub-menu">
            <ul>
                <li><a href="../perfil.html">Perfil</a></li>
                <li><a href="logout.php">Cerrar Sesión</a></li>
            </ul>
        </div>
    </header>

    <main>
        <div class="menu">
            <ul>
                <li><a href="../ingresos.html">Ingresos</a></li>
                <li><a href="../gastos.html">Gastos</a></li>
                <li><a href="../ahorros.html">Ahorros</a></li>
                <li><a href="../metas.php" class="activo">Metas</a></li>
                <li><a href="../presupuesto.html">Presupuesto</a></li>
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
            <p><a href="../metas.php">Volver a mis metas</a></p>
        </section>
    </main>
</body>
</html>
