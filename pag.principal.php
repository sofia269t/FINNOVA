<?php
require_once __DIR__ . "/php/sesion.php";

if (!isset($_SESSION["usuario_id"])) {
    header("Location: login.php");
    exit;
}

$usuarioNombre = htmlspecialchars($_SESSION["usuario_nombre"] ?? "Usuario");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bienvenido</title>
    <link rel="stylesheet" href="Estilos/pag.principal.css?v=2">
</head>
<body>
    <header>
        <a href="pag.principal.php"><img class="logo" src="Estilos/img/logo_img.png" alt="Logo"></a>
        <a href="pag.principal.php"><img class="logo-nombre" src="Estilos/img/Logo.png" alt="Finnova Logo"></a>
        <h1>Bienvenido</h1>
        <div class="user-icon">
            <p>Bienvenido, <?= $usuarioNombre ?></p>
            <a href="perfil.php">
                <img src="Estilos/img/user-icon.png" alt="User Icon">
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
                <li>
                    <label for="total-restante"> Total restante:</label>
                    <input type="text" id="total-restante" name="total-restante" value="$0.00" readonly>
                </li>
            </ul>
        </div>
        <section class="bienvenida">
            <img src="Estilos/img/logo_completo.png" alt="logo finnova">
            <h2>Bienvenido <?= $usuarioNombre ?></h2>
            <p>¿Por donde quieres empezar hoy?</p>
            <a href="ingresos.html">Registra tus ingresos</a>
            <a href="gastos.html">Registra tus gastos</a>
        </section>
    </main>
    <footer>
        <p> &copy; 2026 Finnova. Todos los derechos reservados.</p>
    </footer>
</body>
</html>
