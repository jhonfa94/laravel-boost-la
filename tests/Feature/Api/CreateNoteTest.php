<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates a note for an authenticated user', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/notes', [
        'title' => 'My first note',
        'body' => 'Some body content',
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('data.title', 'My first note')
        ->assertJsonPath('data.body', 'Some body content');

    $this->assertDatabaseHas('notes', [
        'user_id' => $user->id,
        'title' => 'My first note',
        'body' => 'Some body content',
    ]);
});

it('creates a note without a body', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/notes', [
        'title' => 'Title only',
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('data.body', null);

    $this->assertDatabaseHas('notes', [
        'user_id' => $user->id,
        'title' => 'Title only',
        'body' => null,
    ]);
});

it('rejects a guest with 401', function (): void {
    $response = $this->postJson('/api/notes', [
        'title' => 'Guest note',
    ]);

    $response->assertUnauthorized();

    $this->assertDatabaseCount('notes', 0);
});

it('requires a title', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/notes', [
        'body' => 'Body without title',
    ]);

    $response
        ->assertUnprocessable()
        ->assertJsonValidationErrors('title');
});

it('rejects a title longer than 120 characters', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/notes', [
        'title' => str_repeat('a', 121),
    ]);

    $response
        ->assertUnprocessable()
        ->assertJsonValidationErrors('title');
});

it('rejects a body longer than 5000 characters', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/notes', [
        'title' => 'Valid title',
        'body' => str_repeat('a', 5001),
    ]);

    $response
        ->assertUnprocessable()
        ->assertJsonValidationErrors('body');
});
