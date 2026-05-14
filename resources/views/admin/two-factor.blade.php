<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>Admin verification</title>
        <style>
            body { margin: 0; min-height: 100vh; display: grid; place-items: center; font-family: system-ui, sans-serif; background: #0f172a; color: #e5e7eb; }
            main { width: min(100% - 32px, 380px); }
            label, input, button { display: block; width: 100%; box-sizing: border-box; }
            label { margin-bottom: 8px; font-size: 14px; color: #cbd5e1; }
            input { border: 1px solid #334155; border-radius: 8px; padding: 12px; background: #020617; color: #f8fafc; font-size: 18px; }
            button { margin-top: 16px; border: 0; border-radius: 8px; padding: 12px; background: #4f46e5; color: #fff; font-weight: 700; cursor: pointer; }
            .error { margin-top: 10px; color: #fca5a5; font-size: 14px; }
        </style>
    </head>
    <body>
        <main>
            <h1>Admin verification</h1>
            <form method="POST" action="{{ route('admin.two-factor.store') }}">
                @csrf
                <label for="code">Verification code</label>
                <input id="code" name="code" type="password" inputmode="numeric" autocomplete="one-time-code" autofocus required>
                @error('code')
                    <div class="error">{{ $message }}</div>
                @enderror
                <button type="submit">Verify</button>
            </form>
        </main>
    </body>
</html>
