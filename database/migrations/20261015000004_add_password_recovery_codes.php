<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddPasswordRecoveryCodes extends AbstractMigration
{
    public function up(): void
    {
        $this->table('users')->addColumn('session_version', 'integer', [
            'signed' => false, 'default' => 0, 'null' => false,
        ])->update();

        $this->table('password_recovery_codes', ['id' => 'id', 'signed' => true, 'collation' => 'utf8mb4_unicode_ci'])
            ->addColumn('user_id', 'integer', ['signed' => true, 'null' => false])
            ->addColumn('code_hash', 'string', ['limit' => 64, 'null' => false])
            ->addColumn('consumed_at', 'datetime', ['null' => true])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['code_hash'], ['unique' => true, 'name' => 'uq_recovery_codes_hash'])
            ->addIndex(['user_id', 'consumed_at'], ['name' => 'idx_recovery_codes_user'])
            ->addForeignKey('user_id', 'users', 'id', ['delete' => 'CASCADE', 'constraint' => 'fk_recovery_codes_user'])
            ->create();
    }

    public function down(): void
    {
        if ($this->hasTable('password_recovery_codes')
            && $this->fetchRow('SELECT 1 FROM password_recovery_codes LIMIT 1') !== false) {
            throw new RuntimeException('A reversão foi interrompida: códigos de recuperação existem.');
        }
        if ($this->hasTable('password_recovery_codes')) {
            $this->table('password_recovery_codes')->drop()->save();
        }
        if ($this->table('users')->hasColumn('session_version')) {
            $this->table('users')->removeColumn('session_version')->update();
        }
    }
}
