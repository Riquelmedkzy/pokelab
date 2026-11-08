<?php
final class PasswordResetRepository
{
    public function issue(array $user): string
    {
        $db = Database::connect();
        $token = bin2hex(random_bytes(32));
        $db->beginTransaction();
        try {
            $db->prepare('DELETE FROM password_resets WHERE user_id = ? OR expires_at < ?')->execute([(int)$user['id'], time()]);
            $db->prepare('INSERT INTO password_resets(token_hash, user_id, email, expires_at) VALUES(?, ?, ?, ?)')->execute([hash('sha256', $token), (int)$user['id'], $user['email'], time() + 1800]);
            $db->commit();
            return $token;
        } catch (Throwable $error) { $db->rollBack(); throw $error; }
    }
    public function find(string $token): ?array
    {
        if (!preg_match('/^[a-f0-9]{64}$/D', $token)) return null;
        $query = Database::connect()->prepare('SELECT r.* FROM password_resets r JOIN users u ON u.id = r.user_id AND u.email = r.email WHERE r.token_hash = ? AND r.expires_at > ?');
        $query->execute([hash('sha256', $token), time()]);
        return $query->fetch() ?: null;
    }
    public function consume(string $token, string $password): bool
    {
        if (!preg_match('/^[a-f0-9]{64}$/D', $token)) return false;
        $db = Database::connect();
        // O bloqueio de escrita impede que duas requisições consumam o mesmo link.
        $db->exec('BEGIN IMMEDIATE');
        try {
            $row = $this->find($token);
            if (!$row) { $db->exec('ROLLBACK'); return false; }
            $db->prepare('UPDATE users SET password_hash = ?, session_version = session_version + 1 WHERE id = ?')->execute([password_hash($password, PASSWORD_DEFAULT), $row['user_id']]);
            $db->prepare('DELETE FROM password_resets WHERE user_id = ?')->execute([$row['user_id']]);
            $db->exec('COMMIT');
            return true;
        } catch (Throwable $error) { $db->exec('ROLLBACK'); throw $error; }
    }
    public function revoke(string $token): void
    {
        Database::connect()->prepare('DELETE FROM password_resets WHERE token_hash = ?')->execute([hash('sha256', $token)]);
    }
}
