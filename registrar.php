<?php
// =========================================
// AgendaWeb · Formulario de registro
// =========================================
// Crear:  registrar.php            Editar: registrar.php?editar=ID
//
// Patrón PRG (Post / Redirect / Get):
//   1. El formulario se envía a esta misma página con POST.
//   2. Se valida en el servidor (validacion.php).
//      - Con errores: se vuelve a mostrar el formulario con lo que escribió la persona
//        (formulario "pegajoso") y un mensaje junto a cada campo.
//      - Sin errores: se guarda con consulta preparada (datos.php) y se REDIRIGE a
//        index.php?ok=... para que recargar la página no vuelva a enviar el formulario.
//   3. La redirección llega por GET a index.php (Eventos), que muestra el mensaje de éxito.
require __DIR__ . '/datos.php';
require __DIR__ . '/validacion.php';
require __DIR__ . '/ayudas.php';

$valores = VALORES_INICIALES;
$errores = [];
$aviso = null;   // ['texto' => ..., 'error' => bool, 'enlace' => ['texto' => ..., 'href' => ...]]

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ---------- 1-2. Recibir y validar ----------
    $valores = leer_formulario($_POST);
    [$datos, $errores] = validar_registro($valores);

    if (!$errores && $valores['id'] === '') {
        $registro = guardar_registro($datos);
        header('Location: index.php?ok=creado&id=' . $registro['id'], true, 303);   // Redirect
        exit;
    }
    if (!$errores) {
        $registro = actualizar_registro((int) $valores['id'], $datos);
        if ($registro !== null) {
            header('Location: index.php?ok=actualizado&id=' . $registro['id'], true, 303);
            exit;
        }
        $aviso = ['texto' => 'Ese registro ya no existe; puede que se haya eliminado.', 'error' => true,
                  'enlace' => ['texto' => 'Ver mis eventos', 'href' => 'index.php']];
    } else {
        $aviso = ['texto' => 'Revisa los campos marcados.', 'error' => true];
    }
} else {
    // ---------- 3. Página pedida por GET ----------
    $id_editar = numero_de_url('editar');
    if ($id_editar !== null) {
        $registro = obtener_registro($id_editar);
        if ($registro !== null) {
            $valores = registro_a_valores($registro);
        } else {
            $aviso = ['texto' => 'No se encontró ese registro; puede que ya se haya eliminado.', 'error' => true,
                      'enlace' => ['texto' => 'Ver mis eventos', 'href' => 'index.php']];
        }
    } elseif (es_fecha((string) ($_GET['fecha'] ?? ''))) {
        $valores['fecha'] = $_GET['fecha'];   // viene de "Agregar registro este día" del calendario
    }
}

$editando = $valores['id'] !== '';

// El primer campo con error en el orden del formulario: ahí se pone el cursor
$primer_error = null;
foreach (array_keys(VALORES_INICIALES) as $campo) {
    if (isset($errores[$campo])) {
        $primer_error = $campo;
        break;
    }
}

// ---------- Ayudas para escribir el HTML del formulario ----------
// Atributos de un campo con error: lo marcan como inválido, lo enlazan a su mensaje
// y ponen el cursor en el primer campo con error
function atributos_error(string $campo): string
{
    global $errores, $primer_error;
    if (!isset($errores[$campo])) {
        return '';
    }
    $atributos = ' aria-invalid="true" aria-describedby="error-' . $campo . '"';
    return $campo === $primer_error ? $atributos . ' autofocus' : $atributos;
}

function mensaje_error(string $campo): string
{
    global $errores;
    if (!isset($errores[$campo])) {
        return '';
    }
    return '<p class="campo__error" id="error-' . $campo . '">' . e($errores[$campo]) . '</p>';
}

