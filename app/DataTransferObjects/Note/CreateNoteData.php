<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Note;

final readonly class CreateNoteData
{
    public function __construct(
        public int $userId,
        public string $title,
        public ?string $body = null,
    ) {}
}
