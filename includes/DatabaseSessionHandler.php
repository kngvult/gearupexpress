<?php

class DatabaseSessionHandler implements SessionHandlerInterface {
    private $pdo;
    public function __construct(PDO $pdo) { $this->pdo = $pdo; }
    public function open($save_path, $session_name): bool { return true; }
    public function close(): bool { return true; }
    public function read($session_id): string {
        try {
            $stmt = $this->pdo->prepare("SELECT data FROM sessions WHERE id = ?");
            $stmt->execute([$session_id]);
            if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) { return $row['data'] ?? ''; }
        } catch (PDOException $e) { error_log("Erro ao ler sessão: " . $e->getMessage()); }
        return '';
    }
    public function write($session_id, $session_data): bool {
        try {
            $stmt = $this->pdo->prepare("UPDATE sessions SET data = ?, access = NOW() WHERE id = ?");
            $stmt->execute([$session_data, $session_id]);
            if ($stmt->rowCount() === 0) {
                $stmtInsert = $this->pdo->prepare("INSERT INTO sessions (id, data, access) VALUES (?, ?, NOW()) ON CONFLICT (id) DO UPDATE SET data = EXCLUDED.data, access = NOW()");
                $stmtInsert->execute([$session_id, $session_data]);
            }
            return true;
        } catch (PDOException $e) { error_log("Erro ao escrever sessão: " . $e->getMessage()); return false; }
    }
    public function destroy($session_id): bool {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM sessions WHERE id = ?");
            return $stmt->execute([$session_id]);
        } catch (PDOException $e) { error_log("Erro ao destruir sessão: " . $e->getMessage()); return false; }
    }
    public function gc($maxlifetime): int|false {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM sessions WHERE access < NOW() - INTERVAL '1 DAY'");
            $stmt->execute(); return $stmt->rowCount();
        } catch (PDOException $e) { error_log("Erro no GC da sessão: " . $e->getMessage()); return 0; }
    }
}
?>