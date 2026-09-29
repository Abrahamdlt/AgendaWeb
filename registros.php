<?php
// Devuelve registros en JSON:
//   registros.php        todos (los usan el calendario y la lista "Próximos")
//   registros.php?id=5   solo ese registro (lo usa el formulario al editar), o 404 si no existe
require __DIR__ . '/datos.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    header('Allow: GET');
    exit;
}

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $registro = is_string($id) && ctype_digit($id) ? obtener_registro((int) $id) : null;
    if ($registro === null) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'error' => 'Ese registro no existe.']);
        exit;
    }
    echo json_encode($registro, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

echo json_encode(obtener_registros(), JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
