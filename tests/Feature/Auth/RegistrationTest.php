<?php

test('administrator registration is disabled', function () {
    $this->get('/admin/register')->assertNotFound();
    $this->post('/admin/register')->assertNotFound();
});
