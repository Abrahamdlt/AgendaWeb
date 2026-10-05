<?php
// Funciones pequeñas que usan las páginas PHP (index.php y registrar.php).

// Escapa texto para mostrarlo en HTML sin riesgo (evita que se inyecte código)
function e($texto): string
{
    return htmlspecialchars((string) $texto, ENT_QUOTES, 'UTF-8');
}

// Un número entero que viene en la URL (?id=5), o null
function numero_de_url(string $nombre): ?int
{
    $valor = $_GET[$nombre] ?? null;
    return is_string($valor) && ctype_digit($valor) ? (int) $valor : null;
}
