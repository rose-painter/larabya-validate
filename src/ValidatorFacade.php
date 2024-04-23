<?php
namespace larabya\validate;

/**
 * Class ValidatorFacade
 * @package larabya\validate
 * @method static Validator make(array $data, array $rules, array $messages = [], array $attributes = [])
 */
class ValidatorFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'validator';
    }
}