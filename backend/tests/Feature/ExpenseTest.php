<?php

it('has expense page', function () {
    $response = $this->get('/expense');

    $response->assertStatus(200);
});
