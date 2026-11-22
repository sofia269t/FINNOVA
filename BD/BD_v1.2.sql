DROP DATABASE IF EXISTS Finnova;
CREATE DATABASE Finnova;
USE Finnova;

-- 1. CREACIÓN DE TABLAS


CREATE TABLE IF NOT EXISTS usuario(
    idUsuario INT NOT NULL AUTO_INCREMENT,
    usuNombre VARCHAR(100) NOT NULL,
    usuApellido VARCHAR(100) NOT NULL,
    usuTelefono VARCHAR(100) NOT NULL,
    usuCorreo VARCHAR(100) NOT NULL,
    usuContraseña VARCHAR(100) NOT NULL,
    usuAdministrador INT NOT NULL DEFAULT 0,
    PRIMARY KEY (idUsuario)
);

CREATE TABLE IF NOT EXISTS administrador(
    idAdministrador INT NOT NULL AUTO_INCREMENT,
    adminNombre VARCHAR(100) NOT NULL,
    adminCorreo VARCHAR(100) NOT NULL,
    adminContraseña VARCHAR(100) NOT NULL,
    usuAdministrador INT NOT NULL,
    PRIMARY KEY (idAdministrador),
    FOREIGN KEY (usuAdministrador) REFERENCES usuario(idUsuario)
);

CREATE TABLE IF NOT EXISTS ingreso(
    idIngreso INT NOT NULL AUTO_INCREMENT,
    ingMonto INT NOT NULL,
    ingFecha DATE NOT NULL,
    ingDescripcion VARCHAR(100) NOT NULL,
    ingUsuario INT NOT NULL,
    PRIMARY KEY (idIngreso),
    FOREIGN KEY (ingUsuario) REFERENCES usuario(idUsuario)
);

CREATE TABLE IF NOT EXISTS ahorro(
    idAhorro INT NOT NULL AUTO_INCREMENT,
    ahorFecha DATE NOT NULL,
    ahorMonto INT NOT NULL,
    ahorUsuario INT NOT NULL,
    PRIMARY KEY (idAhorro),
    FOREIGN KEY (ahorUsuario) REFERENCES usuario(idUsuario)
);

CREATE TABLE IF NOT EXISTS gastos(
    idGasto INT NOT NULL AUTO_INCREMENT,
    gasFecha DATE NOT NULL,
    gasMonto INT NOT NULL,
    gasCategoria VARCHAR(100) NOT NULL,
    gasNombre VARCHAR(100) NOT NULL,
    gasUsuario INT NOT NULL,
    PRIMARY KEY (idGasto),
    FOREIGN KEY (gasUsuario) REFERENCES usuario(idUsuario)
);

CREATE TABLE IF NOT EXISTS meta(
    idMeta INT NOT NULL AUTO_INCREMENT,
    metFechaInicio DATE NOT NULL,
    metFechaFin DATE NOT NULL,
    metMontoObjetivo DECIMAL(12,2) NOT NULL,
    metEstado VARCHAR(20) NOT NULL DEFAULT 'Pendiente',
    metNombre VARCHAR(100) NOT NULL,
    metImagen VARCHAR(255) NULL,
    metUsuario INT NOT NULL,

    PRIMARY KEY (idMeta),

    FOREIGN KEY (metUsuario)
        REFERENCES usuario(idUsuario)
);

CREATE TABLE IF NOT EXISTS aporte_meta(
    idAporte INT NOT NULL AUTO_INCREMENT,
    apmMonto DECIMAL(12,2) NOT NULL,
    apmFecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    apmDescripcion VARCHAR(150),
    apmMeta INT NOT NULL,

    PRIMARY KEY (idAporte),

    CONSTRAINT fk_aporte_meta
        FOREIGN KEY (apmMeta)
        REFERENCES meta(idMeta)
        ON DELETE RESTRICT
);



-- 2. REGISTROS INICIALES


