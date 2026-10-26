<?php
require __DIR__ . '/partes/sesion.php';   // sin sesión responde 401 en JSON
// Devuelve en JSON los registros del usuario. Lo usan el calendario y la lista "Próximos".
require __DIR__ . '/datos.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    header('Allow: GET');
    exit;
}

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
echo json_encode(obtener_registros($usuarioId), JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
