# SIAMTEL - Sistema de Información y Gestión

**Gobernación del Estado Lara**

Bienvenido al repositorio oficial del sistema SIAMTEL. Este proyecto está construido bajo una arquitectura **MVC Modular** en **PHP 8.2**, servido mediante **Apache**, con base de datos **MariaDB** y contenedorizado mediante **Docker Compose**.

---

## 🚀 Inicio Rápido (Linux y Windows)

El entorno está preparado para ejecutarse de manera idéntica tanto en Linux como en Windows (Docker Desktop / WSL2).

### 1. Clonar el repositorio

```bash
git clone <URL_DEL_REPOSITORIO>
cd siamtel-system
```

### 2. Configurar variables de entorno

Crea tu archivo local `.env` a partir de la plantilla:

```bash
cp .envexample .env
```

> **Nota para usuarios de Windows:** Si ya tienes XAMPP o MySQL local corriendo en el puerto `3306`, cambia en tu `.env`:
>
> ```env
> DB_PORT=3307
> ```

### 3. Levantar los contenedores

Puedes usar cualquiera de los dos modos de desarrollo:

* **Modo estándar en segundo plano (Recomendado):**

  ```bash
  docker --env-file .env compose up -d
  ```

* **Modo observador con recarga automática:**

### 4. Accesos directos en el navegador

