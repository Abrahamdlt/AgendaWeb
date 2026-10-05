<?php
// Funciones pequeñas que usan las páginas PHP (index.php y registrar.php).

// Escapa texto para mostrarlo en HTML sin riesgo (evita que se inyecte código)
function e($texto): string
{
    return htmlspecialchars((string) $texto, ENT_QUOTES, 'UTF-8');
}

// Ruta de un archivo CSS o JS con su versión (?v=fecha de modificación).
// DOM Cloud le pide al navegador guardar CSS y JS 7 días; con la versión en la URL,
// cada vez que el archivo cambia el navegador descarga el nuevo en vez de usar el guardado.
function recurso(string $ruta): string
{
    $archivo = __DIR__ . '/' . $ruta;
    return is_file($archivo) ? $ruta . '?v=' . filemtime($archivo) : $ruta;
}

// Un número entero que viene en la URL (?id=5), o null
function numero_de_url(string $nombre): ?int
{
    $valor = $_GET[$nombre] ?? null;
    return is_string($valor) && ctype_digit($valor) ? (int) $valor : null;
}
