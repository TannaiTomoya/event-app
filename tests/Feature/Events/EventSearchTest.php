<?php

use App\Models\Event;
use Livewire\Livewire;

beforeEach(function () {
    $this->trekking = Event::factory()->create([
        'title' => '朝焼けトレッキング',
        'venue' => '安比高原',
        'starts_at' => now()->addWeeks(2),
        'ends_at' => now()->addWeeks(2)->addHours(2),
    ]);

    $this->walk = Event::factory()->create([
        'title' => '岩手朝散歩',
        'venue' => '八幡平市体育館',
        'starts_at' => now()->addWeek(),
        'ends_at' => now()->addWeek()->addHours(2),
    ]);

    $this->yoga = Event::factory()->create([
        'title' => '雪上ヨガ体験',
        'venue' => '八幡平市民ホール',
        'starts_at' => now()->addWeeks(3),
        'ends_at' => now()->addWeeks(3)->addHours(2),
    ]);
});

test('events can be filtered by part of the title', function () {
    Livewire::test('pages::events.index')
        ->set('search', '朝焼け')
        ->assertSeeText('朝焼けトレッキング')
        ->assertDontSeeText('岩手朝散歩')
        ->assertDontSeeText('雪上ヨガ体験');
});

test('events can be filtered by part of the venue', function () {
    Livewire::test('pages::events.index')
        ->set('search', '体育館')
        ->assertSeeText('岩手朝散歩')
        ->assertDontSeeText('朝焼けトレッキング')
        ->assertDontSeeText('雪上ヨガ体験');
});

test('a keyword matching both venues returns every matching event in order of start time', function () {
    $this->get(route('events.index', ['q' => '八幡平']))
        ->assertSeeTextInOrder(['岩手朝散歩', '雪上ヨガ体験'])
        ->assertDontSeeText('朝焼けトレッキング');
});

test('clearing the keyword shows every event again', function () {
    Livewire::test('pages::events.index')
        ->set('search', '朝焼け')
        ->set('search', '')
        ->assertSeeText('朝焼けトレッキング')
        ->assertSeeText('岩手朝散歩')
        ->assertSeeText('雪上ヨガ体験');
});

test('guests can open the list already filtered by the q parameter', function () {
    $this->get(route('events.index', ['q' => 'ヨガ']))
        ->assertOk()
        ->assertSeeText('雪上ヨガ体験')
        ->assertDontSeeText('朝焼けトレッキング');
});

test('surrounding spaces in the keyword are ignored', function () {
    Livewire::test('pages::events.index')
        ->set('search', '  ヨガ  ')
        ->assertSeeText('雪上ヨガ体験')
        ->assertDontSeeText('岩手朝散歩');
});

test('like wildcards in the keyword are treated as plain characters', function (string $keyword) {
    Livewire::test('pages::events.index')
        ->set('search', $keyword)
        ->assertDontSeeText('朝焼けトレッキング')
        ->assertDontSeeText('岩手朝散歩')
        ->assertDontSeeText('雪上ヨガ体験')
        ->assertSeeText("「{$keyword}」に一致するイベントはありません。");
})->with(['%', '_']);

test('a message is shown when no event matches', function () {
    Livewire::test('pages::events.index')
        ->set('search', '存在しないイベント')
        ->assertSeeText('「存在しないイベント」に一致するイベントはありません。')
        ->assertDontSeeText('イベントはまだありません。');
});