INSERT INTO usuario (usuNombre, usuApellido, usuTelefono, usuCorreo, usuContraseña, usuAdministrador)
VALUES
('Johan', 'Morales', '3001234567', 'johan@gmail.com', '123456', 1),
('Laura', 'Gomez', '3012345678', 'laura@gmail.com', '123456', 0),
('Carlos', 'Rodriguez', '3023456789', 'carlos@gmail.com', '123456', 0),
('Ana', 'Martinez', '3034567890', 'ana@gmail.com', '123456', 0),
('Daniel', 'Perez', '3045678901', 'daniel@gmail.com', '123456', 0),
('Sofia', 'Hernandez', '3056789012', 'sofia@gmail.com', '123456', 0),
('Miguel', 'Torres', '3067890123', 'miguel@gmail.com', '123456', 0),
('Valentina', 'Castro', '3078901234', 'valentina@gmail.com', '123456', 0),
('Andres', 'Ramirez', '3089012345', 'andres@gmail.com', '123456', 0),
('Camila', 'Vargas', '3090123456', 'camila@gmail.com', '123456', 0);

INSERT INTO administrador(adminNombre, adminCorreo, adminContraseña, usuAdministrador)
VALUES
('Administrador Principal', 'admin@finanzas.com', '123456', 1);

INSERT INTO ingreso(ingMonto, ingFecha, ingDescripcion, ingUsuario)
VALUES
(1500000, '2026-08-01', 'Salario', 1),
(300000, '2026-08-10', 'Trabajo extra', 1),
(250000, '2026-08-12', 'Venta de producto', 1),
(180000, '2026-08-14', 'Freelance', 1),
(95000, '2026-08-16', 'Venta de articulo', 1),
(400000, '2026-08-18', 'Trabajo adicional', 1),
(120000, '2026-08-20', 'Comisión', 1),
(350000, '2026-08-22', 'Proyecto freelance', 1),
(75000, '2026-08-24', 'Devolución de dinero', 1),
(220000, '2026-08-25', 'Venta online', 1),
(150000, '2026-08-27', 'Trabajo extra', 1),
(500000, '2026-08-29', 'Pago adicional', 1);

INSERT INTO ahorro(ahorFecha, ahorMonto, ahorUsuario)
VALUES
('2026-08-05', 200000, 1),
('2026-08-15', 150000, 1),
('2026-08-07', 100000, 1),
('2026-08-09', 50000, 1),
('2026-08-12', 120000, 1),
('2026-08-14', 80000, 1),
('2026-08-17', 150000, 1),
('2026-08-19', 100000, 1),
('2026-08-21', 75000, 1),
('2026-08-23', 125000, 1),
('2026-08-26', 200000, 1),
('2026-08-30', 100000, 1);

INSERT INTO gastos(gasFecha, gasMonto, gasCategoria, gasNombre, gasUsuario)
VALUES
('2026-08-03', 50000, 'Alimentación', 'Almuerzo', 1),
('2026-08-06', 30000, 'Transporte', 'Bus', 1),
('2026-08-10', 80000, 'Entretenimiento', 'Cine', 1),
('2026-08-04', 25000, 'Alimentación', 'Desayuno', 1),
('2026-08-07', 45000, 'Transporte', 'Taxi', 1),
('2026-08-09', 60000, 'Servicios', 'Internet', 1),
('2026-08-11', 35000, 'Alimentación', 'Cena', 1),
('2026-08-13', 90000, 'Entretenimiento', 'Videojuego', 1),
('2026-08-16', 70000, 'Salud', 'Medicamentos', 1),
('2026-08-18', 55000, 'Transporte', 'Gasolina', 1),
('2026-08-21', 110000, 'Servicios', 'Electricidad', 1),
('2026-08-25', 85000, 'Alimentación', 'Mercado', 1),
('2026-08-28', 40000, 'Entretenimiento', 'Salida con amigos', 1);

