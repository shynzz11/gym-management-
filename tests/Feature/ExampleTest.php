<?php

test('the root route redirects to the admin login', function () {
    $this->get(route('home'))
        ->assertRedirect(route('admin.login'));
});
