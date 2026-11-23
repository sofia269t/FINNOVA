<?php
require_once __DIR__ . "/php/sesion.php";
require_once __DIR__ . "/php/conexion.php";

if (!isset($_SESSION["usuario_id"])) {
    header("Location: login.php");
    exit;
}

$usuarioNombre = htmlspecialchars($_SESSION["usuario_nombre"] ?? "Usuario");
$sql = "SELECT
            m.idMeta,
            m.metNombre,
            m.metFechaFin,
            m.metMontoObjetivo,
            m.metEstado,
            COALESCE(SUM(a.apmMonto), 0) AS total_ahorrado,
            ROUND(LEAST(COALESCE(SUM(a.apmMonto), 0) / NULLIF(m.metMontoObjetivo, 0) * 100, 100), 2) AS porcentaje_progreso
        FROM meta m
        LEFT JOIN aporte_meta a ON a.apmMeta = m.idMeta
        WHERE m.metUsuario = ?
        GROUP BY m.idMeta, m.metNombre, m.metFechaFin, m.metMontoObjetivo, m.metEstado
        ORDER BY m.metFechaFin ASC";
$stmt = $conexion->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    exit("No fue posible consultar tus metas.");
}
$usuarioId = (int) $_SESSION["usuario_id"];
$stmt->bind_param("i", $usuarioId);
if (!$stmt->execute()) {
    http_response_code(500);
    exit("No fue posible cargar tus metas.");
}
$metas = $stmt->get_result();
$mensajeMeta = $_SESSION["mensaje_meta"] ?? "";
unset($_SESSION["mensaje_meta"]);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Metas</title>
    <link rel="stylesheet" href="Estilos/metas.css">
</head>
<body>
        <header>
        <a href="pag.principal.php"><img class="logo"src="Estilos/img/logo_img.png" alt="Logo"></a>
        <a href="pag.principal.php"><img class="logo-nombre"src="Estilos/img/Logo.png" alt="Finnova Logo"></a>
        <h1>Metas</h1>
        <div class="user-icon">
                    <p><?= $usuarioNombre ?></p>
                    <a href="perfil.html">
                        <img  src="Estilos/img/user-icon.png" alt="User Icon">
                    </a>
        </div>
        <div class="sub-menu">
            <ul>
                <li><a href="perfil.html">Perfil</a></li>
                <li><a href="index.html">Cerrar Sesión</a></li>
            </ul>
        </div>
    </header>
    
    <main>
        <div class="menu">
            <ul>
                <li><a href="ingresos.html">Ingresos</a></li>
                <li><a href="gastos.html">Gastos</a></li>
                <li><a href="ahorros.php">Ahorros</a></li>
                <li><a href="metas.php" class="activo">Metas</a></li>
                <li><a href="presupuesto.html">Presupuesto</a></li>
                <li><a href="#">Reportes</a></li>
                <li><label for="total-restante"> Total restante:</label>
                    <input type="text" id="total-restante" name="total-restante" value="$0.00" readonly>
                
                </li>
            </ul>
        </div>
        <section class="metas">
            <a href="crear_meta.html" class="btn-graficas">Crear nueva meta</a>
            <?php if ($mensajeMeta !== ""): ?>
                <p role="status"><?= htmlspecialchars($mensajeMeta, ENT_QUOTES, "UTF-8") ?></p>
            <?php endif; ?>
                <!--En esta parte el sistema va a buscar en la base de datos la informacion de las metas del usuario que tiene la sesion iniciada
                 y las va a mostrar en esta seccion primero el nombre de la meta, al lado el valor de la meta, abajo la fecha que coloco el usuario
                  y abajo una barra de progreso todo esto dentro de un bloque y cada meta tendra su categoria sin extender el codigo-->
            <?php if ($metas->num_rows === 0): ?>
                <p>Aún no tienes metas registradas.</p>
            <?php else: ?>
                <?php while ($meta = $metas->fetch_assoc()):
                    $nombreMeta = htmlspecialchars($meta["metNombre"], ENT_QUOTES, "UTF-8");
                    $porcentaje = number_format((float) $meta["porcentaje_progreso"], 2, ".", "");
                ?>
                    <article class="meta">
                        <h2><?= $nombreMeta ?></h2>
                        <p>Valor objetivo: $<?= number_format((float) $meta["metMontoObjetivo"], 2, ",", ".") ?></p>
                        <p>Fecha límite: <?= htmlspecialchars(date("d/m/Y", strtotime($meta["metFechaFin"])), ENT_QUOTES, "UTF-8") ?></p>
                        <p>Estado: <?= htmlspecialchars($meta["metEstado"], ENT_QUOTES, "UTF-8") ?></p>
                        <progress id="meta-<?= (int) $meta["idMeta"] ?>" value="<?= $porcentaje ?>" max="100"><?= $porcentaje ?>%</progress>
                        <p>Ahorrado: $<?= number_format((float) $meta["total_ahorrado"], 2, ",", ".") ?> (<?= $porcentaje ?>%)</p>
                        <a href="formulario_meta.html?idMeta=<?= (int) $meta["idMeta"] ?>">Agregar aporte</a>
                        <a href="php/detalle_meta.php?idMeta=<?= (int) $meta["idMeta"] ?>">Ver meta</a>
                    </article>
                <?php endwhile; ?>
            <?php endif; ?>
        </section>
    </main>
    <footer>
        <p> &copy; 2026 Finnova. Todos los derechos reservados.</p>
    </footer>
</body>
</html>
<?php
$stmt->close();
$conexion->close();
?>