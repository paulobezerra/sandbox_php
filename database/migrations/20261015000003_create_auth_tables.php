<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateAuthTables extends AbstractMigration
{
    public function up(): void
    {
        $this->table('password_resets', ['id' => 'id', 'signed' => true, 'collation' => 'utf8mb4_unicode_ci'])
            ->addColumn('user_id', 'integer', ['signed' => true, 'null' => false])
            ->addColumn('token_hash', 'string', ['limit' => 64, 'null' => false])
            ->addColumn('expires_at', 'datetime', ['null' => false])
            ->addColumn('consumed_at', 'datetime', ['null' => true])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['token_hash'], ['unique' => true, 'name' => 'uq_password_resets_token'])
            ->addIndex(['user_id', 'consumed_at'], ['name' => 'idx_password_resets_user'])
            ->addForeignKey('user_id', 'users', 'id', ['delete' => 'CASCADE', 'constraint' => 'fk_password_resets_user'])
            ->create();
        $this->table('auth_attempts', ['id' => 'id', 'signed' => true, 'collation' => 'utf8mb4_unicode_ci'])
            ->addColumn('attempt_key', 'string', ['limit' => 64, 'null' => false])
            ->addColumn('attempted_at', 'datetime', ['null' => false])
            ->addIndex(['attempt_key', 'attempted_at'], ['name' => 'idx_auth_attempts_window'])
            ->create();
    }

    public function down(): void
    {
        foreach (['password_resets', 'auth_attempts'] as $table) {
            if ($this->hasTable($table) && $this->fetchRow("SELECT 1 FROM {$table} LIMIT 1") !== false) {
                throw new RuntimeException("A reversão foi interrompida: {$table} contém dados.");
            }
        }
        $this->table('password_resets')->drop()->save();
        $this->table('auth_attempts')->drop()->save();
    }
}
