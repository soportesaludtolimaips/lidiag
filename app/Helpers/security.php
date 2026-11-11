<?php

if (! function_exists('maskData')) {
    /**
     * Enmascara datos sensibles según el tipo indicado.
     *
     * @param  string  $value  Valor original a enmascarar.
     * @param  string  $type  Tipo de dato (document, phone, address, email, dob).
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    function maskData(string $value, string $type): string
    {
        return match ($type) {
            'document' => strlen($value) > 4
                ? str_repeat('*', strlen($value) - 4).substr($value, -4)
                : $value,
            'phone' => strlen($value) > 4
                ? str_repeat('*', strlen($value) - 4).substr($value, -4)
                : $value,
            'address' => preg_replace('/\d+/', '***', $value) ?: $value,
            'email' => preg_replace('/(.?)(.?)(.?)(@.*)/', '***$2$3$4', $value) ?: $value,
            'dob' => strlen($value) >= 4
                ? substr($value, 0, 4).'-**-**'
                : $value,
            default => $value,
        };
    }
}
