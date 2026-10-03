<?php

test('hello page is publicly accessible', function () {
    $this->get('/hello')
        ->assertOk()
        ->assertSeeText('Hello, World!');
});
