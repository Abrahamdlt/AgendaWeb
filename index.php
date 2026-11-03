<?php
require __DIR__ . '/partes/sesion.php';   // solo con sesión iniciada: define $usuarioId y $usuarioNombre
// =========================================
// AgendaWeb · Eventos (página principal)
// =========================================
// Práctica 4: las tarjetas las genera PHP. El HTML de UNA tarjeta se escribe una sola vez
// en mostrarEvento(), y un foreach la repite con los datos de cada evento de la tabla "eventos".
//
// Práctica 9: buscador por texto y por categoría (index.php?q=...&categoria=...), solo entre tus eventos.
//
// También es el destino de la redirección PRG de registrar.php y eliminar.php:
//   index.php?ok=creado&id=5    index.php?ok=actualizado&id=5    index.php?ok=eliminado
require __DIR__ . '/datos.php';        // conexion.php ($conexion), obtener_registro() y resumen_por_categoria()
require __DIR__ . '/validacion.php';   // CATEGORIAS: clave => nombre
require __DIR__ . '/ayudas.php';       // e() (limpia texto contra XSS), recurso(), numero_de_url()

// ---------- Paso 3 · Funciones pequeñas de ayuda ----------

// '2026-10-08' → '08/10/2026'
function formatearFecha(string $fecha): string
{
    return date('d/m/Y', strtotime($fecha));
}

// 'escuela' → 'Escuela' (los nombres están en CATEGORIAS, en validacion.php)
function nombreCategoria(string $clave): string
{
    return CATEGORIAS[$clave] ?? $clave;   // si la clave no existe, devuelve la original
}

// ---------- Paso 4 · La función principal: la tarjeta de UN evento ----------
// Recibe un evento (arreglo asociativo) y DEVUELVE el HTML de su tarjeta.
function mostrarEvento(array $ev): string
{
    $meses = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
    $id = (int) $ev['id'];                         // el id SIEMPRE como número
    $marca = strtotime($ev['fecha']);
    // Solo valores de la lista blanca terminan en la clase (baja, media o alta)
    $prioridad = array_key_exists($ev['prioridad'] ?? '', PRIORIDADES) ? $ev['prioridad'] : 'media';

    $html  = '<article class="tarjeta tarjeta--registro tarjeta--prioridad-' . $prioridad . '" data-id="' . $id . '">';

    // Cuadro con el día y el mes
    $html .= '<div class="registro__hora">'
           . '<span class="registro__dia">' . date('j', $marca) . '</span>'
           . '<span class="registro__mes">' . $meses[date('n', $marca) - 1] . '</span>'
           . '</div>';

    $html .= '<div class="registro__info">';
    $html .= '<h2 class="registro__titulo">' . e($ev['titulo']) . '</h2>';

    $cuando = formatearFecha($ev['fecha']);
    if ($ev['hora']) {                                       // la hora es opcional
        $cuando .= ' · ' . substr($ev['hora'], 0, 5);        // '10:30:00' → '10:30'
        if (!empty($ev['hora_fin'])) {
            $cuando .= ' – ' . substr($ev['hora_fin'], 0, 5);
        }
    }
    $html .= '<p class="registro__meta"><time datetime="' . e($ev['fecha']) . '">' . e($cuando) . '</time>';
    if (!empty($ev['lugar'])) {
        $html .= ' · ' . e($ev['lugar']);
    }
    $html .= '</p>';

    if ($ev['descripcion']) {                                // la descripción también
        $html .= '<p class="registro__texto">' . e($ev['descripcion']) . '</p>';
    }

    // La prioridad también va escrita, para no depender solo del color de la franja
    $html .= '<p class="registro__datos">'
           . '<span class="insignia insignia--categoria">' . e(nombreCategoria($ev['categoria'])) . '</span>'
           . '<span class="insignia insignia--' . $prioridad . '">Prioridad ' . $prioridad . '</span>'
           . '</p>';

    $html .= '<div class="registro__acciones">'
           . '<a href="registrar.php?editar=' . $id . '" class="boton boton--secundario boton--chico"'
           . ' aria-label="Editar «' . e($ev['titulo']) . '»">Editar</a>'
           . '<form method="post" action="eliminar.php" class="registro__borrar" data-titulo="' . e($ev['titulo']) . '">'
           . '<input type="hidden" name="id" value="' . $id . '">'
           . '<button type="submit" class="boton boton--peligro boton--chico"'
           . ' aria-label="Eliminar «' . e($ev['titulo']) . '»">Eliminar</button>'
           . '</form></div>';

    $html .= '</div>';
    return $html . '</article>';
}

