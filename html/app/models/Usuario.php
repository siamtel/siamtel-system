<?php

declare(strict_types=1);

namespace App\Models;

use Exception;
use App\Core\Database;

class Usuario extends Database
{
    protected ?int $id;
    protected ?int $empleado_id_referencia;
    protected string $nombre_usuario;
    protected ?string $password;
    protected ?string $password_hash;
    protected bool $activo;
    protected ?string $ultimo_login;

    public function __construct()
    {
        $this->id = null;
        $this->empleado_id_referencia = null;
        $this->nombre_usuario = "";
        $this->password = null;
        $this->password_hash = null;
        $this->activo = true;
        $this->ultimo_login = null;
    }

    // =========================================================================
    // GETTERS Y SETTERS CON VALIDACIONES
    // =========================================================================

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setEmpleadoIdReferencia(?int $id): void
    {
        if ($id !== null && $id <= 0) {
            throw new Exception("El ID de referencia de empleado debe ser un entero positivo.");
        }
        $this->empleado_id_referencia = $id;
    }

    public function getEmpleadoIdReferencia(): ?int
    {
        return $this->empleado_id_referencia;
    }

    public function setNombreUsuario(string $nombreUsuario): void
    {
        $nombreUsuario = trim($nombreUsuario);
        if (empty($nombreUsuario)) {
            throw new Exception("El nombre de usuario es obligatorio.");
        }
        if (mb_strlen($nombreUsuario) < 3) {
            throw new Exception("El nombre de usuario debe tener al menos 3 caracteres.");
        }
        if (mb_strlen($nombreUsuario) > 50) {
            throw new Exception("El nombre de usuario no puede exceder 50 caracteres.");
        }
        $this->nombre_usuario = $nombreUsuario;
    }

    public function getNombreUsuario(): string
    {
        return $this->nombre_usuario;
    }

    public function setPassword(?string $password): void
    {
        if ($password !== null) {
            if (empty($password)) {
                throw new Exception("La contraseña no puede estar vacía.");
            }
            if (strlen($password) < 4) {
                throw new Exception("La contraseña debe contener al menos 4 caracteres.");
            }
            $this->password = $password;
            $this->password_hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        }
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function getPasswordHash(): ?string
    {
        return $this->password_hash;
    }

    public function setActivo(bool $activo): void
    {
        $this->activo = $activo;
    }

    public function getActivo(): bool
    {
        return $this->activo;
    }

    public function getUltimoLogin(): ?string
    {
        return $this->ultimo_login;
    }

    // =========================================================================
    // MÉTODOS DE BASE DE DATOS (SECURITY)
    // =========================================================================

    /**
     * Autentica directamente por nombre de usuario y contraseña en la base de seguridad.
     */
    public function autenticar(): ?array
    {
        if (empty($this->nombre_usuario) || empty($this->password)) {
            throw new Exception("Debe ingresar usuario y contraseña.");
        }

        $pdo = self::getConnection();
        $secDb = self::getSecurityDb();

        $stmt = $pdo->prepare("
            SELECT id, empleado_id_referencia, nombre_usuario, password_hash, activo, ultimo_login
            FROM `{$secDb}`.`usuario`
            WHERE nombre_usuario = :usuario
            LIMIT 1
        ");
        $stmt->execute([':usuario' => $this->nombre_usuario]);

        $user = $stmt->fetch();
        if (!$user) {
            return null;
        }

        if (!password_verify($this->password, $user['password_hash'])) {
            return null;
        }

        if ((int)$user['activo'] !== 1) {
            throw new Exception("El usuario ingresado se encuentra inhabilitado.");
        }

        // Actualizar último inicio de sesión
        $pdo->prepare("UPDATE `{$secDb}`.`usuario` SET ultimo_login = NOW() WHERE id = :id")
            ->execute([':id' => $user['id']]);

        // Registrar auditoría en bitácora
        $pdo->prepare("
            INSERT INTO `{$secDb}`.`bitacora` (id_usuario, accion, tabla_afectada, registro_id, detalles, ip_address)
            VALUES (:id, 'LOGIN', 'usuario', :registro_id, 'Inicio de sesión exitoso', :ip)
        ")->execute([
            ':id'          => $user['id'],
            ':registro_id' => $user['id'],
            ':ip'          => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
        ]);

        unset($user['password_hash']);
        return $user;
    }

    /**
     * Registra un nuevo usuario en la base de datos de seguridad.
     */
    public function registrar(): int
    {
        if (empty($this->nombre_usuario)) {
            throw new Exception("El nombre de usuario es requerido.");
        }
        if (empty($this->password_hash)) {
            throw new Exception("Debe definir una contraseña válida.");
        }

        $pdo = self::getConnection();
        $secDb = self::getSecurityDb();

        $stmtCheck = $pdo->prepare("SELECT id FROM `{$secDb}`.`usuario` WHERE nombre_usuario = :nombre_usuario LIMIT 1");
        $stmtCheck->execute([':nombre_usuario' => $this->nombre_usuario]);
        if ($stmtCheck->fetch()) {
            throw new Exception("El usuario '{$this->nombre_usuario}' ya se encuentra registrado.");
        }

        $stmtInsert = $pdo->prepare("
            INSERT INTO `{$secDb}`.`usuario` 
                (empleado_id_referencia, nombre_usuario, password_hash, activo)
            VALUES 
                (:emp_id, :nombre_usuario, :password_hash, :activo)
        ");
        $stmtInsert->execute([
            ':emp_id'        => $this->empleado_id_referencia,
            ':nombre_usuario'=> $this->nombre_usuario,
            ':password_hash' => $this->password_hash,
            ':activo'        => $this->activo ? 1 : 0,
        ]);

        $this->id = (int)$pdo->lastInsertId();
        return $this->id;
    }
}
