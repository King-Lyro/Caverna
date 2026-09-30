<?php

use App\Http\Middleware\EnsureAdmin;
use App\Http\Middleware\EnsureStaff;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command('cavernas:advance-lifecycle')->weeklyOn(0, '00:05');
        $schedule->command('cavernas:process-pregnancies')->dailyAt('00:00');
    })
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'staff' => EnsureStaff::class,
            'admin' => EnsureAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (HttpExceptionInterface $exception, Request $request) {
            if ($request->expectsJson()) {
                return null;
            }

            $status = $exception->getStatusCode();
            if ($status === 422 && ! $request->isMethod('GET')) {
                return back()->withInput()->with('error', $exception->getMessage() ?: 'Please review your submission.');
            }

            if (in_array($status, [403, 404, 422], true)) {
                $message = match ($status) {
                    403 => 'You do not have permission to access this page or action.',
                    404 => 'The page or item you requested could not be found.',
                    default => 'Please review your request.',
                };

                return response()->view('errors.inline', ['status' => $status, 'pageError' => $status === 422 && $exception->getMessage() ? $exception->getMessage() : $message], $status);
            }

            return null;
        });
    })->create();
