<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ApplicationPassword implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || mb_strlen($value) < 12) {
            $fail('Use a password with at least 12 characters.');
        }
        // Bcrypt must not silently truncate a longer UTF-8 password.
        elseif (strlen($value) > 72) {
            $fail('This password is too long. Try a shorter passphrase.');
        }
    }
}
