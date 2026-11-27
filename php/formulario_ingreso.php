<?php
require_once __DIR__ . "/sesion.php";

if (!isset($_SESSION["usuario_id"])) {
    header("Location: ../login.php");
    exit;
}

$usuarioNombre = htmlspecialchars($_SESSION["usuario_nombre"] ?? "Usuario", ENT_QUOTES, "UTF-8");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agregar ingreso - Finnova</title>
    <link rel="stylesheet" href="../Estilos/ingresos-pagina.css?v=3">
</head>
<body>
    <header class="encabezado">
        <a href="../pag.principal.php" aria-label="Ir al inicio">
            <img class="logo" src="../Estilos/img/logo_img.png" alt="Logo de Finnova">
        </a>
        <a href="../pag.principal.php" aria-label="Ir al inicio">
            <img class="logo-nombre" src="../Estilos/img/Logo.png" alt="Finnova">
        </a>
        <h1>Ingresos</h1>

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
            <section class="vista-formulario" aria-label="Nuevo ingreso">
            <form action="guardar_ingreso.php" method="POST">
                <button class="boton-guardar" type="submit">Guardar</button>

                <div class="campos-principales">
                    <div class="campo-formulario">
                        <label for="nombre-ingreso">Ingrese el nombre de su ingreso</label>
                        <input id="nombre-ingreso" name="nombre" type="text"
                               placeholder="Salario" maxlength="100" required>
                    </div>

                    <div class="campo-formulario">
                        <label for="monto-ingreso">Ingrese su salario o ingreso*</label>
                        <div class="campo-monto">
                            <span aria-hidden="true">$</span>
                            <input id="monto-ingreso" name="monto" type="number"
                                   min="0.01" step="0.01" placeholder="1.750.000" required>
                        </div>
                    </div>

                    <div class="campo-formulario">
                        <label for="frecuencia-ingreso">Ingrese cada cuánto va a registrar sus ingresos *</label>
                        <select id="frecuencia-ingreso" name="frecuencia" required>
                            <option value="Mensual">Mensual</option>
                            <option value="Quincenal">Quincenal</option>
                            <option value="Semanal">Semanal</option>
                        </select>
                    </div>
                </div>

                <div class="opciones-ingreso">
                    <label class="opcion-ingreso" for="recordatorios">
                        <span>¿Desea recibir recordatorios para ingresar sus ingresos?*</span>
                        <input id="recordatorios" name="recordatorios" type="checkbox" value="1" checked>
                    </label>

                    <label class="opcion-ingreso" for="activar-descripcion">
                        <span>¿Desea añadir una descripción?</span>
                        <input id="activar-descripcion" name="activar-descripcion" type="checkbox" value="1" checked>
                    </label>

                    <input class="campo-descripcion" id="descripcion-ingreso" name="descripcion"
                           type="text" placeholder="Descripción..." maxlength="200">
                </div>
            </form>
            </section>
        </section>
    </main>
</body>
</html>