// ---------- Aviso después de la redirección (PRG) ----------
$aviso = null;   // ['texto' => ..., 'error' => bool, 'enlace' => ['texto' => ..., 'href' => ...]]
$ok = $_GET['ok'] ?? '';

if ($ok === 'creado' || $ok === 'actualizado') {
    $id = numero_de_url('id');
    $registro = $id !== null ? obtener_registro($id, $usuarioId) : null;
    if ($registro !== null) {
        $aviso = [
            'texto' => $ok === 'creado'
                ? 'Se guardó «' . $registro['titulo'] . '».'
                : 'Se guardaron los cambios de «' . $registro['titulo'] . '».',
            'error' => false,
            'enlace' => ['texto' => 'Ver en el calendario', 'href' => 'calendario.php?fecha=' . $registro['fecha']],
        ];
    } else {
        $aviso = ['texto' => $ok === 'creado' ? 'Se guardó el registro.' : 'Se guardaron los cambios.', 'error' => false];
    }
} elseif ($ok === 'eliminado') {
    $aviso = ['texto' => 'Se eliminó el registro.', 'error' => false];
} elseif (($_GET['error'] ?? '') === 'noexiste') {
    $aviso = ['texto' => 'Ese registro ya no existe; puede que ya se haya eliminado.', 'error' => true];
}

// ---------- P9 · Buscador: lo que escribió la persona ----------
// Llega por GET (index.php?q=examen&categoria=escuela): la búsqueda queda en la URL,
// así se puede recargar o guardar en marcadores. Buscar no cambia datos, por eso no es POST.
$busqueda = mb_substr(texto($_GET, 'q'), 0, 100);       // texto libre, máximo 100 caracteres
$categoria = texto($_GET, 'categoria');
if (!array_key_exists($categoria, CATEGORIAS)) {        // lista blanca: lo que no sea una categoría se ignora
    $categoria = '';
}
$filtrando = $busqueda !== '' || $categoria !== '';

// ---------- Paso 7 · Los eventos, desde MySQL ----------
// Los alias (AS) dejan las mismas claves que el arreglo de ejemplo de la práctica
// (hora, categoria, descripcion), así mostrarEvento() y el foreach no cambian.
// P8: la consulta lleva un dato del usuario, así que pasa de query() a prepare().
// P9: se arma por partes. "Solo los míos" va SIEMPRE; cada filtro agrega su condición y su "?".
$sql = 'SELECT e.id, e.titulo, e.fecha, e.hora_inicio AS hora, e.hora_fin, e.lugar,
               c.clave AS categoria, e.prioridad, e.notas AS descripcion
          FROM eventos e
          JOIN categoria c ON c.id = e.categoria_id
         WHERE e.usuario_id = ?';
$tipos = 'i';
$valores = [$usuarioId];

if ($busqueda !== '') {
    // LIKE '%examen%' = "contiene examen" en cualquier parte. Si la persona escribe % o _,
    // se escapan (\% \_) para buscarlos como letras y no como comodines.
    $patron = '%' . addcslashes($busqueda, '%_\\') . '%';
    $sql .= ' AND (e.titulo LIKE ? OR e.notas LIKE ?)';
    $tipos .= 'ss';
    array_push($valores, $patron, $patron);
}
if ($categoria !== '') {
    $sql .= ' AND c.clave = ?';
    $tipos .= 's';
    $valores[] = $categoria;
}
$sql .= ' ORDER BY e.fecha, e.hora_inicio';

$consulta = $conexion->prepare($sql);
$consulta->bind_param($tipos, ...$valores);   // "..." reparte el arreglo: un valor por cada "?"
$consulta->execute();
$eventos = $consulta->get_result()->fetch_all(MYSQLI_ASSOC);   // ← un arreglo de arreglos asociativos
$consulta->close();

// Resumen: cuántos eventos tienes en cada categoría (las vacías salen con 0)
$resumen = resumen_por_categoria($usuarioId);
$conexion->close();

$total = count($eventos);                                   // con filtros: cuántos resultados hay
$totalRegistrados = array_sum(array_column($resumen, 'total'));   // todos tus eventos
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>AgendaWeb · Eventos</title>

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
  <script src="<?= e(recurso('js/eventos.js')) ?>" defer></script>
