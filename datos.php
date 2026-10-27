<?php
// =========================================
// AgendaWeb · Capa de datos
// =========================================
// Es el único archivo que habla con MySQL (tablas "eventos", "categoria" y "usuarios",
// ver bd/instalar_agenda.sql y bd/migracion_p8.sql). Las páginas solo llaman a estas funciones.
//
// Autorización (P8): toda función de eventos recibe $usuario_id y lleva
// "AND usuario_id = ?", así cada quien ve, edita y borra SOLO lo suyo.
require __DIR__ . '/conexion.php';

// Si una consulta falla, se responde un mensaje de error en lugar de romper la página
// (en JSON cuando lo pidió el JavaScript, en texto cuando es una página)
set_exception_handler(function (Throwable $e): void {
    error_log('AgendaWeb: ' . $e->getMessage());
    http_response_code(500);
    if (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'error' => 'Ocurrió un error con la base de datos.']);
    } else {
        header('Content-Type: text/plain; charset=utf-8');
        echo 'Ocurrió un error con la base de datos.';
    }
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

// Los registros del usuario, por fecha; dentro del día, los de "todo el día" primero y luego por hora
function obtener_registros(int $usuario_id): array
{
    global $conexion;
    $consulta = $conexion->prepare(
        CONSULTA_EVENTOS . ' WHERE e.usuario_id = ? ORDER BY e.fecha, e.todo_el_dia DESC, e.hora_inicio'
    );
    $consulta->bind_param('i', $usuario_id);
    $consulta->execute();
    return array_map('formatear_registro', $consulta->get_result()->fetch_all(MYSQLI_ASSOC));
}

// Un registro por su id, o null si no existe o es de otro usuario
function obtener_registro(int $id, int $usuario_id): ?array
{
    global $conexion;
    $consulta = $conexion->prepare(CONSULTA_EVENTOS . ' WHERE e.id = ? AND e.usuario_id = ?');
    $consulta->bind_param('ii', $id, $usuario_id);
    $consulta->execute();
    $fila = $consulta->get_result()->fetch_assoc();
    return $fila ? formatear_registro($fila) : null;
}

// Inserta un registro ya validado a nombre del usuario y lo devuelve tal como quedó en la base
function guardar_registro(array $r, int $usuario_id): array
{
    global $conexion;
    $consulta = $conexion->prepare(
        'INSERT INTO eventos (usuario_id, tipo, titulo, fecha, hora_inicio, hora_fin, todo_el_dia, lugar, persona,
                              categoria_id, recordatorio, prioridad, notas)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, (SELECT id FROM categoria WHERE clave = ?), ?, ?, ?)'
    );
    $todo_el_dia = (int) $r['todo_el_dia'];
    // s = texto, i = número entero (una letra por cada "?")
    $consulta->bind_param(
        'isssssisssiss',
        $usuario_id, $r['tipo'], $r['titulo'], $r['fecha'], $r['hora_inicio'], $r['hora_fin'], $todo_el_dia,
        $r['lugar'], $r['persona'], $r['categoria'], $r['recordatorio'], $r['prioridad'], $r['notas']
    );
    $consulta->execute();
    return obtener_registro($conexion->insert_id, $usuario_id);
}

// Actualiza un registro ya validado. Devuelve el registro actualizado, o null si no existe o es de otro usuario.
function actualizar_registro(int $id, array $r, int $usuario_id): ?array
{
    global $conexion;
    if (obtener_registro($id, $usuario_id) === null) {
        return null;
    }
    $consulta = $conexion->prepare(
        'UPDATE eventos
            SET tipo = ?, titulo = ?, fecha = ?, hora_inicio = ?, hora_fin = ?, todo_el_dia = ?,
                lugar = ?, persona = ?, categoria_id = (SELECT id FROM categoria WHERE clave = ?),
                recordatorio = ?, prioridad = ?, notas = ?
          WHERE id = ? AND usuario_id = ?'
    );
    $todo_el_dia = (int) $r['todo_el_dia'];
    $consulta->bind_param(
        'sssssisssissii',
        $r['tipo'], $r['titulo'], $r['fecha'], $r['hora_inicio'], $r['hora_fin'], $todo_el_dia,
        $r['lugar'], $r['persona'], $r['categoria'], $r['recordatorio'], $r['prioridad'], $r['notas'],
        $id, $usuario_id
    );
    $consulta->execute();
    return obtener_registro($id, $usuario_id);
}

