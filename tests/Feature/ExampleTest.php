<?php

test('root redirects to the admin application', function () {
    $response = $this->get(route('home'));

    $response->assertRedirect('/admin');
});
