<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminTwoFactorController extends Controller
{
    public function create(): View|RedirectResponse
    {
        if (! config('security.admin_two_factor.enabled')) {
            return redirect('/admin');
        }

        return view('admin.two-factor');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'string'],
        ]);

        $expected = (string) config('security.admin_two_factor.code', '');
        if ($expected === '' || ! hash_equals($expected, trim((string) $request->input('code')))) {
            return back()->withErrors(['code' => 'Invalid verification code.']);
        }

        $request->session()->put('admin_2fa_passed_at', now()->timestamp);

        return redirect()->intended('/admin');
    }
}
