<?php
// Elimina un registro (POST con "id").
// - Desde el formulario "Eliminar" de una tarjeta (index.php): redirige a la lista
//   con el aviso (patrón PRG): index.php?ok=eliminado
// - Desde JavaScript (calendario, "Próximos"; envía Accept: application/json): responde JSON
//     200 { ok: true }      404 { ok: false, error: "..." }
require __DIR__ . '/datos.php';

$quiere_json = str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit;
}

function responder(int $codigo, array $json, string $redireccion, bool $quiere_json): void
{
    if ($quiere_json) {
        http_response_code($codigo);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($json);
    } else {
        header('Location: ' . $redireccion, true, 303);   // Redirect → GET (PRG)
    }
    exit;
}

$id = $_POST['id'] ?? '';
if (!is_string($id) || !ctype_digit($id)) {
    responder(422, ['ok' => false, 'error' => 'Falta el id del registro.'], 'index.php?error=noexiste', $quiere_json);
}

if (!eliminar_registro((int) $id)) {
    responder(404, ['ok' => false, 'error' => 'Ese registro ya no existe.'], 'index.php?error=noexiste', $quiere_json);
}

responder(200, ['ok' => true], 'index.php?ok=eliminado', $quiere_json);
