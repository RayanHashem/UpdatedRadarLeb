<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LocaleController extends Controller
{
    /**
     * POST /locale/{locale}.
     *
     * Persists the requested locale into the session. The actual `app->setLocale()`
     * call happens on the NEXT request, in App\Http\Middleware\SetLocale.
     *
     * Locale strings are validated against config('contact.locales') so a user
     * cannot trick us into setting an unsupported value.
     */
    public function update(Request $request, string $locale): RedirectResponse
    {
        $supported = array_keys(config('contact.locales', ['en' => 'English']));

        abort_unless(in_array($locale, $supported, true), 422, 'Unsupported locale');

        $request->session()->put('locale', $locale);

        return back();
    }
}
