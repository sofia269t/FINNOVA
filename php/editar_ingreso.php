<?php
require_once __DIR__ . "/sesion.php";

// Solo se permite editar ingresos dentro de una sesión autenticada.
if (!isset($_SESSION["usuario_id"])) {
    header("Location: ../login.php");
    exit;
}

if (!in_array($_SERVER["REQUEST_METHOD"], ["GET", "POST"], true)) {
    http_response_code(405);
    header("Allow: GET, POST");
    exit("Esta página solo acepta solicitudes GET y POST.");
}

if (!isset($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

// Muestra errores seguros para el usuario sin exponer detalles de la base de datos.
function mostrar_error_edicion(int $codigo, string $mensaje): void
{
    http_response_code($codigo);
    $mensajeSeguro = htmlspecialchars($mensaje, ENT_QUOTES, "UTF-8");
    echo "<!DOCTYPE html>
<html lang=\"es\">
<head>
    <meta charset=\"UTF-8\">
    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
    <title>Error al actualizar el ingreso</title>
</head>
<body>
    <main>
        <h1>No se pudo actualizar el ingreso</h1>
        <p>{$mensajeSeguro}</p>
        <a href=\"ingresos.php\">Volver a mis ingresos</a>
    </main>
</body>
</html>";
    exit;
}

$idEntrada = $_SERVER["REQUEST_METHOD"] === "POST"
    ? ($_POST["idIngreso"] ?? "")
    : ($_GET["idIngreso"] ?? "");
$idIngreso = is_string($idEntrada)
    ? filter_var($idEntrada, FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]])
    : false;

// Se valida el identificador antes de consultar, tanto al abrir como al enviar el formulario.
if ($idIngreso === false) {
    mostrar_error_edicion(400, "El identificador del ingreso no es válido.");
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Impide que un sitio externo envíe cambios en nombre del usuario autenticado.
    $tokenEnviado = $_POST["csrf_token"] ?? "";
    $tokenSesion = $_SESSION["csrf_token"];
    if (!is_string($tokenEnviado) || !hash_equals($tokenSesion, $tokenEnviado)) {
        mostrar_error_edicion(403, "La solicitud expiró o no es válida. Vuelve a tus ingresos.");
    }
}

$usuarioId = (int) $_SESSION["usuario_id"];
require_once __DIR__ . "/conexion.php";

$stmt = $conexion->prepare(
    "SELECT ingMonto, ingFecha, ingNombre, ingDescripcion, ingFrecuencia, ingRecordatorio
     FROM ingreso
     WHERE idIngreso = ? AND ingUsuario = ?"
);
if (!$stmt) {
    error_log("No se pudo preparar la consulta del ingreso: " . $conexion->error);
    $conexion->close();
    mostrar_error_edicion(500, "No fue posible consultar el ingreso.");
}

$stmt->bind_param("ii", $idIngreso, $usuarioId);
if (!$stmt->execute()) {
    error_log("No se pudo consultar el ingreso: " . $stmt->error);
    $stmt->close();
    $conexion->close();
    mostrar_error_edicion(500, "No fue posible consultar el ingreso.");
}

$ingreso = $stmt->get_result()->fetch_assoc();
$stmt->close();

// La condición de propietario evita consultar o modificar ingresos de otras cuentas.
if (!$ingreso) {
    $conexion->close();
    mostrar_error_edicion(404, "No se encontró ese ingreso entre tus registros.");
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Obtiene y valida todos los campos enviados antes de actualizar la fila.
    $nombreEntrada = $_POST["nombre"] ?? "";
    $montoEntrada = $_POST["monto"] ?? "";
    $fechaEntrada = $_POST["fecha"] ?? "";
    $frecuenciaEntrada = $_POST["frecuencia"] ?? "";
    $descripcionEntrada = $_POST["descripcion"] ?? "";

    if (!is_string($nombreEntrada) || !is_string($montoEntrada)
        || !is_string($fechaEntrada) || !is_string($frecuenciaEntrada)
        || !is_string($descripcionEntrada)) {
        $conexion->close();
        mostrar_error_edicion(400, "Los datos enviados no son válidos.");
    }

    $nombre = trim($nombreEntrada);
    $frecuencia = $frecuenciaEntrada;
    $fecha = $fechaEntrada;
    $descripcion = isset($_POST["activar-descripcion"]) ? trim($descripcionEntrada) : "";
    $recordatorios = isset($_POST["recordatorios"]) ? 1 : 0;
    $fechaPartes = [];
    $fechaValida = preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $fecha, $fechaPartes)
        && checkdate((int) $fechaPartes[2], (int) $fechaPartes[3], (int) $fechaPartes[1]);

    if ((isset($_POST["activar-descripcion"]) && $_POST["activar-descripcion"] !== "1")
        || (isset($_POST["recordatorios"]) && $_POST["recordatorios"] !== "1")) {
        $conexion->close();
        mostrar_error_edicion(400, "Las opciones seleccionadas no son válidas.");
    }

    if ($nombre === "" || strlen($nombre) > 100) {
        $conexion->close();
        mostrar_error_edicion(400, "El nombre del ingreso es obligatorio y no debe superar 100 caracteres.");
    }

    if (!is_numeric($montoEntrada) || !is_finite((float) $montoEntrada)
        || (float) $montoEntrada <= 0 || (float) $montoEntrada > 9999999999.99) {
        $conexion->close();
        mostrar_error_edicion(400, "Ingresa un monto válido mayor que cero.");
    }

    if (!$fechaValida) {
        $conexion->close();
        mostrar_error_edicion(400, "Selecciona una fecha válida.");
    }

    if (!in_array($frecuencia, ["Mensual", "Quincenal", "Semanal"], true)) {
        $conexion->close();
        mostrar_error_edicion(400, "Selecciona una frecuencia válida.");
    }

    if (strlen($descripcion) > 200) {
        $conexion->close();
        mostrar_error_edicion(400, "La descripción no debe superar 200 caracteres.");
    }

    $monto = (float) $montoEntrada;
    // Actualiza únicamente la fila que pertenece al usuario de la sesión.
    $stmt = $conexion->prepare(
        "UPDATE ingreso
         SET ingNombre = ?, ingMonto = ?, ingFecha = ?, ingDescripcion = ?,
             ingFrecuencia = ?, ingRecordatorio = ?
         WHERE idIngreso = ? AND ingUsuario = ?"
    );
    if (!$stmt) {
        error_log("No se pudo preparar la actualización del ingreso: " . $conexion->error);
        $conexion->close();
        mostrar_error_edicion(500, "No fue posible actualizar el ingreso.");
    }

    $stmt->bind_param(
        "sdsssiii",
        $nombre,
        $monto,
        $fecha,
        $descripcion,
        $frecuencia,
        $recordatorios,
        $idIngreso,
        $usuarioId
    );
    if (!$stmt->execute()) {
        error_log("No se pudo actualizar el ingreso: " . $stmt->error);
        $stmt->close();
        $conexion->close();
        mostrar_error_edicion(500, "No fue posible actualizar el ingreso.");
    }

    $stmt->close();
    $conexion->close();
    header("Location: ingresos.php", true, 303);
    exit;
}

