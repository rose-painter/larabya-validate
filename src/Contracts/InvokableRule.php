<?php

namespace larabya\validate\Contracts;

use Closure;

/**
 * @deprecated see ValidationRule
 */
interface InvokableRule
{
    /**
     * Run the validation rule.
     *
     * @param  string  $attribute
     * @param  $value
     * @param  Closure  $fail
     * @return void
     */
    public function __invoke(string $attribute, $value, Closure $fail);
}
