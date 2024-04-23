<?php

namespace larabya\validate\Contracts;

use Closure;

interface ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  string  $attribute
     * @param  $value
     * @param  Closure  $fail
     * @return void
     */
    public function validate(string $attribute, $value, Closure $fail): void;
}
