<?php
final class UserRepository
{
    public function findById(int $id): ?array
    {
        $query = Database::connect()->prepare('SELECT id, name, email, avatar_id, session_version, created_at FROM users WHERE id = ?');
        $query->execute([$id]);
        return $query->fetch() ?: null;
    }
    public function findByEmail(string $email): ?array
    {
        $query = Database::connect()->prepare('SELECT * FROM users WHERE email = ?');
        $query->execute([$email]);
        return $query->fetch() ?: null;
    }
    public function create(string $name, string $email, string $password): int
    {
        $db = Database::connect();
        $db->beginTransaction();
        try {
            $query = $db->prepare('INSERT INTO users(name, email, password_hash) VALUES(?, ?, ?)');
            $query->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT)]);
            $id = (int)$db->lastInsertId();
            $db->prepare('INSERT INTO teams(user_id) VALUES (?)')->execute([$id]);
            $db->commit();
            return $id;
        } catch (Throwable $error) {
            $db->rollBack();
            throw $error;
        }
    }
    public function consumeAttempt(string $action, int $limit = 10): bool
    {
        $db = Database::connect();
        $now = time();
        $key = hash('sha256', $action . '|' . ($_SERVER['REMOTE_ADDR'] ?? 'local'));
        $db->prepare('DELETE FROM auth_attempts WHERE created_at < ?')->execute([$now - 900]);
        $query = $db->prepare('SELECT COUNT(*) FROM auth_attempts WHERE key_hash = ?');
        $query->execute([$key]);
        if ((int)$query->fetchColumn() >= $limit) return false;
        $db->prepare('INSERT INTO auth_attempts(key_hash, created_at) VALUES(?, ?)')->execute([$key, $now]);
        return true;
    }
    public function updateProfile(int $id, string $name, string $email, int $avatarId): void
    {
        $db = Database::connect();
        $db->beginTransaction();
        try {
            $query = $db->prepare('UPDATE users SET name = ?, email = ?, avatar_id = ? WHERE id = ?');
            $query->execute([$name, $email, $avatarId, $id]);
            $db->prepare('DELETE FROM password_resets WHERE user_id = ?')->execute([$id]);
            $db->commit();
        } catch (Throwable $error) { $db->rollBack(); throw $error; }
    }
    public function changePassword(int $id, string $password): int
    {
        $db = Database::connect();
        $db->beginTransaction();
        try {
            $query = $db->prepare('UPDATE users SET password_hash = ?, session_version = session_version + 1 WHERE id = ?');
            $query->execute([password_hash($password, PASSWORD_DEFAULT), $id]);
            $db->prepare('DELETE FROM password_resets WHERE user_id = ?')->execute([$id]);
            $db->commit();
            return (int)$this->findById($id)['session_version'];
        } catch (Throwable $error) { $db->rollBack(); throw $error; }
    }

}
