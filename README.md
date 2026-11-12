# SIAMTEL - Sistema de Información y Gestión

**Gobernación del Estado Lara**

Bienvenido al repositorio oficial del sistema **SIAMTEL**. Este proyecto está desarrollado bajo una arquitectura **MVC Modular (Modelo - Vista - Controlador)** en **PHP 8.2**, servido mediante **Apache**, con base de datos **MariaDB 10.11 LTS** y contenedorizado mediante **Docker Compose**.

---

## 🎯 0. La Regla de Oro: ¿Qué necesitas instalar en tu computadora?

Para trabajar en este proyecto, **NO necesitas instalar en tu computadora ni PHP, ni MariaDB/MySQL, ni Composer, ni XAMPP**.

Todo el entorno ya viene empaquetado y listo dentro de **Docker**:
* **En Windows:** Solo necesitas tener instalado **Docker Desktop** (con soporte para WSL2 activado).
* **En Linux:** Solo necesitas tener instalado **Docker** y el plugin **Docker Compose**.
* **En macOS:** Solo necesitas tener instalado **Docker Desktop**.

> [!TIP]
> Al usar Docker, todos los desarrolladores del equipo (sin importar si usan Windows, Linux o Mac) trabajan exactamente sobre el mismo sistema, con la misma versión de PHP (8.2) y la misma versión de MariaDB (10.11 LTS), evitando el clásico problema de *"en mi máquina sí funciona"*.

---

## 🚀 1. Puesta en Marcha Rápida (Paso a Paso)

Sigue estos 4 pasos para tener el sistema funcionando en tu computadora en menos de 5 minutos:

### Paso 1: Clonar el repositorio
Abre tu terminal (o Git Bash en Windows) y descarga el código:
```bash
git clone <https://github.com/siamtel/siamtel-system>
cd siamtel-system
```

### Paso 2: Crear tu archivo de configuración local `.env`
El repositorio incluye una plantilla llamada `.envexample`. Crea una copia local llamada `.env`:
* **En Linux / Mac / Git Bash:**
  ```bash
  cp .envexample .env
  ```
* **En Windows (CMD / PowerShell):**
  ```cmd
  copy .envexample .env
  ```

> [!NOTE]
> **Usuarios de Windows con XAMPP o MySQL local:** Si ya tienes XAMPP u otro MySQL encendido en tu puerto `3306`, abre tu archivo `.env` y cambia el puerto de la base de datos a `3307`:
> ```env
> DB_PORT=3307
> ```

### Paso 3: Levantar los contenedores de Docker
Ejecuta el siguiente comando en la raíz del proyecto:
```bash
docker compose up -d
```
* **¿Qué hace este comando?** Descarga las imágenes oficiales, compila el servidor web y levanta en segundo plano:
  1. `siamtel_web`: Servidor Apache con PHP 8.2 y Composer.
  2. `siamtel_db`: Base de datos MariaDB 10.11 LTS.
  3. `siamtel_pma`: Interfaz gráfica de phpMyAdmin.
* **Magia automática en el primer inicio:** MariaDB creará automáticamente la base de datos `siamtel_db`, creará todas las tablas iniciales y cargará todos los catálogos y cargos del estado. ¡No tienes que importar ningún archivo SQL a mano!

