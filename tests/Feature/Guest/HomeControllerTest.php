<?php

test('home redirects to the admin login', function () {
    $this->get(route('home'))
        ->assertRedirect(route('admin.login'));
});
