CREATE DATABASE IF NOT EXISTS quequetecnotech
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_general_ci;

USE quequetecnotech;

CREATE TABLE IF NOT EXISTS personal (
    id_personal INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    nro_legajo VARCHAR(20) NOT NULL,
    nombre VARCHAR(50) NOT NULL,
    apellido VARCHAR(50) NOT NULL,
    dni VARCHAR(15) NOT NULL,
    fecha_nacimiento DATE NULL,
    domicilio VARCHAR(100) NULL,
    telefono VARCHAR(20) NULL,
    email VARCHAR(100) NULL,
    cargo VARCHAR(50) NULL,
    area VARCHAR(50) NULL,
    fecha_ingreso DATE NULL,
    estado VARCHAR(20) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS empleados (
    id_empleado INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    id_personal INT NOT NULL,
    nombre_usuario VARCHAR(50) NOT NULL,
    contrasenia VARCHAR(255) NOT NULL,
    rol VARCHAR(30) NULL,
    estado VARCHAR(20) NULL,
    ultimo_acceso DATETIME NULL,
    INDEX (id_personal),
    CONSTRAINT empleados_ibfk_1
        FOREIGN KEY (id_personal) REFERENCES personal (id_personal)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS registro_acceso (
    id_registro INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    id_empleado INT NOT NULL,
    fecha_hora DATETIME NULL,
    tipo VARCHAR(20) NULL,
    INDEX (id_empleado),
    CONSTRAINT registro_acceso_ibfk_1
        FOREIGN KEY (id_empleado) REFERENCES empleados (id_empleado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS tareas (
    id_tarea INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    cliente VARCHAR(100) NULL,
    titulo VARCHAR(100) NOT NULL,
    descripcion TEXT NULL,
    tipo VARCHAR(20) NULL,
    fecha_solicitud DATE NULL,
    fecha_vencimiento DATE NULL,
    estado VARCHAR(20) NULL,
    prioridad VARCHAR(20) NULL,
    id_creador INT NULL,
    INDEX (id_creador),
    CONSTRAINT tareas_ibfk_1
        FOREIGN KEY (id_creador) REFERENCES empleados (id_empleado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS asignacion_tarea (
    id_asignacion INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    id_tarea INT NOT NULL,
    id_personal INT NOT NULL,
    fecha_asignacion DATE NULL,
    INDEX (id_tarea),
    INDEX (id_personal),
    CONSTRAINT asignacion_tarea_ibfk_1
        FOREIGN KEY (id_tarea) REFERENCES tareas (id_tarea),
    CONSTRAINT asignacion_tarea_ibfk_2
        FOREIGN KEY (id_personal) REFERENCES personal (id_personal)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