INSERT INTO meta(metFechaInicio, metFechaFin, metMontoObjetivo, metEstado, metNombre, metUsuario)
VALUES
('2026-08-01', '2026-12-31', 2000000, 'En progreso', 'Comprar computador', 1),
('2026-08-01', '2026-10-31', 500000, 'En progreso', 'Comprar celular', 1),
('2026-08-05', '2026-11-30', 1000000, 'En progreso', 'Viaje', 1),
('2026-08-10', '2026-12-31', 3000000, 'En progreso', 'Comprar moto', 1),
('2026-08-15', '2027-01-31', 800000, 'En progreso', 'Comprar escritorio', 1),
('2026-08-20', '2027-02-28', 1500000, 'Pendiente', 'Curso profesional', 1),
('2026-08-25', '2027-03-31', 5000000, 'Pendiente', 'Fondo de emergencia', 1),
('2026-08-01', '2026-09-30', 300000, 'En progreso', 'Audífonos', 1),
('2026-08-10', '2026-12-15', 700000, 'En progreso', 'Ropa nueva', 1),
('2026-08-15', '2027-04-30', 2500000, 'Pendiente', 'Computador portátil', 1),
('2026-08-20', '2027-06-30', 10000000, 'Pendiente', 'Cuota inicial vivienda', 1);



-- 3. CREACIÓN DE PROCEDIMIENTOS ALMACENADOS


DROP PROCEDURE IF EXISTS sp_registrar_gasto;
DROP PROCEDURE IF EXISTS sp_registrar_ingreso;
DROP PROCEDURE IF EXISTS sp_gastos_por_categoria;
DROP PROCEDURE IF EXISTS sp_estado_metas_usuario;
DROP PROCEDURE IF EXISTS sp_crear_usuario;
DROP PROCEDURE IF EXISTS sp_actualizar_usuario;
DROP PROCEDURE IF EXISTS sp_actualizar_gasto;
DROP PROCEDURE IF EXISTS sp_crear_meta;
DROP PROCEDURE IF EXISTS sp_registrar_aporte_meta;
DROP PROCEDURE IF EXISTS sp_finalizar_meta;
DROP PROCEDURE IF EXISTS sp_posponer_meta;

DELIMITER //

CREATE PROCEDURE sp_registrar_aporte_meta(
    IN p_idMeta INT,
    IN p_idUsuario INT,
    IN p_monto DECIMAL(12,2),
    IN p_fecha DATETIME,
    IN p_descripcion VARCHAR(150)
)
BEGIN
    DECLARE v_objetivo DECIMAL(12,2);
    DECLARE v_acumulado DECIMAL(12,2);
    DECLARE v_estado VARCHAR(20);

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    START TRANSACTION;

    IF p_monto IS NULL OR p_monto <= 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'El aporte debe ser mayor que cero';
    END IF;

    IF NOT EXISTS (
        SELECT 1
        FROM meta
        WHERE idMeta = p_idMeta
          AND metUsuario = p_idUsuario
    ) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'La meta no existe o no pertenece al usuario';
    END IF;

    SELECT metMontoObjetivo, metEstado
    INTO v_objetivo, v_estado
    FROM meta
    WHERE idMeta = p_idMeta
    FOR UPDATE;

    IF v_estado IN ('Terminada', 'Cancelada', 'Vencida') THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'No se pueden realizar aportes a una meta terminada, cancelada o vencida';
    END IF;

    SELECT COALESCE(SUM(apmMonto), 0)
    INTO v_acumulado
    FROM aporte_meta
    WHERE apmMeta = p_idMeta;


    INSERT INTO aporte_meta(
        apmMonto,
        apmFecha,
        apmDescripcion,
        apmMeta
    )
    VALUES(
        p_monto,
        COALESCE(p_fecha, NOW()),
        p_descripcion,
        p_idMeta
    );

    COMMIT;

    SELECT
        p_idMeta AS idMeta,
        v_acumulado + p_monto AS total_ahorrado,
        v_objetivo - (v_acumulado + p_monto) AS restante,
        'Aporte registrado exitosamente' AS mensaje;

END //

DELIMITER ;


