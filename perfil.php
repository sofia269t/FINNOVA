<?php
require_once __DIR__ . "/php/sesion.php";
require_once __DIR__ . "/php/conexion.php";

if (!isset($_SESSION["usuario_id"]) || !filter_var($_SESSION["usuario_id"], FILTER_VALIDATE_INT)) {
    header("Location: login.php");
    exit;
}

$usuarioId = (int) $_SESSION["usuario_id"];
$editando = ($_GET["editar"] ?? "") === "1";

function redirigirConMensajePerfil(string $mensaje): void
{
    $_SESSION["mensaje_perfil"] = $mensaje;
    header("Location: perfil.php?editar=1");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $token = $_POST["csrf_token"] ?? "";
    if (!is_string($token) || !isset($_SESSION["csrf_perfil"]) ||
        !hash_equals($_SESSION["csrf_perfil"], $token)) {
        http_response_code(400);
        exit("La solicitud no es válida. Recarga el perfil e inténtalo nuevamente.");
    }

    $nombre = $_POST["nombre"] ?? "";
    $apellido = $_POST["apellido"] ?? "";
    $correo = $_POST["correo"] ?? "";
    $telefono = $_POST["telefono"] ?? "";
    if (!is_string($nombre) || !is_string($apellido) || !is_string($correo) || !is_string($telefono)) {
        redirigirConMensajePerfil("Los datos ingresados no son válidos.");
    }

    $nombre = trim($nombre);
    $apellido = trim($apellido);
    $correo = trim($correo);
    $telefono = trim($telefono);
    $longitudValida = static function (string $valor, int $maximo): bool {
        return preg_match('/^.{1,' . $maximo . '}$/us', $valor) === 1;
    };

    if (!$longitudValida($nombre, 100) || !$longitudValida($apellido, 100) ||
        !$longitudValida($telefono, 100) || !$longitudValida($correo, 100)) {
        redirigirConMensajePerfil("Completa todos los campos y respeta el máximo de 100 caracteres.");
    }
    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        redirigirConMensajePerfil("Ingresa un correo electrónico válido.");
    }

    $consultaDuplicados = $conexion->prepare(
        "SELECT idUsuario FROM usuario
         WHERE idUsuario <> ? AND (usuCorreo = ? OR usuTelefono = ?)
         LIMIT 1"
    );
    if (!$consultaDuplicados) {
        error_log("No fue posible comprobar los datos del perfil: " . $conexion->error);
        http_response_code(500);
        exit("No fue posible guardar los cambios del perfil.");
    }
    $consultaDuplicados->bind_param("iss", $usuarioId, $correo, $telefono);
    if (!$consultaDuplicados->execute()) {
        error_log("No fue posible comprobar los datos del perfil: " . $consultaDuplicados->error);
        http_response_code(500);
        exit("No fue posible guardar los cambios del perfil.");
    }
    $duplicado = $consultaDuplicados->get_result()->num_rows > 0;
    $consultaDuplicados->close();
    if ($duplicado) {
        redirigirConMensajePerfil("Ese correo o teléfono ya está asociado a otra cuenta.");
    }

    $actualizar = $conexion->prepare(
        "UPDATE usuario
         SET usuNombre = ?, usuApellido = ?, usuCorreo = ?, usuTelefono = ?
         WHERE idUsuario = ?"
    );
    if (!$actualizar) {
        error_log("No fue posible preparar la actualización del perfil: " . $conexion->error);
        http_response_code(500);
        exit("No fue posible guardar los cambios del perfil.");
    }
    $actualizar->bind_param("ssssi", $nombre, $apellido, $correo, $telefono, $usuarioId);
    if (!$actualizar->execute()) {
        error_log("No fue posible actualizar el perfil: " . $actualizar->error);
        http_response_code(500);
        exit("No fue posible guardar los cambios del perfil.");
    }
    $actualizar->close();

    $_SESSION["usuario_nombre"] = $nombre;
    $_SESSION["usuario_apellido"] = $apellido;
    $_SESSION["usuario_correo"] = $correo;
    $_SESSION["usuario_telefono"] = $telefono;
    $_SESSION["csrf_perfil"] = bin2hex(random_bytes(32));
    $_SESSION["mensaje_perfil"] = "La información del perfil se actualizó correctamente.";
    header("Location: perfil.php");
    exit;
}

$stmt = $conexion->prepare(
    "SELECT usuNombre, usuApellido, usuCorreo, usuTelefono
     FROM usuario
     WHERE idUsuario = ?
     LIMIT 1"
);
if (!$stmt) {
    error_log("No fue posible preparar la consulta del perfil: " . $conexion->error);
    http_response_code(500);
    exit("No fue posible cargar el perfil.");
}
$stmt->bind_param("i", $usuarioId);
if (!$stmt->execute()) {
    error_log("No fue posible consultar el perfil: " . $stmt->error);
    http_response_code(500);
    exit("No fue posible cargar el perfil.");
}
$usuario = $stmt->get_result()->fetch_assoc();
$stmt->close();
$conexion->close();

if (!$usuario) {
    $_SESSION = [];
    session_destroy();
    header("Location: login.php");
    exit;
}

if (!isset($_SESSION["csrf_perfil"])) {
    $_SESSION["csrf_perfil"] = bin2hex(random_bytes(32));
}
$tokenPerfil = htmlspecialchars($_SESSION["csrf_perfil"], ENT_QUOTES, "UTF-8");
$mensajePerfil = $_SESSION["mensaje_perfil"] ?? "";
unset($_SESSION["mensaje_perfil"]);
$usuarioNombre = htmlspecialchars($usuario["usuNombre"] . " " . $usuario["usuApellido"], ENT_QUOTES, "UTF-8");
$usuarioCorreo = htmlspecialchars($usuario["usuCorreo"], ENT_QUOTES, "UTF-8");
$usuarioTelefono = htmlspecialchars($usuario["usuTelefono"], ENT_QUOTES, "UTF-8");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil</title>
    <link rel="stylesheet" href="Estilos/perfil.css">
