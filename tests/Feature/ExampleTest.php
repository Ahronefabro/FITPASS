<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /** Every page is behind the sign-in, so the front page is the login. */
    public function test_the_application_sends_a_visitor_to_the_login(): void
    {
        $this->get('/')->assertRedirect(route('login'));

        $this->get(route('login'))->assertOk();
    }
}
