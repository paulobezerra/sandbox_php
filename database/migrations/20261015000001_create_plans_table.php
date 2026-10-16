<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreatePlansTable extends AbstractMigration
{
    public function up(): void
    {
        if (!$this->hasTable('plans')) {
            $table = $this->table('plans', [
                'id' => 'id',
                'signed' => true,
                'collation' => 'utf8mb4_unicode_ci',
            ]);

            $table->addColumn('name', 'string', ['limit' => 100, 'null' => false])
                  ->addColumn('price', 'decimal', ['precision' => 10, 'scale' => 2, 'null' => false])
                  ->addColumn('description', 'text', ['null' => true])
                  ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                  ->create();

            $table->insert([
                [
                    'name' => 'Básico',
                    'price' => 29.90,
                    'description' => 'Plano ideal para pequenas equipes e projetos iniciais.',
                ],
                [
                    'name' => 'Profissional',
                    'price' => 79.90,
                    'description' => 'Recursos avançados, maior performance e suporte prioritário.',
                ],
            ])->saveData();
        }
    }

    public function down(): void
    {
        if ($this->hasTable('plans')) {
            $this->table('plans')->drop()->save();
        }
    }
}
