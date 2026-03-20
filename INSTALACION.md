# VIZENGO - Sistema de Gestión de Pedidos para Tienda de Ropa Deportiva

Sistema web completo para la gestión de pedidos de ropa deportiva por encargo, con roles diferenciados para Vendedor, Diseñador y Administrador.

## 📋 Características

### Roles de Usuario

1. **Vendedor**
   - Registro de contrato del pedido (Paso 1)
   - Registro de integrantes del equipo (Paso 2)
   - Registro de datos de entrega (Paso 6)

2. **Diseñador**
   - Selección de pedido y subida de diseño final (Paso 3)
   - Registro de datos de planchado (Paso 4)
   - Registro de datos de costura (Paso 5)

3. **Administrador**
   - Acceso completo a todas las etapas
   - Gestión de usuarios
   - Reportes y estadísticas

### Ciclo del Pedido

```
1. Contrato → 2. Integrantes → 3. Diseño → 4. Planchado → 5. Costura → 6. Entrega
```

## 🚀 Instalación en Cpanel

### Requisitos Previos

- Cpanel con acceso a MySQL
- PHP 7.4 o superior
- MySQL 5.7 o superior

### Paso 1: Base de Datos

1. Ingresa a **cPanel** → **MySQL Databases**
2. Crea una nueva base de datos (ej: `tuusuario_vizengo`)
3. Crea un usuario de base de datos con contraseña segura
4. Asigna el usuario a la base de datos con **TODOS LOS PRIVILEGIOS**

### Paso 2: Configurar Conexión

Edita el archivo `backend/config/database.php`:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'tuusuario_vizengo');  // Tu base de datos
define('DB_USER', 'tuusuario_dbuser');    // Tu usuario
define('DB_PASS', 'tu_contraseña_segura');
```

### Paso 3: Importar Estructura

1. Ingresa a **cPanel** → **phpMyAdmin**
2. Selecciona tu base de datos
3. Ve a la pestaña **Importar**
4. Sube el archivo `backend/config/schema.sql`
5. Ejecuta la importación

### Paso 4: Subir Archivos

1. Comprime todos los archivos del proyecto en un ZIP
2. En **cPanel** → **File Manager**, ve a `public_html`
3. Sube y extrae el ZIP
4. Asegúrate de que las carpetas de uploads tengan permisos de escritura:
   - `assets/uploads/disenos/` (755 o 777)
   - `assets/uploads/integrantes/` (755 o 777)

### Paso 5: Verificar Instalación

Accede a tu dominio: `https://tudominio.com`

**Usuarios por defecto:**
- Admin: `admin` / `admin`
- Vendedor: `luis` / `123`
- Diseñador: `carolina` / `123`

## 📁 Estructura del Proyecto

```
/
├── backend/
│   ├── api/
│   │   ├── auth.php          # Autenticación
│   │   ├── pedidos.php       # Gestión de pedidos
│   │   ├── integrantes.php   # Gestión de integrantes
│   │   ├── disenos.php       # Subida de diseños
│   │   └── proceso.php       # Planchado y costura
│   └── config/
│       ├── database.php      # Configuración DB
│       ├── Database.php      # Clase de conexión
│       └── schema.sql        # Estructura DB
├── includes/
│   └── auth.php              # Funciones de autenticación
├── assets/
│   ├── css/                  # Estilos personalizados
│   ├── js/
│   │   └── app.js            # JavaScript principal
│   └── uploads/
│       ├── disenos/          # Diseños subidos
│       └── integrantes/      # Imágenes de integrantes
├── index.html                # Login
├── dashboard.html            # Panel principal
├── ingreso-pedido.html       # Registro de contrato
├── registro-integrantes.html # Registro de integrantes
├── diseno.html               # Subida de diseños
├── planchado.html            # Registro de planchado
├── costura.html              # Registro de costura
├── entrega.html              # Registro de entrega
├── lista-pedidos.html        # Listado de pedidos
├── seguimiento.html          # Seguimiento de pedidos
└── usuarios.html             # Gestión de usuarios
```

## 🔐 Seguridad

