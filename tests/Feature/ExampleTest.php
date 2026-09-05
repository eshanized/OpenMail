<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        // When not installed, should redirect to /install
        $response->assertStatus(302);
        $response->assertRedirect('/install');
    }
}