<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class LegalController extends Controller
{
    /**
     * Render the Terms of Service page. The Vue component is shared between
     * /terms and /privacy and switches content via the `type` prop.
     */
    public function terms(): Response
    {
        return Inertia::render('Legal', ['type' => 'terms']);
    }

    /**
     * Render the Privacy Policy page.
     */
    public function privacy(): Response
    {
        return Inertia::render('Legal', ['type' => 'privacy']);
    }

    /**
     * Render the Credits / About page. Same Vue component as the legal pages —
     * the page is content-only (no forms, no app state) so reusing the shell
     * keeps the visual treatment consistent and saves us a redundant component.
     */
    public function credits(): Response
    {
        return Inertia::render('Legal', ['type' => 'credits']);
    }
}
