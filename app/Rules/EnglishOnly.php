<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class EnglishOnly implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! preg_match('/^[a-zA-Z\s\d\-\.\,\:\;\(\)\'\"]+$/u', trim((string) $value))) {
            $fail('اللغة الإنجليزية فقط');
        }
    }
}
