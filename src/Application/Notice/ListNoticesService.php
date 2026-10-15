<?php
declare(strict_types=1);

namespace App\Application\Notice;

use App\Domain\Notice\Notice;
use App\Domain\Notice\NoticeRepository;

final readonly class ListNoticesService
{
    public function __construct(private NoticeRepository $notices)
    {
    }

    /** @return list<Notice> */
    public function execute(): array
    {
        return $this->notices->all();
    }
}
