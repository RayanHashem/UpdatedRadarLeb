<?php

namespace App\Http\Responses\Filament;

use Filament\Facades\Filament;
use Filament\Http\Responses\Auth\Contracts\LoginResponse as LoginResponseContract;

class AdminLoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {
        $intended = $request->session()->pull('url.intended');

        if (is_string($intended) && str_contains($intended, '/admin')) {
            return redirect($intended);
        }

        return redirect(Filament::getUrl());
    }
}