</head>
<body>

  <header class="barra">
    <a href="index.php" class="marca">Agenda<span>Web</span></a>
    <nav class="barra__nav">
      <a href="index.php" class="enlace enlace--activo" aria-current="page">Eventos</a>
      <a href="calendario.php" class="enlace">Calendario</a>
      <a href="registrar.php" class="enlace">Nuevo registro</a>
    </nav>
    <div class="barra__usuario">
      <span class="barra__saludo">Hola, <?= e($usuarioNombre) ?></span>
      <a href="logout.php" class="enlace">Salir</a>
    </div>
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

  <main class="contenedor contenedor--sencillo">

    <section class="eventos" aria-labelledby="eventos-titulo">
      <div class="eventos__cabecera">
        <div>
          <h1 class="titulo" id="eventos-titulo">Eventos</h1>
          <!-- Paso 6 · El contador ya no está escrito a mano -->
          <p class="subtitulo"><?= $totalRegistrados ?> <?= $totalRegistrados === 1 ? 'evento registrado' : 'eventos registrados' ?></p>
          <p class="subtitulo">Por categoría:
            <?php foreach ($resumen as $i => $fila): ?><?= $i > 0 ? ' · ' : ' ' ?><?= e($fila['nombre']) ?> <?= (int) $fila['total'] ?><?php endforeach; ?>
          </p>
        </div>
        <a class="boton boton--primario" href="registrar.php">Nuevo registro</a>
      </div>

      <!-- Paso 6 · El aviso, solo si registrar.php o eliminar.php nos mandaron ?ok= (o ?error=) -->
      <?php if ($aviso): ?>
        <div class="aviso<?= $aviso['error'] ? ' aviso--error' : '' ?>" role="<?= $aviso['error'] ? 'alert' : 'status' ?>">
          <span><?= e($aviso['texto']) ?></span>
          <?php if (!empty($aviso['enlace'])): ?>
            <a class="enlace enlace--activo" href="<?= e($aviso['enlace']['href']) ?>"><?= e($aviso['enlace']['texto']) ?></a>
          <?php endif; ?>
        </div>
      <?php endif; ?>

      <!-- P9 · Buscador. Los campos conservan lo que se buscó (formulario "pegajoso") -->
      <form class="buscador" action="index.php" method="get" role="search">
        <div class="campo">
          <label class="etiqueta" for="q">Buscar</label>
          <input class="entrada" type="search" id="q" name="q" maxlength="100"
                 value="<?= e($busqueda) ?>" placeholder="Título o notas">
        </div>
        <div class="campo">
          <label class="etiqueta" for="filtro-categoria">Categoría</label>
          <select class="entrada" id="filtro-categoria" name="categoria">
            <option value="">Todas</option>
            <?php foreach (CATEGORIAS as $valor => $texto): ?>
              <option value="<?= e($valor) ?>"<?= $categoria === $valor ? ' selected' : '' ?>><?= e($texto) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="buscador__acciones">
          <button type="submit" class="boton boton--secundario">Buscar</button>
          <?php if ($filtrando): ?>
            <a class="enlace" href="index.php">Limpiar</a>
          <?php endif; ?>
        </div>
      </form>

      <!-- P9 · Cuántos resultados hay (solo cuando se está buscando) -->
      <?php if ($filtrando): ?>
        <p class="resultados">
          <?= $total ?> <?= $total === 1 ? 'resultado' : 'resultados' ?><?php if ($busqueda !== ''): ?>
            para «<?= e($busqueda) ?>»<?php endif; ?><?php if ($categoria !== ''): ?>
            en <?= e(CATEGORIAS[$categoria]) ?><?php endif; ?>
        </p>
      <?php endif; ?>

      <!-- Paso 6 · Lista O estado vacío, nunca los dos -->
      <?php if (empty($eventos) && $filtrando): ?>
        <div class="tarjeta tarjeta--vacia">
          <p>Ningún evento coincide con tu búsqueda. <a class="enlace enlace--activo" href="index.php">Ver todos</a></p>
        </div>
      <?php elseif (empty($eventos)): ?>
        <div class="tarjeta tarjeta--vacia">
          <p>Aún no tienes eventos registrados. <a class="enlace enlace--activo" href="registrar.php">Crea el primero</a></p>
        </div>
      <?php else: ?>
        <!-- Paso 5 · Las tarjetas las genera el foreach con mostrarEvento() -->
        <section class="lista-registros lista-registros--rejilla" aria-label="Lista de eventos">
          <?php foreach ($eventos as $ev): ?>
            <?= mostrarEvento($ev) ?>
          <?php endforeach; ?>
        </section>
      <?php endif; ?>
    </section>

  </main>

</body>
</html>
