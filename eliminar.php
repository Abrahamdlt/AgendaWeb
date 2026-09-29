<?php
// Elimina un registro (POST con "id") y responde JSON:
//   200 { ok: true }
//   404 { ok: false, error: "..." }   el registro ya no existía
require __DIR__ . '/datos.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit;
}

$id = $_POST['id'] ?? '';
if (!is_string($id) || !ctype_digit($id)) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'Falta el id del registro.']);
    exit;
}

if (!eliminar_registro((int) $id)) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'error' => 'Ese registro ya no existe.']);
    exit;
}

echo json_encode(['ok' => true]);
