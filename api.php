<?php
// =========================================
// AgendaWeb · API: mis eventos en JSON (Práctica 10)
// =========================================
// index.php responde una PÁGINA para una persona; este archivo responde DATOS para otro programa.
// La consulta es la del buscador de P9; solo cambia el empaque: json_encode en lugar de tarjetas.
//
//   api.php                    todos mis eventos
//   api.php?q=examen           los que dicen "examen" en el título o las notas
//   api.php?categoria=3        los de esa categoría (también acepta la clave: ?categoria=escuela)
//
// Respuestas: 200 datos · 401 sin sesión · 405 método distinto de GET · 500 error de la base

session_start();

// 1 · Lo PRIMERO que sale: la etiqueta "esto es JSON" (antes de cualquier echo)
header('Content-Type: application/json; charset=utf-8');

// Una sola salida para todas las respuestas: código + JSON + fin
function responder(int $codigo, array $datos): void
{
    http_response_code($codigo);
    echo json_encode($datos, JSON_UNESCAPED_UNICODE);
    exit;
}

// 2 · El guardia de la API: sin sesión no hay datos. 401 = "no sé quién eres" (no se redirige a login.php:
//     quien pidió JSON recibiría una página HTML)
if (!isset($_SESSION['uid'])) {
    responder(401, ['error' => 'Inicia sesión para consultar tus eventos.']);
}
$usuarioId = (int) $_SESSION['uid'];

// Esta API solo CONSULTA: cualquier otro método se rechaza
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('Allow: GET');
    responder(405, ['error' => 'Método no permitido. Usa GET.']);
}

require __DIR__ . '/datos.php';        // conexion.php ($conexion)
require __DIR__ . '/validacion.php';   // texto()

// Si una consulta falla, la respuesta sigue siendo JSON (no un texto ni un Warning)
set_exception_handler(function (Throwable $e): void {
    error_log('AgendaWeb API: ' . $e->getMessage());
    responder(500, ['error' => 'No se pudieron cargar los eventos.']);
});

// 5 · Filtros opcionales: los mismos del buscador de P9
$q = mb_substr(texto($_GET, 'q'), 0, 100);
$categoria = texto($_GET, 'categoria');

// 3 · Nunca SELECT *: solo las columnas que se entregan. "Solo mis eventos" va SIEMPRE.
$condiciones = ['e.usuario_id = ?'];
$tipos = 'i';
$valores = [$usuarioId];

if ($q !== '') {
    // % y _ escritos por la persona se buscan como letras, no como comodines
    $patron = '%' . addcslashes($q, '%_\\') . '%';
    $condiciones[] = '(e.titulo LIKE ? OR e.notas LIKE ?)';
    $tipos .= 'ss';
    array_push($valores, $patron, $patron);
}
if (ctype_digit($categoria)) {                           // ?categoria=3 (el id); 0 = todas
    $categoria = (int) $categoria;
    if ($categoria > 0) {
        $condiciones[] = 'e.categoria_id = ?';
        $tipos .= 'i';
        $valores[] = $categoria;
    }
} elseif ($categoria !== '') {                           // ?categoria=escuela (la clave, como en index.php)
    $condiciones[] = 'c.clave = ?';
    $tipos .= 's';
    $valores[] = $categoria;
}

// Los alias (AS) dejan nombres claros para quien consume: hora, categoria, descripcion
$sql = "SELECT e.id, e.titulo, e.fecha,
               TIME_FORMAT(e.hora_inicio, '%H:%i') AS hora,
               c.nombre AS categoria, e.notas AS descripcion
          FROM eventos e
          JOIN categoria c ON c.id = e.categoria_id
         WHERE " . implode(' AND ', $condiciones) . "
         ORDER BY e.fecha, e.hora_inicio";
$consulta = $conexion->prepare($sql);
$consulta->bind_param($tipos, ...$valores);   // "..." reparte el arreglo: un valor por cada "?"
$consulta->execute();
$eventos = $consulta->get_result()->fetch_all(MYSQLI_ASSOC);
$consulta->close();
$conexion->close();

// El id siempre como número (no como texto) en el JSON
foreach ($eventos as &$evento) {
    $evento['id'] = (int) $evento['id'];
}
unset($evento);

// 4 · Respuesta: datos de contexto + el arreglo de eventos
responder(200, [
    'usuario' => $_SESSION['nombre'] ?? '',
    'total'   => count($eventos),
    'filtros' => ['q' => $q, 'categoria' => $categoria === '' ? 0 : $categoria],
    'eventos' => $eventos,
]);