-- Finaliza una meta cuando el usuario confirma que desea darla por terminada.
DELIMITER //

CREATE PROCEDURE sp_finalizar_meta(
    IN p_idMeta INT,
    IN p_idUsuario INT
)
BEGIN
    DECLARE v_estado VARCHAR(20);

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    START TRANSACTION;

    SELECT metEstado
    INTO v_estado
    FROM meta
    WHERE idMeta = p_idMeta
      AND metUsuario = p_idUsuario
    FOR UPDATE;

    IF v_estado IS NULL THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'La meta no existe o no pertenece al usuario';
    ELSEIF v_estado NOT IN ('Por finalizar', 'En progreso') THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'La meta no se encuentra disponible para finalizar';
    ELSE
        UPDATE meta
        SET metEstado = 'Terminada'
        WHERE idMeta = p_idMeta
          AND metUsuario = p_idUsuario;
    END IF;

    COMMIT;

    SELECT p_idMeta AS idMeta,
           'Terminada' AS estado,
           'Meta finalizada exitosamente' AS mensaje;
END //

DELIMITER ;


-- Permite continuar ahorrando cuando el usuario decide no finalizar todavía.
DELIMITER //

CREATE PROCEDURE sp_posponer_meta(
    IN p_idMeta INT,
    IN p_idUsuario INT
)
BEGIN
    DECLARE v_estado VARCHAR(20);

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    START TRANSACTION;

    SELECT metEstado
    INTO v_estado
    FROM meta
    WHERE idMeta = p_idMeta
      AND metUsuario = p_idUsuario
    FOR UPDATE;

    IF v_estado IS NULL THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'La meta no existe o no pertenece al usuario';
    ELSEIF v_estado <> 'Por finalizar' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'La meta no está pendiente de confirmación';
    ELSE
        UPDATE meta
        SET metEstado = 'En progreso'
        WHERE idMeta = p_idMeta
          AND metUsuario = p_idUsuario;
    END IF;

    COMMIT;

    SELECT p_idMeta AS idMeta,
           'En progreso' AS estado,
           'La meta continúa en progreso' AS mensaje;
END //

DELIMITER ;


DELIMITER //

CREATE PROCEDURE sp_registrar_gasto(
    IN p_idUsuario INT,
    IN p_monto DECIMAL(12,2),
    IN p_fecha DATE,
    IN p_categoria VARCHAR(100),
    IN p_nombre VARCHAR(100)
)
BEGIN
    INSERT INTO gastos (gasMonto, gasFecha, gasCategoria, gasNombre, gasUsuario)
    VALUES (p_monto, p_fecha, p_categoria, p_nombre, p_idUsuario);
END //

CREATE PROCEDURE sp_registrar_ingreso(
    IN p_idUsuario INT,
    IN p_monto DECIMAL(12,2),
    IN p_fecha DATE,
    IN p_descripcion VARCHAR(100)
)
BEGIN
    INSERT INTO ingreso (ingMonto, ingFecha, ingDescripcion, ingUsuario)
    VALUES (p_monto, p_fecha, p_descripcion, p_idUsuario);
END //

CREATE PROCEDURE sp_gastos_por_categoria(
    IN p_idUsuario INT,
    IN p_fechaInicio DATE,
    IN p_fechaFin DATE
)
BEGIN
    SELECT 
        gasCategoria AS categoria,
        COUNT(idGasto) AS cantidad_transacciones,
        SUM(gasMonto) AS total_gastado
    FROM gastos
    WHERE gasUsuario = p_idUsuario 
      AND gasFecha BETWEEN p_fechaInicio AND p_fechaFin
    GROUP BY gasCategoria
    ORDER BY total_gastado DESC;
END //

