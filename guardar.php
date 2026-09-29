<?php
// Recibe el formulario (POST), lo valida y lo guarda con datos.php.
// Sin "id" crea un registro nuevo; con "id" actualiza ese registro (modo edición).
//
// - Si lo llama el JavaScript del formulario (Accept: application/json) responde JSON:
//     201 { ok: true,  registro: {...} }   registro creado
//     200 { ok: true,  registro: {...} }   registro actualizado
//     422 { ok: false, errores: { campo: "mensaje" } }
//     404 { ok: false, error: "..." }      el registro a editar ya no existe
// - Si el navegador envía el formulario sin JavaScript, redirige al calendario
//   o muestra una página sencilla con los errores.
require __DIR__ . '/datos.php';

// Valores permitidos para los campos cerrados del formulario
const TIPOS         = ['evento', 'cita'];
const CATEGORIAS    = ['escuela', 'trabajo', 'salud', 'personal'];
const RECORDATORIOS = [0, 15, 60, 1440];
const PRIORIDADES   = ['baja', 'media', 'alta'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit;
}

$quiere_json = str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');

// ---------- Lectura y validación ----------
// Lee un campo como texto UTF-8 válido (los bytes mal codificados se reemplazan
// para que nunca rompan el JSON de registros.php)
function texto(string $campo): string
{
    return trim(mb_scrub((string) ($_POST[$campo] ?? ''), 'UTF-8'));
}

function es_hora(string $valor): bool
{
    return (bool) preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $valor);
}

function es_fecha(string $valor): bool
{
    $fecha = DateTime::createFromFormat('!Y-m-d', $valor);
    return $fecha !== false && $fecha->format('Y-m-d') === $valor;
}

$errores = [];

// Vacío = registro nuevo; número = registro a editar
$id = texto('id');
if ($id !== '' && !ctype_digit($id)) {
    $errores['id'] = 'Registro no válido.';
}

$tipo = texto('tipo');
if (!in_array($tipo, TIPOS, true)) {
    $errores['tipo'] = 'Elige si es un evento o una cita.';
}

$titulo = texto('titulo');
if ($titulo === '') {
    $errores['titulo'] = 'Escribe un título.';
} elseif (mb_strlen($titulo) > 80) {
    $errores['titulo'] = 'El título admite hasta 80 caracteres.';
}

$fecha = texto('fecha');
if (!es_fecha($fecha)) {
    $errores['fecha'] = 'Elige una fecha válida.';
}

$todo_el_dia = isset($_POST['todo_el_dia']);
$hora_inicio = null;
$hora_fin = null;
if (!$todo_el_dia) {
    $hora_inicio = texto('hora_inicio');
    $hora_fin = texto('hora_fin') ?: null;
    if (!es_hora($hora_inicio)) {
        $errores['hora_inicio'] = 'Indica la hora de inicio.';
    }
    if ($hora_fin !== null && !es_hora($hora_fin)) {
        $errores['hora_fin'] = 'Hora de fin no válida.';
    } elseif ($hora_fin !== null && es_hora($hora_inicio) && $hora_fin <= $hora_inicio) {
        $errores['hora_fin'] = 'Debe ser después de la hora de inicio.';
    }
}

// Campos de texto opcionales: vacío se guarda como null
$opcionales = ['lugar' => 120, 'persona' => 80, 'notas' => 1000];
$valores = [];
foreach ($opcionales as $campo => $maximo) {
    $valor = texto($campo);
    if (mb_strlen($valor) > $maximo) {
        $errores[$campo] = "Admite hasta $maximo caracteres.";
    }
    $valores[$campo] = $valor === '' ? null : $valor;
}

$categoria = texto('categoria');
if (!in_array($categoria, CATEGORIAS, true)) {
    $errores['categoria'] = 'Elige una categoría de la lista.';
}

$recordatorio = texto('recordatorio');
if (!ctype_digit($recordatorio) || !in_array((int) $recordatorio, RECORDATORIOS, true)) {
    $errores['recordatorio'] = 'Elige un recordatorio de la lista.';
}

$prioridad = texto('prioridad');
if (!in_array($prioridad, PRIORIDADES, true)) {
    $errores['prioridad'] = 'Elige una prioridad.';
}

// ---------- Respuesta ----------
if ($errores) {
    http_response_code(422);
    if ($quiere_json) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'errores' => $errores], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    } else {
        header('Content-Type: text/html; charset=utf-8');
        echo '<!DOCTYPE html><html lang="es"><meta charset="UTF-8"><title>Revisa el registro</title>';
        echo '<h1>No se pudo guardar el registro</h1><ul>';
        foreach ($errores as $mensaje) {
            echo '<li>' . htmlspecialchars($mensaje) . '</li>';
        }
        echo '</ul><p><a href="index.html">Volver al formulario</a></p></html>';
    }
    exit;
}

$datos = [
    'tipo'         => $tipo,
    'titulo'       => $titulo,
    'fecha'        => $fecha,
    'hora_inicio'  => $hora_inicio,
    'hora_fin'     => $hora_fin,
    'lugar'        => $valores['lugar'],
    'persona'      => $valores['persona'],
    'categoria'    => $categoria,
    'recordatorio' => (int) $recordatorio,
    'prioridad'    => $prioridad,
    'notas'        => $valores['notas'],
    'todo_el_dia'  => $todo_el_dia,
];

if ($id === '') {
    $registro = guardar_registro($datos);
    $codigo = 201;
} else {
    $registro = actualizar_registro((int) $id, $datos);
    $codigo = 200;
    if ($registro === null) {
        http_response_code(404);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'error' => 'Ese registro ya no existe.']);
        exit;
    }
}

if ($quiere_json) {
    http_response_code($codigo);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => true, 'registro' => $registro], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
} else {
    header('Location: calendario.html?fecha=' . rawurlencode($fecha), true, 303);
}
