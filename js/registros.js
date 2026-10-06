// Utilidades compartidas: hablar con los archivos PHP y pintar los registros como tarjetas.
// El DOM se arma con textContent, nunca con innerHTML, porque los datos vienen del usuario.
var Registros = (function () {
  var MESES = ["enero", "febrero", "marzo", "abril", "mayo", "junio", "julio",
               "agosto", "septiembre", "octubre", "noviembre", "diciembre"];
  var MESES_CORTOS = ["ene", "feb", "mar", "abr", "may", "jun", "jul", "ago", "sep", "oct", "nov", "dic"];
  var DIAS_SEMANA = ["domingo", "lunes", "martes", "miércoles", "jueves", "viernes", "sábado"];

  // ---------- Comunicación con PHP ----------
  function pedirJson(url, opciones) {
    opciones = opciones || {};
    opciones.headers = { Accept: "application/json" };
    return fetch(url, opciones).then(function (respuesta) {
      if (!respuesta.ok) throw new Error(url + " respondió " + respuesta.status);
      return respuesta.json();
    });
  }

  // Todos los registros (arreglo con los mismos campos que el formulario)
  function cargar() {
    return pedirJson("registros.php");
  }

  function eliminar(id) {
    var datos = new FormData();
    datos.append("id", id);
    return pedirJson("eliminar.php", { method: "POST", body: datos });
  }

  // ---------- Fechas en hora local (sin pasar por UTC) ----------
  function clave(fecha) {
    var m = String(fecha.getMonth() + 1).padStart(2, "0");
    var d = String(fecha.getDate()).padStart(2, "0");
    return fecha.getFullYear() + "-" + m + "-" + d;
  }
  function desdeClave(texto) {
    var p = texto.split("-");
    return new Date(Number(p[0]), Number(p[1]) - 1, Number(p[2]));
  }
  function esClave(texto) {
    return /^\d{4}-\d{2}-\d{2}$/.test(texto || "") && clave(desdeClave(texto)) === texto;
  }

  function capitalizar(texto) {
    return texto.charAt(0).toUpperCase() + texto.slice(1);
  }

  function hoy() {
    var ahora = new Date();
    return new Date(ahora.getFullYear(), ahora.getMonth(), ahora.getDate());
  }

  // "Jueves, 8 de octubre" (con el año solo si no es el actual)
  function nombreFecha(fecha) {
    var texto = DIAS_SEMANA[fecha.getDay()] + ", " + fecha.getDate() + " de " + MESES[fecha.getMonth()];
    if (fecha.getFullYear() !== hoy().getFullYear()) texto += " de " + fecha.getFullYear();
    return capitalizar(texto);
  }

  // Días completos de hoy a esa fecha: 0 = hoy, 1 = mañana, -1 = ayer...
  // (Math.round absorbe los días de 23 o 25 horas del cambio de horario)
  function diasHasta(fecha) {
    return Math.round((fecha - hoy()) / 86400000);
  }

  // "Hoy", "Mañana", "En 5 días"
  function cuando(fecha) {
    var dias = diasHasta(fecha);
    if (dias === 0) return "Hoy";
    if (dias === 1) return "Mañana";
    return dias > 1 ? "En " + dias + " días" : "Hace " + (-dias) + (dias === -1 ? " día" : " días");
  }

  // Pendiente = de hoy en adelante, sin contar los de hoy que ya terminaron.
  // Un registro termina a su hora de fin (o de inicio, si no tiene fin); "todo el día" dura todo el día.
  function esPendiente(r, ahora) {
    var claveHoy = clave(ahora);
    if (r.fecha !== claveHoy) return r.fecha > claveHoy;
    if (r.todo_el_dia || !r.hora_inicio) return true;
    var horaActual = String(ahora.getHours()).padStart(2, "0") + ":" + String(ahora.getMinutes()).padStart(2, "0");
    return (r.hora_fin || r.hora_inicio) >= horaActual;
  }

  // Los registros pendientes, en el mismo orden (registros.php ya los manda por fecha y hora)
  function pendientes(registros) {
    var ahora = new Date();
    return registros.filter(function (r) { return esPendiente(r, ahora); });
  }

  function crear(etiqueta, clase, texto) {
    var el = document.createElement(etiqueta);
    if (clase) el.className = clase;
    if (texto != null) el.textContent = texto;
    return el;
  }

  // ---------- Diálogo de confirmación ----------
  // Devuelve una promesa: true si la persona confirma, false si cancela (o presiona Esc).
  // Solo hay una confirmación pendiente a la vez, y se responde en el momento del clic
  // (el evento "close" del diálogo puede llegar tarde, por ejemplo con la pestaña en segundo plano).
  var dialogo = null;
  var responder = null;   // resuelve la confirmación abierta, si la hay

  function terminar(confirmado) {
    if (!responder) return;
    var r = responder;
    responder = null;
    r(confirmado);
  }

  function confirmar(titulo, texto, textoConfirmar) {
    if (!dialogo) {
      dialogo = crear("dialog", "dialogo");
      dialogo.setAttribute("aria-labelledby", "dialogo-titulo");
      dialogo.setAttribute("aria-describedby", "dialogo-texto");
      var formulario = crear("form");
      formulario.method = "dialog";   // cualquier botón cierra el diálogo con su "value"
      var h2 = crear("h2", "dialogo__titulo");
      h2.id = "dialogo-titulo";
      var p = crear("p", "dialogo__texto");
      p.id = "dialogo-texto";
      var acciones = crear("div", "dialogo__acciones");
      var cancelar = crear("button", "boton boton--secundario", "Cancelar");
      cancelar.value = "cancelar";
      cancelar.autofocus = true;       // la opción segura recibe el foco
      var aceptar = crear("button", "boton boton--peligro");
      aceptar.value = "confirmar";
      acciones.append(cancelar, aceptar);
      formulario.append(h2, p, acciones);
      dialogo.appendChild(formulario);
      document.body.appendChild(dialogo);

      // El clic en un botón envía el formulario en ese mismo instante: ahí se responde
      formulario.addEventListener("submit", function (e) {
        terminar(Boolean(e.submitter) && e.submitter.value === "confirmar");
      });
      // Esc o cualquier otro cierre cuenta como cancelar (salvo que, al llegar este aviso
      // atrasado, ya se haya abierto otra confirmación)
      dialogo.addEventListener("close", function () {
        if (!dialogo.open) terminar(false);
      });
    }
    terminar(false);   // si había otra confirmación abierta, se cancela
    dialogo.querySelector(".dialogo__titulo").textContent = titulo;
    dialogo.querySelector(".dialogo__texto").textContent = texto;
    dialogo.querySelector('[value="confirmar"]').textContent = textoConfirmar || "Eliminar";
    if (!dialogo.open) dialogo.showModal();
    return new Promise(function (resolver) { responder = resolver; });
  }

  // Pregunta y elimina. Devuelve una promesa que se cumple solo si se eliminó.
  function confirmarYEliminar(registro) {
    return confirmar("¿Eliminar este registro?",
                     "«" + registro.titulo + "» se borrará de tu agenda. Esta acción no se puede deshacer.")
      .then(function (si) {
        if (!si) throw null;   // cancelado: no es un error
        return eliminar(registro.id);
      });
  }

  // ---------- Tarjetas ----------
  // alCambiar (opcional): si se pasa, la tarjeta muestra Editar / Eliminar y
  // se llama a esa función después de eliminar para recargar la lista.
  function crearTarjeta(r, alCambiar) {
    var fecha = desdeClave(r.fecha);
    var tarjeta = crear("article", "tarjeta tarjeta--registro");
    tarjeta.dataset.id = r.id;

    var hora = crear("div", "registro__hora");
    hora.appendChild(crear("span", "registro__dia", fecha.getDate()));
    hora.appendChild(crear("span", "registro__mes", MESES_CORTOS[fecha.getMonth()]));
    tarjeta.appendChild(hora);

    var info = crear("div", "registro__info");
    info.appendChild(crear("h3", "registro__titulo", r.titulo));

    var partes = [];
    if (r.todo_el_dia) partes.push("Todo el día");
    else if (r.hora_inicio) partes.push(r.hora_fin ? r.hora_inicio + " – " + r.hora_fin : r.hora_inicio);
    if (r.lugar) partes.push(r.lugar);
    if (r.persona) partes.push("con " + r.persona);
    info.appendChild(crear("p", "registro__meta", partes.join(" · ")));

    info.appendChild(crear("span", "insignia", capitalizar(r.categoria)));
    if (r.prioridad === "alta") {
      info.appendChild(document.createTextNode(" "));
      info.appendChild(crear("span", "insignia insignia--alta", "Alta"));
    }

    if (alCambiar) {
      var acciones = crear("div", "registro__acciones");

      var editar = crear("a", "boton boton--secundario boton--chico", "Editar");
      editar.href = "registrar.php?editar=" + r.id;
      editar.setAttribute("aria-label", "Editar «" + r.titulo + "»");

      var borrar = crear("button", "boton boton--peligro boton--chico", "Eliminar");
      borrar.type = "button";
      borrar.setAttribute("aria-label", "Eliminar «" + r.titulo + "»");
      borrar.addEventListener("click", function () {
        confirmarYEliminar(r)
          .then(function () { return desvanecer(tarjeta); })
          .then(alCambiar)
          .catch(function (error) {
            if (error === null) return;   // canceló
            console.error(error);
            window.alert("No se pudo eliminar el registro. Intenta de nuevo.");
          });
      });

      acciones.append(editar, borrar);
      info.appendChild(acciones);
    }

    tarjeta.appendChild(info);
    return tarjeta;
  }

  // La tarjeta eliminada se encoge y desvanece antes de recargar la lista.
  // Si la pestaña queda en segundo plano el navegador pausa la animación; el tiempo
  // máximo evita que la lista se quede sin actualizar por eso.
  function desvanecer(elemento) {
    if (matchMedia("(prefers-reduced-motion: reduce)").matches || !elemento.animate) {
      return Promise.resolve();
    }
    var animacion = elemento.animate(
      [{ opacity: 1, transform: "scale(1)" }, { opacity: 0, transform: "scale(0.94)" }],
      { duration: 260, easing: "cubic-bezier(0.32, 0.72, 0, 1)", fill: "forwards" }
    );
    var tiempoMaximo = new Promise(function (listo) { setTimeout(listo, 400); });
    return Promise.race([animacion.finished, tiempoMaximo]);
  }

  function crearVacia(texto) {
    var vacia = crear("div", "tarjeta tarjeta--vacia");
    vacia.appendChild(crear("p", null, texto));
    return vacia;
  }

  // Reemplaza el contenido de una lista con tarjetas (escalonadas) o con un mensaje si está vacía
  function pintarLista(contenedor, registros, textoVacio, alCambiar) {
    var fragmento = document.createDocumentFragment();
    if (registros.length) {
      registros.forEach(function (r, i) {
        var tarjeta = crearTarjeta(r, alCambiar);
        tarjeta.style.setProperty("--orden", i);
        fragmento.appendChild(tarjeta);
      });
    } else {
      fragmento.appendChild(crearVacia(textoVacio));
    }
    contenedor.replaceChildren(fragmento);
  }

  return {
    MESES: MESES,
    DIAS_SEMANA: DIAS_SEMANA,
    cargar: cargar,
    confirmar: confirmar,
    confirmarYEliminar: confirmarYEliminar,
    clave: clave,
    desdeClave: desdeClave,
    esClave: esClave,
    capitalizar: capitalizar,
    hoy: hoy,
    nombreFecha: nombreFecha,
    cuando: cuando,
    pendientes: pendientes,
    crear: crear,
    crearVacia: crearVacia,
    pintarLista: pintarLista
  };
})();