</head>
<body>
    <header>
        <a href="pag.principal.php"><img class="logo"src="Estilos/img/logo_img.png" alt="Logo"></a>
        <a href="pag.principal.php"><img class="logo-nombre"src="Estilos/img/Logo.png" alt="Finnova Logo"></a>
        <h1>Perfil</h1>
        
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
                <li><a href="presupuesto.php">Presupuesto</a></li>
                <li><a href="reportes/finanzas.php">Reportes</a></li>
                <li><label for="total-restante"> Total restante:</label>
                    <input type="text" id="total-restante" name="total-restante" value="$0.00" readonly>
                
                </li>
            </ul>
        </div>

        <section class="perfil-contenido" aria-labelledby="titulo-perfil">
            <div class="foto-perfil">
                <img src="Estilos/img/user-icon.png" alt="Foto de perfil">
                <div>
                    <p class="etiqueta">Mi cuenta</p>
                    <h2><?= $usuarioNombre ?></h2>
                    <p class="estado">Cuenta activa</p>
                </div>
            </div>

            <section class="perfil" aria-labelledby="titulo-perfil">
                <div class="perfil-titulo">
                    <div>
                        <p class="etiqueta">Información personal</p>
                        <h2 id="titulo-perfil">Información del usuario</h2>
                    </div>
                    <?php if (!$editando): ?>
                        <a class="boton-editar" href="perfil.php?editar=1">Editar perfil</a>
                    <?php endif; ?>
                </div>

                <?php if ($mensajePerfil !== ""): ?>
                    <p role="status"><?= htmlspecialchars($mensajePerfil, ENT_QUOTES, "UTF-8") ?></p>
                <?php endif; ?>

                <?php if ($editando): ?>
                    <form class="form-editar-perfil" action="perfil.php" method="post">
                        <input type="hidden" name="csrf_token" value="<?= $tokenPerfil ?>">
                        <div class="datos-usuario">
                            <label class="dato" for="nombre">Nombre
                                <input type="text" id="nombre" name="nombre" maxlength="100" value="<?= htmlspecialchars($usuario["usuNombre"], ENT_QUOTES, "UTF-8") ?>" required>
                            </label>
                            <label class="dato" for="apellido">Apellido
                                <input type="text" id="apellido" name="apellido" maxlength="100" value="<?= htmlspecialchars($usuario["usuApellido"], ENT_QUOTES, "UTF-8") ?>" required>
                            </label>
                            <label class="dato" for="correo">Correo electrónico
                                <input type="email" id="correo" name="correo" maxlength="100" value="<?= $usuarioCorreo ?>" required>
                            </label>
                            <label class="dato" for="telefono">Teléfono
                                <input type="tel" id="telefono" name="telefono" maxlength="100" value="<?= $usuarioTelefono ?>" required>
                            </label>
                        </div>
                        <button class="boton-editar" type="submit">Guardar cambios</button>
                        <a href="perfil.php">Cancelar</a>
                    </form>
                <?php else: ?>
                    <div class="datos-usuario">
                        <div class="dato">
                            <span>Correo electrónico</span>
                            <p><?= $usuarioCorreo ?></p>
                        </div>
                        <div class="dato">
                            <span>Teléfono</span>
                            <p><?= $usuarioTelefono ?></p>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="preferencias">
                    <div class="preferencias-titulo">
                        <p class="etiqueta">Preferencias</p>
                        <h3>Notificaciones y presupuesto</h3>
                        <small>Estas preferencias todavía no se guardan en la base de datos.</small>
                    </div>

                    <div class="opcion opcion-checkbox">
                        <div>
                            <label for="notificaciones">Recibir notificaciones</label>
                            <small>Deseas recibir notificaciones </small>
                        </div>
                        <input type="checkbox" id="notificaciones" name="notificaciones" <?= $editando ? "" : "disabled" ?>>
                    </div>

                    <div class="opcion" id="frecuencia-notificaciones" hidden>
                        <label for="fecha_notificaciones">Cuándo recibir notificaciones</label>
                        <select id="fecha_notificaciones" name="fecha_notificaciones" disabled>
                            <option value="Sin Notificaciones" selected>Sin notificaciones</option>
                            <option value="Mensual">Mensual</option>
                            <option value="Quincenal">Quincenal</option>
                            <option value="Semanal">Semanal</option>
                        </select>
                    </div>

                    <div class="opcion">
                        <label for="presupuesto">Cuándo realizará el presupuesto</label>
                        <input type="date" id="presupuesto" name="presupuesto" disabled>
                    </div>
                </div>
            </section>
        </section>
        

    </main>
    <footer>
        <p> &copy; 2026 Finnova. Todos los derechos reservados.</p>
    </footer>
    <script>
        const notificaciones = document.getElementById("notificaciones");
        const frecuenciaNotificaciones = document.getElementById("frecuencia-notificaciones");

        function actualizarFrecuenciaNotificaciones() {
            frecuenciaNotificaciones.hidden = !notificaciones.checked;
        }

        notificaciones.addEventListener("change", actualizarFrecuenciaNotificaciones);
        actualizarFrecuenciaNotificaciones();
    </script>
</body>
</html>