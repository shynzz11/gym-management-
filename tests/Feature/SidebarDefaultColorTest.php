<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('it uses distinct default colors for sidebar panel and rail', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('--admin-sidebar-bg: #ffffff;', false)
        ->assertSee('--admin-sidebar-bg-dark: #0f172a;', false)
        ->assertSee('--admin-sidebar-rail-bg: #1e3a8a;', false)
        ->assertSee('--admin-sidebar-rail-bg-dark: #172554;', false);
});
