<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateCmsTables extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('users')) {
            $this->table('users', [
                'id' => 'id',
                'signed' => true,
                'collation' => 'utf8mb4_unicode_ci',
            ])
                ->addColumn('name', 'string', ['limit' => 120, 'null' => false])
                ->addColumn('email', 'string', ['limit' => 254, 'null' => false])
                ->addColumn('password_hash', 'string', ['limit' => 255, 'null' => false])
                ->addColumn('role', 'string', ['limit' => 20, 'default' => 'USER', 'null' => false])
                ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('updated_at', 'timestamp', [
                    'default' => 'CURRENT_TIMESTAMP',
                    'update' => 'CURRENT_TIMESTAMP',
                ])
                ->addIndex(['email'], ['unique' => true, 'name' => 'uq_users_email'])
                ->create();
        }

        if (!$this->hasTable('posts')) {
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

        if (!$this->hasTable('pages')) {
            $this->table('pages', [
                'id' => 'id',
                'signed' => true,
                'collation' => 'utf8mb4_unicode_ci',
            ])
                ->addColumn('title', 'string', ['limit' => 200, 'null' => false])
                ->addColumn('slug', 'string', ['limit' => 200, 'null' => false])
                ->addColumn('content', 'text', ['null' => false])
                ->addColumn('status', 'string', ['limit' => 20, 'default' => 'DRAFT', 'null' => false])
                ->addColumn('published_at', 'datetime', ['null' => true])
                ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                ->addColumn('updated_at', 'timestamp', [
                    'default' => 'CURRENT_TIMESTAMP',
                    'update' => 'CURRENT_TIMESTAMP',
                ])
                ->addIndex(['slug'], ['unique' => true, 'name' => 'uq_pages_slug'])
                ->addIndex(['status', 'published_at'], ['name' => 'idx_pages_publication'])
                ->create();
        }
    }

    public function down(): void
    {
        foreach (['pages', 'posts', 'users'] as $tableName) {
            if ($this->hasTable($tableName) && $this->fetchRow("SELECT 1 FROM {$tableName} LIMIT 1") !== false) {
                throw new RuntimeException(
                    "A reversão foi interrompida: {$tableName} contém dados. Faça backup e migre os dados antes."
                );
            }
        }

        foreach (['pages', 'posts', 'users'] as $tableName) {
            if ($this->hasTable($tableName)) {
                $this->table($tableName)->drop()->save();
            }
        }
    }
}
