<?php
/**
 * DB 기반 세션 핸들러 (App Engine 멀티 인스턴스 환경 대응)
 * config/db.php 에서 $pdo 생성 직후 호출됨
 */
class DbSessionHandler implements SessionHandlerInterface {
    private PDO $pdo;
    private int $lifetime;

    public function __construct(PDO $pdo) {
        $this->pdo      = $pdo;
        $this->lifetime = (int)ini_get('session.gc_maxlifetime') ?: 1440;
    }

    public function open(string $path, string $name): bool { return true; }
    public function close(): bool { return true; }

    public function read(string $id): string|false {
        $stmt = $this->pdo->prepare(
            "SELECT data FROM sessions WHERE id = ? AND last_activity > ?"
        );
        $stmt->execute([$id, time() - $this->lifetime]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? ($row['data'] ?? '') : '';
    }

    public function write(string $id, string $data): bool {
        $stmt = $this->pdo->prepare(
            "INSERT INTO sessions (id, data, last_activity)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE data = VALUES(data), last_activity = VALUES(last_activity)"
        );
        return $stmt->execute([$id, $data, time()]);
    }

    public function destroy(string $id): bool {
        $stmt = $this->pdo->prepare("DELETE FROM sessions WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function gc(int $max_lifetime): int|false {
        $stmt = $this->pdo->prepare(
            "DELETE FROM sessions WHERE last_activity < ?"
        );
        $stmt->execute([time() - $max_lifetime]);
        return $stmt->rowCount();
    }
}
