<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class MinArabicWords implements ValidationRule
{
    public function __construct(
        private int $min,
        private string $message = '',
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $words = preg_split('/\s+/', trim((string) $value), -1, PREG_SPLIT_NO_EMPTY);

        if (count($words) < $this->min) {
            $fail($this->message !== '' ? $this->message : "يجب إدخال {$this->min} كلمات على الأقل");
        }
    }
}