// Elimina un registro del usuario. Devuelve false si no existía (o era de otro usuario).
function eliminar_registro(int $id, int $usuario_id): bool
{
    global $conexion;
    $consulta = $conexion->prepare('DELETE FROM eventos WHERE id = ? AND usuario_id = ?');
    $consulta->bind_param('ii', $id, $usuario_id);
    $consulta->execute();
    return $consulta->affected_rows > 0;
}

// ---------- Usuarios (P8) ----------

// Crea una cuenta SIN verificar y devuelve su id. Guarda el HASH de la contraseña, nunca la contraseña.
// Si el correo ya existe, MySQL lanza una excepción con el código 1062 (valor repetido en un UNIQUE).
function crear_usuario(string $nombre, string $correo, string $clave): int
{
    global $conexion;
    // Si alguien registró este correo y nunca lo confirmó (enlace vencido), esa cuenta se borra:
    // así nadie puede "apartar" el correo de otra persona.
    $limpiar = $conexion->prepare(
        'DELETE FROM usuarios
          WHERE correo = ? AND verificado = FALSE
            AND COALESCE(token_enviado, creado_en) < NOW() - INTERVAL 24 HOUR'
    );
    $limpiar->bind_param('s', $correo);
    $limpiar->execute();

    $hash = password_hash($clave, PASSWORD_DEFAULT);
    $consulta = $conexion->prepare('INSERT INTO usuarios (nombre, correo, clave_hash) VALUES (?, ?, ?)');
    $consulta->bind_param('sss', $nombre, $correo, $hash);
    $consulta->execute();
    return $conexion->insert_id;
}

// La cuenta con ese correo (id, nombre, clave_hash, verificado), o null si no existe
function buscar_usuario(string $correo): ?array
{
    global $conexion;
    $consulta = $conexion->prepare('SELECT id, nombre, clave_hash, verificado FROM usuarios WHERE correo = ?');
    $consulta->bind_param('s', $correo);
    $consulta->execute();
    $usuario = $consulta->get_result()->fetch_assoc();
    return $usuario ?: null;
}

// ---------- Verificación del correo ----------
// El enlace lleva un código secreto al azar (el "token"). En la base solo se guarda su huella SHA-256:
// si alguien leyera la tabla, no podría armar un enlace válido.

// Crea un código nuevo para una cuenta sin verificar y lo devuelve (para ponerlo en el enlace).
// Devuelve null si ya se envió uno hace menos de 1 minuto (evita usar la página para mandar spam).
function nuevo_token(int $usuario_id): ?string
{
    global $conexion;
    $token = bin2hex(random_bytes(32));   // 64 caracteres al azar, imposibles de adivinar
    $huella = hash('sha256', $token);
    $consulta = $conexion->prepare(
        'UPDATE usuarios SET token_hash = ?, token_enviado = NOW()
          WHERE id = ? AND verificado = FALSE
            AND (token_enviado IS NULL OR token_enviado < NOW() - INTERVAL 1 MINUTE)'
    );
    $consulta->bind_param('si', $huella, $usuario_id);
    $consulta->execute();
    return $consulta->affected_rows === 1 ? $token : null;
}

// Marca como verificada la cuenta dueña del código, si no ha vencido (24 horas).
// El código se borra al usarse: el mismo enlace no sirve dos veces.
function verificar_token(string $token): bool
{
    global $conexion;
    $huella = hash('sha256', $token);
    $consulta = $conexion->prepare(
        'UPDATE usuarios SET verificado = TRUE, token_hash = NULL, token_enviado = NULL
          WHERE token_hash = ? AND verificado = FALSE AND token_enviado > NOW() - INTERVAL 24 HOUR'
    );
    $consulta->bind_param('s', $huella);
    $consulta->execute();
    return $consulta->affected_rows === 1;
}

// Cuántos eventos tiene el usuario en cada categoría. Las categorías sin eventos salen con 0:
// el filtro va en el ON del LEFT JOIN; en un WHERE, esas categorías desaparecerían.
function resumen_por_categoria(int $usuario_id): array
{
    global $conexion;
    $consulta = $conexion->prepare(
        'SELECT c.nombre, COUNT(e.id) AS total
           FROM categoria c
           LEFT JOIN eventos e ON e.categoria_id = c.id AND e.usuario_id = ?
          GROUP BY c.id, c.nombre
          ORDER BY c.nombre'
    );
    $consulta->bind_param('i', $usuario_id);
    $consulta->execute();
    return $consulta->get_result()->fetch_all(MYSQLI_ASSOC);
}
