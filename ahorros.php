<?php
require_once __DIR__ . "/php/sesion.php";
require_once __DIR__ . "/php/conexion.php";

if (!isset($_SESSION["usuario_id"])) {
    header("Location: login.php");
    exit;
}

$usuarioNombre = htmlspecialchars($_SESSION["usuario_nombre"] ?? "Usuario");
$usuarioId = (int) $_SESSION["usuario_id"];

// 1. Movimientos del historial
$sql = "SELECT
            a.idAhorro,
            a.ahorFecha,
            a.ahorMonto,
            a.ahorUsuario
        FROM ahorro a
        WHERE a.ahorUsuario = ?
        ORDER BY a.ahorFecha ASC";

$stmt = $conexion->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    exit("No fue posible consultar tus ahorros.");
}

$stmt->bind_param("i", $usuarioId);
if (!$stmt->execute()) {
    http_response_code(500);
    exit("No fue posible cargar tus ahorros.");
}
$movimientos = $stmt->get_result();

// 2. Total ahorrado acumulado
$sqlTotal = "SELECT COALESCE(SUM(ahorMonto), 0) AS total_ahorrado
             FROM ahorro
             WHERE ahorUsuario = ?";
$stmtTotal = $conexion->prepare($sqlTotal);
$stmtTotal->bind_param("i", $usuarioId);
$stmtTotal->execute();
$totalAhorrado = (float) ($stmtTotal->get_result()->fetch_assoc()["total_ahorrado"] ?? 0);

$mensajeMeta = $_SESSION["mensaje_ahorro"] ?? "";
unset($_SESSION["mensaje_ahorro"]);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ahorros - Finnova</title>
    <link rel="stylesheet" href="Estilos/ahorros.css">
</head>
<body>
    <header class="encabezado">
        <a href="pag.principal.php" aria-label="Ir al inicio">
            <img class="logo" src="Estilos/img/logo_img.png" alt="Logo de Finnova">
        </a>
        <a href="pag.principal.php" aria-label="Ir al inicio">
            <img class="logo-nombre" src="Estilos/img/Logo.png" alt="Finnova">
        </a>
        <h1>Ahorros</h1>

        <div class="usuario">
            <span><?= $usuarioNombre ?></span>
            <a href="perfil.html">
                <img src="Estilos/img/user-icon.png" alt="Ver perfil">
            </a>
        </div>

        <nav class="sub-menu" aria-label="Opciones de usuario">
            <a href="perfil.html">Perfil</a>
            <a href="index.html">Cerrar sesión</a>
        </nav>
    </header>

    <main>
        <aside class="menu">
            <nav aria-label="Navegación principal">
                <ul>
                    <li><a href="ingresos.html">Ingresos</a></li>
                    <li><a href="gastos.html">Gastos</a></li>
                    <li><a href="ahorros.php" class="activo" aria-current="page">Ahorros</a></li>
                    <li><a href="metas.php">Metas</a></li>
                    <li><a href="presupuesto.html">Presupuesto</a></li>
                    <li><a href="#">Reportes</a></li>
                </ul>
            </nav>

            <div class="total">
                <label for="total-restante">Total restante:</label>
                <input type="text" id="total-restante" value="$0.00" readonly>
            </div>
        </aside>

        <section class="contenido">
            <div class="resumen">
                <div>
                    <p class="etiqueta">Mi ahorro</p>
                    <h2>Ahorros</h2>
                    <p class="descripcion">Organiza tu dinero y alcanza tus objetivos.</p>
                </div>

                <div class="saldo">
                    <span>Saldo actual</span>
                    <strong id="saldo">$<?= number_format($totalAhorrado, 2) ?></strong>
                </div>
            </div>

            <?php if ($mensajeMeta !== ""): ?>
                <p class="mensaje-estado"><?= htmlspecialchars($mensajeMeta) ?></p>
            <?php endif; ?>

            <form class="formulario" action="#" method="get">
                <div class="campo">
                    <label for="cantidad">Valor del ahorro</label>
                    <input type="number" id="cantidad" name="cantidad"
                           min="0.01" step="0.01" placeholder="0.00" required>
                </div>
                <button type="submit">Ahorrar</button>
                <a class="boton-secundario" href="#">Retirar</a>
            </form>

            <section class="historial" aria-labelledby="titulo-historial">
                <div class="historial-encabezado">
                    <div>
                        <p class="etiqueta">Movimientos</p>
                        <h2 id="titulo-historial">Historial</h2>
                    </div>

                    <label class="filtro" for="mostrarHistorial">
                        <input type="checkbox" id="mostrarHistorial" checked>
                        Mostrar historial
                    </label>
                </div>

                <?php if ($movimientos->num_rows === 0): ?>
                    <p class="sin-movimientos">Todavía no tienes movimientos registrados.</p>
                <?php else: ?>
                    <div class="tabla-contenedor">
                        <table>
                            <thead>
                                <tr>
                                    <th scope="col">Movimiento</th>
                                    <th scope="col">Valor</th>
                                    <th scope="col">Fecha</th>
                                    <th scope="col">Estado</th>
                                </tr>
                            </thead>
                            <tbody id="listaMovimientos">
                                <?php while ($fila = $movimientos->fetch_assoc()): ?>
                                    <?php $monto = (float) $fila["ahorMonto"]; ?>
                                    <tr>
                                        <td><?= $monto >= 0 ? "Ahorro" : "Retiro" ?></td>
                                        <td>$<?= number_format(abs($monto), 2) ?></td>
                                        <td><?= htmlspecialchars($fila["ahorFecha"]) ?></td>
                                        <td>Completado</td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>

                    <a class="borrar-historial" href="ahorros.php">Borrar historial</a>
                <?php endif; ?>
            </section>
        </section>
    </main>

    <footer>
        <p>&copy; 2026 Finnova. Todos los derechos reservados.</p>
    </footer>
</body>
</html>