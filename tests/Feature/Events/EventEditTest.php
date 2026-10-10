<?php

use App\Models\Event;
use App\Models\User;
use Livewire\Livewire;

test('organizers can update their event and are redirected to the index with a flash message', function () {
    $event = Event::factory()->create();

    Livewire::actingAs($event->user)
        ->test('pages::events.edit', ['event' => $event])
        ->set('title', '更新後のタイトル')
        ->set('description', '更新後の説明です。')
        ->set('venue', '更新後の会場')
        ->set('starts_at', '2026-11-01T10:00')
        ->set('ends_at', '2026-11-01T12:00')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('events.index'));

    expect(session('status'))->toBe('イベントを更新しました。');

    $this->assertDatabaseHas('events', [
        'id' => $event->id,
        'user_id' => $event->user_id,
        'title' => '更新後のタイトル',
        'description' => '更新後の説明です。',
        'venue' => '更新後の会場',
        'starts_at' => '2026-11-01 10:00:00',
        'ends_at' => '2026-11-01 12:00:00',
    ]);
});

test('the edit form is prefilled with the current event values', function () {
    $event = Event::factory()->create();

    Livewire::actingAs($event->user)
        ->test('pages::events.edit', ['event' => $event])
        ->assertSet('title', $event->title)
        ->assertSet('venue', $event->venue)
        ->assertSet('starts_at', $event->starts_at->format('Y-m-d\TH:i'));
});

test('other users cannot open the edit page of someone else\'s event', function () {
    $event = Event::factory()->create();

    $this->actingAs(User::factory()->create())
        ->get(route('events.edit', $event))
        ->assertForbidden();
});

test('guests are redirected to login from the edit page', function () {
    $event = Event::factory()->create();

    $this->get(route('events.edit', $event))
        ->assertRedirect(route('login'));
});

test('a user who replaces the owner after the page is opened cannot save changes', function () {
    $event = Event::factory()->create();
    $originalTitle = $event->title;

    $component = Livewire::actingAs($event->user)
        ->test('pages::events.edit', ['event' => $event]);

    $this->actingAs(User::factory()->create());

    $component
        ->set('title', '乗っ取りタイトル')
        ->call('save')
        ->assertForbidden();

    expect($event->fresh()->title)->toBe($originalTitle);
});

test('edit form rejects empty required fields and keeps the event unchanged', function () {
    $event = Event::factory()->create();
    $original = $event->only(['title', 'description', 'venue']);

    Livewire::actingAs($event->user)
        ->test('pages::events.edit', ['event' => $event])
        ->set('title', '')
        ->set('description', '')
        ->set('venue', '')
        ->set('starts_at', '')
        ->set('ends_at', '')
        ->call('save')
        ->assertHasErrors([
            'title' => 'required',
            'description' => 'required',
            'venue' => 'required',
            'starts_at' => 'required',
            'ends_at' => 'required',
        ])
        ->assertNoRedirect();

    expect($event->fresh()->only(['title', 'description', 'venue']))->toBe($original);
});

test('edit form limits text lengths', function (string $field, int $length, string $rule) {
    $event = Event::factory()->create();

    Livewire::actingAs($event->user)
        ->test('pages::events.edit', ['event' => $event])
        ->set($field, str_repeat('あ', $length))
        ->call('save')
        ->assertHasErrors([$field => $rule]);

    expect($event->fresh()->{$field})->toBe($event->{$field});
})->with([
    'title 101 chars' => ['title', 101, 'max'],
    'description 2001 chars' => ['description', 2001, 'max'],
    'venue 101 chars' => ['venue', 101, 'max'],
]);

test('edit form rejects an end time that is not after the start time', function (string $endsAt) {
    $event = Event::factory()->create();

    Livewire::actingAs($event->user)
        ->test('pages::events.edit', ['event' => $event])
        ->set('starts_at', '2026-11-01T10:00')
        ->set('ends_at', $endsAt)
        ->call('save')
        ->assertHasErrors(['ends_at' => 'after']);

    expect($event->fresh()->starts_at->eq($event->starts_at))->toBeTrue();
})->with([
    'before start' => '2026-11-01T09:00',
    'same as start' => '2026-11-01T10:00',
]);

test('edit form rejects malformed dates', function () {
    $event = Event::factory()->create();

    Livewire::actingAs($event->user)
        ->test('pages::events.edit', ['event' => $event])
        ->set('starts_at', 'not-a-date')
        ->set('ends_at', 'also-not-a-date')
        ->call('save')
        ->assertHasErrors(['starts_at' => 'date', 'ends_at' => 'date']);

    expect($event->fresh()->starts_at->eq($event->starts_at))->toBeTrue();
});

test('the edit page of a nonexistent event returns 404', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('events.edit', ['event' => 999999]))
        ->assertNotFound();
});
