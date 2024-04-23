<?php


namespace larabya\validate\Rules;


class RequiredRule implements RuleInterface
{
    public function passes($attribute, $value): bool {
        return !empty($value);
    }

    public function message(): string {
        return 'The :attribute field is required.';
    }
}