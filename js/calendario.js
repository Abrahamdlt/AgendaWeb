// Vista mensual del calendario, registros del día seleccionado y el evento más cercano.
// Los registros llegan de registros.php (ver js/registros.js).
(function () {
  var MESES = Registros.MESES;
  var DIAS_SEMANA = Registros.DIAS_SEMANA;
  var MAX_EN_CELDA = 2;   // registros visibles por día; el resto se resume en "+N más"

  var clave = Registros.clave;
  var desdeClave = Registros.desdeClave;
  var capitalizar = Registros.capitalizar;
  var crear = Registros.crear;

  var tituloMes = document.getElementById("calendario-mes");
  var contenedorDias = document.getElementById("calendario-dias");
  var detalleTitulo = document.getElementById("detalle-titulo");
  var detalleLista = document.getElementById("detalle-lista");
  var detalleAgregar = document.getElementById("detalle-agregar");
  var proximoCuando = document.getElementById("proximo-cuando");
  var proximoLista = document.getElementById("proximo-lista");
  var proximoVerDia = document.getElementById("proximo-ver-dia");

  function sumarDias(fecha, n) {
    return new Date(fecha.getFullYear(), fecha.getMonth(), fecha.getDate() + n);
  }
  function mismoMes(a, b) {
    return a.getFullYear() === b.getFullYear() && a.getMonth() === b.getMonth();
  }
  function diasDelMes(anio, mes) {
    return new Date(anio, mes + 1, 0).getDate();
  }

  // ---------- Estado ----------
  var estado = "cargando";   // cargando | listo | error
  var todos = [];            // todos los registros (ya vienen ordenados de PHP)
  var porFecha = {};         // "AAAA-MM-DD" -> registros de ese día

  var hoy = new Date();
  hoy = new Date(hoy.getFullYear(), hoy.getMonth(), hoy.getDate());

  // Se puede abrir en una fecha concreta: calendario.html?fecha=AAAA-MM-DD
  var fechaInicial = new URLSearchParams(location.search).get("fecha");
  var seleccionado = Registros.esClave(fechaInicial) ? desdeClave(fechaInicial) : hoy;
  var vista = new Date(seleccionado.getFullYear(), seleccionado.getMonth(), 1);

  function agrupar(registros) {
    porFecha = {};
    registros.forEach(function (r) {
      (porFecha[r.fecha] = porFecha[r.fecha] || []).push(r);
    });
  }
  function registrosDe(fecha) {
    return porFecha[clave(fecha)] || [];
  }

  // ---------- Cuadrícula ----------
  function etiquetaAccesible(fecha, total) {
    var texto = DIAS_SEMANA[fecha.getDay()] + " " + fecha.getDate() + " de " +
                MESES[fecha.getMonth()] + " de " + fecha.getFullYear();
    if (clave(fecha) === clave(hoy)) texto += ", hoy";
    if (estado === "listo") {
      texto += total ? ", " + total + (total === 1 ? " registro" : " registros") : ", sin registros";
    }
    return texto;
  }

  function crearDia(fecha) {
    var registros = registrosDe(fecha);
    var boton = crear("button", "dia");
    boton.type = "button";
    boton.dataset.fecha = clave(fecha);
    boton.setAttribute("aria-label", etiquetaAccesible(fecha, registros.length));
    if (!mismoMes(fecha, vista)) boton.classList.add("dia--fuera");
    if (clave(fecha) === clave(hoy)) {
      boton.classList.add("dia--hoy");
      boton.setAttribute("aria-current", "date");
    }

    boton.appendChild(crear("span", "dia__numero", fecha.getDate()));

    if (registros.length) {
      // Con espacio: títulos. Compacto: puntos (el CSS decide cuál se ve).
      registros.slice(0, MAX_EN_CELDA).forEach(function (r) {
        var chip = crear("span", "dia__evento", r.titulo);
        chip.title = r.todo_el_dia || !r.hora_inicio ? r.titulo : r.hora_inicio + " · " + r.titulo;
        if (r.prioridad === "alta") chip.classList.add("dia__evento--alta");
        boton.appendChild(chip);
      });
      if (registros.length > MAX_EN_CELDA) {
        boton.appendChild(crear("span", "dia__mas", "+" + (registros.length - MAX_EN_CELDA) + " más"));
      }

      var puntos = crear("span", "dia__puntos");
      registros.slice(0, 3).forEach(function (r) {
        puntos.appendChild(crear("span", "dia__punto" + (r.prioridad === "alta" ? " dia__punto--alta" : "")));
      });
      boton.appendChild(puntos);
    }
    return boton;
  }

  function renderMes(direccion) {
    tituloMes.textContent = capitalizar(MESES[vista.getMonth()]) + " " + vista.getFullYear();

    // Siempre 6 semanas (42 días) para que la tarjeta no cambie de alto entre meses
    var desfase = (vista.getDay() + 6) % 7;   // la semana empieza en lunes
    var inicio = sumarDias(vista, -desfase);
    var fragmento = document.createDocumentFragment();
    for (var i = 0; i < 42; i++) fragmento.appendChild(crearDia(sumarDias(inicio, i)));
    contenedorDias.replaceChildren(fragmento);

    // Deslizamiento en la dirección del cambio de mes
    if (direccion) {
      contenedorDias.classList.remove("calendario__dias--siguiente", "calendario__dias--anterior");
      void contenedorDias.offsetWidth;   // reinicia la animación
      contenedorDias.classList.add(direccion > 0 ? "calendario__dias--siguiente" : "calendario__dias--anterior");
    }
  }

  function actualizarSeleccion() {
    var k = clave(seleccionado);
    contenedorDias.querySelectorAll(".dia").forEach(function (b) {
      var activo = b.dataset.fecha === k;
      b.setAttribute("aria-pressed", activo ? "true" : "false");
      b.tabIndex = activo ? 0 : -1;   // solo el día seleccionado entra en el orden de tabulación
    });
  }

  // ---------- Detalle del día ----------
  function renderDetalle() {
    detalleTitulo.textContent = Registros.nombreFecha(seleccionado);
    detalleAgregar.href = "registrar.php?fecha=" + clave(seleccionado);

    if (estado === "cargando") {
      detalleLista.replaceChildren(Registros.crearVacia("Cargando registros…"));
    } else if (estado === "error") {
      detalleLista.replaceChildren(Registros.crearVacia(
        "No se pudieron cargar los registros. Abre la página desde el servidor PHP."));
    } else {
      Registros.pintarLista(detalleLista, registrosDe(seleccionado), "No hay registros este día.",
                            recargarTrasEliminar);
    }
  }

  // ---------- Evento más cercano (columna derecha) ----------
  function renderProximo() {
    var proximo = estado === "listo" ? Registros.pendientes(todos)[0] : null;
    proximoVerDia.hidden = !proximo;

    if (estado === "cargando") {
      proximoCuando.textContent = "";
      proximoLista.replaceChildren(Registros.crearVacia("Cargando…"));
    } else if (!proximo) {
      proximoCuando.textContent = "";
      proximoLista.replaceChildren(Registros.crearVacia(
        estado === "error" ? "No se pudieron cargar los registros." : "No tienes eventos pendientes."));
    } else {
      var fecha = desdeClave(proximo.fecha);
      proximoCuando.textContent = Registros.cuando(fecha) + " · " + Registros.nombreFecha(fecha);
      Registros.pintarLista(proximoLista, [proximo], "", recargarTrasEliminar);
      proximoVerDia.href = "calendario.html?fecha=" + proximo.fecha;
    }
  }

  // "Ver ese día" selecciona la fecha del próximo evento sin recargar la página
  proximoVerDia.addEventListener("click", function (e) {
    var fecha = new URL(proximoVerDia.href).searchParams.get("fecha");
    if (!Registros.esClave(fecha)) return;
    e.preventDefault();
    seleccionar(desdeClave(fecha), true);
  });

  // ---------- Acciones ----------
  function seleccionar(fecha, enfocar) {
    var direccion = 0;
    if (!mismoMes(fecha, vista)) {
      direccion = fecha > vista ? 1 : -1;
      vista = new Date(fecha.getFullYear(), fecha.getMonth(), 1);
    }
    seleccionado = fecha;
    if (direccion) renderMes(direccion);
    actualizarSeleccion();
    renderDetalle();
    if (enfocar) {
      var boton = contenedorDias.querySelector('[data-fecha="' + clave(fecha) + '"]');
      if (boton) boton.focus();
    }
  }

  // Cambia de mes conservando el número de día (ajustado si el mes es más corto)
  function cambiarMes(n, enfocar) {
    var destino = new Date(vista.getFullYear(), vista.getMonth() + n, 1);
    var dia = Math.min(seleccionado.getDate(), diasDelMes(destino.getFullYear(), destino.getMonth()));
    seleccionar(new Date(destino.getFullYear(), destino.getMonth(), dia), enfocar);
  }

  contenedorDias.addEventListener("click", function (e) {
    var boton = e.target.closest(".dia");
    if (boton) seleccionar(desdeClave(boton.dataset.fecha), true);
  });

  contenedorDias.addEventListener("keydown", function (e) {
    var pasos = { ArrowLeft: -1, ArrowRight: 1, ArrowUp: -7, ArrowDown: 7 };
    if (e.key in pasos) {
      e.preventDefault();
      seleccionar(sumarDias(seleccionado, pasos[e.key]), true);
    } else if (e.key === "PageUp" || e.key === "PageDown") {
      e.preventDefault();
      cambiarMes(e.key === "PageUp" ? -1 : 1, true);
    } else if (e.key === "Home" || e.key === "End") {
      // Inicio / fin de la semana (lunes a domingo)
      e.preventDefault();
      var posicion = (seleccionado.getDay() + 6) % 7;
      seleccionar(sumarDias(seleccionado, e.key === "Home" ? -posicion : 6 - posicion), true);
    }
  });

  document.getElementById("calendario-anterior").addEventListener("click", function () { cambiarMes(-1); });
  document.getElementById("calendario-siguiente").addEventListener("click", function () { cambiarMes(1); });
  document.getElementById("calendario-hoy").addEventListener("click", function () { seleccionar(hoy); });

  // La cuadrícula se pinta de inmediato; los registros se agregan cuando responde PHP
  function pintarTodo() {
    renderMes(0);
    actualizarSeleccion();
    renderDetalle();
    renderProximo();
  }
  function cargarRegistros(enfocarDia) {
    return Registros.cargar()
      .then(function (registros) {
        todos = registros;
        agrupar(registros);
        estado = "listo";
      })
      .catch(function (error) {
        console.error(error);
        estado = "error";
      })
      .then(function () {
        // Conserva el foco si el usuario ya estaba navegando la cuadrícula
        var teniaFoco = contenedorDias.contains(document.activeElement);
        pintarTodo();
        if (teniaFoco || enfocarDia) contenedorDias.querySelector('[tabindex="0"]').focus();
      });
  }

  // Al eliminar, la tarjeta (y su botón con el foco) desaparece: el foco vuelve al día seleccionado
  function recargarTrasEliminar() {
    return cargarRegistros(true);
  }

  pintarTodo();
  cargarRegistros(false);
})();
