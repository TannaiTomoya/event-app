<?php

use App\Models\Event;
use App\Models\TicketType;
use App\Models\User;
use Livewire\Livewire;

test('organizers can duplicate their event one week later without ticket types', function () {
    $event = Event::factory()->create(['title' => '朝焼けトレッキング']);
    TicketType::factory()->for($event)->create();

    $component = Livewire::actingAs($event->user)
        ->test('pages::events.edit', ['event' => $event])
        ->call('duplicate');

    $copy = Event::query()->where('id', '!=', $event->id)->sole();

    $component->assertRedirect(route('events.edit', $copy));

    expect($copy->title)->toBe('朝焼けトレッキング（複製）')
        ->and($copy->user_id)->toBe($event->user_id)
        ->and($copy->description)->toBe($event->description)
        ->and($copy->venue)->toBe($event->venue)
        ->and($copy->starts_at->equalTo($event->starts_at->copy()->addWeek()))->toBeTrue()
        ->and($copy->ends_at->equalTo($event->ends_at->copy()->addWeek()))->toBeTrue()
        ->and($copy->ticketTypes()->count())->toBe(0)
        ->and($event->ticketTypes()->count())->toBe(1)
        ->and(session('status'))->toBe('イベントを複製しました。');
});

test('the duplicated title stays within 100 characters', function () {
    $event = Event::factory()->create(['title' => str_repeat('あ', 100)]);

    Livewire::actingAs($event->user)
        ->test('pages::events.edit', ['event' => $event])
        ->call('duplicate');

    $copy = Event::query()->where('id', '!=', $event->id)->sole();

    expect(mb_strlen($copy->title))->toBe(100)
        ->and($copy->title)->toEndWith('（複製）');
});

test('other users cannot open the edit page to duplicate an event', function () {
    $event = Event::factory()->create();

    Livewire::actingAs(User::factory()->create())
        ->test('pages::events.edit', ['event' => $event])
        ->assertForbidden();

    expect(Event::count())->toBe(1);
});

test('duplicate is authorized even after the page is opened', function () {
    $event = Event::factory()->create();

    $component = Livewire::actingAs($event->user)
        ->test('pages::events.edit', ['event' => $event]);

    $this->actingAs(User::factory()->create());

    $component->call('duplicate')->assertForbidden();

    expect(Event::count())->toBe(1);
});

test('the duplicate button is shown on the edit page', function () {
    $event = Event::factory()->create();

    Livewire::actingAs($event->user)
        ->test('pages::events.edit', ['event' => $event])
        ->assertSee('イベントを複製');
});
