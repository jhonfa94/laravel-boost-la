<?php

declare(strict_types=1);

namespace App\Actions\Note;

use App\DataTransferObjects\Note\CreateNoteData;
use App\Models\Note;

final readonly class CreateNoteAction
{
    public function __invoke(CreateNoteData $data): Note
    {
        return Note::query()->create([
            'user_id' => $data->userId,
            'title' => $data->title,
            'body' => $data->body,
        ]);
    }
}
