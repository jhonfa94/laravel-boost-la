<?php

declare(strict_types=1);

namespace App\Http\Controllers\Note;

use App\Actions\Note\CreateNoteAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Note\StoreNoteRequest;
use App\Http\Resources\NoteResource;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class StoreNoteController extends Controller
{
    public function __invoke(StoreNoteRequest $request, CreateNoteAction $createNote): JsonResponse
    {
        $note = $createNote($request->toDto());

        return NoteResource::make($note)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }
}