function marcado(bool $condicion, string $atributo): string
{
    return $condicion ? ' ' . $atributo : '';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>AgendaWeb · <?= $editando ? 'Editar registro' : 'Nuevo registro' ?></title>

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
  <script src="<?= e(recurso('js/registros.js')) ?>" defer></script>
  <script src="<?= e(recurso('js/proximos.js')) ?>" defer></script>
  <script src="<?= e(recurso('js/nuevo-registro.js')) ?>" defer></script>
</head>
<body>

  <header class="barra">
    <a href="index.php" class="marca">Agenda<span>Web</span></a>
    <nav class="barra__nav">
      <a href="index.php" class="enlace">Eventos</a>
      <a href="calendario.php" class="enlace">Calendario</a>
      <a href="registrar.php" class="enlace enlace--activo" aria-current="page">Nuevo registro</a>
    </nav>
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

  <main class="contenedor">

    <!-- Formulario principal -->
    <section class="tarjeta tarjeta--principal">
      <h1 class="titulo"><?= $editando ? 'Editar registro' : 'Nuevo registro' ?></h1>
      <p class="subtitulo">
        <?= $editando ? 'Cambia lo que necesites y guarda los cambios.' : 'Agrega un evento o una cita a tu agenda.' ?>
      </p>

      <?php if ($aviso): ?>
        <!-- Mensaje de éxito (después de la redirección) o de error -->
        <div class="aviso<?= $aviso['error'] ? ' aviso--error' : '' ?>" role="<?= $aviso['error'] ? 'alert' : 'status' ?>">
          <span><?= e($aviso['texto']) ?></span>
          <?php if (!empty($aviso['enlace'])): ?>
            <a class="enlace enlace--activo" href="<?= e($aviso['enlace']['href']) ?>"><?= e($aviso['enlace']['texto']) ?></a>
          <?php endif; ?>
        </div>
      <?php endif; ?>

      <form class="formulario" id="formulario-registro" action="registrar.php" method="post">

        <!-- Vacío = registro nuevo; con número = se está editando ese registro -->
        <input type="hidden" name="id" value="<?= e($valores['id']) ?>">

        <!-- Tipo de registro -->
        <fieldset class="campo">
          <legend class="etiqueta">Tipo</legend>
          <div class="selector">
            <?php foreach (TIPOS as $valor => $texto): ?>
              <input type="radio" id="tipo-<?= e($valor) ?>" name="tipo" value="<?= e($valor) ?>"<?= marcado($valores['tipo'] === $valor, 'checked') ?><?= marcado($primer_error === 'tipo' && $valor === array_key_first(TIPOS), 'autofocus') ?>>
              <label for="tipo-<?= e($valor) ?>"><?= e($texto) ?></label>
            <?php endforeach; ?>
          </div>
          <?= mensaje_error('tipo') ?>
        </fieldset>

        <!-- Título -->
        <div class="campo">
          <label class="etiqueta" for="titulo">Título</label>
          <input class="entrada" type="text" id="titulo" name="titulo" value="<?= e($valores['titulo']) ?>"
                 placeholder="Ej. Revisión del proyecto final" required maxlength="80"<?= atributos_error('titulo') ?>>
          <?= mensaje_error('titulo') ?>
        </div>

        <!-- Fecha y horas -->
        <div class="fila">
          <div class="campo">
            <label class="etiqueta" for="fecha">Fecha</label>
            <input class="entrada" type="date" id="fecha" name="fecha" value="<?= e($valores['fecha']) ?>"
                   required<?= atributos_error('fecha') ?>>
            <?= mensaje_error('fecha') ?>
          </div>
          <div class="campo">
            <label class="etiqueta" for="hora-inicio">Inicio</label>
            <input class="entrada" type="time" id="hora-inicio" name="hora_inicio" value="<?= e($valores['hora_inicio']) ?>"
                   <?= $valores['todo_el_dia'] ? 'disabled' : 'required' ?><?= atributos_error('hora_inicio') ?>>
            <?= mensaje_error('hora_inicio') ?>
          </div>
          <div class="campo">
            <label class="etiqueta" for="hora-fin">Fin</label>
            <input class="entrada" type="time" id="hora-fin" name="hora_fin" value="<?= e($valores['hora_fin']) ?>"
                   <?= marcado($valores['todo_el_dia'], 'disabled') ?><?= atributos_error('hora_fin') ?>>
            <?= mensaje_error('hora_fin') ?>
          </div>
        </div>

        <!-- Lugar y con quién -->
        <div class="fila fila--2">
          <div class="campo">
            <label class="etiqueta" for="lugar">Lugar</label>
            <input class="entrada" type="text" id="lugar" name="lugar" value="<?= e($valores['lugar']) ?>"
                   placeholder="Sala B, Zoom, consultorio…" maxlength="120"<?= atributos_error('lugar') ?>>
            <?= mensaje_error('lugar') ?>
          </div>
          <div class="campo">
            <label class="etiqueta" for="persona">Con quién <span class="opcional">(opcional)</span></label>
            <input class="entrada" type="text" id="persona" name="persona" value="<?= e($valores['persona']) ?>"
                   placeholder="Nombre de la persona" maxlength="80"<?= atributos_error('persona') ?>>
            <?= mensaje_error('persona') ?>
          </div>
        </div>

        <!-- Categoría y recordatorio -->
        <div class="fila fila--2">
          <div class="campo">
            <label class="etiqueta" for="categoria">Categoría</label>
            <select class="entrada" id="categoria" name="categoria"<?= atributos_error('categoria') ?>>
              <?php foreach (CATEGORIAS as $valor => $texto): ?>
                <option value="<?= e($valor) ?>"<?= marcado($valores['categoria'] === (string) $valor, 'selected') ?>><?= e($texto) ?></option>
              <?php endforeach; ?>
            </select>
            <?= mensaje_error('categoria') ?>
          </div>
          <div class="campo">
            <label class="etiqueta" for="recordatorio">Recordatorio</label>
            <select class="entrada" id="recordatorio" name="recordatorio"<?= atributos_error('recordatorio') ?>>
              <?php foreach (RECORDATORIOS as $valor => $texto): ?>
                <option value="<?= e($valor) ?>"<?= marcado($valores['recordatorio'] === (string) $valor, 'selected') ?>><?= e($texto) ?></option>
              <?php endforeach; ?>
            </select>
            <?= mensaje_error('recordatorio') ?>
          </div>
        </div>

        <!-- Prioridad -->
        <fieldset class="campo">
          <legend class="etiqueta">Prioridad</legend>
          <div class="selector selector--3">
            <?php foreach (PRIORIDADES as $valor => $texto): ?>
              <input type="radio" id="prio-<?= e($valor) ?>" name="prioridad" value="<?= e($valor) ?>"<?= marcado($valores['prioridad'] === $valor, 'checked') ?><?= marcado($primer_error === 'prioridad' && $valor === array_key_first(PRIORIDADES), 'autofocus') ?>>
              <label for="prio-<?= e($valor) ?>"><?= e($texto) ?></label>
            <?php endforeach; ?>
          </div>
          <?= mensaje_error('prioridad') ?>
        </fieldset>

        <!-- Notas -->
        <div class="campo">
          <label class="etiqueta" for="notas">Notas</label>
          <textarea class="entrada entrada--area" id="notas" name="notas" rows="4"
                    placeholder="Detalles, materiales que llevar, enlaces…" maxlength="1000"<?= atributos_error('notas') ?>><?= e($valores['notas']) ?></textarea>
          <?= mensaje_error('notas') ?>
        </div>

        <!-- Casilla -->
        <label class="casilla">
          <input type="checkbox" id="todo-el-dia" name="todo_el_dia"<?= marcado($valores['todo_el_dia'], 'checked') ?>>
          <span>Todo el día</span>
        </label>

        <!-- Acciones -->
        <div class="acciones">
          <?php if ($editando): ?>
            <button type="button" class="boton boton--peligro" id="boton-eliminar"
                    data-id="<?= e($valores['id']) ?>" data-titulo="<?= e($valores['titulo']) ?>">Eliminar</button>
            <a class="boton boton--secundario" href="registrar.php?editar=<?= e($valores['id']) ?>">Deshacer cambios</a>
            <button type="submit" class="boton boton--primario">Guardar cambios</button>
          <?php else: ?>
            <a class="boton boton--secundario" href="registrar.php">Limpiar</a>
            <button type="submit" class="boton boton--primario">Guardar registro</button>
          <?php endif; ?>
        </div>

      </form>
    </section>

    <!-- Próximos registros (los carga js/proximos.js desde registros.php) -->
    <aside class="lateral">
      <h2 class="titulo titulo--chico">Próximos</h2>
      <div class="lista-registros" id="proximos-lista">
        <div class="tarjeta tarjeta--vacia"><p>Cargando registros…</p></div>
      </div>
      <a class="enlace" href="index.php">Ver todos los eventos</a>
    </aside>

  </main>

</body>
</html>
