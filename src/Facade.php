<?php
namespace larabya\validate;

use Exception;

class Facade
{
    public static function __callStatic($name, $arguments){
        if($name == 'make'){
            return (new ValidatorFactory)->make(...$arguments);
        }
        throw new Exception("Method ".$name." not found.");
    }
}