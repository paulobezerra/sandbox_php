<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateInitialSchema extends AbstractMigration
{
    public function up(): void
    {
        $this->table('users', [
            'id' => 'id',
            'signed' => true,
            'collation' => 'utf8mb4_unicode_ci',
        ])
            ->addColumn('name', 'string', ['limit' => 120, 'null' => false])
            ->addColumn('email', 'string', ['limit' => 254, 'null' => false])
            ->addColumn('password_hash', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'timestamp', [
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
            ])
            ->addIndex(['email'], ['unique' => true, 'name' => 'uq_users_email'])
            ->create();

        $this->table('posts', [
            'id' => 'id',
            'signed' => true,
            'collation' => 'utf8mb4_unicode_ci',
        ])
            ->addColumn('title', 'string', ['limit' => 200, 'null' => false])
            ->addColumn('slug', 'string', ['limit' => 200, 'null' => false])
            ->addColumn('content', 'text', ['null' => false])
            ->addColumn('excerpt', 'text', ['null' => true])
            ->addColumn('status', 'string', ['limit' => 20, 'default' => 'DRAFT', 'null' => false])
            ->addColumn('author_id', 'integer', ['signed' => true, 'null' => false])
            ->addColumn('published_at', 'datetime', ['null' => true])
            ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'timestamp', [
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
            ])
            ->addIndex(['slug'], ['unique' => true, 'name' => 'uq_posts_slug'])
            ->addIndex(['author_id'], ['name' => 'idx_posts_author_id'])
            ->addIndex(['status', 'published_at'], ['name' => 'idx_posts_publication'])
            ->addForeignKey('author_id', 'users', 'id', [
                'delete' => 'RESTRICT',
                'update' => 'CASCADE',
                'constraint' => 'fk_posts_author',
            ])
            ->create();
    }

    public function down(): void
    {
        foreach (['posts', 'users'] as $tableName) {
            if ($this->hasTable($tableName)
                && $this->fetchRow("SELECT 1 FROM {$tableName} LIMIT 1") !== false) {
                throw new RuntimeException(
                    "Rollback stopped: {$tableName} contains data. Back it up before removing it."
                );
            }
        }

        foreach (['posts', 'users'] as $tableName) {
            if ($this->hasTable($tableName)) {
                $this->table($tableName)->drop()->save();
            }
        }
    }
}
