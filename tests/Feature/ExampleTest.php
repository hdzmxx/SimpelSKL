<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_unauthenticated_users_are_redirected_to_login(): void
    {
        $response = $this->get('/');

        $response->assertRedirect(route('login'));
    }
}
