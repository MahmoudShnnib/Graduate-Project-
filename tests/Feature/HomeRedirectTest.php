<?php

it('redirects the homepage to the public apply page', function () {
    $this->get('/')->assertRedirect(route('registration.create'));
});
