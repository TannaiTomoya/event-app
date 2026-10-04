<?php

use App\Models\Event;
use App\Models\User;

it('イベント一覧が表示される', function () {
    $event = Event::factory()->create(['title' => '朝焼けトレッキング']);

    $this->get(route('events.index'))
        ->assertOk()
        ->assertSee('朝焼けトレッキング');
});

it('他人のイベントは編集画面を開けない', function () {
    $event = Event::factory()->create();
    $other = User::factory()->create();

    $this->actingAs($other)
        ->get(route('events.edit', $event))
        ->assertForbidden();
});
