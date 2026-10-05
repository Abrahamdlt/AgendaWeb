// Mejoras del formulario de index.php. El envío, la validación y la redirección los hace PHP;
// esto solo agrega detalles que no requieren recargar la página.
(function () {
  var formulario = document.getElementById("formulario-registro");
  if (!formulario) return;

  // ---------- "Todo el día" desactiva las horas ----------
  var horaInicio = document.getElementById("hora-inicio");
  var horaFin = document.getElementById("hora-fin");
  var todoElDia = document.getElementById("todo-el-dia");

  todoElDia.addEventListener("change", function () {
    var desactivar = todoElDia.checked;
    horaInicio.disabled = desactivar;
    horaFin.disabled = desactivar;
    horaInicio.required = !desactivar;
  });

  // ---------- Al corregir un campo se quita su mensaje de error ----------
  formulario.addEventListener("input", function (e) {
    var mensaje = e.target.name && document.getElementById("error-" + e.target.name);
    if (mensaje) {
      mensaje.remove();
      e.target.removeAttribute("aria-invalid");
      e.target.removeAttribute("aria-describedby");
    }
  });

  // ---------- Eliminar (solo aparece al editar) ----------
  var botonEliminar = document.getElementById("boton-eliminar");
  if (botonEliminar) {
    botonEliminar.addEventListener("click", function () {
      var registro = { id: botonEliminar.dataset.id, titulo: botonEliminar.dataset.titulo };
      Registros.confirmarYEliminar(registro)
        .then(function () {
          location.href = "index.php?ok=eliminado";
        })
        .catch(function (error) {
          if (error === null) return;   // canceló
          console.error(error);
          window.alert("No se pudo eliminar el registro. Intenta de nuevo.");
        });
    });
  }
})();
