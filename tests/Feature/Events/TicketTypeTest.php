<?php

use App\Models\Event;
use App\Models\TicketType;
use App\Models\User;
use App\Policies\TicketTypePolicy;
use Livewire\Livewire;

test('organizers can add a ticket type to their event', function () {
    $event = Event::factory()->create();

    Livewire::actingAs($event->user)
        ->test('pages::events.edit', ['event' => $event])
        ->set('ticketName', '一般')
        ->set('ticketPrice', '3000')
        ->set('ticketCapacity', '30')
        ->call('addTicketType')
        ->assertHasNoErrors()
        ->assertSet('ticketName', '')
        ->assertSee('3,000円');

    $this->assertDatabaseHas('ticket_types', [
        'event_id' => $event->id,
        'name' => '一般',
        'price' => 3000,
        'capacity' => 30,
    ]);
});

test('other users cannot open the edit page where ticket types are added', function () {
    $event = Event::factory()->create();

    Livewire::actingAs(User::factory()->create())
        ->test('pages::events.edit', ['event' => $event])
        ->assertForbidden();

    expect(TicketType::count())->toBe(0);
});

test('ticket type form rejects invalid values', function () {
    $event = Event::factory()->create();

    Livewire::actingAs($event->user)
        ->test('pages::events.edit', ['event' => $event])
        ->set('ticketName', '')
        ->set('ticketPrice', '-1')
        ->set('ticketCapacity', '0')
        ->call('addTicketType')
        ->assertHasErrors(['ticketName', 'ticketPrice', 'ticketCapacity']);

    expect(TicketType::count())->toBe(0);
});

test('deleting an event deletes its ticket types', function () {
    $ticketType = TicketType::factory()->create();

    $ticketType->event->delete();

    expect(TicketType::count())->toBe(0);
});

test('ticket type name must contain a letter', function (string $name) {
    $event = Event::factory()->create();

    Livewire::actingAs($event->user)
        ->test('pages::events.edit', ['event' => $event])
        ->set('ticketName', $name)
        ->set('ticketPrice', '3000')
        ->set('ticketCapacity', '2')
        ->call('addTicketType')
        ->assertHasErrors(['ticketName']);

    expect(TicketType::count())->toBe(0);
})->with(['-1', '---', '123']);

test('organizers can delete a ticket type of their event', function () {
    $ticketType = TicketType::factory()->create();

    Livewire::actingAs($ticketType->event->user)
        ->test('pages::events.edit', ['event' => $ticketType->event])
        ->call('deleteTicketType', $ticketType->id)
        ->assertHasNoErrors();

    expect(TicketType::count())->toBe(0);
});

test('other users cannot open the edit page where ticket types are deleted', function () {
    $ticketType = TicketType::factory()->create();

    Livewire::actingAs(User::factory()->create())
        ->test('pages::events.edit', ['event' => $ticketType->event])
        ->assertForbidden();

    expect(TicketType::count())->toBe(1);
});

test('ticket types of another event cannot be deleted', function () {
    $event = Event::factory()->create();
    $otherTicketType = TicketType::factory()->create();

    Livewire::actingAs($event->user)
        ->test('pages::events.edit', ['event' => $event])
        ->call('deleteTicketType', $otherTicketType->id)
        ->assertNotFound();

    expect(TicketType::count())->toBe(1);
});

test('ticket type name over 50 characters is rejected', function () {
    $event = Event::factory()->create();

    Livewire::actingAs($event->user)
        ->test('pages::events.edit', ['event' => $event])
        ->set('ticketName', str_repeat('あ', 51))
        ->set('ticketPrice', '3000')
        ->set('ticketCapacity', '2')
        ->call('addTicketType')
        ->assertHasErrors(['ticketName']);

    expect(TicketType::count())->toBe(0);
});

test('addTicketType is authorized by the policy even after the page is opened', function () {
    $event = Event::factory()->create();

    $component = Livewire::actingAs($event->user)
        ->test('pages::events.edit', ['event' => $event]);

    $this->actingAs(User::factory()->create());

    $component
        ->set('ticketName', '一般')
        ->set('ticketPrice', '3000')
        ->set('ticketCapacity', '2')
        ->call('addTicketType')
        ->assertForbidden();

    expect(TicketType::count())->toBe(0);
});

test('deleteTicketType is authorized by the policy even after the page is opened', function () {
    $ticketType = TicketType::factory()->create();

    $component = Livewire::actingAs($ticketType->event->user)
        ->test('pages::events.edit', ['event' => $ticketType->event]);

    $this->actingAs(User::factory()->create());

    $component->call('deleteTicketType', $ticketType->id)->assertForbidden();

    expect(TicketType::count())->toBe(1);
});

test('only the event owner is allowed to create or delete ticket types by policy', function () {
    $ticketType = TicketType::factory()->create();
    $owner = $ticketType->event->user;
    $other = User::factory()->create();
    $policy = new TicketTypePolicy;

    expect($policy->create($owner, $ticketType->event))->toBeTrue()
        ->and($policy->create($other, $ticketType->event))->toBeFalse()
        ->and($policy->delete($owner, $ticketType))->toBeTrue()
        ->and($policy->delete($other, $ticketType))->toBeFalse();
});

test('ticket type values on the boundary are accepted', function (string $name, string $price, string $capacity) {
    $event = Event::factory()->create();

    Livewire::actingAs($event->user)
        ->test('pages::events.edit', ['event' => $event])
        ->set('ticketName', $name)
        ->set('ticketPrice', $price)
        ->set('ticketCapacity', $capacity)
        ->call('addTicketType')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('ticket_types', [
        'event_id' => $event->id,
        'name' => $name,
        'price' => (int) $price,
        'capacity' => (int) $capacity,
    ]);
})->with([
    'free ticket' => ['無料招待', '0', '10'],
    'name of exactly 50 characters' => [str_repeat('あ', 50), '1000', '10'],
    'capacity of one' => ['一般', '1000', '1'],
]);

test('ticket type values out of the rules are rejected', function (string $field, string $value, string $rule) {
    $event = Event::factory()->create();

    Livewire::actingAs($event->user)
        ->test('pages::events.edit', ['event' => $event])
        ->set('ticketName', '一般')
        ->set('ticketPrice', '1000')
        ->set('ticketCapacity', '10')
        ->set($field, $value)
        ->call('addTicketType')
        ->assertHasErrors([$field => $rule]);

    expect(TicketType::count())->toBe(0);
})->with([
    'negative price' => ['ticketPrice', '-1', 'min'],
    'decimal price' => ['ticketPrice', '1.5', 'integer'],
    'non-numeric price' => ['ticketPrice', 'abc', 'integer'],
    'zero capacity' => ['ticketCapacity', '0', 'min'],
    'decimal capacity' => ['ticketCapacity', '1.5', 'integer'],
    'non-numeric capacity' => ['ticketCapacity', 'abc', 'integer'],
]);

test('ticket type validation messages are shown in Japanese', function () {
    $event = Event::factory()->create();

    Livewire::actingAs($event->user)
        ->test('pages::events.edit', ['event' => $event])
        ->set('ticketName', '123')
        ->set('ticketPrice', '')
        ->set('ticketCapacity', '0')
        ->call('addTicketType')
        ->assertSee('券種名には、文字を1文字以上含めてください。')
        ->assertSee('価格を入力してください。')
        ->assertSee('定員には、1以上の値を指定してください。');
});
