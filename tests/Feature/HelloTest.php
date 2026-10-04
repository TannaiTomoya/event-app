<?php

use App\Models\User;

test('guests are redirected to the login page from the hello page', function () {
    $this->get(route('hello'))
        ->assertRedirect(route('login'));
});

test('authenticated users can visit the hello page', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('hello'))
        ->assertOk()
        ->assertSeeText('こんにちは、岩手町さん');
});