### Contraseñas por Defecto

**IMPORTANTE:** Cambia las contraseñas por defecto después de instalar!

Para generar nuevos hashes de contraseña:

```php
<?php
echo password_hash('tu_nueva_contraseña', PASSWORD_DEFAULT);
?>
```

Luego actualiza en la tabla `usuarios`:

```sql
UPDATE usuarios SET password_hash = 'nuevo_hash_generado' WHERE nombre_usuario = 'admin';
```

### Recomendaciones de Seguridad

1. Cambia todas las contraseñas por defecto
2. Usa HTTPS en producción
3. Actualiza regularmente PHP y MySQL
4. Realiza copias de seguridad periódicas de la base de datos
5. Limita el tamaño de archivos subidos según necesites

## 🛠️ API Endpoints

### Autenticación
- `POST backend/api/auth.php?accion=login` - Iniciar sesión
- `GET backend/api/auth.php?accion=logout` - Cerrar sesión
- `GET backend/api/auth.php?accion=verificar` - Verificar sesión

### Pedidos
- `GET backend/api/pedidos.php?accion=listar` - Listar pedidos
- `GET backend/api/pedidos.php?accion=obtener&id=X` - Obtener pedido
- `POST backend/api/pedidos.php?accion=crear` - Crear pedido
- `POST backend/api/pedidos.php?accion=actualizar` - Actualizar pedido
- `POST backend/api/pedidos.php?accion=actualizar_estado` - Cambiar estado

### Integrantes
- `GET backend/api/integrantes.php?accion=listar&pedido_id=X` - Listar integrantes
- `POST backend/api/integrantes.php?accion=agregar` - Agregar integrante
- `POST backend/api/integrantes.php?accion=eliminar` - Eliminar integrante

### Diseños
- `GET backend/api/disenos.php?accion=listar` - Listar diseños
- `POST backend/api/disenos.php?accion=subir` - Subir diseño

### Proceso (Planchado/Costura)
- `GET backend/api/proceso.php?tipo=planchado&accion=obtener&pedido_id=X`
- `POST backend/api/proceso.php?tipo=planchado&accion=guardar`
- `GET backend/api/proceso.php?tipo=costura&accion=obtener&pedido_id=X`
- `POST backend/api/proceso.php?tipo=costura&accion=guardar`

## 🎨 Personalización

### Colores de la Marca

Los colores están definidos en las variables CSS de cada archivo HTML:

```css
:root {
    --primary: #2B4FFF;      /* Azul principal */
    --accent: #FFD23F;       /* Amarillo acento */
    --success: #06d6a0;      /* Verde éxito */
    --danger: #ef476f;       /* Rojo error */
}
```

### Logo y Branding

Busca y reemplaza "VIZENGO" en los archivos HTML para personalizar la marca.

## 📊 Base de Datos

### Tablas Principales

- `usuarios` - Usuarios del sistema
- `pedidos` - Pedidos/contratos
- `integrantes` - Integrantes de cada pedido
- `disenos` - Diseños subidos
- `planchados` - Registro de planchado
- `costuras` - Registro de costura
- `entregas` - Registro de entregas
- `seguimiento` - Historial de cambios de estado

## 🔧 Solución de Problemas

### Error de Conexión a la Base de Datos

1. Verifica las credenciales en `backend/config/database.php`
2. Asegúrate de que el usuario tenga privilegios
3. Verifica que el nombre de la base de datos sea correcto

### Error al Subir Archivos

1. Verifica los permisos de las carpetas `uploads`
2. Revisa el límite de subida en php.ini (`upload_max_filesize`)
3. Verifica `MAX_FILE_SIZE` en `backend/config/database.php`

### Error de Sesión

1. Limpia las cookies del navegador
2. Verifica que `session_start()` se ejecute correctamente
3. Revisa la configuración de sesiones en PHP

## 📞 Soporte

Para soporte técnico o personalizaciones adicionales, contacta al administrador del sistema.

---

**© 2025 VIZENGO - Sistema de Gestión de Pedidos**

Desarrollado con PHP, MySQL, HTML, JavaScript y Bootstrap