CREATE PROCEDURE sp_crear_usuario(
    IN p_nombre VARCHAR(100),
    IN p_apellido VARCHAR(100),
    IN p_telefono VARCHAR(100),
    IN p_correo VARCHAR(100),
    IN p_contraseña VARCHAR(100),
    IN p_es_administrador INT
)
BEGIN
    IF EXISTS (SELECT 1 FROM usuario WHERE usuCorreo = p_correo) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'El correo electrónico ya se encuentra registrado.';
    ELSE
        INSERT INTO usuario (usuNombre, usuApellido, usuTelefono, usuCorreo, usuContraseña, usuAdministrador)
        VALUES (p_nombre, p_apellido, p_telefono, p_correo, p_contraseña, p_es_administrador);

        SELECT LAST_INSERT_ID() AS idUsuario, 'Usuario creado exitosamente' AS mensaje;
    END IF;
END //

CREATE PROCEDURE sp_actualizar_usuario(
    IN p_idUsuario INT,
    IN p_nombre VARCHAR(100),
    IN p_apellido VARCHAR(100),
    IN p_telefono VARCHAR(100),
    IN p_correo VARCHAR(100),
    IN p_contraseña VARCHAR(100),
    IN p_es_administrador INT
)
BEGIN
    IF NOT EXISTS (SELECT 1 FROM usuario WHERE idUsuario = p_idUsuario) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Error: El usuario especificado no existe.';
    ELSEIF EXISTS (SELECT 1 FROM usuario WHERE usuCorreo = p_correo AND idUsuario <> p_idUsuario) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Error: El correo electrónico ya pertenece a otro usuario.';
    ELSE
        UPDATE usuario
        SET 
            usuNombre = p_nombre,
            usuApellido = p_apellido,
            usuTelefono = p_telefono,
            usuCorreo = p_correo,
            usuContraseña = p_contraseña,
            usuAdministrador = p_es_administrador
        WHERE idUsuario = p_idUsuario;

        SELECT p_idUsuario AS idUsuario, 'Usuario actualizado exitosamente' AS mensaje;
    END IF;
END //



CREATE PROCEDURE sp_actualizar_gasto(
    IN p_idGasto INT,
    IN p_monto INT,
    IN p_fecha DATE,
    IN p_categoria VARCHAR(100),
    IN p_nombre VARCHAR(100),
    IN p_idUsuario INT
)
BEGIN
    IF NOT EXISTS (SELECT 1 FROM gastos WHERE idGasto = p_idGasto) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Error: El gasto especificado no existe.';
    ELSEIF NOT EXISTS (SELECT 1 FROM gastos WHERE idGasto = p_idGasto AND gasUsuario = p_idUsuario) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Error: El gasto no pertenece al usuario especificado.';
    ELSE
        UPDATE gastos
        SET 
            gasMonto = p_monto,
            gasFecha = p_fecha,
            gasCategoria = p_categoria,
            gasNombre = p_nombre
        WHERE idGasto = p_idGasto AND gasUsuario = p_idUsuario;

        SELECT p_idGasto AS idGasto, 'Gasto actualizado exitosamente' AS mensaje;
    END IF;
END //

DROP PROCEDURE IF EXISTS sp_crear_meta;

DELIMITER //

CREATE PROCEDURE sp_crear_meta(
    IN p_fechaInicio DATE,
    IN p_fechaFin DATE,
    IN p_montoObjetivo DECIMAL(12,2),
    IN p_estado VARCHAR(20),
    IN p_nombre VARCHAR(100),
    IN p_imagen VARCHAR(255),
    IN p_usuario INT
)
BEGIN

    INSERT INTO meta(
        metFechaInicio,
        metFechaFin,
        metMontoObjetivo,
        metEstado,
        metNombre,
        metImagen,
        metUsuario
    )
    VALUES(
        p_fechaInicio,
        p_fechaFin,
        p_montoObjetivo,
        p_estado,
        p_nombre,
        p_imagen,
        p_usuario
    );

    SELECT
        LAST_INSERT_ID() AS idMeta,
        'Meta creada exitosamente' AS mensaje;

END //

DELIMITER ;

DROP PROCEDURE IF EXISTS sp_progreso_meta;

DELIMITER //

