<?php
// =========================================
// AgendaWeb · Correo "Confirma tu correo"
// =========================================
// Los programas de correo (Gmail, Outlook) ignoran casi todo el CSS moderno: no hay variables,
// ni flex, ni archivos .css. Por eso el diseño va con tablas y estilos escritos en cada etiqueta,
// copiando a mano los colores de la marca "Pulso Neón" (css/styles.css, modo oscuro).
// Devuelve [html, texto] del correo
function plantilla_verificacion(string $nombre, string $enlace): array
{
    // Tokens de la marca (mismos valores que :root en styles.css)
    $fondo      = '#0B0D12';
    $superficie = '#151923';
    $borde      = '#2A3142';
    $texto      = '#E8ECF4';
    $secundario = '#9AA4B8';
    $acento     = '#00E5FF';   // solo en lo accionable: el botón y el enlace
    $titulos    = "'Source Serif 4', Georgia, 'Times New Roman', serif";
    $cuerpo     = "Geist, -apple-system, 'Segoe UI', Roboto, Arial, sans-serif";

    // Escapados para el HTML (como e() de ayudas.php)
    $n = htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8');
    $url = htmlspecialchars($enlace, ENT_QUOTES, 'UTF-8');

    $html = <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="color-scheme" content="dark">
  <meta name="supported-color-schemes" content="dark">
  <title>Confirma tu correo · AgendaWeb</title>
  <link href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600&family=Source+Serif+4:opsz,wght@8..60,600&display=swap" rel="stylesheet">
</head>
<body style="margin:0; padding:0; background:$fondo; color:$texto; font-family:$cuerpo;">
  <!-- Vista previa que muestra la bandeja de entrada junto al asunto -->
  <div style="display:none; max-height:0; overflow:hidden;">Abre el enlace para activar tu cuenta de AgendaWeb.</div>

  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:$fondo;">
    <tr>
      <td align="center" style="padding:40px 16px;">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:520px;">

          <!-- Marca -->
          <tr>
            <td style="padding:0 4px 20px; font-family:$titulos; font-size:22px; font-weight:600; letter-spacing:-0.3px; color:$texto;">
              Agenda<span style="color:$acento;">Web</span>
            </td>
          </tr>

          <!-- Tarjeta -->
          <tr>
            <td style="background:$superficie; border:1px solid $borde; border-radius:18px; padding:36px 32px;">
              <h1 style="margin:0 0 12px; font-family:$titulos; font-size:28px; line-height:1.2; font-weight:600; color:$texto;">
                Confirma tu correo
              </h1>
              <p style="margin:0 0 8px; font-size:16px; line-height:1.6; color:$texto;">Hola, $n:</p>
              <p style="margin:0 0 28px; font-size:16px; line-height:1.6; color:$secundario;">
                Gracias por crear tu cuenta en AgendaWeb. Para activarla, confirma que este correo es tuyo.
              </p>

              <!-- Botón (igual que .boton--primario) -->
              <table role="presentation" cellpadding="0" cellspacing="0">
                <tr>
                  <td style="border-radius:11px; background:$acento;">
                    <a href="$url" target="_blank"
                       style="display:inline-block; padding:14px 28px; font-family:$cuerpo; font-size:16px; font-weight:600; color:$fondo; text-decoration:none; border-radius:11px;">
                      Confirmar mi correo
                    </a>
                  </td>
                </tr>
              </table>

              <p style="margin:28px 0 0; font-size:14px; line-height:1.6; color:$secundario;">
                El enlace vence en 24 horas y solo funciona una vez.
              </p>

              <!-- Respaldo por si el botón no funciona -->
              <p style="margin:24px 0 0; padding-top:24px; border-top:1px solid $borde; font-size:13px; line-height:1.6; color:$secundario;">
                ¿El botón no funciona? Copia y pega este enlace en tu navegador:<br>
                <a href="$url" target="_blank" style="color:$acento; word-break:break-all;">$url</a>
              </p>
            </td>
          </tr>

          <!-- Pie -->
          <tr>
            <td style="padding:20px 4px 0; font-size:13px; line-height:1.6; color:$secundario;">
              Si tú no creaste esta cuenta, ignora este correo: sin confirmar, la cuenta no se activa
              y se borra sola.
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>
</body>
</html>
HTML;

    $texto_plano = "Hola, $nombre:\n\n"
        . "Gracias por crear tu cuenta en AgendaWeb. Para activarla, abre este enlace:\n\n"
        . "$enlace\n\n"
        . "El enlace vence en 24 horas y solo funciona una vez.\n\n"
        . "Si tú no creaste esta cuenta, ignora este correo.";

    return [$html, $texto_plano];
}
