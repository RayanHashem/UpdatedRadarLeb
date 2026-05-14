<?php

namespace App\Http\Controllers\Api;

use App\Actions\Game\AttemptScan;
use App\Actions\Game\BuildGameProgress;
use App\Http\Controllers\Controller;
use App\Models\Game;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * SPA-facing JSON endpoints for games.
 *
 * The controller is intentionally thin: business logic lives in
 * App\Actions\Game\*. The controller only knows how to translate HTTP →
 * action input → JSON response.
 */
class GameController extends Controller
{
    public function __construct(
        private readonly BuildGameProgress $buildProgress,
        private readonly AttemptScan $attemptScan,
    ) {
    }

    public function nonce(Request $request): JsonResponse
    {
        $nonce = Str::random(40);
        $request->session()->put("scan_nonces.{$nonce}", now()->timestamp);

        return response()->json(['nonce' => $nonce]);
    }

    /**
     * Lightweight game catalog for the SPA. Dashboard.vue reads this on
     * mount and on focus to refresh the per-user progress without forcing
     * a full Inertia round-trip.
     */
    public function index(): JsonResponse
    {
        $user = auth()->user();

        $games = Game::orderBy('id')->get()->map(fn (Game $g) => [
            'id'         => $g->id,
            'name'       => $g->name,
            'price'      => $g->price,
            'image'      => $g->image_path,
            'progress'   => ($this->buildProgress)($g, $user, includeWinEligibility: true),
            'is_enabled' => (bool) $g->is_enabled,
        ]);

        return response()->json($games);
    }

    /**
     * POST /scan/{game}.
     *
     * Always echoes the authoritative wallet balance in the response (success
     * or failure) so the UI can update Radar Cash on any outcome without a
     * full reload — avoids the "I have to refresh the page" bug we used to
     * see when an abort_*() call fired before the wallet round-tripped.
     */
    public function scan(Request $request, Game $game): JsonResponse
    {
        $user = auth()->user();

        if (! $game->is_enabled) {
            return response()->json([
                'message' => 'Prize is disabled',
                'wallet'  => (float) $user->wallet_balance,
            ], 403);
        }

        try {
            $context = $this->validatedScanContext($request) + [
                'ip_address' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 1000),
            ];

            return response()->json(($this->attemptScan)($user, $game, $context), 200);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Scan verification failed',
                'errors'  => $e->errors(),
                'wallet'  => (float) $user->wallet_balance,
            ], 422);
        } catch (HttpException $e) {
            $user->refresh();

            return response()->json([
                'message' => $e->getMessage() ?: 'Scan failed',
                'wallet'  => (float) $user->wallet_balance,
            ], $e->getStatusCode());
        }
    }

    /**
     * Keep sensitive scan controls server-side. The client may send location
     * and a one-time nonce, but the server decides whether they are required.
     */
    private function validatedScanContext(Request $request): array
    {
        $rules = [
            'nonce' => ['nullable', 'string', 'max:80'],
            'location' => ['nullable', 'array'],
            'location.lat' => ['nullable', 'numeric', 'between:-90,90'],
            'location.lng' => ['nullable', 'numeric', 'between:-180,180'],
            'location.accuracy' => ['nullable', 'numeric', 'min:0', 'max:10000'],
            'location.timestamp' => ['nullable', 'integer', 'min:0'],
        ];

        if (config('security.scan.nonce_required')) {
            $rules['nonce'][0] = 'required';
        }

        if (config('security.scan.location_required')) {
            $rules['location'][0] = 'required';
            $rules['location.lat'][0] = 'required';
            $rules['location.lng'][0] = 'required';
            $rules['location.accuracy'][0] = 'required';
            $rules['location.timestamp'][0] = 'required';
        }

        $context = $request->validate($rules);

        if (config('security.scan.nonce_required')) {
            $nonce = (string) $request->input('nonce');
            $key = "scan_nonces.{$nonce}";
            $issuedAt = (int) $request->session()->pull($key, 0);

            if ($issuedAt === 0 || $issuedAt < now()->subMinutes(5)->timestamp) {
                throw ValidationException::withMessages(['nonce' => 'Scan verification expired. Please try again.']);
            }
        }

        if (config('security.scan.location_required')) {
            $location = $context['location'] ?? [];
            $maxAgeMs = (int) config('security.scan.location_max_age_seconds', 30) * 1000;
            $maxAccuracy = (float) config('security.scan.location_max_accuracy_meters', 100);
            $ageMs = now()->getTimestampMs() - (int) ($location['timestamp'] ?? 0);

            if ($ageMs < 0 || $ageMs > $maxAgeMs) {
                throw ValidationException::withMessages(['location' => 'Location is too old. Please scan again.']);
            }

            if ((float) ($location['accuracy'] ?? 10001) > $maxAccuracy) {
                throw ValidationException::withMessages(['location.accuracy' => 'Location accuracy is too low. Please try again outdoors.']);
            }
        }

        return $context;
    }
}
