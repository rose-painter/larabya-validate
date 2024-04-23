<?php
namespace larabya\validate;

use Closure;
use larabya\validate\Rules\RuleInterface;

class ClosureValidationRule implements RuleInterface
{
    /**
     * The callback that validates the attribute.
     *
     * @var Closure
     */
    public $callback;

    /**
     * Indicates if the validation callback failed.
     *
     * @var bool
     */
    public $failed = false;

    /**
     * The validation error message.
     *
     * @var null|string
     */
    public $message;

    /**
     * Create a new Closure based validation rule.
     */
    public function __construct(Closure $callback)
    {
        $this->callback = $callback;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param $attribute
     * @param mixed $value
     * @return bool
     */
    public function passes($attribute, $value): bool
    {
        $this->failed = false;

        $this->callback->__invoke($attribute, $value, function ($message) {
            $this->failed = true;

            $this->message = $message;
        });

        return !$this->failed;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message(): ?string
    {
        return $this->message;
    }
}
