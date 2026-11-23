<?php
require_once __DIR__ . "/sesion.php";

if (!isset($_SESSION["usuario_id"])) {
    header("Location: ../login.php");
    exit;
}

require_once __DIR__ . "/conexion.php";
$usuarioId = (int) $_SESSION["usuario_id"];
$sql = "SELECT ingNombre, ingMonto, ingFecha, ingDescripcion, ingFrecuencia
        FROM ingreso
        WHERE ingUsuario = ?
        ORDER BY ingFecha DESC, idIngreso DESC";
$stmt = $conexion->prepare($sql);

if (!$stmt) {
    error_log("No se pudo preparar la consulta de ingresos: " . $conexion->error);
    http_response_code(500);
    exit("No fue posible consultar tus ingresos.");
}

$stmt->bind_param("i", $usuarioId);
if (!$stmt->execute()) {
    error_log("No se pudo consultar los ingresos: " . $stmt->error);
    $stmt->close();
    $conexion->close();
    http_response_code(500);
    exit("No fue posible consultar tus ingresos.");
}

$resultado = $stmt->get_result();
$ingresos = [];
while ($fila = $resultado->fetch_assoc()) {
    $ingresos[] = $fila;
}

$stmt->close();
$conexion->close();
$usuarioNombre = htmlspecialchars($_SESSION["usuario_nombre"] ?? "Usuario", ENT_QUOTES, "UTF-8");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ingresos - Finnova</title>
    <link rel="stylesheet" href="../Estilos/ingresos-pagina.css">
</head>
<body class="pagina-ingresos">
    <header class="encabezado">
        <details class="menu-desplegable">
            <summary class="boton-menu" aria-label="Abrir menú de navegación">
                <span></span>
                <span></span>
                <span></span>
            </summary>
            <nav class="menu-navegacion" aria-label="Navegación principal">
                <a href="../pag.principal.php">Inicio</a>
                <a href="ingresos.php" aria-current="page">Ingresos</a>
                <a href="../gastos.html">Gastos</a>
                <a href="../ahorros.html">Ahorros</a>
                <a href="../metas.html">Metas</a>
                <a href="../presupuesto.html">Presupuesto</a>
                <a href="logout.php">Cerrar sesión</a>
            </nav>
        </details>

        <a class="enlace-logo" href="../pag.principal.php" aria-label="Volver al inicio">
            <img class="logo" src="../Estilos/img/logo_img.png" alt="">
        </a>
        <h1>Ingresos</h1>

        <div class="usuario">
            <span><?= $usuarioNombre ?></span>
            <a href="../perfil.html" aria-label="Ver perfil">
                <img src="../Estilos/img/user-icon.png" alt="">
            </a>
        </div>
    </header>

    <main class="contenido">
        <section class="vista-ingresos" aria-label="Mis ingresos">
            <div class="lista-ingresos">
                <?php foreach ($ingresos as $ingreso): ?>
                    <?php
                    $nombre = htmlspecialchars($ingreso["ingNombre"], ENT_QUOTES, "UTF-8");
                    $monto = number_format((float) $ingreso["ingMonto"], 2, ",", ".");
                    $fecha = htmlspecialchars($ingreso["ingFecha"], ENT_QUOTES, "UTF-8");
                    $fechaVisible = htmlspecialchars(date("d/m/Y", strtotime($ingreso["ingFecha"])), ENT_QUOTES, "UTF-8");
                    $frecuencia = htmlspecialchars($ingreso["ingFrecuencia"], ENT_QUOTES, "UTF-8");
                    $descripcion = trim((string) $ingreso["ingDescripcion"]);
                    ?>
                    <article class="tarjeta-ingreso">
                        <h2><?= $nombre ?></h2>
                        <p class="monto-ingreso">$ <?= $monto ?></p>
                        <p class="fecha-ingreso">Fecha: <time datetime="<?= $fecha ?>"><?= $fechaVisible ?></time></p>
                        <p class="fecha-ingreso">Frecuencia: <?= $frecuencia ?></p>
                        <?php if ($descripcion !== ""): ?>
                            <p class="fecha-ingreso"><?= htmlspecialchars($descripcion, ENT_QUOTES, "UTF-8") ?></p>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>

            <a class="boton-agregar" href="formulario_ingreso.php">
                <span aria-hidden="true">+</span>
                Agregar ingresos
            </a>
        </section>
    </main>
</body>
</html>
