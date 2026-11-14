<?php

declare(strict_types=1);

namespace App\Models;

use Exception;
use App\Core\Database;

class Persona extends Database
{
    protected ?int $id;
    protected string $cedula;
    protected string $nombre;
    protected string $apellido;
    protected ?string $fecha_nacimiento;
    protected ?string $telefono;
    protected ?string $correo;
    protected string $direccion;
    protected string $sexo;

    public function __construct()
    {
        $this->id = null;
        $this->cedula = "";
        $this->nombre = "";
        $this->apellido = "";
        $this->fecha_nacimiento = null;
        $this->telefono = null;
        $this->correo = null;
        $this->direccion = "";
        $this->sexo = "";
    }


    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

 
    public function setCedula(string $cedula): void
    {
        $cedula = trim($cedula);
        if (!preg_match('/^[VEJPGvejpg]-\d{7,9}$/', $cedula)) {
            throw new Exception('La cédula debe tener un prefijo válido (V, E, J, P, G), un guion y 7 a 9 dígitos.');
        }
        
        $this->cedula = strtoupper($cedula);
    }

    public function setNombre(string $nombre): void
    {
        $nombre = trim($nombre);
        if (empty($nombre)) {
            throw new Exception('El nombre es obligatorio.');
        }
        if (mb_strlen($nombre) < 2) {
            throw new Exception('El nombre debe tener al menos 2 caracteres.');
        }
        if (!preg_match('/^[a-zA-ZÁÉÍÓÚáéíóúüñÑçÇ][a-zA-ZÁÉÍÓÚáéíóúüñÑçÇ ]*$/', $nombre)) {
            throw new Exception('El nombre solo puede contener letras y espacios.');
        }
        $this->nombre = $nombre;
    }

    public function setApellido(string $apellido): void
    {
        $apellido = trim($apellido);
        if (empty($apellido)) {
            throw new Exception('El apellido es obligatorio.');
        }
        if (mb_strlen($apellido) < 2) {
            throw new Exception('El apellido debe tener al menos 2 caracteres.');
        }
        if (!preg_match('/^[a-zA-ZÁÉÍÓÚáéíóúüñÑçÇ][a-zA-ZÁÉÍÓÚáéíóúüñÑçÇ ]*$/', $apellido)) {
            throw new Exception('El apellido solo puede contener letras y espacios.');
        }
        $this->apellido = $apellido;
    }

