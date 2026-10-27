<?php
// =========================================
// AgendaWeb · Envío de correos
// =========================================
// Habla con un servidor de correo (SMTP) como lo haría un programa de correo: se conecta,
// inicia sesión con la cuenta de correo.php y entrega el mensaje. Sin librerías externas.
//
// DOM Cloud bloquea el puerto 25, por eso se usa el 465 (o el 587) de un servicio como Gmail.
require_once __DIR__ . '/partes/correo_verificacion.php';

// Los datos de correo.php, o null si no existe (entonces los correos se guardan en correos/)
function config_correo(): ?array
{
    $archivo = __DIR__ . '/correo.php';
    return is_file($archivo) ? require $archivo : null;
}

// Manda el correo con el enlace para confirmar la cuenta. Devuelve false si no se pudo enviar.
function enviar_verificacion(string $nombre, string $correo, string $token): bool
{
    $config = config_correo();
    $sitio = rtrim($config['url_sitio'] ?? 'http://localhost:8080', '/');
    $enlace = $sitio . '/verificar.php?token=' . $token;

    [$html, $texto] = plantilla_verificacion($nombre, $enlace);
    return enviar_correo($correo, 'Confirma tu correo · AgendaWeb', $html, $texto);
}

// Envía un correo con versión HTML y versión de texto (para programas que no muestran HTML)
function enviar_correo(string $para, string $asunto, string $html, string $texto): bool
{
    $config = config_correo();

    // Sin servidor configurado: se guarda el correo como archivo para poder abrirlo y probar el enlace.
    // Solo en tu computadora: en internet, cualquiera podría abrir correos/ y usar los enlaces.
    if (empty($config['servidor'])) {
        if (!in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1'], true)) {
            error_log('AgendaWeb: falta configurar correo.php (servidor de correo)');
            return false;
        }
        $carpeta = __DIR__ . '/correos';
        if (!is_dir($carpeta)) {
            mkdir($carpeta);
        }
        $archivo = $carpeta . '/' . date('Y-m-d_His') . '_' . bin2hex(random_bytes(2)) . '_' . preg_replace('/[^a-z0-9]+/i', '_', $para) . '.html';
        file_put_contents($archivo, $html);
        error_log('AgendaWeb: correo guardado en ' . $archivo);
        return true;
    }

    try {
        smtp_enviar($config, $para, armar_mensaje($config, $para, $asunto, $html, $texto));
        return true;
    } catch (RuntimeException $ex) {
        error_log('AgendaWeb: no se pudo enviar el correo: ' . $ex->getMessage());
        return false;
    }
}

// Texto con acentos en un encabezado (Asunto, nombre): se codifica en base64 como pide el estándar
function encabezado_utf8(string $texto): string
{
    return '=?UTF-8?B?' . base64_encode($texto) . '?=';
}

// El mensaje completo: encabezados + las dos versiones (texto y HTML)
function armar_mensaje(array $config, string $para, string $asunto, string $html, string $texto): string
{
    $separador = 'agendaweb-' . bin2hex(random_bytes(8));
    $dominio = substr(strrchr($config['usuario'], '@'), 1);

    $lineas = [
        'From: ' . encabezado_utf8($config['remitente']) . ' <' . $config['usuario'] . '>',
        'To: <' . $para . '>',
        'Subject: ' . encabezado_utf8($asunto),
        'Date: ' . date('r'),
        'Message-ID: <' . bin2hex(random_bytes(16)) . '@' . $dominio . '>',
        'MIME-Version: 1.0',
        'Content-Type: multipart/alternative; boundary="' . $separador . '"',
        '',
        '--' . $separador,
        'Content-Type: text/plain; charset=UTF-8',
        'Content-Transfer-Encoding: base64',
        '',
        chunk_split(base64_encode($texto)),
        '--' . $separador,
        'Content-Type: text/html; charset=UTF-8',
        'Content-Transfer-Encoding: base64',
        '',
        chunk_split(base64_encode($html)),
        '--' . $separador . '--',
    ];
    return implode("\r\n", $lineas);
}

// La "conversación" con el servidor SMTP. Cada orden espera un código de respuesta;
// si llega otro, algo falló y se detiene.
function smtp_enviar(array $config, string $para, string $mensaje): void
{
    $puerto = (int) $config['puerto'];
    $direccion = ($puerto === 465 ? 'ssl://' : 'tcp://') . $config['servidor'] . ':' . $puerto;
    $socket = @stream_socket_client($direccion, $num, $error, 15);
    if (!$socket) {
        throw new RuntimeException("sin conexión con $direccion ($error)");
    }
    stream_set_timeout($socket, 15);

    $orden = function (?string $linea, int $esperado) use ($socket): void {
        if ($linea !== null) {
            fwrite($socket, $linea . "\r\n");
        }
        // La respuesta puede ocupar varias líneas: "250-..." sigue, "250 ..." es la última
        $respuesta = '';
        while (($renglon = fgets($socket, 1024)) !== false) {
            $respuesta .= $renglon;
            if (strlen($renglon) < 4 || $renglon[3] === ' ') {
                break;
            }
        }
        if ((int) substr($respuesta, 0, 3) !== $esperado) {
            throw new RuntimeException('el servidor respondió "' . trim($respuesta) . '"');
        }
    };

    $orden(null, 220);                          // saludo del servidor
    $orden('EHLO agendaweb', 250);
    if ($puerto !== 465) {                      // 587: se pide cifrar la conexión ya abierta
        $orden('STARTTLS', 220);
        if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            throw new RuntimeException('no se pudo cifrar la conexión');
        }
        $orden('EHLO agendaweb', 250);
    }
    $orden('AUTH LOGIN', 334);                  // inicio de sesión: usuario y clave en base64
    $orden(base64_encode($config['usuario']), 334);
    $orden(base64_encode($config['clave']), 235);
    $orden('MAIL FROM:<' . $config['usuario'] . '>', 250);
    $orden('RCPT TO:<' . $para . '>', 250);
    $orden('DATA', 354);
    $orden($mensaje . "\r\n.", 250);            // un punto solo en su línea = fin del mensaje
    fwrite($socket, "QUIT\r\n");              // el correo ya se entregó; solo se despide
    fclose($socket);
}