* 🌐 **Aplicación Web:** `http://localhost:${WEB_PORT}` (ej: [http://localhost:8080](http://localhost:8080))
* 🗄️ **phpMyAdmin:** `http://localhost:${PMA_PORT}` (ej: [http://localhost:8088](http://localhost:8088))
  * **Servidor:** `siamtel_db`
  * **Usuario:** `root` (o el indicado en `DB_USER` de tu `.env`)
  * **Contraseña:** la definida en `DB_ROOT_PASSWORD` de tu `.env`

---

## 📁 Estructura del Proyecto

El código fuente sigue un estándar modular organizado por responsabilidades:

```text
siamtel-system/
├── app/
│   ├── Controllers/             # Controladores organizados por módulo (Archivos de Script)
│   │   └── Auth/
│   │       └── AuthController.php
│   ├── Core/                    # Núcleo del framework MVC y clases base
│   │   ├── Database.php         # Clase abstracta con conexión PDO Singleton
│   │   ├── Helpers.php          # Funciones globales auxiliares (view, redirect, json)
│   │   └── Model.php            # Clase abstracta base para todos los modelos
│   ├── Models/                  # Modelos de negocio y datos
│   │   ├── Security/            # Modelos de seguridad (Usuario, Roles, etc.)
│   │   └── System/              # Modelos funcionales (Cliente, etc.)
│   └── Views/                   # Vistas organizadas por módulo
│       └── auth/
│           └── login.php
├── public/                      # DocumentRoot público accesible por la web
│   ├── .htaccess                # Reescritura de URLs para el Front Controller
│   ├── index.php                # Front Controller y Enrutador inteligente
│   ├── css/                     # Hojas de estilo por módulo (ej: css/auth/login.css)
│   ├── js/                      # Scripts JavaScript por módulo
│   └── img/                     # Recursos gráficos e imágenes
├── docker-compose.yml           # Definición de servicios (Web, DB, phpMyAdmin)
├── php.dockerfile               # Configuración del contenedor PHP 8.2 + Apache
├── composer.json                # Autoload PSR-4 ("App\\": "app/")
├── .envexample                  # Plantilla de variables de entorno
└── .gitattributes               # Normalización de saltos de línea (LF)
```

---

## 📐 Estándares de Código y Nomenclatura

Para mantener coherencia en el equipo y evitar fallos de portabilidad entre Windows y Linux (sensibilidad a mayúsculas/minúsculas), todo el equipo debe seguir estas reglas:

| Elemento | Convención | Ejemplo | Dónde se aplica |
| :--- | :--- | :--- | :--- |
| **Clases, Interfaces y Traits** | `PascalCase` | `Usuario`, `Cliente`, `Database`, `Model` | Nombres de clases y modelos en `app/Models/` y `app/Core/` |
| **Archivos de Controlador** | `PascalCase` | `AuthController.php`, `DashboardController.php` | Archivos de script en `app/Controllers/{Modulo}/` |
| **Directorios en `app/`** | `PascalCase` | `app/Controllers/Auth/`, `app/Models/Security/` | Carpetas bajo `app/` |
| **Métodos y Funciones** | `camelCase` | `authProcessLogin()`, `getConnection()`, `findByCedula()` | Funciones en controladores, métodos en modelos y helpers |
| **Variables y Propiedades** | `camelCase` | `$usuarioId`, `$loginSettings`, `$cedula` | Variables locales y propiedades de clase |
| **Constantes y Variables `.env`** | `UPPER_SNAKE_CASE` | `BASE_URL`, `DB_PASSWORD`, `WEB_PORT` | Constantes de PHP (`define`) y claves de `.env` |
| **Tablas y Columnas de BD** | `snake_case` | `tbl_usuario`, `fecha_nacimiento`, `estatus_clave` | Base de datos MariaDB/MySQL |
| **Rutas y Archivos Públicos** | `kebab-case` o `snake_case` | `/crear-cuenta`, `login.css`, `app.js` | URLs del navegador, archivos en `public/css/`, `public/js/` y vistas en `app/Views/` |

---

## 🏛️ Principios SOLID y Clean Code Aplicados

1. **S - Responsabilidad Única (Single Responsibility Principle):**
   * En los **Controladores**, en lugar de bloques gigantescos con condicionales anidados, cada acción se delega a una función específica (`authProcessLogin()`, `authProcessRegister()`, `authVerifyCedula()`, etc.).
   * Los **Modelos** solo se encargan del acceso a datos y reglas de negocio.
   * La clase abstracta **`Database`** solo gestiona el ciclo de vida de la conexión PDO.
2. **O - Abierto / Cerrado (Open/Closed Principle):**
   * La infraestructura base (`Model`, `Database`, `Helpers`) no necesita modificarse para agregar nuevas pantallas o módulos.
3. **L - Sustitución de Liskov:**
   * Cualquier modelo hereda de `Model`, manteniendo un comportamiento predecible y consistente en el acceso a datos.
4. **I - Segregación e Independencia:**
   * Funciones y métodos pequeños, con nombres declarativos y una sola tarea concreta.
5. **D - Inversión de Dependencias:**
   * Los modelos dependen de la abstracción `Model` y `Database`, no de llamadas manuales a `new PDO()` en cada archivo.

---

## 🗄️ Manejo de Base de Datos (Conexión Reutilizable)

Para evitar abrir conexiones PDO innecesarias en cada consulta:

* La clase abstracta `App\Core\Database` mantiene una **conexión Singleton compartida**.
* Todos los modelos deben heredar de `App\Core\Model`.

### Ejemplo: Creando un Modelo limpio

Crea tu archivo en `app/Models/Security/Usuario.php`:

```php
<?php

namespace App\Models\Security;

use App\Core\Model;

class Usuario extends Model
{
    /**
     * Busca un usuario por su cédula reutilizando la conexión base
     */
    public function findByCedula(string $cedula): ?array
    {
        $sql = "SELECT * FROM usuario WHERE cedula = ? LIMIT 1";
        return $this->fetchOne($sql, [$cedula]);
    }

    /**
     * Inserta un nuevo usuario
     */
    public function create(string $cedula, string $username, string $claveHash): int
    {
        $sql = "INSERT INTO usuario (cedula, username, clave, estatus) VALUES (?, ?, ?, 1)";
        return $this->execute($sql, [$cedula, $username, $claveHash]);
    }
}
```

---

## 🛠️ Guía de Desarrollo para el Equipo

### ¿Cómo crear una nueva pantalla (Vista) para avanzar UI/UX?

Si solo estás maquetando diseño sin lógica backend:

1. Crea tu archivo de vista en: `app/Views/modulo/nombre.php` (ej: `app/Views/dashboard/index.php`).
2. Crea tus estilos en: `public/css/modulo/nombre.css`.
3. Enlaza el CSS en tu vista:

   ```html
   <link rel="stylesheet" href="/css/modulo/nombre.css">
   ```

4. Ya puedes abrirla en el navegador en `http://localhost:8080/modulo/nombre`.

### ¿Cómo crear un nuevo Controlador?

Cuando vayas a conectar datos o formularios:

1. Crea tu archivo en: `app/Controllers/{Modulo}/{Nombre}Controller.php` (ej: `app/Controllers/Dashboard/DashboardController.php`).
2. Recuerda que los controladores **son archivos de script (no clases)**:

   ```php
   <?php

   declare(strict_types=1);

   // Importa tus modelos si los necesitas
   // use App\Models\Security\Usuario;

   $type = $_REQUEST['type'] ?? 'index';

   if ($type === 'index') {
       // Procesamiento de datos o peticiones...

       // Renderiza la vista con la función global view()
       view('dashboard/index', [
           'titulo' => 'Panel de Control - SIAMTEL'
       ]);
   }
   ```

3. El enrutador [public/index.php](file:///home/gimz/proyectos/siamtel-system/public/index.php) lo despachará automáticamente cuando visites `/dashboard` o `/modulo/nombre`.

---

## 🤝 Soporte y Buenas Prácticas de Git

* **No subir `.env`:** Cada desarrollador tiene su propia configuración local.
* **No subir `vendor/`:** Se genera automáticamente con `composer install` o `composer dump-autoload` dentro del contenedor.
* **Saltos de línea:** `.gitattributes` fuerza automáticamente los saltos de línea a estilo UNIX (`LF`) para evitar conflictos entre desarrolladores en Linux y Windows.
