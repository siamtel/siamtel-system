# SIAMTEL - Sistema de Información y Gestión

**Gobernación del Estado Lara**

Bienvenido al repositorio oficial del sistema **SIAMTEL**. Este proyecto está desarrollado bajo una arquitectura **MVC Modular (Modelo - Vista - Controlador)** en **PHP 8.2**, servido mediante **Apache**, con base de datos **MariaDB 10.11 LTS** dividida en dos esquemas independientes (**`system`** y **`security`**), contenedorizado mediante **Docker Compose**.

---

## 🎯 0. ¿Qué necesitas instalar en tu computadora?

Para trabajar en este proyecto, **NO necesitas instalar en tu computadora ni PHP, ni MariaDB/MySQL, ni Composer, ni XAMPP**.

Todo el entorno viene empaquetado y listo dentro de **Docker**:
* **En Windows:** Instala **Docker Desktop** (con soporte para WSL2 activado).
* **En Linux:** Instala **Docker** y el plugin **Docker Compose**.
* **En macOS:** Instala **Docker Desktop**.

> [!TIP]
> Al usar Docker, todos los desarrolladores trabajan exactamente sobre el mismo sistema con PHP 8.2 y MariaDB 10.11 LTS, evitando inconsistencias entre sistemas operativos.

---

## 🗄️ 1. Arquitectura de Base de Datos Dividida

Por lineamientos institucionales y académicos, la base de datos se divide estrictamente en dos esquemas dentro de la misma instancia de MariaDB:

1. **`system` (Lógica de Negocio y Servicios Médicos):**
   * Contiene los catálogos institucionales, el registro de personal y la lógica médica:
   * Tablas: `persona`, `contacto`, `empleado`, `cargo`, `unidad_organizativa`, `tipo_documento`, `tipo_personal`, `tipo_nomina`, `tipo_contrato`, etc.
   * Próximos módulos: Citas médicas, historias clínicas, triaje y morbilidad.
2. **`security` (Seguridad y Control de Acceso):**
   * Contiene las credenciales de los usuarios y auditoría:
   * Tablas: `usuario` (con referencia opcional foránea a `system.empleado.id`), `bitacora` de accesos.
   * Próximos módulos: Roles y permisos granulares según la documentación oficial.

---

## 🚀 2. Puesta en Marcha Rápida (Paso a Paso)

### Paso 1: Clonar el repositorio
```bash
git clone <https://github.com/siamtel/siamtel-system>
cd siamtel-system
```

### Paso 2: Crear el archivo de configuración `.env`
Copia la plantilla `.envexample` a `.env`:
* **En Linux / Mac / Git Bash:**
  ```bash
  cp .envexample .env
  ```
* **En Windows (CMD / PowerShell):**
  ```cmd
  copy .envexample .env
  ```

> [!NOTE]
> Si tienes XAMPP u otro MySQL activo en el puerto `3306`, cambia en `.env` el puerto de MariaDB a `3307`: `DB_PORT=3307`.

### Paso 3: Levantar los contenedores de Docker
```bash
docker compose up -d
```
* **Contenedores desplegados:**
  1. `siamtel_web`: Servidor Apache con PHP 8.2 y Composer en puerto `8080`.
  2. `siamtel_db`: Base de datos MariaDB 10.11 en puerto `3306`.
  3. `siamtel_pma`: Gestor gráfico phpMyAdmin en puerto `8088`.

