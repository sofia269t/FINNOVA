<?php
function obtenerRangoReporte(array $parametros): array
{
    $rango = [];
    foreach (["desde", "hasta"] as $campo) {
        $fecha = $parametros[$campo] ?? "";
        if (!is_string($fecha)) {
            throw new InvalidArgumentException("El rango de fechas no es válido.");
        }

        $fecha = trim($fecha);
        if ($fecha === "") {
            $rango[$campo] = null;
            continue;
        }

        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/D', $fecha, $partes) !== 1 ||
            !checkdate((int) $partes[2], (int) $partes[3], (int) $partes[1])) {
            throw new InvalidArgumentException("Ingresa fechas válidas.");
        }
        $rango[$campo] = $fecha;
    }

    if ($rango["desde"] !== null && $rango["hasta"] !== null &&
        $rango["desde"] > $rango["hasta"]) {
        throw new InvalidArgumentException("La fecha inicial no puede ser posterior a la fecha final.");
    }

    return $rango;
}

function obtenerFiltrosMetaReporte(array $parametros): array
{
    $estado = $parametros["estado"] ?? "";
    if (!is_string($estado)) {
        throw new InvalidArgumentException("El estado de la meta no es válido.");
    }

    $estado = trim($estado);
    $estadosPermitidos = [
        "pendiente" => "Pendiente",
        "en progreso" => "En progreso",
        "por finalizar" => "Por finalizar",
        "terminada" => "Terminada",
        "finalizada" => "Terminada",
        "cancelada" => "Cancelada",
        "vencida" => "Vencida"
    ];
    $estadoNormalizado = strtolower($estado);
    if ($estado !== "" && !isset($estadosPermitidos[$estadoNormalizado])) {
        throw new InvalidArgumentException("El estado de la meta no es válido.");
    }

    $metaId = $parametros["meta_id"] ?? "";
    if (!is_string($metaId)) {
        throw new InvalidArgumentException("La meta seleccionada no es válida.");
    }
    $metaId = trim($metaId);
    if ($metaId !== "" && (preg_match('/^\d+$/D', $metaId) !== 1 || (int) $metaId < 1)) {
        throw new InvalidArgumentException("La meta seleccionada no es válida.");
    }

    return [
        "estado" => $estado === "" ? null : $estadosPermitidos[$estadoNormalizado],
        "meta_id" => $metaId === "" ? null : (int) $metaId
    ];
}

function obtenerMetasReporte(mysqli $conexion, int $usuarioId): array
{
    $stmt = $conexion->prepare(
        "SELECT idMeta, metNombre, metEstado
         FROM meta
         WHERE metUsuario = ?
         ORDER BY metNombre ASC"
    );
    if (!$stmt) {
        error_log("No fue posible preparar la lista de metas para reportes: " . $conexion->error);
        throw new RuntimeException("No fue posible cargar las metas.");
    }

    $stmt->bind_param("i", $usuarioId);
    if (!$stmt->execute()) {
        error_log("No fue posible consultar las metas para reportes: " . $stmt->error);
        $stmt->close();
        throw new RuntimeException("No fue posible cargar las metas.");
    }

    $metas = [];
    $resultado = $stmt->get_result();
    while ($meta = $resultado->fetch_assoc()) {
        $metas[] = $meta;
    }
    $stmt->close();

    return $metas;
}