### Paso 4: Abrir el sistema en tu navegador
Una vez que el comando termine, abre tu navegador web favorito:
* 🌐 **Sistema Web SIAMTEL:** [http://localhost:8080](http://localhost:8080)
* 🗄️ **Administrador de Base de Datos (phpMyAdmin):** [http://localhost:8088](http://localhost:8088)
  * **Servidor:** `siamtel_db`
  * **Usuario:** `root` (o el valor de `DB_USER` en tu `.env`)
  * **Contraseña:** la definida en `DB_ROOT_PASSWORD` de tu `.env` (por defecto: `root_siamtel`)

---

## 📦 2. Manejo de Librerías con Composer (Sin instalar Composer en tu PC)

### ¿Qué es Composer?
Composer es el gestor de paquetes y dependencias de PHP (el equivalente a `npm` en JavaScript o `pip` en Python). Además, se encarga de generar el **Autoload** (para que puedas usar tus clases y modelos sin tener que hacer `require` manual en cada archivo).

Como Composer ya viene instalado dentro del contenedor web, tú y tus compañeros pueden ejecutarlo a través de Docker con comandos simples:

### Comandos frecuentes de Composer:

* **Instalar las dependencias del proyecto:**
  Si recién clonaste el repositorio o se agregó una nueva librería:
  ```bash
  docker compose exec siamtel_web composer install
  ```
  * *¿Qué hace?* Lee el archivo `html/composer.json`, descarga las librerías necesarias y las guarda en `html/vendor/`.

* **Actualizar el Autoload (Carga automática de clases):**
  Si creas una clase nueva en `app/Models/` o en `app/Core/` y PHP te dice que no la encuentra:
  ```bash
  docker compose exec siamtel_web composer dump-autoload
  ```
  * *¿Qué hace?* Vuelve a indexar todas las clases PHP del proyecto para que estén disponibles de inmediato.

* **Instalar una nueva librería externa:**
  Si en el futuro necesitas una librería (por ejemplo, para generar archivos PDF o Excel):
  ```bash
  docker compose exec siamtel_web composer require nombre/libreria
  ```

* **Entrar a la terminal del contenedor (Consola interactiva):**
  Si quieres trabajar directamente dentro del contenedor como si fuera tu propia máquina:
  ```bash
  docker compose exec siamtel_web bash
  ```
  Una vez adentro, puedes escribir `composer`, `php`, etc. directamente. Para salir, escribe `exit`.

---

## 🗃️ 3. Sistema de Migraciones de Base de Datos

### ¿Qué es una migración y por qué la usamos?
Imagina que estás trabajando en una función y necesitas agregar una columna `telefono` a la tabla `empleado`. Si le dices a tu compañero por chat *"ejecuta este SQL"*, es muy fácil que a alguien se le olvide, que se generen errores o peor: **que alguien borre la base de datos completa y todos pierdan sus registros de prueba**.

Una **migración** es un archivo numerado con código SQL que representa un cambio en el tiempo. El sistema tiene una tabla de control llamada `migrations` que sabe qué archivos ya se aplicaron en tu máquina y cuáles faltan, aplicando **únicamente los cambios nuevos sin borrar datos existentes**.

---

### Comandos de Migraciones (Mediante Docker y Composer):

Para garantizar que los comandos se ejecuten dentro del entorno unificado de PHP 8.2 y MariaDB del contenedor web:

#### 1. Aplicar cambios pendientes (sin perder datos existentes)
```bash
docker compose exec siamtel_web composer migrate
```
*O con PHP directo:*
```bash
docker compose exec siamtel_web php bin/migrate.php
```
* **¿Qué hace?** Revisa `html/database/migrations/`, detecta qué archivos aún no han sido aplicados en tu base de datos y ejecuta únicamente los nuevos.
* **Cuándo usarlo:** Cada vez que hagas `git pull` y tus compañeros hayan agregado nuevas tablas o columnas.

#### 2. Ver el estado de las migraciones
```bash
docker compose exec siamtel_web php bin/migrate.php status
```
* **¿Qué hace?** Muestra una tabla en consola con todos los archivos de migración, indicando con `✔ OK` los aplicados (con lote y fecha) y con `⏳ PEND` los que faltan por aplicar.

#### 3. Crear una nueva migración (Agregar tablas o columnas nuevas)
```bash
docker compose exec siamtel_web php bin/migrate.php make agregar_telefono_a_empleado
```
* **¿Qué hace?** Crea automáticamente un archivo SQL numerado en orden cronológico dentro de `html/database/migrations/`, por ejemplo: `0002_agregar_telefono_a_empleado.sql`.

#### 4. Cargar catálogos y datos iniciales de prueba (Seeders)
```bash
docker compose exec siamtel_web composer seed
```
*O con PHP directo:*
```bash
docker compose exec siamtel_web php bin/seed.php
```
* **¿Qué hace?** Ejecuta los scripts de `html/database/seeds/` para cargar los catálogos base, 92 unidades organizativas y 225 cargos del estado.

#### 5. Reiniciar la base de datos completa desde cero
```bash
docker compose exec siamtel_web composer refresh
```
*O con PHP directo:*
```bash
docker compose exec siamtel_web php bin/refresh.php
```
* **¿Qué hace?** Elimina todas las tablas, vuelve a correr todas las migraciones en orden desde la `0001` y recarga los catálogos iniciales.

> [!TIP]
> **Modo interactivo (Comandos aún más cortos):**
> Si vas a ejecutar varios comandos seguidos, puedes entrar a la consola del contenedor:
> ```bash
> docker compose exec siamtel_web bash
> ```
> Y adentro ejecutas directamente: `composer migrate`, `php bin/migrate.php status`, `composer seed`, etc. Escribe `exit` para salir.

---

## 📁 4. Estructura del Proyecto Explicada (Arquitectura MVC Limpia)

El código fuente sigue una arquitectura desacoplada donde todo el ecosistema PHP vive bajo `html/` (mapeado directamente a `/var/www/html` en el contenedor web):

```text
siamtel-system/                         # 1. Capa de Infraestructura (Docker y Git)
├── config-available/                   # Configuraciones de servidor Apache
│   └── public-directory.conf           # Permisos de Apache para el directorio /public
├── docker-compose.yml                  # Orquestación de servicios (Web, DB MariaDB 10.11, phpMyAdmin)
├── Dockerfile                          # Receta de construcción de la imagen de PHP 8.2
├── .env                                # Contraseñas y puertos locales (ignorado por Git)
├── .envexample                         # Plantilla de ejemplo del .env
├── .gitattributes                      # Normalización automática de saltos de línea (LF)
├── .gitignore                          # Exclusiones de Git
│
└── html/                               # 2. Capa de la Aplicación PHP
    ├── app/                            # 3. Capa MVC (Lógica del Sistema)
    │   ├── Controllers/                # [C] Controladores (rutas, peticiones, lógica)
    │   │   ├── FrontController.php     # Despachador principal de rutas
    │   │   └── AuthController.php      # Controlador de autenticación y login
    │   ├── Models/                     # [M] Modelos de datos y consultas a MariaDB
    │   │   ├── Security/               # Modelos de seguridad (Usuario, Roles, etc.)
    │   │   └── System/                 # Modelos de negocio (Empleado, etc.)
    │   ├── Views/                      # [V] Vistas y pantallas (HTML + PHP)
    │   │   ├── errors/                 # Pantallas de error (404.php)
    │   │   └── login.php               # Pantalla de inicio de sesión
    │   └── Core/                       # [Núcleo] Conexión PDO Singleton (Database.php), Model base, Helpers
    │
    ├── database/                       # 4. Capa de Persistencia y Base de Datos
    │   ├── migrations/                 # Migraciones incrementales DDL (.sql)
    │   │   └── 0001_create_initial_schema.sql # Estructura inicial (12 tablas)
    │   ├── seeds/                      # Catálogos y datos iniciales (.sql)
    │   │   └── 0001_initial_catalogs.sql      # Catálogos, 92 OUs y 225 cargos
    │   └── backups/                    # Respaldos y volcados SQL históricos
    │
    ├── bin/                            # 5. Entrada CLI (Scripts ejecutables por Terminal)
    │   ├── migrate.php                 # Motor de migraciones (reutiliza App\Core\Database)
    │   ├── seed.php                    # Carga de datos iniciales
    │   └── refresh.php                 # Reinicio completo
    │
    ├── public/                         # 6. Entrada Web (Apache DocumentRoot)
    │   ├── .htaccess                   # Reglas de Apache para URLs amigables
    │   ├── index.php                   # Punto de entrada de peticiones HTTP
    │   ├── css/                        # Hojas de estilo CSS (login.css, style.css)
    │   ├── js/                         # Scripts JavaScript del frontend
    │   └── img/                        # Imágenes, logotipos e iconos
    │
    └── composer.json                   # Dependencias, Autoload PSR-4 y scripts CLI
```

---

## 📐 5. Estándares y Convenciones de Código (Obligatorio para el Equipo)

Para que el proyecto se mantenga limpio, ordenado y sin problemas de compatibilidad entre Windows y Linux (los cuales tratan las mayúsculas y minúsculas de forma diferente), **todo el equipo debe seguir estas 4 reglas de oro**:

### 1. Archivos, carpetas, URLs y repositorios: `kebab-case` (Todo en minúsculas con guiones)
Es el estándar web internacional porque es amigable para las URLs del navegador, fácil de leer y no genera conflictos entre sistemas operativos.
* ✅ **Carpetas y archivos públicos:** `seguimiento-poa/`, `modulo-salud/`, `reporte-administrativo.pdf`, `login.css`, `app-main.js`
* ✅ **Rutas web en el navegador:** `midominio.gob.ve/departamentos-salud/registro-pacientes`
* ❌ **Evitar:** `SeguimientoPOA/`, `modulo_salud/`, `Reporte Administrativo.pdf`

---

### 2. Clases, Modelos y Componentes: `PascalCase` (Cada palabra inicia con mayúscula)
Cuando definas estructuras principales en PHP (clases, interfaces, traits o modelos de datos), la convención universal de la industria (PSR-1 / PSR-12) es iniciar cada palabra con mayúscula, sin espacios ni guiones.
* ✅ **Nombres de clases y modelos:**
  ```php
  class DepartamentoSalud extends Model { ... }
  class PlanOperativoAnual extends Model { ... }
  class UsuarioSeguridad extends Model { ... }
  ```
* ✅ **Nombres de archivos de clase:** `DepartamentoSalud.php`, `Usuario.php`, `Database.php`
* ❌ **Evitar:** `departamento_salud.php`, `planOperativo.php`, `usuario.class.php`

---

### 3. Variables, Funciones y Métodos: `camelCase` (La primera palabra en minúscula)
Para el código de programación dentro de tus controladores, modelos y helpers:
* **En PHP, JavaScript y TypeScript:** Se utiliza obligatoriamente **`camelCase`**:
  * ✅ **Métodos y funciones:** `generarReporteSalud()`, `validarRequisitos()`, `obtenerPorCedula()`
  * ✅ **Variables:** `$usuarioActual`, `$idDepartamento`, `$loginCorrecto`
* *(Nota comparativa: Si algún día se integra un script auxiliar en **Python**, el estándar oficial de Python PEP 8 exige `snake_case`, por ejemplo `generar_reporte_salud()`).*

---

### 4. Bases de Datos Relacionales (SQL): `snake_case` (Minúsculas con guiones bajos)
Para los nombres de las tablas y las columnas en MariaDB/MySQL, el uso de guiones bajos es la regla de oro para evitar fallos de portabilidad entre Windows (que no distingue mayúsculas en tablas por defecto) y Linux (que sí las distingue):
* ✅ **Nombres de tablas:** `departamentos_salud`, `plan_operativo`, `tipo_documento`, `historial_laboral`
* ✅ **Nombres de columnas:** `id`, `fecha_creacion`, `id_unidad_organizativa`, `primer_nombre`, `estatus_activo`
* ❌ **Evitar:** `DepartamentosSalud`, `FechaCreacion`, `idUnidadOrganizativa`

---

### Resumen Rápido de Convenciones:

| Elemento | Convención | Ejemplo | Dónde se aplica |
| :--- | :--- | :--- | :--- |
| **URLs, Archivos y Carpetas** | `kebab-case` | `registro-pacientes`, `modulo-rrhh/` | Rutas del navegador, vistas, CSS, JS |
| **Clases y Modelos** | `PascalCase` | `Usuario`, `PlanOperativoAnual` | Archivos de clase en `app/Models/` y `app/Core/` |
| **Métodos y Funciones** | `camelCase` | `generarReporte()`, `buscarPorCedula()` | Funciones en controladores y modelos |
| **Variables** | `camelCase` | `$usuarioId`, `$datosFormulario` | Variables de PHP y JavaScript |
| **Tablas y Columnas BD** | `snake_case` | `tipo_documento`, `fecha_ingreso` | Base de datos MariaDB (SQL) |
| **Constantes y `.env`** | `UPPER_SNAKE`| `DB_PASSWORD`, `BASE_URL` | Archivo `.env` y constantes `define()` |


## 🤝 7. Buenas Prácticas de Git para el Equipo

1. **NUNCA subir el archivo `.env`:** Cada desarrollador tiene sus propias contraseñas y puertos locales. Este archivo está en el `.gitignore`.
2. **NUNCA subir la carpeta `vendor/`:** Se genera automáticamente con `docker compose exec siamtel_web composer install`.
3. **Saltos de línea consistentes (LF):** El archivo `.gitattributes` ya fuerza automáticamente los saltos de línea a estilo UNIX (`LF`) para evitar que Git marque archivos modificados simplemente por saltos de línea diferentes entre Windows y Linux.
4. **Commits claros y descriptivos:** Usa mensajes concisos que expliquen qué se hizo:
   * ✅ `git commit -m "feat: agregar modulo de consulta de empleados"`
   * ✅ `git commit -m "fix: corregir validacion de cedula en login"`
   * ❌ `git commit -m "cambios"`

---

**Gobernación del Estado Lara** — *Dirección de Tecnología de Información y Comunicaciones*.
