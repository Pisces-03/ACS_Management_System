<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class StrongPassword implements ValidationRule
{
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $password = (string) $value;
        
        // Check minimum length
        if (strlen($password) < 8) {
            $fail('The :attribute must be at least 8 characters long.');
            return;
        }
        
        // Check for uppercase letter
        if (!preg_match('/[A-Z]/', $password)) {
            $fail('The :attribute must contain at least one uppercase letter.');
            return;
        }
        
        // Check for lowercase letter
        if (!preg_match('/[a-z]/', $password)) {
            $fail('The :attribute must contain at least one lowercase letter.');
            return;
        }
        
        // Check for number
        if (!preg_match('/\d/', $password)) {
            $fail('The :attribute must contain at least one number.');
            return;
        }
        
        // Check for special character
        if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
            $fail('The :attribute must contain at least one special character (!@#$%^&*(),.?":{}|<>).');
            return;
        }
    }
}
