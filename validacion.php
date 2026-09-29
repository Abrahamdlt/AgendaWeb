<?php
// =========================================
// AgendaWeb · Validación del formulario (en el servidor)
// =========================================
// Campos obligatorios, formatos (fecha, hora) y listas blancas para los campos cerrados.
// La usa index.php antes de guardar.

// ---------- Listas blancas: los únicos valores aceptados (valor => texto que se muestra) ----------
const TIPOS = ['evento' => 'Evento', 'cita' => 'Cita'];
const CATEGORIAS = ['escuela' => 'Escuela', 'trabajo' => 'Trabajo', 'salud' => 'Salud', 'personal' => 'Personal'];
const RECORDATORIOS = ['0' => 'Sin recordatorio', '15' => '15 minutos antes', '60' => '1 hora antes', '1440' => '1 día antes'];
const PRIORIDADES = ['baja' => 'Baja', 'media' => 'Media', 'alta' => 'Alta'];

// Valores con los que empieza un formulario nuevo
const VALORES_INICIALES = [
    'id' => '', 'tipo' => 'evento', 'titulo' => '', 'fecha' => '', 'hora_inicio' => '', 'hora_fin' => '',
    'lugar' => '', 'persona' => '', 'categoria' => 'escuela', 'recordatorio' => '15', 'prioridad' => 'media',
    'notas' => '', 'todo_el_dia' => false,
];

// Lee un campo como texto UTF-8 válido y sin espacios de sobra
// (los bytes mal codificados se reemplazan para que nunca rompan el JSON de registros.php)
function texto(array $entrada, string $campo): string
{
    $valor = $entrada[$campo] ?? '';
    return is_string($valor) ? trim(mb_scrub($valor, 'UTF-8')) : '';
}

// Lo que la persona envió, tal cual (sirve para volver a mostrarlo si hay errores)
function leer_formulario(array $entrada): array
{
    $valores = [];
    foreach (VALORES_INICIALES as $campo => $inicial) {
        $valores[$campo] = $campo === 'todo_el_dia' ? isset($entrada['todo_el_dia']) : texto($entrada, $campo);
    }
    return $valores;
}

// Un registro de la base con la misma forma que leer_formulario() (para editarlo)
function registro_a_valores(array $registro): array
{
    $valores = [];
    foreach (VALORES_INICIALES as $campo => $inicial) {
        $valores[$campo] = $campo === 'todo_el_dia' ? (bool) $registro[$campo] : (string) ($registro[$campo] ?? '');
    }
    return $valores;
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

// Devuelve [$datos, $errores]:
//   $datos   los valores listos para guardar (vacío = null, números como número)
//   $errores campo => mensaje; si está vacío, todo es válido
function validar_registro(array $v): array
{
    $errores = [];

    if ($v['id'] !== '' && !ctype_digit($v['id'])) {
        $errores['id'] = 'Registro no válido.';
    }

    // Listas blancas
    if (!array_key_exists($v['tipo'], TIPOS)) {
        $errores['tipo'] = 'Elige si es un evento o una cita.';
    }
    if (!array_key_exists($v['categoria'], CATEGORIAS)) {
        $errores['categoria'] = 'Elige una categoría de la lista.';
    }
    if (!array_key_exists($v['recordatorio'], RECORDATORIOS)) {
        $errores['recordatorio'] = 'Elige un recordatorio de la lista.';
    }
    if (!array_key_exists($v['prioridad'], PRIORIDADES)) {
        $errores['prioridad'] = 'Elige una prioridad.';
    }

    // Obligatorios
    if ($v['titulo'] === '') {
        $errores['titulo'] = 'Escribe un título.';
    } elseif (mb_strlen($v['titulo']) > 80) {
        $errores['titulo'] = 'El título admite hasta 80 caracteres.';
    }
    if (!es_fecha($v['fecha'])) {
        $errores['fecha'] = 'Elige una fecha válida.';
    }

    // Horas: obligatoria la de inicio, salvo que sea "todo el día"
    $hora_inicio = null;
    $hora_fin = null;
    if (!$v['todo_el_dia']) {
        $hora_inicio = $v['hora_inicio'];
        $hora_fin = $v['hora_fin'] === '' ? null : $v['hora_fin'];
        if (!es_hora($hora_inicio)) {
            $errores['hora_inicio'] = 'Indica la hora de inicio.';
        }
        if ($hora_fin !== null && !es_hora($hora_fin)) {
            $errores['hora_fin'] = 'Hora de fin no válida.';
        } elseif ($hora_fin !== null && es_hora($hora_inicio) && $hora_fin <= $hora_inicio) {
            $errores['hora_fin'] = 'Debe ser después de la hora de inicio.';
        }
    }

    // Opcionales con largo máximo
    foreach (['lugar' => 120, 'persona' => 80, 'notas' => 1000] as $campo => $maximo) {
        if (mb_strlen($v[$campo]) > $maximo) {
            $errores[$campo] = "Admite hasta $maximo caracteres.";
        }
    }

    $datos = [
        'tipo'         => $v['tipo'],
        'titulo'       => $v['titulo'],
        'fecha'        => $v['fecha'],
        'hora_inicio'  => $hora_inicio,
        'hora_fin'     => $hora_fin,
        'lugar'        => $v['lugar'] === '' ? null : $v['lugar'],
        'persona'      => $v['persona'] === '' ? null : $v['persona'],
        'categoria'    => $v['categoria'],
        'recordatorio' => (int) $v['recordatorio'],
        'prioridad'    => $v['prioridad'],
        'notas'        => $v['notas'] === '' ? null : $v['notas'],
        'todo_el_dia'  => $v['todo_el_dia'],
    ];
    return [$datos, $errores];
}
