<?php
require_once __DIR__ . "/sesion.php";

// La ficha solo está disponible para usuarios que iniciaron sesión.
if (!isset($_SESSION["usuario_id"])) {
    header("Location: ../login.php");
    exit;
}

// Esta página no modifica información, por eso solo necesita aceptar GET.
if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    http_response_code(405);
    header("Allow: GET");
    exit("Esta página solo acepta solicitudes GET.");
}

// Valida que el identificador de la URL sea un número entero positivo.
$idEntrada = $_GET["idIngreso"] ?? "";
$idIngreso = is_string($idEntrada)
    ? filter_var($idEntrada, FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]])
    : false;

if ($idIngreso === false) {
    http_response_code(400);
    exit("El identificador del ingreso no es válido.");
}

$usuarioId = (int) $_SESSION["usuario_id"];
require_once __DIR__ . "/conexion.php";

// La consulta exige que el ingreso pertenezca al usuario autenticado.
$stmt = $conexion->prepare(
    "SELECT ingNombre, ingMonto, ingFecha, ingDescripcion, ingFrecuencia, ingRecordatorio
     FROM ingreso
     WHERE idIngreso = ? AND ingUsuario = ?"
);
if (!$stmt) {
    error_log("No se pudo preparar la consulta del ingreso: " . $conexion->error);
    $conexion->close();
    http_response_code(500);
    exit("No fue posible consultar el ingreso.");
}

$stmt->bind_param("ii", $idIngreso, $usuarioId);
if (!$stmt->execute()) {
    error_log("No se pudo consultar el ingreso: " . $stmt->error);
    $stmt->close();
    $conexion->close();
    http_response_code(500);
    exit("No fue posible consultar el ingreso.");
}

$ingreso = $stmt->get_result()->fetch_assoc();
$stmt->close();
$conexion->close();

// No se revela si un identificador ajeno existe: solo se informa que no está disponible.
if (!$ingreso) {
    http_response_code(404);
    exit("No se encontró ese ingreso entre tus registros.");
}

// Escapa el texto al prepararlo para HTML y formatea la fecha y el monto para mostrarlos.
$usuarioNombre = htmlspecialchars($_SESSION["usuario_nombre"] ?? "Usuario", ENT_QUOTES, "UTF-8");
$nombre = htmlspecialchars($ingreso["ingNombre"], ENT_QUOTES, "UTF-8");
$monto = number_format((float) $ingreso["ingMonto"], 2, ",", ".");
$fechaISO = htmlspecialchars($ingreso["ingFecha"], ENT_QUOTES, "UTF-8");
$fechaVisible = htmlspecialchars(date("d/m/Y", strtotime($ingreso["ingFecha"])), ENT_QUOTES, "UTF-8");
$descripcion = trim((string) $ingreso["ingDescripcion"]);
$descripcionVisible = $descripcion !== ""
    ? htmlspecialchars($descripcion, ENT_QUOTES, "UTF-8")
    : "Sin descripción";
$frecuencia = htmlspecialchars($ingreso["ingFrecuencia"], ENT_QUOTES, "UTF-8");
$recordatorios = (int) $ingreso["ingRecordatorio"] === 1 ? "Activados" : "Desactivados";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consultar ingreso - Finnova</title>
    <link rel="stylesheet" href="../Estilos/ingresos-pagina.css?v=2">
</head>
<body>
    <header class="encabezado">
        <a href="../pag.principal.php" aria-label="Ir al inicio">
            <img class="logo" src="../Estilos/img/logo_img.png" alt="Logo de Finnova">
        </a>
        <a href="../pag.principal.php" aria-label="Ir al inicio">
            <img class="logo-nombre" src="../Estilos/img/Logo.png" alt="Finnova">
        </a>
        <h1>Consultar ingreso</h1>

        <div class="usuario">
            <span><?= $usuarioNombre ?></span>
            <a href="../perfil.php" aria-label="Ver perfil">
                <img src="../Estilos/img/user-icon.png" alt="">
            </a>
        </div>
        <nav class="sub-menu" aria-label="Opciones de usuario">
            <a href="../perfil.php">Perfil</a>
            <a href="../php/logout.php">Cerrar sesión</a>
        </nav>
    </header>

    <main>
        <aside class="menu">
            <nav aria-label="Navegación principal">
                <ul>
                    <li><a href="ingresos.php" class="activo" aria-current="page">Ingresos</a></li>
                    <li><a href="../gastos.html">Gastos</a></li>
                    <li><a href="../ahorros.php">Ahorros</a></li>
                    <li><a href="../metas.php">Metas</a></li>
                    <li><a href="../presupuesto.php">Presupuesto</a></li>
                    <li><a href="../reportes/finanzas.php">Reportes</a></li>
                </ul>
            </nav>
            <div class="total">
                <label for="total-restante">Total restante:</label>
                <input type="text" id="total-restante" value="$0.00" readonly>
            </div>
        </aside>

        <section class="contenido">
            <section class="vista-ingresos" aria-label="Detalle del ingreso">
            <article class="tarjeta-ingreso detalle-ingreso">
                <h2><?= $nombre ?></h2>
                <dl>
                    <dt>Monto</dt>
                    <dd>$ <?= $monto ?></dd>

                    <dt>Fecha</dt>
                    <dd><time datetime="<?= $fechaISO ?>"><?= $fechaVisible ?></time></dd>

                    <dt>Frecuencia</dt>
                    <dd><?= $frecuencia ?></dd>

                    <dt>Descripción</dt>
                    <dd><?= $descripcionVisible ?></dd>

                    <dt>Recordatorios</dt>
                    <dd><?= $recordatorios ?></dd>
                </dl>
                <!-- Ofrece volver a la lista sin ejecutar ninguna acción de modificación. -->
                <a class="boton-agregar" href="ingresos.php">Volver a ingresos</a>
            </article>
            </section>
        </section>
    </main>
</body>
</html>
