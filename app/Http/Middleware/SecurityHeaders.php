<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Añade headers de seguridad sin romper el frontend legacy.
     */
    public function handle(Request $request, Closure $next): Response
    {

        /** @var Response $response */
        $response = $next($request);

        // Ocultar versión de PHP/Laravel
        $response->headers->remove('X-Powered-By');

        // Evitar que el navegador "adivine" tipos
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Mitiga clickjacking sin romper embebidos internos
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');

        // No filtrar URLs completas a terceros.
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Permissions-Policy:
        // - Por seguridad, bloqueamos APIs innecesarias.
        // - PERO este proyecto usa dictado (SpeechRecognition) y grabación de audio en el módulo de estudios,
        //   por lo que debemos permitir micrófono para el mismo origen.
        //   Si se requiere bloquearlo en ciertos entornos, controla con ENV.
        $allowMicrophone = (bool) env('SECURITY_HEADERS_ALLOW_MICROPHONE', true);
        $microphonePolicy = $allowMicrophone ? 'microphone=(self)' : 'microphone=()';

        $response->headers->set(
            'Permissions-Policy',
            implode(', ', [
                'camera=()',
                $microphonePolicy,
                'geolocation=()',
                'payment=()',
                'usb=()',
                'autoplay=()',
            ])
        );

        // HSTS solo si la request viene por HTTPS
        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        $this->applyCsp($response);

        return $response;
    }

    protected function applyCsp(Response $response)
    {
        /**
         * CSP en modo "Report-Only" (no bloquea; solo registra).
         *
         * Nota: En desarrollo (Vite/HMR) suele generar muchísimo ruido (wss://:5173, @vite/client, etc).
         * Por defecto solo lo activamos en producción, o si se fuerza con ENV:
         * SECURITY_HEADERS_CSP_REPORT_ONLY=true
         */
        $enableCspReportOnly = (bool) env('SECURITY_HEADERS_CSP_REPORT_ONLY', app()->environment('production'));
        if ($enableCspReportOnly) {
            $isLocal = app()->environment('local');

            // Orígenes típicos del devserver (ajusta si usas otro host/puerto)
            $viteOrigins = $isLocal
                ? [
                    'http://localhost:5173',
                    'http://127.0.0.1:5173',
                    'http://lidiag.test:5173',
                    'https://lidiag.test:5173',
                ]
                : [];

            $scriptSrc = array_merge([
                "'self'",
                "'unsafe-inline'",
                "'unsafe-eval'",
                'https://cdn.jsdelivr.net',
                'https://cdn.datatables.net',
                'https://cdnjs.cloudflare.com',
                'https://cdn.rawgit.com',
            ], $viteOrigins);

            $connectSrc = array_merge([
                "'self'",
                'https:',
                // Vite HMR / websockets en dev
                'ws:',
                'wss:',
            ], $viteOrigins);

            // Importante: el layout incluye scripts inline + CDNs, por eso la política es permisiva.
            $cspReportOnly = implode('; ', [
                "default-src 'self'",
                "base-uri 'self'",
                "form-action 'self'",
                "frame-ancestors 'self'",
                "img-src 'self' data: https:",
                "font-src 'self' data: https:",
                "style-src 'self' 'unsafe-inline' https:",
                "script-src ".implode(' ', $scriptSrc),
                "connect-src ".implode(' ', $connectSrc),
            ]);

            // En Producción real (lidiag.com), deberiamos bloquear (quitar -Report-Only)
            // Por seguridad actual, mantenemos Report-Only hasta verificar que no rompa Vue/Vite.
            if (app()->environment('production')) {
                $response->headers->set('Content-Security-Policy', $cspReportOnly);
            } else {
                $response->headers->set('Content-Security-Policy-Report-Only', $cspReportOnly);
            }
        }
    }
}
