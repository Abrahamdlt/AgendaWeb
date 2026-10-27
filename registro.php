<?php
// =========================================
// AgendaWeb · Crear cuenta (Práctica 8)
// =========================================
// Misma estructura que registrar.php: arriba el PHP que recibe y valida; abajo el formulario.
session_start();
if (isset($_SESSION['uid'])) {   // ya tiene sesión: no necesita registrarse
    header('Location: index.php');
    exit;
}
require __DIR__ . '/datos.php';        // conexion.php y crear_usuario()
require __DIR__ . '/validacion.php';   // texto(): lee un campo como texto limpio
require __DIR__ . '/ayudas.php';       // e(), recurso()
require __DIR__ . '/enviar_correo.php'; // enviar_verificacion()

$nombre = $correo = '';
$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = texto($_POST, 'nombre');
    $correo = mb_strtolower(texto($_POST, 'correo'));   // Ana@x.com = ana@x.com
    $clave  = is_string($_POST['clave'] ?? null) ? $_POST['clave'] : '';     // la contraseña NO se recorta
    $clave2 = is_string($_POST['clave2'] ?? null) ? $_POST['clave2'] : '';

    if ($nombre === '' || mb_strlen($nombre) > 80) {
        $errores['nombre'] = 'Escribe tu nombre (máximo 80 caracteres).';
    }
    if (!filter_var($correo, FILTER_VALIDATE_EMAIL) || mb_strlen($correo) > 120) {
        $errores['correo'] = 'Escribe un correo válido.';
    }
    if (mb_strlen($clave) < 8) {
        $errores['clave'] = 'La contraseña debe tener al menos 8 caracteres.';
    } elseif ($clave !== $clave2) {
        $errores['clave2'] = 'Las contraseñas no coinciden.';
    }

    if (empty($errores)) {
        try {
            $id = crear_usuario($nombre, $correo, $clave);   // se guarda el HASH, nunca la contraseña
            // La cuenta nace sin verificar: se manda el enlace secreto al correo que escribió
            $enviado = enviar_verificacion($nombre, $correo, nuevo_token($id));
            header('Location: login.php?' . ($enviado ? 'pendiente=1' : 'no_enviado=1'), true, 303);
            exit;
        } catch (mysqli_sql_exception $ex) {
            if ($ex->getCode() === 1062) {   // 1062 = valor repetido en un UNIQUE
                $errores['correo'] = 'Ese correo ya tiene una cuenta.';
            } else {
                error_log('AgendaWeb: ' . $ex->getMessage());
                $errores['general'] = 'No se pudo crear la cuenta. Intenta de nuevo.';
            }
        }
    }
}

// Atributos y mensaje de un campo con error (como en registrar.php)
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
  <title>AgendaWeb · Crear cuenta</title>

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
      <h1 class="titulo">Crear cuenta</h1>
      <p class="subtitulo">Tu agenda, solo tuya. Te enviaremos un enlace para confirmar tu correo.</p>

      <?php if (isset($errores['general'])): ?>
        <div class="aviso aviso--error" role="alert"><span><?= e($errores['general']) ?></span></div>
      <?php endif; ?>

      <!-- Las contraseñas nunca se vuelven a escribir en el HTML: quedarían visibles en el código de la página -->
      <form class="formulario" action="registro.php" method="post">
        <div class="campo">
          <label class="etiqueta" for="nombre">Nombre</label>
          <input class="entrada" type="text" id="nombre" name="nombre" maxlength="80"
                 value="<?= e($nombre) ?>" autocomplete="name" required<?= atributos_error($errores, 'nombre') ?>>
          <?= mensaje_error($errores, 'nombre') ?>
        </div>

        <div class="campo">
          <label class="etiqueta" for="correo">Correo</label>
          <input class="entrada" type="email" id="correo" name="correo" maxlength="120"
                 value="<?= e($correo) ?>" autocomplete="email" required<?= atributos_error($errores, 'correo') ?>>
          <?= mensaje_error($errores, 'correo') ?>
        </div>

        <div class="campo">
          <label class="etiqueta" for="clave">Contraseña</label>
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
          <a class="enlace" href="login.php">Ya tengo cuenta</a>
          <button type="submit" class="boton boton--primario">Crear cuenta</button>
        </div>
      </form>
    </section>
  </main>

</body>
</html>
