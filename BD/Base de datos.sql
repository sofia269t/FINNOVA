CREATE DATABASE IF NOT EXISTS finanzas;
USE finanzas;




CREATE TABLE IF NOT EXISTS usuario(
    idUsuario INT NOT NULL AUTO_INCREMENT,
    usuNombre VARCHAR(100) NOT NULL,
    usuApellido VARCHAR(100) NOT NULL,
    usuTelefono VARCHAR(100) NOT NULL,
    usuCorreo VARCHAR(100) NOT NULL,
    usuContraseña VARCHAR(100) NOT NULL,
    usuAdministrador INT NOT NULL,
    PRIMARY KEY (idUsuario)


);


CREATE TABLE IF NOT EXISTS administrador(
    idAdministrador INT NOT NULL AUTO_INCREMENT,
    adminNombre VARCHAR(100) NOT NULL,
    adminCorreo VARCHAR(100) NOT NULL,
    adminContraseña VARCHAR(100) NOT NULL,
    usuAdministrador INT NOT NULL,
    PRIMARY KEY (idAdministrador),
	FOREIGN KEY (usuAdministrador)
		REFERENCES usuario(idUsuario)
);


CREATE TABLE IF NOT EXISTS ingreso(
    idIngreso INT NOT NULL AUTO_INCREMENT,
    ingMonto INT NOT NULL,
    ingFecha DATE NOT NULL,
    ingDescripcion VARCHAR(100) NOT NULL,
    ingUsuario INT NOT NULL,
    PRIMARY KEY (idIngreso),

    FOREIGN KEY (ingUsuario)
    REFERENCES usuario(idUsuario)
);


CREATE TABLE IF NOT EXISTS ahorro(
    idAhorro INT NOT NULL AUTO_INCREMENT,
    ahorFecha DATE NOT NULL,
    ahorMonto INT NOT NULL,
    ahorUsuario INT NOT NULL,
    PRIMARY KEY (idAhorro),

    FOREIGN KEY (ahorUsuario)
    REFERENCES usuario(idUsuario)
);


CREATE TABLE IF NOT EXISTS gastos(
    idGasto INT NOT NULL AUTO_INCREMENT,
    gasFecha DATE NOT NULL,
    gasMonto INT NOT NULL,
    gasCategoria VARCHAR(100) NOT NULL,
    gasNombre VARCHAR(100) NOT NULL,
    gasUsuario INT NOT NULL,
    PRIMARY KEY (idGasto),

    FOREIGN KEY (gasUsuario)
    REFERENCES usuario(idUsuario)
);


CREATE TABLE IF NOT EXISTS meta(
    idMeta INT NOT NULL AUTO_INCREMENT,
    metFechaInicio DATE NOT NULL,
    metFechaFin DATE NOT NULL,
    metMontoObjetivo INT NOT NULL,
    metEstado VARCHAR(100) NOT NULL,
    metNombre VARCHAR(100) NOT NULL,
    metUsuario INT NOT NULL,
    PRIMARY KEY (idMeta),

    FOREIGN KEY (metUsuario)
    REFERENCES usuario(idUsuario)
);



-- Registros 
INSERT INTO usuario
(usuNombre, usuApellido, usuTelefono, usuCorreo, usuContraseña)
VALUES
('Johan', 'Morales', '3001234567', 'johan@gmail.com', '123456');
 
INSERT INTO administrador(adminNombre, adminCorreo, adminContraseña, usuAdministrador)
VALUES
('Administrador Principal', 'admin@finanzas.com', '123456', 1);


INSERT INTO ingreso(ingMonto, ingFecha, ingDescripcion, ingUsuario)
VALUES
(1500000, '2026-08-01', 'Salario', 1),
(300000, '2026-08-10', 'Trabajo extra', 1);


INSERT INTO ahorro(ahorFecha, ahorMonto, ahorUsuario)
VALUES
('2026-08-05', 200000, 1),
('2026-08-15', 150000, 1);


INSERT INTO gastos(gasFecha, gasMonto, gasCategoria, gasNombre, gasUsuario)
VALUES
('2026-08-03', 50000, 'Alimentación', 'Almuerzo', 1),
('2026-08-06', 30000, 'Transporte', 'Bus', 1),
('2026-08-10', 80000, 'Entretenimiento', 'Cine', 1);



INSERT INTO meta(metFechaInicio, metFechaFin, metMontoObjetivo, metEstado, metNombre, metUsuario)
VALUES
('2026-08-01', '2026-12-31', 2000000, 'En progreso', 'Comprar computador', 1);





-- Ver tablas
SELECT * FROM administrador;
SELECT * FROM usuario;
SELECT * FROM ingreso;
SELECT * FROM ahorro;
SELECT * FROM gastos;
SELECT * FROM meta;