### Paso 4: Abrir el sistema en tu navegador
* 🌐 **Sistema Web SIAMTEL:** [http://localhost:8080](http://localhost:8080)
  * **Usuario Inicial:** `admin`
  * **Contraseña:** `admin123`
* 🗄️ **phpMyAdmin:** [http://localhost:8088](http://localhost:8088)
  * **Servidor:** `siamtel_db`
  * **Usuario:** `root`
  * **Contraseña:** `root_siamtel` *(permite visualizar ambas bases: `system` y `security`)*

---

## 🗃️ 3. Motor Modular de Migraciones y Seeders

El proyecto incluye un motor CLI moderno para ejecutar migraciones y datos de prueba de forma global o independiente por módulo:

### A. Ejecutar migraciones

* **Migrar todos los esquemas (`system` y `security`):**
  ```bash
  php bin/migrate.php
  # o vía docker:
  docker compose exec siamtel_web php bin/migrate.php
  ```

* **Migrar únicamente la base de datos de negocio (`system`):**
  ```bash
  php bin/migrate.php system
  ```

* **Migrar únicamente la base de datos de seguridad (`security`):**
  ```bash
  php bin/migrate.php security
  ```

* **Ver el estado de las migraciones en consola:**
  ```bash
  php bin/migrate.php status
  ```
  *Muestra una tabla con el estado (`✔ OK` o `⏳ PEND`), archivo, lote y fecha de aplicación para cada base de datos.*

* **Crear una nueva migración:**
  ```bash
  php bin/migrate.php make agregar_tabla_citas system
  php bin/migrate.php make agregar_sesiones_activas security
  ```

### B. Cargar datos iniciales y catálogos (Seeders)

```bash
php bin/seed.php
# o vía docker:
docker compose exec siamtel_web php bin/seed.php
```
* *¿Qué hace?* Carga los catálogos base, 92 unidades organizativas, 225 cargos en `system` y el usuario inicial `admin` en `security`.

### C. Reiniciar bases de datos desde cero (Fresh Refresh)

```bash
php bin/refresh.php
# o vía docker:
docker compose exec siamtel_web php bin/refresh.php
```
* *¿Qué hace?* Vacía las bases de datos `system` y `security`, ejecuta todas las migraciones en orden y aplica los seeders automáticamente.
* Para reiniciar solo seguridad:
  ```bash
  php bin/refresh.php security
  ```
* Para reiniciar solo el sistema de negocio:
  ```bash
  php bin/refresh.php system
  ```

---

## 📁 4. Estructura del Proyecto

```text
siamtel-system/
├── config-available/                   # Configuraciones de Apache
│   └── public-directory.conf
├── docker-compose.yml                  # Orquestación Docker (siamtel_web, siamtel_db, siamtel_pma)
├── Dockerfile                          # Receta PHP 8.2 Apache
├── .env                                # Variables locales (puertos y credenciales)
├── .envexample                         # Plantilla de variables
│
├── bin/                                # Acceso rápido a comandos CLI desde la raíz
│   ├── migrate.php
│   ├── seed.php
│   └── refresh.php
│
└── html/                               # Código fuente de la aplicación
    ├── app/                            # Capa de Lógica y Presentación (MVC)
    │   ├── controllers/                # Controladores procedimentales (auth, dashboard, FrontController)
    │   ├── core/                       # Núcleo (Database.php Singleton y helpers.php)
    │   ├── models/                     # Modelos POO con validaciones (Usuario.php, Persona.php)
    │   └── views/                      # Vistas PHP limpias (login.php, dashboard.php, 404.php)
    │
    ├── database/                       # Capa de Base de Datos
    │   ├── migrations/                 # Migraciones SQL modulares
    │   │   ├── 00_init_databases.sql   # Inicialización de bases `system` y `security`
    │   │   ├── system/                 # Migraciones del esquema `system`
    │   │   │   └── 0001_create_system_schema.sql
    │   │   └── security/               # Migraciones del esquema `security`
    │   │       └── 0001_create_security_schema.sql
    │   └── seeds/                      # Carga de catálogos y datos iniciales
    │       ├── 0001_initial_catalogs.sql
    │       └── 0002_initial_security_seed.sql
    │
    ├── bin/                            # Motores CLI internos
    │   ├── migrate.php
    │   ├── seed.php
    │   └── refresh.php
    │
    ├── public/                         # Directorio raíz público de Apache
    │   ├── index.php                   # Punto de entrada HTTP
    │   ├── .htaccess                   # Reescritura de URLs limpias
    │   └── css/                        # Estilos limpios y componentizados (sin Bootstrap)
    │       ├── components.css
    │       ├── dashboard.css
    │       └── login.css
    │
    └── composer.json                   # Autoloader PSR-4 y dependencias
```

---

## 📐 5. Convenciones del Proyecto

1. **Modelos:** Heredan de `App\Core\Database`. Utilizan propiedades protegidas, constructor por defecto y métodos Getters/Setters con validación de excepciones nativas (`Exception`).
2. **Controladores:** Estrictamente procedimentales (funciones y lógica desacoplada, sin clases de controlador).
3. **Frontend:** Estilos modulares en `public/css/`. Sin Bootstrap, sin estilos embebidos `<style>` y sin atributos `style="..."` en las vistas.
4. **Bases de datos:** Consultas preparadas PDO con nombres cualificados (`` `system`.`tabla` `` y `` `security`.`tabla` ``) para garantizar integridad referencial y transacciones atómicas.

---

**Gobernación del Estado Lara** — *Dirección de Tecnología de Información y Comunicaciones*.
