<?php

use App\Models\Event;
use App\Models\TicketType;

test('the lowest ticket price is shown with a tilde', function () {
    $event = Event::factory()->create();
    TicketType::factory()->for($event)->create(['price' => 3000]);
    TicketType::factory()->for($event)->create(['price' => 1500]);

    $this->get(route('events.index'))
        ->assertOk()
        ->assertSeeText('1,500円〜')
        ->assertDontSeeText('3,000円');
});

test('a free lowest ticket is shown as free', function () {
    $event = Event::factory()->create();
    TicketType::factory()->for($event)->create(['price' => 0]);
    TicketType::factory()->for($event)->create(['price' => 2000]);

    $this->get(route('events.index'))->assertSeeText('無料');
});

test('an event without ticket types shows a dash', function () {
    Event::factory()->create();

    $this->get(route('events.index'))->assertSeeText('—');
});

test('prices of other events are not mixed in', function () {
    $event = Event::factory()->create(['title' => '高いイベント']);
    TicketType::factory()->for($event)->create(['price' => 5000]);
    TicketType::factory()->create(['price' => 1000]);

    $this->get(route('events.index'))
        ->assertSeeText('5,000円〜')
        ->assertSeeText('1,000円〜');
});
