## 🧪 GUÍA RÁPIDA DE PRUEBA

Sigue estos pasos para verificar que el proyecto funciona completamente:

---

### PASO 1: Preparar la base de datos

```
1. Abre phpMyAdmin: http://localhost/phpmyadmin
2. En la pestaña "SQL", copia TODO el contenido de basedatos.sql
3. Ejecuta el SQL
4. Verifica que se crearon las tablas: usuarios, profesionales, resenas
```

---

### PASO 2: Crear tu primera cuenta (Turista)

```
URL: http://localhost/xampp/htdocs/oficioMar4/index.html

1. Haz clic en "Crear cuenta nueva"
2. Rellena:
   - Nombre: Tu Nombre
   - Email: turista@test.com
   - Contraseña: 123456
   - Rol: Soy turista (turista)
3. Haz clic en "Crear cuenta"
4. Verás: ✅ Registro exitoso
5. Inicia sesión con turista@test.com / 123456
```

**Resultado esperado**: 
- Entras a `inicio.php`
- Ves mensaje: "Todavía no hay profesionales registrados"
- Aparecen los chips de filtro (Todos, Plomero, Electricista, etc.)
- La barra de búsqueda funciona

---

### PASO 3: Crear una cuenta Profesional

```
URL: http://localhost/xampp/htdocs/oficioMar4/registro.html

1. Haz clic en "Crear cuenta nueva"
2. Rellena:
   - Nombre: Carlos Plomero
   - Email: profesional@test.com
   - Contraseña: 123456
   - Rol: Soy profesional (profesional)
3. Haz clic en "Crear cuenta"
4. Inicia sesión con profesional@test.com / 123456
```

**Resultado esperado**:
- Entras a `inicio2.php`
- Ves el panel profesional con stats
- Ves un formulario para completar tu perfil

---

### PASO 4: Completar el perfil profesional

```
En el panel profesional:

1. Rubro: Plomero
2. Zona de trabajo: Centro
3. Precio estimado: $8.000 – $15.000
4. Descripción: Plomero con 10 años de experiencia
5. Haz clic en "Guardar cambios"
```

**Resultado esperado**:
- Se guarda el perfil sin errores
- El toggle de "Disponible" sigue activo

---

### PASO 5: Volver como turista y verificar que aparece

```
1. Haz clic en "Cerrar sesión"
2. Inicia sesión con turista@test.com / 123456
3. Deberías ver a "Carlos Plomero" en la lista
```

**Resultado esperado**:
- Aparece la tarjeta del profesional
- Muestra: nombre, rubro (Plomero), zona (Centro)
- Badge: ● Disponible
- Precio: $8.000 – $15.000
- Botones: "Contactar" y "Ver perfil"

---

### PASO 6: Probar filtros

```
1. Haz clic en el chip "Plomero"
   → Solo Carlos debe aparecer

2. Haz clic en el chip "Todos" nuevamente
   → Debe mostrar todos

3. Escribe "Carlos" en la búsqueda
   → Solo Carlos debe aparecer

4. Escribe "Electricista" en la búsqueda
   → Debe ver "Sin resultados"
```

---

### PASO 7: Ver perfil del profesional

```
1. Haz clic en "Ver perfil" de Carlos
```

**Resultado esperado**:
- Se abre `perfil.php?id=2`
- Muestra: nombre, rubro, zona, precio, descripción
- Botones de contacto

---

### PASO 8: Crear más profesionales de prueba

```
Repite el PASO 3 con diferentes datos:

Usuario 2:
- Nombre: Ana Electricista
- Email: electricista@test.com
- Rol: Profesional
- Perfil: Rubro=Electricista, Zona="La Perla", Precio="$7.000 – $12.000"

Usuario 3:
- Nombre: Marcos Cerrajero
- Email: cerrajero@test.com
- Rol: Profesional
- Perfil: Rubro=Cerrajero, Zona="Centro", Precio="$6.000 – $10.000"
```

---

### ✅ CHECKLIST DE FUNCIONALIDAD

Verifica que todo funcione:

- [ ] Base de datos creada correctamente
- [ ] Registro de turista funciona
- [ ] Login funciona
- [ ] Registro de profesional funciona
- [ ] Perfil profesional se guarda
- [ ] Turistas ven profesionales en lista
- [ ] Filtros funcionan (por rubro)
- [ ] Búsqueda funciona
- [ ] Toggle de disponibilidad funciona
- [ ] Se pueden ver perfiles individuales
- [ ] Múltiples profesionales aparecen

---

### 🐛 Si algo no funciona:

1. **"No se puede conectar a BD"**
   - Verifica que MySQL está corriendo
   - Verifica credenciales en `conexion.php`

2. **"1054 Unknown column"**
   - Ejecuta nuevamente basedatos.sql completo
   - Borra la base de datos y crea de nuevo

3. **"Error 500 en login"**
   - Verifica que hay al menos un usuario en BD
   - Verifica la estructura de usuarios

4. **"Los profesionales no aparecen en la lista"**
   - Verifica que el usuario tiene rol='profesional'
   - Verifica que activo=1

---

**¡Si todo funciona, el proyecto está listo para la siguiente fase de mejoras!**
