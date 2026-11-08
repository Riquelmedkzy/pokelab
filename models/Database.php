<?php
final class Database
{
    private static ?PDO $connection = null;

    public static function connect(): PDO
    {
        if (self::$connection) return self::$connection;
        $path = getenv('POKELAB_DB_PATH') ?: BASE_PATH . '/.private/pokelab.sqlite';
        if (!is_dir(dirname($path)) && !mkdir(dirname($path), 0770, true)) {
            throw new RuntimeException('Não foi possível criar o diretório do banco. Consulte o README.');
        }
        $db = new PDO('sqlite:' . $path, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
        // O SQLite cria arquivos restritivos; mantém escrita para o grupo/ACL do serviço.
        @chmod($path, 0660);
        $db->exec('PRAGMA foreign_keys = ON; PRAGMA busy_timeout = 5000;');
        $db->exec('CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            email TEXT NOT NULL UNIQUE COLLATE NOCASE,
            password_hash TEXT NOT NULL,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        );
        CREATE TABLE IF NOT EXISTS teams (
            user_id INTEGER PRIMARY KEY REFERENCES users(id) ON DELETE CASCADE,
            members TEXT NOT NULL DEFAULT "[null,null,null,null,null]",
            revision INTEGER NOT NULL DEFAULT 0,
            updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        );
        CREATE TABLE IF NOT EXISTS auth_attempts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            key_hash TEXT NOT NULL,
            created_at INTEGER NOT NULL
        );
        CREATE INDEX IF NOT EXISTS auth_attempts_key ON auth_attempts(key_hash, created_at);');
        // Migração aditiva serializada: mantém contas/equipes e evita ALTER concorrente.
        $db->exec('BEGIN IMMEDIATE');
        try {
            $columns = array_column($db->query('PRAGMA table_info(users)')->fetchAll(), 'name');
            if (!in_array('avatar_id', $columns, true)) $db->exec('ALTER TABLE users ADD COLUMN avatar_id INTEGER NOT NULL DEFAULT 3');
            if (!in_array('session_version', $columns, true)) $db->exec('ALTER TABLE users ADD COLUMN session_version INTEGER NOT NULL DEFAULT 0');
            $db->exec('CREATE TABLE IF NOT EXISTS password_resets (
                token_hash TEXT PRIMARY KEY,
                user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
                email TEXT NOT NULL,
                expires_at INTEGER NOT NULL
            ); CREATE INDEX IF NOT EXISTS password_resets_user ON password_resets(user_id);');
            $db->exec('COMMIT');
        } catch (Throwable $error) { $db->exec('ROLLBACK'); throw $error; }
        return self::$connection = $db;
    }
}
