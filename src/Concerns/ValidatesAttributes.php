<?php
namespace larabya\validate\Concerns;


use larabya\validate\Utils\Arr;

trait ValidatesAttributes
{
    /**
     * Validate that a required attribute exists.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function validateRequired($attribute, $value)
    {
        if (is_null($value)) {
            return false;
        } elseif (is_string($value) && trim($value) === '') {
            return false;
        } elseif (is_countable($value) && count($value) < 1) {
            return false;
        }

        return true;
    }

    /**
     * Validate that an attribute contains only alphabetic characters.
     * If the 'ascii' option is passed, validate that an attribute contains only ascii alphabetic characters.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function validateAlpha($attribute, $value, $parameters)
    {
        if (isset($parameters[0]) && $parameters[0] === 'ascii') {
            return is_string($value) && preg_match('/\A[a-zA-Z]+\z/u', $value);
        }

        return is_string($value) && preg_match('/\A[\pL\pM]+\z/u', $value);
    }

    /**
     * Validate that an attribute exists even if not filled.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function validatePresent($attribute, $value)
    {
        return Arr::has($this->data, $attribute);
    }
}