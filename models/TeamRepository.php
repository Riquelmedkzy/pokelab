<?php
final class TeamRepository
{
    public function get(int $userId): array
    {
        $db = Database::connect();
        $db->prepare('INSERT OR IGNORE INTO teams(user_id) VALUES (?)')->execute([$userId]);
        $query = $db->prepare('SELECT members, revision FROM teams WHERE user_id = ?');
        $query->execute([$userId]);
        $row = $query->fetch();
        return ['members' => json_decode($row['members'], true, 512, JSON_THROW_ON_ERROR), 'revision' => (int)$row['revision']];
    }
    public function save(int $userId, array $members, int $revision): bool
    {
        $query = Database::connect()->prepare('UPDATE teams SET members = ?, revision = revision + 1, updated_at = CURRENT_TIMESTAMP WHERE user_id = ? AND revision = ?');
        $query->execute([json_encode($members, JSON_THROW_ON_ERROR), $userId, $revision]);
        return $query->rowCount() === 1;
    }
}
