<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * /terms, /privacy, /credits all hit LegalController and render the same
 * Inertia 'Legal' component, just with a different `type` prop. These tests
 * lock that wiring in — if anyone accidentally renames the route or changes
 * the controller method signature, this catches it before deploy.
 *
 * Inertia responses come back as a normal HTTP 200 with a JSON body when
 * `X-Inertia: true` is sent; otherwise it's an HTML page that wraps the
 * same data. We hit the HTML path because that's what a browser navigation
 * actually does.
 */
class LegalPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_terms_route_returns_200(): void
    {
        $this->get('/terms')->assertStatus(200);
    }

    public function test_privacy_route_returns_200(): void
    {
        $this->get('/privacy')->assertStatus(200);
    }

    public function test_credits_route_returns_200(): void
    {
        $this->get('/credits')->assertStatus(200);
    }

    /**
     * Each route must be registered under its named alias so route('credits')
     * etc. work in templates. Catches typos in routes/web.php.
     */
    public function test_named_routes_are_registered(): void
    {
        $this->assertSame(url('/terms'),   route('terms'));
        $this->assertSame(url('/privacy'), route('privacy'));
        $this->assertSame(url('/credits'), route('credits'));
    }
}
