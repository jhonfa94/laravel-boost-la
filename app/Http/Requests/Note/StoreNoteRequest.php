<?php

declare(strict_types=1);

namespace App\Http\Requests\Note;

use App\DataTransferObjects\Note\CreateNoteData;
use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class StoreNoteRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:120'],
            'body' => ['nullable', 'string', 'max:5000'],
        ];
    }

    /**
     * @throws AuthenticationException
     */
    public function toDto(): CreateNoteData
    {
        $user = $this->user();

        if (!$user instanceof User) {
            throw new AuthenticationException;
        }

        /** @var array{title: string, body?: string|null} $validated */
        $validated = $this->validated();

        return new CreateNoteData(
            userId: $user->id,
            title: $validated['title'],
            body: $validated['body'] ?? null,
        );
    }
}
