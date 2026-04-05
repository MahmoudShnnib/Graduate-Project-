<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ArabicOnly implements ValidationRule
{
    /**
     * Allow digits alongside Arabic letters (e.g. for addresses).
     */
    public function __construct(private bool $allowNumbers = false) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $pattern = $this->allowNumbers
            ? '/^[\p{Arabic}\d\s،,\-\.]+$/u'
            : '/^[\p{Arabic}\s]+$/u';

        if (! preg_match($pattern, trim((string) $value))) {
            $fail('اللغة العربية فقط');
        }
    }
}
