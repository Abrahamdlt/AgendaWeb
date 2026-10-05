# AgendaWeb

Aplicación web para registrar y organizar eventos y citas: formulario de registro,
calendario mensual y edición / eliminación de registros guardados en MySQL.

Hecha con HTML, CSS y JavaScript (sin frameworks), PHP y MySQL.

## Requisitos

- PHP 8.1 o superior con la extensión `mysqli`
- MySQL 8 (o compatible)

> GitHub Pages solo sirve archivos estáticos: ahí se ve el diseño, pero el calendario
> y el formulario necesitan PHP y MySQL para funcionar.

## Cómo correrlo

1. Crea la base de datos con sus tablas y datos iniciales (funciona en MySQL 8 y MariaDB):

   ```bash
   mysql -u root -p -e "CREATE DATABASE agenda CHARACTER SET utf8mb4"
   mysql -u root -p agenda < bd/instalar_agenda.sql
   ```

2. Copia `conexion.ejemplo.php` como `conexion.php` y pon tu usuario, contraseña y nombre
   de la base. `conexion.php` está en `.gitignore`: los datos de acceso nunca se suben a GitHub.

3. Desde la carpeta del proyecto, levanta el servidor de PHP y abre http://localhost:8080

   ```bash
   php -S localhost:8080
   ```

## Estructura

| Archivo | Qué hace |
|---|---|
| `index.php` | Página principal **Eventos**: todos los pendientes agrupados por día. Muestra el mensaje de éxito tras guardar, editar o eliminar (`index.php?ok=...`) |
| `registrar.php` | Formulario para crear o editar un registro (`registrar.php?editar=ID`) y lista "Próximos". Valida en el servidor, vuelve a mostrar lo escrito si hay errores y usa PRG (redirige a `index.php?ok=...`) |
| `validacion.php` | Campos obligatorios, formatos y listas blancas del formulario |
| `ayudas.php` | Funciones pequeñas que comparten las páginas PHP |
| `datos.php` | Consultas preparadas a la base (leer, guardar, actualizar, eliminar) |
| `conexion.ejemplo.php` | Plantilla de `conexion.php` (conexión a MySQL, sin datos reales) |
| `calendario.php` | Vista mensual, registros del día seleccionado y el evento más cercano |
| `registros.php` | Devuelve los registros en JSON (calendario y "Próximos") |
| `eliminar.php` | Elimina un registro |
| `bd/instalar_agenda.sql` | Crea las tablas y los datos iniciales |
| `bd/respaldo_agenda_antes.sql`, `bd/actualizar_agenda.sql` | Base original y cómo se actualizó (historial) |
| `css/styles.css` | Estilos (sistema de marca "Pulso Neón", modo claro/oscuro) |
| `js/` | Calendario, formulario, lista "Próximos" y cambio de tema |
