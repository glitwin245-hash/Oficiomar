# OficioMar

   Aplicación web que ayuda a los turistas que visitan Mar del Plata a encontrar
   rápido un profesional de confianza (plomería, electricidad, gas, mecánica)
   cuando les pasa una urgencia durante sus vacaciones.

   ## Roles
   - **Turista:** se registra, inicia sesión y ve los profesionales con su rubro,
     experiencia, tarifas y zona.
   - **Profesional:** se registra y carga su información para que lo contacten.
   - **Administrador:** controla usuarios y perfiles.

   ## Tecnologías
   HTML, CSS, JavaScript, PHP y MySQL (XAMPP). Editor: Visual Studio Code.

   ## Estructura del repositorio
   - `client/`: frontend (páginas, estilos, scripts, imágenes)
   - `server/`: backend en PHP (conexión, login, registro, etc.)
   - `database/`: scripts SQL de la base de datos
   - `docs/`: documentación del proyecto (PDF)

   ## Cómo ejecutarlo
   1. Instalar XAMPP e iniciar Apache y MySQL.
   2. Copiar la carpeta del proyecto dentro de `C:\xampp\htdocs`.
   3. Entrar a phpMyAdmin, crear la base `oficioMar` e importar el archivo SQL de `database/`.
   4. Verificar los datos de conexión en `server/conexion.php`.
   5. Abrir en el navegador `http://localhost/oficiomar`.

   ## Autor
   Gabriel Emanuel Litwin
