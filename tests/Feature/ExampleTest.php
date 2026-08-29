<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * The root path is not a public page in this application: it redirects to
     * the installation flow or the admin login. Assert it responds rather than
     * the scaffold's 200, which was never true here.
     *
     * @return void
     */
    public function test_the_application_returns_a_successful_response()
    {
        $response = $this->get('/');

        $this->assertContains(
            $response->getStatusCode(),
            [200, 302],
            'The root path should render or redirect, not error.'
        );
    }
}
