<?php

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
        ])
        ->assertSet('submitted', false);
});

test('event title is limited to 100 characters', function () {
    Livewire::test('pages::events.create')
        ->set('title', str_repeat('あ', 101))
        ->call('save')
        ->assertHasErrors(['title' => 'max'])
        ->assertSet('submitted', false);
});

test('event form requires the end time to be after the start time', function () {
    Livewire::test('pages::events.create')
        ->set('title', '岩手山トレッキング')
        ->set('description', '岩手山を歩くイベントです。')
        ->set('venue', '馬返し登山口')
        ->set('starts_at', '2026-10-10T10:00')
        ->set('ends_at', '2026-10-10T09:00')
        ->call('save')
        ->assertHasErrors(['ends_at' => 'after'])
        ->assertSet('submitted', false);
});

test('valid event input is accepted without being saved', function () {
    Livewire::test('pages::events.create')
        ->set('title', '岩手山トレッキング')
        ->set('description', '岩手山を歩くイベントです。')
        ->set('venue', '馬返し登山口')
        ->set('starts_at', '2026-10-10T09:00')
        ->set('ends_at', '2026-10-10T12:00')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('submitted', true)
        ->assertSeeText('入力内容を受け付けました')
        ->assertSeeText('岩手山トレッキング')
        ->assertSeeText('馬返し登山口');
});
