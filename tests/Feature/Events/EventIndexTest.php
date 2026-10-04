<?php

use App\Models\Event;
use App\Models\User;

test('guests can view the event list', function () {
    $event = Event::factory()->create(['title' => '朝焼けトレッキング']);

    $this->get(route('events.index'))
        ->assertOk()
        ->assertSeeText('朝焼けトレッキング')
        ->assertSeeText($event->user->name);
});

test('events are listed in order of start time', function () {
    $later = Event::factory()->create([
        'starts_at' => now()->addMonth(),
        'ends_at' => now()->addMonth()->addHours(2),
    ]);

    $sooner = Event::factory()->create([
        'starts_at' => now()->addWeek(),
        'ends_at' => now()->addWeek()->addHours(2),
    ]);

    $this->get(route('events.index'))
        ->assertSeeTextInOrder([$sooner->title, $later->title]);
});

test('the edit button is only shown to the event owner', function () {
    $event = Event::factory()->create();

    $this->actingAs($event->user)
        ->get(route('events.index'))
        ->assertSeeText('編集');

    $this->actingAs(User::factory()->create())
        ->get(route('events.index'))
        ->assertDontSeeText('編集');
});