function obtenerRegistrosReporte(
    mysqli $conexion,
    int $usuarioId,
    ?string $desde,
    ?string $hasta,
    string $tipo = "todos",
    ?string $estadoMeta = null,
    ?int $metaId = null
): array {
    $consultaAportes = "SELECT ap.apmFecha AS fecha, 'Aporte a meta' AS tipo,
                               CONCAT('Meta #', m.idMeta, ': ', m.metNombre,
                                      CASE WHEN ap.apmDescripcion IS NULL OR ap.apmDescripcion = ''
                                           THEN '' ELSE CONCAT(' - ', ap.apmDescripcion) END) AS detalle,
                               ap.apmMonto AS monto, m.metEstado AS estado
                        FROM aporte_meta ap
                        INNER JOIN meta m ON m.idMeta = ap.apmMeta
                        WHERE m.metUsuario = ?
                          AND (? IS NULL OR ap.apmFecha >= ?)
                          AND (? IS NULL OR ap.apmFecha < DATE_ADD(?, INTERVAL 1 DAY))";
    $filtroEstadoMeta = "
                          AND (? IS NULL OR
                               CASE
                                   WHEN LOWER(TRIM(m.metEstado)) IN ('terminada', 'finalizada') THEN 'terminada'
                                   ELSE LOWER(TRIM(m.metEstado))
                               END = LOWER(?))
                          AND (? IS NULL OR m.idMeta = ?)";
    $consultaAportesFiltrados = $consultaAportes . $filtroEstadoMeta;
    $consultaMetas = "SELECT ap.apmFecha AS fecha,
                             CASE WHEN ap.idAporte IS NULL THEN 'Meta sin aporte' ELSE 'Aporte a meta' END AS tipo,
                             CASE WHEN ap.idAporte IS NULL
                                  THEN CONCAT('Meta #', m.idMeta, ': ', m.metNombre, ' - Sin aportes en el periodo')
                                  ELSE CONCAT('Meta #', m.idMeta, ': ', m.metNombre,
                                              CASE WHEN ap.apmDescripcion IS NULL OR ap.apmDescripcion = ''
                                                   THEN '' ELSE CONCAT(' - ', ap.apmDescripcion) END)
                             END AS detalle,
                             COALESCE(ap.apmMonto, 0) AS monto, m.metEstado AS estado
                      FROM meta m
                      LEFT JOIN aporte_meta ap
                        ON ap.apmMeta = m.idMeta
                       AND (? IS NULL OR ap.apmFecha >= ?)
                       AND (? IS NULL OR ap.apmFecha < DATE_ADD(?, INTERVAL 1 DAY))
                      WHERE m.metUsuario = ?
                        $filtroEstadoMeta";
    $consultas = [
        "ingresos" => "SELECT i.ingFecha AS fecha, 'Ingreso' AS tipo, i.ingDescripcion AS detalle,
                              i.ingMonto AS monto, '' AS estado
                       FROM ingreso i
                       WHERE i.ingUsuario = ?
                         AND (? IS NULL OR i.ingFecha >= ?)
                         AND (? IS NULL OR i.ingFecha < DATE_ADD(?, INTERVAL 1 DAY))",
        "gastos" => "SELECT g.gasFecha AS fecha, 'Gasto' AS tipo,
                            CONCAT(g.gasCategoria, ' - ', g.gasNombre) AS detalle,
                            g.gasMonto AS monto, '' AS estado
                     FROM gastos g
                     WHERE g.gasUsuario = ?
                       AND (? IS NULL OR g.gasFecha >= ?)
                       AND (? IS NULL OR g.gasFecha < DATE_ADD(?, INTERVAL 1 DAY))",
        "ahorros" => "SELECT a.ahorFecha AS fecha, 'Ahorro' AS tipo, 'Ahorro registrado' AS detalle,
                             a.ahorMonto AS monto, '' AS estado
                      FROM ahorro a
                      WHERE a.ahorUsuario = ?
                        AND (? IS NULL OR a.ahorFecha >= ?)
                        AND (? IS NULL OR a.ahorFecha < DATE_ADD(?, INTERVAL 1 DAY))",
        "aportes" => $consultaAportesFiltrados,
        "metas" => $consultaMetas
    ];

    if ($tipo !== "todos" && !isset($consultas[$tipo])) {
        throw new InvalidArgumentException("El tipo de reporte no es válido.");
    }

    $tiposAConsultar = $tipo === "todos"
        ? ["ingresos", "gastos", "ahorros", "aportes"]
        : [$tipo];
    $registros = [];
    foreach ($tiposAConsultar as $tipoConsulta) {
        $consulta = $consultas[$tipoConsulta];
        $stmt = $conexion->prepare($consulta);
        if (!$stmt) {
            error_log("No fue posible preparar la consulta de reportes: " . $conexion->error);
            throw new RuntimeException("No fue posible generar el reporte.");
        }

        if ($tipoConsulta === "metas") {
            $stmt->bind_param(
                "ssssissii",
                $desde,
                $desde,
                $hasta,
                $hasta,
                $usuarioId,
                $estadoMeta,
                $estadoMeta,
                $metaId,
                $metaId
            );
        } elseif ($tipoConsulta === "aportes") {
            $stmt->bind_param(
                "issssssii",
                $usuarioId,
                $desde,
                $desde,
                $hasta,
                $hasta,
                $estadoMeta,
                $estadoMeta,
                $metaId,
                $metaId
            );
        } else {
            $stmt->bind_param("issss", $usuarioId, $desde, $desde, $hasta, $hasta);
        }
        if (!$stmt->execute()) {
            error_log("No fue posible consultar los registros del reporte: " . $stmt->error);
            $stmt->close();
            throw new RuntimeException("No fue posible generar el reporte.");
        }

        $resultado = $stmt->get_result();
        while ($fila = $resultado->fetch_assoc()) {
            $registros[] = $fila;
        }
        $stmt->close();
    }

    usort($registros, static function (array $a, array $b): int {
        return strcmp((string) $b["fecha"], (string) $a["fecha"]);
    });

    return $registros;
}

function protegerValorCsv(string $valor): string
{
    if (preg_match('/^[\t\r ]*[=+\-@]/', $valor) === 1) {
        return "'" . $valor;
    }
    return $valor;
}