$conexion->close();
$usuarioNombre = htmlspecialchars($_SESSION["usuario_nombre"] ?? "Usuario", ENT_QUOTES, "UTF-8");
$tokenCSRF = htmlspecialchars($_SESSION["csrf_token"], ENT_QUOTES, "UTF-8");
$nombreActual = htmlspecialchars($ingreso["ingNombre"], ENT_QUOTES, "UTF-8");
$montoActual = htmlspecialchars((string) $ingreso["ingMonto"], ENT_QUOTES, "UTF-8");
$fechaActual = htmlspecialchars($ingreso["ingFecha"], ENT_QUOTES, "UTF-8");
$descripcionActual = htmlspecialchars($ingreso["ingDescripcion"], ENT_QUOTES, "UTF-8");
$frecuenciaActual = $ingreso["ingFrecuencia"];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar ingreso - Finnova</title>
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
        <h1>Editar ingreso</h1>

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
            <section class="vista-formulario" aria-label="Editar ingreso">
            <!-- Este formulario HTML envía los cambios mediante POST, sin JavaScript. -->
            <form action="editar_ingreso.php" method="POST">
                <input type="hidden" name="idIngreso" value="<?= (int) $idIngreso ?>">
                <input type="hidden" name="csrf_token" value="<?= $tokenCSRF ?>">
                <button class="boton-guardar" type="submit">Guardar cambios</button>

                <div class="campos-principales">
                    <div class="campo-formulario">
                        <label for="nombre-ingreso">Nombre del ingreso</label>
                        <input id="nombre-ingreso" name="nombre" type="text"
                               value="<?= $nombreActual ?>" maxlength="100" required>
                    </div>

                    <div class="campo-formulario">
                        <label for="monto-ingreso">Monto del ingreso</label>
                        <div class="campo-monto">
                            <span aria-hidden="true">$</span>
                            <input id="monto-ingreso" name="monto" type="number"
                                   min="0.01" max="9999999999.99" step="0.01"
                                   value="<?= $montoActual ?>" required>
                        </div>
                    </div>

                    <div class="campo-formulario">
                        <label for="fecha-ingreso">Fecha</label>
                        <input id="fecha-ingreso" name="fecha" type="date"
                               value="<?= $fechaActual ?>" required>
                    </div>

                    <div class="campo-formulario">
                        <label for="frecuencia-ingreso">Frecuencia</label>
                        <select id="frecuencia-ingreso" name="frecuencia" required>
                            <option value="Mensual" <?= $frecuenciaActual === "Mensual" ? "selected" : "" ?>>Mensual</option>
                            <option value="Quincenal" <?= $frecuenciaActual === "Quincenal" ? "selected" : "" ?>>Quincenal</option>
                            <option value="Semanal" <?= $frecuenciaActual === "Semanal" ? "selected" : "" ?>>Semanal</option>
                        </select>
                    </div>
                </div>

                <div class="opciones-ingreso">
                    <label class="opcion-ingreso" for="recordatorios">
                        <span>¿Desea recibir recordatorios?</span>
                        <input id="recordatorios" name="recordatorios" type="checkbox" value="1"
                            <?= (int) $ingreso["ingRecordatorio"] === 1 ? "checked" : "" ?>>
                    </label>

                    <label class="opcion-ingreso" for="activar-descripcion">
                        <span>¿Desea añadir una descripción?</span>
                        <input id="activar-descripcion" name="activar-descripcion" type="checkbox"
                               value="1" <?= $ingreso["ingDescripcion"] !== "" ? "checked" : "" ?>>
                    </label>

                    <input class="campo-descripcion" id="descripcion-ingreso" name="descripcion"
                           type="text" value="<?= $descripcionActual ?>"
                           placeholder="Descripción..." maxlength="200">
                    <a class="boton-agregar" href="ingresos.php">Cancelar</a>
                </div>
            </form>
            </section>
        </section>
    </main>
</body>
</html>