CREATE PROCEDURE sp_progreso_meta(
    IN p_idMeta INT,
    IN p_idUsuario INT
)
BEGIN

    SELECT
        m.idMeta,
        m.metNombre,
        m.metMontoObjetivo,
        m.metImagen,
        m.metEstado,

        COALESCE(SUM(a.apmMonto), 0) AS total_ahorrado,

        GREATEST(
            m.metMontoObjetivo - COALESCE(SUM(a.apmMonto), 0),
            0
        ) AS dinero_restante,

        ROUND(
            LEAST(
                COALESCE(SUM(a.apmMonto), 0) /
                NULLIF(m.metMontoObjetivo, 0) * 100,
                100
            ),
            2
        ) AS porcentaje_progreso

    FROM meta m

    LEFT JOIN aporte_meta a
        ON m.idMeta = a.apmMeta

    WHERE m.idMeta = p_idMeta
      AND m.metUsuario = p_idUsuario

    GROUP BY
        m.idMeta,
        m.metNombre,
        m.metMontoObjetivo,
        m.metImagen,
        m.metEstado;

END //

DELIMITER ;

-- 4. TRIGGERS


DELIMITER $$

CREATE TRIGGER validar_ingreso_insert
BEFORE INSERT ON ingreso
FOR EACH ROW
BEGIN
    IF NEW.ingMonto <= 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'El monto del ingreso debe ser mayor que cero';
    END IF;
END$$

CREATE TRIGGER validar_ingreso_update
BEFORE UPDATE ON ingreso
FOR EACH ROW
BEGIN
    IF NEW.ingMonto <= 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'El monto del ingreso debe ser mayor que cero';
    END IF;
END$$

CREATE TRIGGER validar_ahorro_insert
BEFORE INSERT ON ahorro
FOR EACH ROW
BEGIN
    IF NEW.ahorMonto <= 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'El monto del ahorro debe ser mayor que cero';
    END IF;
END$$

CREATE TRIGGER validar_ahorro_update
BEFORE UPDATE ON ahorro
FOR EACH ROW
BEGIN
    IF NEW.ahorMonto <= 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'El monto del ahorro debe ser mayor que cero';
    END IF;
END$$

CREATE TRIGGER validar_gasto_insert
BEFORE INSERT ON gastos
FOR EACH ROW
BEGIN
    IF NEW.gasMonto <= 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'El monto del gasto debe ser mayor que cero';
    END IF;
END$$

CREATE TRIGGER validar_gasto_update
BEFORE UPDATE ON gastos
FOR EACH ROW
BEGIN
    IF NEW.gasMonto <= 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'El monto del gasto debe ser mayor que cero';
    END IF;
END$$

CREATE TRIGGER validar_meta_insert
BEFORE INSERT ON meta
FOR EACH ROW
BEGIN
    IF NEW.metMontoObjetivo <= 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'El monto de la meta debe ser mayor que cero';
    END IF;

    IF NEW.metFechaFin <= NEW.metFechaInicio THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'La fecha final debe ser posterior a la fecha inicial';
    END IF;
END$$

CREATE TRIGGER validar_meta_update
BEFORE UPDATE ON meta
FOR EACH ROW
BEGIN
    IF NEW.metMontoObjetivo <= 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'El monto de la meta debe ser mayor que cero';
    END IF;

    IF NEW.metFechaFin <= NEW.metFechaInicio THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'La fecha final debe ser posterior a la fecha inicial';
    END IF;
END$$

CREATE TRIGGER actualizar_estado_meta
BEFORE INSERT ON meta
FOR EACH ROW
BEGIN
    IF NEW.metFechaFin < CURDATE() THEN
        SET NEW.metEstado = 'Vencida';
    ELSEIF NEW.metEstado IS NULL OR NEW.metEstado = '' THEN
        SET NEW.metEstado = 'Pendiente';
    END IF;
END$$

