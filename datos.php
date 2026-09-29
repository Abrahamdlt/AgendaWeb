<?php
// =========================================
// AgendaWeb · Capa de datos
// =========================================
// Es el único archivo que habla con MySQL (tablas "eventos" y "categoria",
// ver bd/actualizar_agenda.sql). guardar.php, registros.php y eliminar.php solo
// llaman a estas funciones.
require __DIR__ . '/conexion.php';

// Si una consulta falla, se responde un JSON de error en lugar de romper la página
set_exception_handler(function (Throwable $e): void {
    error_log('AgendaWeb: ' . $e->getMessage());
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'error' => 'Ocurrió un error con la base de datos.']);
});

// Consulta base. La categoría se devuelve con su clave (escuela, trabajo, salud, personal)
// y las horas como "HH:MM", igual que las usa el formulario.
const CONSULTA_EVENTOS = "
    SELECT e.id, e.tipo, e.titulo, e.fecha,
           TIME_FORMAT(e.hora_inicio, '%H:%i') AS hora_inicio,
           TIME_FORMAT(e.hora_fin, '%H:%i')    AS hora_fin,
           e.todo_el_dia, e.lugar, e.persona, c.clave AS categoria,
           e.recordatorio, e.prioridad, e.notas
      FROM eventos e
      JOIN categoria c ON c.id = e.categoria_id";

// MySQL entrega los números y el booleano como texto; aquí se convierten
function formatear_registro(array $fila): array
{
    $fila['id'] = (int) $fila['id'];
    $fila['todo_el_dia'] = (bool) $fila['todo_el_dia'];
    $fila['recordatorio'] = (int) $fila['recordatorio'];
    return $fila;
}

// Todos los registros, por fecha; dentro del día, los de "todo el día" primero y luego por hora
function obtener_registros(): array
{
    global $conexion;
    $resultado = $conexion->query(CONSULTA_EVENTOS . ' ORDER BY e.fecha, e.todo_el_dia DESC, e.hora_inicio');
    return array_map('formatear_registro', $resultado->fetch_all(MYSQLI_ASSOC));
}

// Un registro por su id, o null si no existe
function obtener_registro(int $id): ?array
{
    global $conexion;
    $consulta = $conexion->prepare(CONSULTA_EVENTOS . ' WHERE e.id = ?');
    $consulta->bind_param('i', $id);
    $consulta->execute();
    $fila = $consulta->get_result()->fetch_assoc();
    return $fila ? formatear_registro($fila) : null;
}

// Inserta un registro ya validado y lo devuelve tal como quedó en la base
function guardar_registro(array $r): array
{
    global $conexion;
    $consulta = $conexion->prepare(
        'INSERT INTO eventos (tipo, titulo, fecha, hora_inicio, hora_fin, todo_el_dia, lugar, persona,
                              categoria_id, recordatorio, prioridad, notas)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, (SELECT id FROM categoria WHERE clave = ?), ?, ?, ?)'
    );
    $todo_el_dia = (int) $r['todo_el_dia'];
    // s = texto, i = número entero (una letra por cada "?")
    $consulta->bind_param(
        'sssssisssiss',
        $r['tipo'], $r['titulo'], $r['fecha'], $r['hora_inicio'], $r['hora_fin'], $todo_el_dia,
        $r['lugar'], $r['persona'], $r['categoria'], $r['recordatorio'], $r['prioridad'], $r['notas']
    );
    $consulta->execute();
    return obtener_registro($conexion->insert_id);
}

// Actualiza un registro ya validado. Devuelve el registro actualizado, o null si no existe.
function actualizar_registro(int $id, array $r): ?array
{
    global $conexion;
    if (obtener_registro($id) === null) {
        return null;
    }
    $consulta = $conexion->prepare(
        'UPDATE eventos
            SET tipo = ?, titulo = ?, fecha = ?, hora_inicio = ?, hora_fin = ?, todo_el_dia = ?,
                lugar = ?, persona = ?, categoria_id = (SELECT id FROM categoria WHERE clave = ?),
                recordatorio = ?, prioridad = ?, notas = ?
          WHERE id = ?'
    );
    $todo_el_dia = (int) $r['todo_el_dia'];
    $consulta->bind_param(
        'sssssisssissi',
        $r['tipo'], $r['titulo'], $r['fecha'], $r['hora_inicio'], $r['hora_fin'], $todo_el_dia,
        $r['lugar'], $r['persona'], $r['categoria'], $r['recordatorio'], $r['prioridad'], $r['notas'],
        $id
    );
    $consulta->execute();
    return obtener_registro($id);
}

// Elimina un registro. Devuelve false si no existía.
function eliminar_registro(int $id): bool
{
    global $conexion;
    $consulta = $conexion->prepare('DELETE FROM eventos WHERE id = ?');
    $consulta->bind_param('i', $id);
    $consulta->execute();
    return $consulta->affected_rows > 0;
}
