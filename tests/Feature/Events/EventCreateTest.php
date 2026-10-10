<?php

use App\Models\Event;
use App\Models\User;
use Livewire\Livewire;

test('guests are redirected to login from the event creation page', function () {
    $this->get(route('events.create'))
        ->assertRedirect(route('login'));
});

test('authenticated users can visit the event creation page', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('events.create'))
        ->assertOk()
        ->assertSee('イベントを登録');
});

test('event form rejects missing required fields', function () {
    Livewire::test('pages::events.create')
        ->call('save')
        ->assertHasErrors([
            'title' => 'required',
            'description' => 'required',
            'venue' => 'required',
            'starts_at' => 'required',
            'ends_at' => 'required',
        ]);
});

test('event title is limited to 100 characters', function () {
    Livewire::test('pages::events.create')
        ->set('title', str_repeat('あ', 101))
        ->call('save')
        ->assertHasErrors(['title' => 'max']);
});

test('event form requires the end time to be after the start time', function () {
    Livewire::test('pages::events.create')
        ->set('title', '岩手山トレッキング')
        ->set('description', '岩手山を歩くイベントです。')
        ->set('venue', '馬返し登山口')
        ->set('starts_at', '2026-10-10T10:00')
        ->set('ends_at', '2026-10-10T09:00')
        ->call('save')
        ->assertHasErrors(['ends_at' => 'after']);
});

test('valid event input is saved as the logged-in user\'s event', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test('pages::events.create')
        ->set('title', '岩手山トレッキング')
        ->set('description', '岩手山を歩くイベントです。')
        ->set('venue', '馬返し登山口')
        ->set('starts_at', '2026-10-10T09:00')
        ->set('ends_at', '2026-10-10T12:00')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('events.index'));

    $this->assertDatabaseHas('events', [
        'user_id' => $user->id,
        'title' => '岩手山トレッキング',
        'venue' => '馬返し登山口',
    ]);
});

test('event description is limited to 2000 characters', function () {
    Livewire::actingAs(User::factory()->create())
        ->test('pages::events.create')
        ->set('description', str_repeat('あ', 2001))
        ->call('save')
        ->assertHasErrors(['description' => 'max']);

    expect(Event::count())->toBe(0);
});

test('event venue is limited to 100 characters', function () {
    Livewire::actingAs(User::factory()->create())
        ->test('pages::events.create')
        ->set('venue', str_repeat('あ', 101))
        ->call('save')
        ->assertHasErrors(['venue' => 'max']);

    expect(Event::count())->toBe(0);
});

test('event form rejects malformed dates', function () {
    Livewire::actingAs(User::factory()->create())
        ->test('pages::events.create')
        ->set('title', '岩手山トレッキング')
        ->set('description', '岩手山を歩くイベントです。')
        ->set('venue', '馬返し登山口')
        ->set('starts_at', 'not-a-date')
        ->set('ends_at', 'also-not-a-date')
        ->call('save')
        ->assertHasErrors(['starts_at' => 'date', 'ends_at' => 'date']);

    expect(Event::count())->toBe(0);
});

test('invalid input does not create an event', function () {
    Livewire::actingAs(User::factory()->create())
        ->test('pages::events.create')
        ->call('save')
        ->assertHasErrors()
        ->assertNoRedirect();

    expect(Event::count())->toBe(0);
});
