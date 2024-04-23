<?php

namespace larabya\validate\Contracts;

interface TranslatorContract
{
    /**
     * Get the translation for a given key.
     *
     * @param  string  $key
     * @param  array  $replace
     * @return mixed
     */
    public function get($key, array $replace = []);

}
