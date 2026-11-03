<?php
// =========================================
// AgendaWeb · Elegir contraseña nueva
// =========================================
// Aquí llega el enlace del correo: restablecer.php?token=<código secreto>.
// 1) Se revisa que el código exista y no haya vencido (1 hora).
// 2) Se pide la contraseña nueva dos veces; el código viaja en un campo oculto del formulario.
// 3) Se guarda su hash y el código se borra: el enlace ya no sirve otra vez.
require __DIR__ . '/datos.php';        // conexion.php, token_restablecer_valido(), restablecer_clave()
require __DIR__ . '/ayudas.php';       // e(), recurso()

// Un código válido son exactamente 64 caracteres hexadecimales (0-9, a-f)
function formato_token(mixed $token): bool
{
    return is_string($token) && strlen($token) === 64 && ctype_xdigit($token);
}

$token = $_SERVER['REQUEST_METHOD'] === 'POST' ? ($_POST['token'] ?? '') : ($_GET['token'] ?? '');
$valido = formato_token($token) && token_restablecer_valido($token);
$errores = [];

if ($valido && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $clave  = is_string($_POST['clave'] ?? null) ? $_POST['clave'] : '';    // la contraseña NO se recorta
    $clave2 = is_string($_POST['clave2'] ?? null) ? $_POST['clave2'] : '';

    // Mismas reglas que en registro.php
    if (mb_strlen($clave) < 8) {
        $errores['clave'] = 'La contraseña debe tener al menos 8 caracteres.';
    } elseif ($clave !== $clave2) {
        $errores['clave2'] = 'Las contraseñas no coinciden.';
    }

    if (empty($errores)) {
        if (restablecer_clave($token, $clave)) {
            header('Location: login.php?restablecida=1', true, 303);
            exit;
        }
        $valido = false;   // venció justo mientras escribía
    }
}

// Atributos y mensaje de un campo con error (como en registro.php)
function atributos_error(array $errores, string $campo): string
{
    return isset($errores[$campo]) ? ' aria-invalid="true" aria-describedby="error-' . $campo . '"' : '';
}
function mensaje_error(array $errores, string $campo): string
{
    return isset($errores[$campo])
        ? '<p class="campo__error" id="error-' . $campo . '">' . e($errores[$campo]) . '</p>'
        : '';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>AgendaWeb · Contraseña nueva</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600&family=Source+Serif+4:opsz,wght@8..60,500;8..60,600&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="<?= e(recurso('css/styles.css')) ?>">

  <!-- Aplica el tema guardado antes de pintar, para evitar un parpadeo -->
  <script>
    (function () {
      var tema = null;
      try { tema = localStorage.getItem("tema"); } catch (e) {}
      if (!tema) tema = matchMedia("(prefers-color-scheme: light)").matches ? "claro" : "oscuro";
      document.documentElement.dataset.tema = tema;
    })();
  </script>
  <script src="<?= e(recurso('js/tema.js')) ?>" defer></script>
</head>
<body>

  <header class="barra">
    <a href="login.php" class="marca">Agenda<span>Web</span></a>
    <button type="button" class="boton boton--icono" id="boton-tema"
            aria-label="Cambiar a modo claro" hidden>
      <svg class="icono-luna" viewBox="0 0 24 24" fill="none" stroke="currentColor"
           stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/>
      </svg>
      <svg class="icono-sol" viewBox="0 0 24 24" fill="none" stroke="currentColor"
           stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <circle cx="12" cy="12" r="4"/>
        <path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/>
      </svg>
    </button>
  </header>

  <main class="contenedor contenedor--acceso">
    <section class="tarjeta tarjeta--principal">
      <?php if ($valido): ?>
        <h1 class="titulo">Contraseña nueva</h1>
        <p class="subtitulo">Elige una contraseña de al menos 8 caracteres.</p>

        <form class="formulario" action="restablecer.php" method="post">
          <input type="hidden" name="token" value="<?= e($token) ?>">

          <div class="campo">
            <label class="etiqueta" for="clave">Contraseña nueva</label>
            <input class="entrada" type="password" id="clave" name="clave" minlength="8"
                   autocomplete="new-password" required<?= atributos_error($errores, 'clave') ?>>
            <?= mensaje_error($errores, 'clave') ?>
          </div>

          <div class="campo">
            <label class="etiqueta" for="clave2">Repítela</label>
            <input class="entrada" type="password" id="clave2" name="clave2" minlength="8"
                   autocomplete="new-password" required<?= atributos_error($errores, 'clave2') ?>>
            <?= mensaje_error($errores, 'clave2') ?>
          </div>

          <div class="acciones">
            <a class="enlace" href="login.php">Cancelar</a>
            <button type="submit" class="boton boton--primario">Guardar contraseña</button>
          </div>
        </form>
      <?php else: ?>
        <h1 class="titulo">Enlace no válido</h1>
        <p class="subtitulo">
          Este enlace ya se usó o venció (dura 1 hora). Pide uno nuevo para elegir tu contraseña.
        </p>

        <div class="acciones">
          <a class="enlace" href="login.php">Iniciar sesión</a>
          <a class="boton boton--primario" href="olvide.php">Enviar otro enlace</a>
        </div>
      <?php endif; ?>
    </section>
  </main>

</body>
</html>