CREATE TRIGGER validar_correo_usuario
BEFORE INSERT ON usuario
FOR EACH ROW
BEGIN
    IF EXISTS (SELECT 1 FROM usuario WHERE usuCorreo = NEW.usuCorreo) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'El correo del usuario ya existe';
    END IF;
END$$

CREATE TRIGGER validar_telefono_usuario
BEFORE INSERT ON usuario
FOR EACH ROW
BEGIN
    IF EXISTS (SELECT 1 FROM usuario WHERE usuTelefono = NEW.usuTelefono) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'El telefono del usuario ya existe';
    END IF;
END$$

DELIMITER ;



-- 5. FUNCIONES


DELIMITER $$

CREATE FUNCTION total_ingresos(p_usuario INT)
RETURNS INT
DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE total INT;
    SELECT COALESCE(SUM(ingMonto), 0) INTO total FROM ingreso WHERE ingUsuario = p_usuario;
    RETURN total;
END$$

CREATE FUNCTION total_gastos(p_usuario INT)
RETURNS INT
DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE total INT;
    SELECT COALESCE(SUM(gasMonto), 0) INTO total FROM gastos WHERE gasUsuario = p_usuario;
    RETURN total;
END$$

CREATE FUNCTION total_ahorros(p_usuario INT)
RETURNS INT
DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE total INT;
    SELECT COALESCE(SUM(ahorMonto), 0) INTO total FROM ahorro WHERE ahorUsuario = p_usuario;
    RETURN total;
END$$

CREATE FUNCTION saldo_disponible(p_usuario INT)
RETURNS INT
DETERMINISTIC
READS SQL DATA
BEGIN
    RETURN total_ingresos(p_usuario) - total_gastos(p_usuario) - total_ahorros(p_usuario);
END$$

CREATE FUNCTION cantidad_metas(p_usuario INT)
RETURNS INT
DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE cantidad INT;
    SELECT COUNT(*) INTO cantidad FROM meta WHERE metUsuario = p_usuario;
    RETURN cantidad;
END$$

CREATE FUNCTION total_objetivos(p_usuario INT)
RETURNS INT
DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE total INT;
    SELECT COALESCE(SUM(metMontoObjetivo), 0) INTO total FROM meta WHERE metUsuario = p_usuario;
    RETURN total;
END$$

CREATE FUNCTION cantidad_gastos(p_usuario INT)
RETURNS INT
DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE cantidad INT;
    SELECT COUNT(*) INTO cantidad FROM gastos WHERE gasUsuario = p_usuario;
    RETURN cantidad;
END$$

CREATE FUNCTION cantidad_ingresos(p_usuario INT)
RETURNS INT
DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE cantidad INT;
    SELECT COUNT(*) INTO cantidad FROM ingreso WHERE ingUsuario = p_usuario;
    RETURN cantidad;
END$$

CREATE FUNCTION promedio_gastos(p_usuario INT)
RETURNS DECIMAL(15,2)
DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE promedio DECIMAL(15,2);
    SELECT COALESCE(AVG(gasMonto), 0) INTO promedio FROM gastos WHERE gasUsuario = p_usuario;
    RETURN promedio;
END$$

CREATE FUNCTION porcentaje_ahorro(p_usuario INT)
RETURNS DECIMAL(10,2)
DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE ingresos INT;
    DECLARE ahorros INT;

    SET ingresos = total_ingresos(p_usuario);
    SET ahorros = total_ahorros(p_usuario);

    IF ingresos = 0 THEN
        RETURN 0;
    END IF;

    RETURN (ahorros * 100) / ingresos;
END$$

DELIMITER ;

DROP TRIGGER IF EXISTS validar_aporte_meta_insert;

DELIMITER $$

CREATE TRIGGER validar_aporte_meta_insert
BEFORE INSERT ON aporte_meta
FOR EACH ROW
BEGIN
    IF NEW.apmMonto IS NULL OR NEW.apmMonto <= 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'El aporte debe ser mayor que cero';
    END IF;
END$$

DELIMITER ;

DROP TRIGGER IF EXISTS actualizar_estado_meta_aporte;

