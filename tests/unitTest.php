<?php
require_once __DIR__ . '/../vendor/autoload.php';

use larabya\validate\ValidationException;
use larabya\validate\ValidatorFacade;

try {
    $validator = ValidatorFacade::make(array(
        'id'=>12,
        'en_name'=>'堆放室'), [
        'id' => ['required'],
        'en_name' => ['required','alpha:ascii']
    ],[
        'id.required' => 'We need to know your id!',
        'en_name.required' => 'We need to know your name!',
        'en_name.alpha' => 'your name must be alpha:ascii!',
    ])->validate();
} catch (ValidationException $e){
    $errors = $e->validator->errors()->all();
    print_r($errors);
}

?>