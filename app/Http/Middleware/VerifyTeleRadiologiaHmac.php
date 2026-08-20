<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyTeleRadiologiaHmac
{
    /** Máxima antigüedad del timestamp en segundos (replay protection). */
    private const MAX_AGE = 300;

    public function handle(Request $request, Closure $next): Response
    {
        $secret = config('services.teleradiologia.hmac_secret');
        if (empty($secret)) {
            return response()->json(['message' => 'HMAC no configurado.'], 503);
        }

        $ts = $request->header('X-Timestamp');
        $signature = $request->header('X-Signature');

        if (empty($ts) || empty($signature)) {
            return response()->json(['message' => 'Faltan X-Timestamp o X-Signature.'], 401);
        }

        $tsInt = (int) $ts;
        if (abs(time() - $tsInt) > self::MAX_AGE) {
            return response()->json(['message' => 'Timestamp expirado o inválido.'], 401);
        }

        $method = $request->method();
        $path = '/'.$request->path();
        $body = $request->getContent();
        $utf8Body = mb_convert_encoding($body ?: '', 'UTF-8', 'ISO-8859-1');
        $bodySha = hash('sha256', $utf8Body);
        $stringToSign = implode("\n", [$method, $path, (string) $ts, $bodySha]);
        $expected = base64_encode(hash_hmac('sha256', $stringToSign, $secret, true));

        if (! hash_equals($expected, $signature)) {
            return response()->json(['message' => 'Firma inválida.'], 401);
        }

        return $next($request);
    }
}
