<?php
// Plantilla de conexion.php (el archivo real no se sube a GitHub: está en .gitignore).
// Cópiala como conexion.php y pon tus datos de MySQL.
try {
    $conexion = new mysqli("localhost", "TU_USUARIO", "TU_CONTRASEÑA", "agenda");
} catch (mysqli_sql_exception $e) {
    die("Error de conexión");
}
$conexion->set_charset("utf8mb4");
