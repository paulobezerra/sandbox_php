<?php
declare(strict_types=1);

namespace App\Domain\Notice;

interface NoticeRepository
{
    /** @return list<Notice> */
    public function all(): array;
}
