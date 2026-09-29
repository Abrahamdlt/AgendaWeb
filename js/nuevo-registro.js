// Formulario de registro: crear (index.html) y editar (index.html?editar=ID).
// Envía a guardar.php sin recargar la página; elimina con eliminar.php.
(function () {
  var formulario = document.getElementById("formulario-registro");
  if (!formulario) return;

  var campoId = document.getElementById("registro-id");
  var campoFecha = document.getElementById("fecha");
  var horaInicio = document.getElementById("hora-inicio");
  var horaFin = document.getElementById("hora-fin");
  var todoElDia = document.getElementById("todo-el-dia");
  var aviso = document.getElementById("aviso");
  var botonGuardar = formulario.querySelector('button[type="submit"]');
  var botonLimpiar = document.getElementById("boton-limpiar");
  var botonEliminar = document.getElementById("boton-eliminar");

  var parametros = new URLSearchParams(location.search);
  var textoGuardar = "Guardar registro";
  var registroEditado = null;   // el registro tal como está en la base (solo en modo edición)

  // ---------- "Todo el día" desactiva las horas ----------
  function actualizarHoras() {
    var desactivar = todoElDia.checked;
    horaInicio.disabled = desactivar;
    horaFin.disabled = desactivar;
    horaInicio.required = !desactivar;
  }
  todoElDia.addEventListener("change", actualizarHoras);

  // El evento "reset" llega antes de que el navegador restaure los campos, por eso el setTimeout.
  // Tras guardar un registro nuevo también se limpia, pero ahí el aviso de éxito debe quedarse.
  var conservarAviso = false;
  formulario.addEventListener("reset", function () {
    setTimeout(function () {
      actualizarHoras();
      limpiarErrores();
      if (!conservarAviso) ocultarAviso();
      conservarAviso = false;
    });
  });

  // ---------- Errores por campo ----------
  function limpiarErrores() {
    formulario.querySelectorAll(".campo__error").forEach(function (e) { e.remove(); });
    formulario.querySelectorAll('[aria-invalid="true"]').forEach(function (c) {
      c.removeAttribute("aria-invalid");
      c.removeAttribute("aria-describedby");
    });
  }

  function mostrarErrores(errores) {
    var primero = null;
    Object.keys(errores).forEach(function (nombre) {
      var control = formulario.querySelector('[name="' + nombre + '"]');
      var campo = control && control.closest(".campo");
      if (!campo) return;
      var mensaje = Registros.crear("p", "campo__error", errores[nombre]);
      mensaje.id = "error-" + nombre;
      campo.appendChild(mensaje);
      // Los radios comparten un mensaje en su fieldset; el resto se marca campo por campo
      if (control.type !== "radio") {
        control.setAttribute("aria-invalid", "true");
        control.setAttribute("aria-describedby", mensaje.id);
      }
      if (!primero) primero = control;
    });
    if (primero) primero.focus();
  }

  // Al corregir un campo se quita su error
  formulario.addEventListener("input", function (e) {
    var nombre = e.target.name;
    var mensaje = nombre && document.getElementById("error-" + nombre);
    if (mensaje) {
      mensaje.remove();
      e.target.removeAttribute("aria-invalid");
      e.target.removeAttribute("aria-describedby");
    }
  });

  // ---------- Aviso general ----------
  function mostrarAviso(texto, esError, enlace) {
    aviso.replaceChildren(Registros.crear("span", null, texto));
    if (enlace) {
      var a = Registros.crear("a", "enlace enlace--activo", enlace.texto);
      a.href = enlace.href;
      aviso.appendChild(a);
    }
    aviso.classList.toggle("aviso--error", Boolean(esError));
    aviso.hidden = false;
  }
  function ocultarAviso() {
    aviso.hidden = true;
  }

  // ---------- Modo edición ----------
  // Pone los datos del registro en el formulario. También los fija como valores "por defecto",
  // así el botón de reinicio ("Deshacer cambios") regresa a lo que está guardado.
  function llenarFormulario(r) {
    ["titulo", "fecha", "hora_inicio", "hora_fin", "lugar", "persona", "notas"].forEach(function (nombre) {
      var campo = formulario.elements[nombre];
      campo.value = campo.defaultValue = r[nombre] || "";
    });
    ["tipo", "prioridad"].forEach(function (nombre) {
      formulario.querySelectorAll('[name="' + nombre + '"]').forEach(function (radio) {
        radio.checked = radio.defaultChecked = radio.value === r[nombre];
      });
    });
    ["categoria", "recordatorio"].forEach(function (nombre) {
      Array.prototype.forEach.call(formulario.elements[nombre].options, function (opcion) {
        opcion.selected = opcion.defaultSelected = opcion.value === String(r[nombre]);
      });
    });
    todoElDia.checked = todoElDia.defaultChecked = Boolean(r.todo_el_dia);
    campoId.value = campoId.defaultValue = r.id;
    actualizarHoras();
  }

  function activarModoEdicion(r) {
    registroEditado = r;
    llenarFormulario(r);
    document.title = "AgendaWeb · Editar registro";
    document.getElementById("formulario-titulo").textContent = "Editar registro";
    document.getElementById("formulario-subtitulo").textContent = "Cambia lo que necesites y guarda los cambios.";
    textoGuardar = "Guardar cambios";
    botonGuardar.textContent = textoGuardar;
    botonLimpiar.textContent = "Deshacer cambios";
    botonEliminar.hidden = false;
  }

  botonEliminar.addEventListener("click", function () {
    Registros.confirmarYEliminar(registroEditado)
      .then(function () {
        location.href = "calendario.html?fecha=" + registroEditado.fecha;
      })
      .catch(function (error) {
        if (error === null) return;   // canceló
        console.error(error);
        mostrarAviso("No se pudo eliminar el registro. Intenta de nuevo.", true);
      });
  });

  var idEditar = parametros.get("editar");
  if (idEditar && /^\d+$/.test(idEditar)) {
    Registros.obtener(idEditar)
      .then(activarModoEdicion)
      .catch(function (error) {
        console.error(error);
        mostrarAviso("No se encontró ese registro; puede que ya se haya eliminado.", true,
                     { texto: "Ir al calendario", href: "calendario.html" });
      });
  } else {
    // Fecha que viene del calendario (index.html?fecha=AAAA-MM-DD)
    var fecha = parametros.get("fecha");
    if (Registros.esClave(fecha)) campoFecha.value = fecha;
  }
  actualizarHoras();

  // ---------- Envío ----------
  formulario.addEventListener("submit", function (e) {
    e.preventDefault();
    limpiarErrores();
    ocultarAviso();
    botonGuardar.disabled = true;
    botonGuardar.textContent = "Guardando…";

    fetch(formulario.action, {
      method: "POST",
      body: new FormData(formulario),
      headers: { Accept: "application/json" }
    })
      .then(function (respuesta) {
        return respuesta.json().then(function (datos) { return { estado: respuesta.status, datos: datos }; });
      })
      .then(function (r) {
        if (r.datos.ok) {
          var guardado = r.datos.registro;
          var enlace = { texto: "Ver en el calendario", href: "calendario.html?fecha=" + guardado.fecha };
          if (registroEditado) {
            // Editando: el formulario se queda con los datos nuevos
            registroEditado = guardado;
            llenarFormulario(guardado);
            mostrarAviso("Se guardaron los cambios de «" + guardado.titulo + "».", false, enlace);
          } else {
            conservarAviso = true;
            formulario.reset();
            mostrarAviso("Se guardó «" + guardado.titulo + "».", false, enlace);
          }
          if (window.Proximos) Proximos.actualizar();
        } else if (r.estado === 422 && r.datos.errores) {
          mostrarErrores(r.datos.errores);
          mostrarAviso("Revisa los campos marcados.", true);
        } else if (r.estado === 404) {
          mostrarAviso("Este registro ya no existe; puede que se haya eliminado.", true,
                       { texto: "Ir al calendario", href: "calendario.html" });
        } else {
          throw new Error("Respuesta inesperada de guardar.php");
        }
      })
      .catch(function (error) {
        console.error(error);
        mostrarAviso("No se pudo guardar. Revisa que la página esté abierta desde el servidor PHP.", true);
      })
      .then(function () {
        botonGuardar.disabled = false;
        botonGuardar.textContent = textoGuardar;
      });
  });
})();