    public function setFechaNacimiento(?string $fecha_nacimiento): void
    {
        $fecha_nacimiento = trim($fecha_nacimiento ?? '');
        if (empty($fecha_nacimiento)) {
            throw new Exception('La fecha de nacimiento es obligatoria.');
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_nacimiento)) {
            throw new Exception('El formato de la fecha de nacimiento no es válido.');
        }
        if ($fecha_nacimiento >= date('Y-m-d')) {
            throw new Exception('La fecha de nacimiento debe ser anterior a hoy.');
        }
        $this->fecha_nacimiento = $fecha_nacimiento;
    }

  
    public function setTelefono(?string $telefono): void
    {
        $telefono = trim($telefono ?? '');
        if ($telefono === '') {
            $this->telefono = null;
            return;
        }
        if (!preg_match('/^\d{4}-?\d{7}$/', $telefono)) {
            throw new Exception('El teléfono debe incluir el prefijo (4 dígitos) más 7 dígitos de número.');
        }
        
        if (strpos($telefono, '-') === false) {
            $telefono = substr($telefono, 0, 4) . '-' . substr($telefono, 4);
        }

        $this->telefono = $telefono;
    }

    public function setCorreo(?string $correo): void
    {
        $correo = trim($correo ?? '');
        if ($correo === '') {
            $this->correo = null;
            return;
        }
        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('El formato del correo electrónico no es válido.');
        }
        $this->correo = $correo;
    }

    public function setDireccion(string $direccion): void
    {
        $direccion = trim($direccion);
        if (empty($direccion)) {
            throw new Exception('La dirección es obligatoria.');
        }
        if (mb_strlen($direccion) < 3) {
            throw new Exception('La dirección debe tener al menos 3 caracteres.');
        }
        $this->direccion = $direccion;
    }

    public function setSexo(string $sexo): void
    {
        $sexo = strtoupper(trim($sexo));
        if (!in_array($sexo, ['M', 'F'], true)) {
            throw new Exception('El sexo debe ser M (Masculino) o F (Femenino).');
        }
        $this->sexo = $sexo;
    }

    public function getCedula(): string
    { 
        return $this->cedula; 
    }

    public function getNombre(): string
    { 
        return $this->nombre; 
    }

    public function getApellido(): string
    { 
        return $this->apellido; 
    }

    public function getFechaNacimiento(): ?string
    {
        return $this->fecha_nacimiento;
    }

    public function getTelefono(): ?string
    {
        return $this->telefono; 
    }

    public function getCorreo(): ?string
    {
        return $this->correo; 
    }

    public function getDireccion(): string
    {
        return $this->direccion; 
    }
    
    public function getSexo(): string
    {
        return $this->sexo; 
    }

   
    public function crear(): int
    {
        if (empty($this->cedula)) {
            throw new Exception("La cédula es requerida.");
        }
        if (empty($this->nombre) || empty($this->apellido)) {
            throw new Exception("Nombre y apellido son obligatorios.");
        }

        $parts = explode('-', $this->cedula, 2);
        $acronimo = $parts[0];
        $numeroDoc = $parts[1] ?? '';

        $pdo = self::getConnection();
        $systemDb = self::getSystemDb();

        $stmtTipo = $pdo->prepare("SELECT id FROM `{$systemDb}`.`tipo_documento` WHERE acronimo = :acronimo LIMIT 1");
        $stmtTipo->execute([':acronimo' => $acronimo]);
        $tipoDoc = $stmtTipo->fetch();
        if (!$tipoDoc) {
            throw new Exception("Tipo de documento no encontrado para '{$acronimo}'.");
        }
        $idTipoDocumento = (int)$tipoDoc['id'];

        $stmtCheck = $pdo->prepare("
            SELECT id FROM `{$systemDb}`.`persona` 
            WHERE numero_documento = :num AND id_tipo_documento = :tipo 
            LIMIT 1
        ");
        $stmtCheck->execute([':num' => $numeroDoc, ':tipo' => $idTipoDocumento]);
        if ($stmtCheck->fetch()) {
            throw new Exception("Ya existe una persona registrada con el documento '{$this->cedula}'.");
        }

        self::beginTransaction();

        try {
            $stmtInsertPersona = $pdo->prepare("
                INSERT INTO `{$systemDb}`.`persona` 
                    (nombre, apellido, numero_documento, id_tipo_documento, fecha_nacimiento, genero)
                VALUES 
                    (:nombre, :apellido, :num, :tipo, :fecha_nac, :genero)
            ");
            $stmtInsertPersona->execute([
                ':nombre'    => $this->nombre,
                ':apellido'  => $this->apellido,
                ':num'       => $numeroDoc,
                ':tipo'      => $idTipoDocumento,
                ':fecha_nac' => $this->fecha_nacimiento,
                ':genero'    => $this->sexo,
            ]);

            $this->id = (int)$pdo->lastInsertId();

            // Guardar contacto (relación 1 a N)
            $stmtContacto = $pdo->prepare("
                INSERT INTO `{$systemDb}`.`contacto` (id_persona, direccion, telefono, correo)
                VALUES (:id_persona, :direccion, :telefono, :correo)
            ");
            $stmtContacto->execute([
                ':id_persona' => $this->id,
                ':direccion'  => $this->direccion,
                ':telefono'   => $this->telefono,
                ':correo'     => $this->correo,
            ]);

            self::commit();
            return $this->id;
        } catch (Exception $e) {
            self::rollBack();
            throw $e;
        }
    }

    /**
     * Busca una persona por cédula completa (ej: V-12345678).
     */
    public static function obtenerPorCedula(string $cedula): ?array
    {
        $parts = explode('-', trim($cedula), 2);
        if (count($parts) < 2) {
            return null;
        }

        $pdo = self::getConnection();
        $systemDb = self::getSystemDb();

        $sql = "
            SELECT 
                p.id,
                p.nombre,
                p.apellido,
                p.numero_documento,
                p.fecha_nacimiento,
                p.genero AS sexo,
                td.acronimo AS tipo_documento,
                CONCAT(td.acronimo, '-', p.numero_documento) AS cedula_completa,
                c.telefono,
                c.correo,
                c.direccion
            FROM `{$systemDb}`.`persona` p
            INNER JOIN `{$systemDb}`.`tipo_documento` td ON p.id_tipo_documento = td.id
            LEFT JOIN `{$systemDb}`.`contacto` c ON p.id = c.id_persona
            WHERE td.acronimo = :acronimo AND p.numero_documento = :num
            LIMIT 1
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':acronimo' => strtoupper($parts[0]), ':num' => $parts[1]]);
        $data = $stmt->fetch();
        return $data ?: null;
    }

  
    public static function listar(int $limit = 50): array
    {
        $pdo = self::getConnection();
        $systemDb = self::getSystemDb();

        $sql = "
            SELECT 
                p.id,
                p.nombre,
                p.apellido,
                CONCAT(td.acronimo, '-', p.numero_documento) AS cedula,
                p.fecha_nacimiento,
                p.genero AS sexo,
                c.telefono,
                c.correo,
                c.direccion
            FROM `{$systemDb}`.`persona` p
            INNER JOIN `{$systemDb}`.`tipo_documento` td ON p.id_tipo_documento = td.id
            LEFT JOIN `{$systemDb}`.`contacto` c ON p.id = c.id_persona
            ORDER BY p.id DESC
            LIMIT :limit
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}

