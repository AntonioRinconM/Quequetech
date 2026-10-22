<?php
declare(strict_types=1);

$servidor = 'localhost';
$puerto = 3306;
$base_datos = 'quequetech';
$usuario = 'root';
$contrasena = '';

$conexion = new PDO(
    "mysql:host={$servidor};port={$puerto};dbname={$base_datos};charset=utf8mb4",
    $usuario,
    $contrasena,
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]
);
