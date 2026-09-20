<?php

namespace App\Database\Session;

use CodeIgniter\Session\Handlers\BaseHandler;
use SessionHandlerInterface;
use SQLite3;

class SQLiteHandler extends BaseHandler implements SessionHandlerInterface
{
    protected SQLite3 $db;

    public function __construct($config, string $ipAddress)
    {
        parent::__construct($config, $ipAddress);
        
        $dbPath = $this->savePath;
        $this->db = new SQLite3($dbPath);
        $this->db->exec('PRAGMA journal_mode=WAL');
        $this->db->exec('PRAGMA timeout=10000');
    }

    public function open(string $path, string $name): bool
    {
        return true;
    }

    public function close(): bool
    {
        return true;
    }

    public function read(string $id): string
    {
        $expiration = $this->config->expiration ?? 7200;
        $stmt = $this->db->prepare("SELECT data FROM ci_sessions WHERE id = :id AND (timestamp > :expiry OR timestamp = 0)");
        $stmt->bindValue(':id', $id);
        $stmt->bindValue(':expiry', time() - $expiration, SQLITE3_INTEGER);
        $result = $stmt->execute();
        $row = $result->fetchArray(SQLITE3_ASSOC);
        return $row['data'] ?? '';
    }

    public function write(string $id, string $data): bool
    {
        $stmt = $this->db->prepare("SELECT id FROM ci_sessions WHERE id = :id");
        $stmt->bindValue(':id', $id);
        $result = $stmt->execute();
        $exists = $result->fetchArray(SQLITE3_ASSOC) !== false;

        $ip = $this->ipAddress;
        $time = time();
        $dataEscaped = $this->db->escapeString($data);

        if ($exists) {
            $stmt = $this->db->prepare("UPDATE ci_sessions SET ip_address = :ip, timestamp = :time, data = :data WHERE id = :id");
        } else {
            $stmt = $this->db->prepare("INSERT INTO ci_sessions (id, ip_address, timestamp, data) VALUES (:id, :ip, :time, :data)");
        }
        
        $stmt->bindValue(':id', $id);
        $stmt->bindValue(':ip', $ip);
        $stmt->bindValue(':time', $time, SQLITE3_INTEGER);
        $stmt->bindValue(':data', $dataEscaped);
        
        return $stmt->execute() !== false;
    }

    public function destroy(string $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM ci_sessions WHERE id = :id");
        $stmt->bindValue(':id', $id);
        return $stmt->execute() !== false;
    }

    public function gc(int $maxLifetime): int
    {
        $stmt = $this->db->prepare("DELETE FROM ci_sessions WHERE timestamp < :time");
        $stmt->bindValue(':time', time() - $maxLifetime, SQLITE3_INTEGER);
        $stmt->execute();
        return $this->db->changes();
    }
}