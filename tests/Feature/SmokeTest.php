<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('loads the public homepage', function () {
    $response = $this->get('/');

    $response->assertOk();
});
