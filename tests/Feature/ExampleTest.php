<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /** The front page is the member scanner, open without signing in. */
    public function test_the_front_page_is_the_scanner(): void
    {
        $this->get('/')->assertOk()->assertSee('Member QR Scanner');

        $this->get(route('login'))->assertOk();
    }
}
