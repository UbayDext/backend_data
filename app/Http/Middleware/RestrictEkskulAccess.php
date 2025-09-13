<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Ekskul;

class RestrictEkskulAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        // Admin bebas
        if (method_exists($user, 'isAdmin') && $user->isAdmin()) {
            return $next($request);
        }

        // Batasi guru ekskul
        if (($user->role ?? null) === 'guru_ekskul') {
            $owned = (int) ($user->ekskul_id ?? 0);
            if ($owned <= 0) {
                return response()->json(['message' => 'Akun belum terikat ekskul.'], 403);
            }

            // Jika ada ekskul_id di route / parameter, harus sama
            $routeParam = $request->route('ekskul') ?? $request->route('ekskul_id');
            if ($routeParam) {
                $routeEkskulId = $routeParam instanceof Ekskul ? (int) $routeParam->id : (int) $routeParam;
                if ($routeEkskulId !== $owned) {
                    return response()->json(['message' => 'Tidak boleh mengakses ekskul lain.'], 403);
                }
            }

            // Jika ada ekskul_id di input/query, harus sama
            if ($request->has('ekskul_id')) {
                if ((int) $request->input('ekskul_id') !== $owned) {
                    return response()->json(['message' => 'Tidak boleh mengakses ekskul lain.'], 403);
                }
            }

            // Paksa ekskul_id → selalu milik user
            $request->merge(['ekskul_id' => $owned]);
            $request->query->set('ekskul_id', $owned);
            if ($request->route() && $request->route()->hasParameter('ekskul_id')) {
                $request->route()->setParameter('ekskul_id', $owned);
            }
        }

        return $next($request);
    }
}
