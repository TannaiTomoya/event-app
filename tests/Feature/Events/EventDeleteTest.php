<?php

use App\Models\Event;
use App\Models\User;
use Livewire\Livewire;

test('organizers can delete their event and are redirected to the index', function () {
    $event = Event::factory()->create();

    Livewire::actingAs($event->user)
        ->test('pages::events.edit', ['event' => $event])
        ->call('delete')
        ->assertRedirect(route('events.index'));

    expect(session('status'))->toBe('イベントを削除しました。');
    $this->assertDatabaseMissing('events', ['id' => $event->id]);
});

test('other users cannot open the edit page and the event remains', function () {
    $event = Event::factory()->create();

    Livewire::actingAs(User::factory()->create())
        ->test('pages::events.edit', ['event' => $event])
        ->assertForbidden();

    $this->assertDatabaseHas('events', ['id' => $event->id]);
});

test('a user who replaces the owner after the page is opened cannot delete the event', function () {
    $event = Event::factory()->create();

    $component = Livewire::actingAs($event->user)
        ->test('pages::events.edit', ['event' => $event]);

    $this->actingAs(User::factory()->create());

    $component->call('delete')->assertForbidden();

    $this->assertDatabaseHas('events', ['id' => $event->id]);
});

test('deleting a nonexistent event returns 404', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('events.edit', ['event' => 999999]))
        ->assertNotFound();

    expect(Event::count())->toBe(0);
});