DELIMITER $$

CREATE TRIGGER actualizar_estado_meta_aporte
AFTER INSERT ON aporte_meta
FOR EACH ROW
BEGIN

    UPDATE meta
    SET metEstado =
        CASE
            WHEN (
                SELECT COALESCE(SUM(apmMonto), 0)
                FROM aporte_meta
                WHERE apmMeta = NEW.apmMeta
            ) >= metMontoObjetivo
            THEN 'Por finalizar'

            ELSE 'En progreso'
        END
    WHERE idMeta = NEW.apmMeta
      AND metEstado NOT IN ('Terminada', 'Cancelada', 'Vencida');

END$$

DELIMITER ;

-- 6. PRUEBAS RÁPIDAS Y CONSULTAS


CALL sp_gastos_por_categoria(1, '2026-08-01', '2026-08-31');
CALL Finnova.sp_actualizar_gasto(2, 5000, '2026-10-26', 'Alimentación', 'arroz con pollo de la esquina', 1);
CALL Finnova.sp_actualizar_usuario(5, 'Juan miguel', 'Rodriguez Perez', '13543523', 'juan23@gmail.com', 'arroz con pollo', 0);
CALL Finnova.sp_crear_meta('2026-10-07','2027-10-07', 1000000,'En progreso', 'el viaje soñado', NULL, 5);
CALL Finnova.sp_crear_usuario('Jose Antonio ', 'Rodriguez Rodriguez', '32455465', 'josefito@gmail.com', 'pan con arroz', 0);
CALL Finnova.sp_registrar_gasto(5, 100000, '2026-09-26', 'Varios', 'cumpleaños');
CALL Finnova.sp_registrar_ingreso(1, 1750000, '2026-09-25', 'Salario');

-- Ejemplos para probar la decisión de finalización (ejecutar manualmente):
-- Si el usuario confirma:
-- CALL sp_finalizar_meta(1, 1);
--
-- Si el usuario decide seguir ahorrando:
-- CALL sp_posponer_meta(1, 1);

-- Ver Triggers
SHOW TRIGGERS FROM Finnova;

-- Consultas de prueba (Joins)
SELECT 
    usuario.idUsuario, usuario.usuNombre, usuario.usuApellido, 
    ingreso.ingMonto, ingreso.ingFecha, ingreso.ingDescripcion
FROM usuario
INNER JOIN ingreso ON usuario.idUsuario = ingreso.ingUsuario;


-- Metas "En Progreso" con los Datos de Contacto del Usuario

SELECT 
    u.usuNombre,
    u.usuApellido,
    u.usuTelefono,
    m.metNombre AS Meta,
    m.metMontoObjetivo,
    m.metFechaInicio,
    m.metFechaFin,
    m.metEstado
FROM usuario u
INNER JOIN meta m ON u.idUsuario = m.metUsuario
WHERE m.metEstado = 'En progreso'
ORDER BY m.metFechaFin ASC;

-- Resumen Consolidado por Usuario

SELECT 
    u.idUsuario,
    u.usuNombre,
    u.usuApellido,
    COALESCE(SUM(DISTINCT i.ingMonto), 0) AS TotalIngresos,
    COALESCE(SUM(DISTINCT g.gasMonto), 0) AS TotalGastos,
    COALESCE(SUM(DISTINCT a.ahorMonto), 0) AS TotalAhorros
FROM usuario u
LEFT JOIN ingreso i ON u.idUsuario = i.ingUsuario
LEFT JOIN gastos g ON u.idUsuario = g.gasUsuario
LEFT JOIN ahorro a ON u.idUsuario = a.ahorUsuario
GROUP BY u.idUsuario, u.usuNombre, u.usuApellido;

-- Probar Funciones
SELECT total_ingresos(1) AS Total_Ingresos;
SELECT saldo_disponible(1) AS Saldo_Disponible;
SELECT porcentaje_ahorro(1) AS Porcentaje_Ahorro;
