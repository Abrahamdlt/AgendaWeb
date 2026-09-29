// Lista "Próximos" de la página del formulario: los siguientes registros desde hoy.
var Proximos = (function () {
  var CUANTOS = 3;
  var lista = document.getElementById("proximos-lista");

  function actualizar() {
    if (!lista) return;
    var hoy = Registros.clave(new Date());
    Registros.cargar()
      .then(function (registros) {
        // registros.php ya los devuelve ordenados por fecha y hora
        var siguientes = registros.filter(function (r) { return r.fecha >= hoy; }).slice(0, CUANTOS);
        Registros.pintarLista(lista, siguientes, "No hay registros próximos.", actualizar);
      })
      .catch(function (error) {
        console.error(error);
        lista.replaceChildren(Registros.crearVacia(
          "No se pudieron cargar los registros. Abre la página desde el servidor PHP."));
      });
  }

  actualizar();
  return { actualizar: actualizar };   // el formulario la vuelve a llamar después de guardar
})();
