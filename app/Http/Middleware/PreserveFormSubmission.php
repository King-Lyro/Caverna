<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\ViewErrorBag;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

class PreserveFormSubmission
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        if ($request->header('X-Preserve-Form') !== '1' || ! $response instanceof RedirectResponse) {
            return $response;
        }

        $errors = $request->session()->get('errors');
        $message = $request->session()->get('error');
        if (($errors instanceof ViewErrorBag && $errors->any()) || $message) {
            $request->session()->forget(['errors', 'error', '_old_input']);

            return response()->json([
                'message' => $message ?: $errors->first(),
                'errors' => $errors instanceof ViewErrorBag ? $errors->getBag('default')->messages() : [],
                'csrf_token' => $request->session()->token(),
            ], 422);
        }

        return response()->json(['redirect' => $response->getTargetUrl(), 'csrf_token' => $request->session()->token()]);
    }
}
