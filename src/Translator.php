<?php

namespace larabya\validate;

use larabya\validate\Contracts\TranslatorContract;
use larabya\validate\Utils\Arr;
use larabya\validate\Utils\Str;
use larabya\validate\Utils\Traits\Macroable;

class Translator implements TranslatorContract
{
    use Macroable;

    /**
     * The fallback locale used by the translator.
     *
     * @var string
     */
    protected $fallback;

    /**
     * The array of loaded translation groups.
     *
     * @var array
     */
    protected $loaded = [];

    public function __construct(array $data = [])
    {
        $this->loader = $data;
    }

    /**
     * Determine if a translation exists.
     *
     * @param  string  $key
     * @return bool
     */
    public function has($key)
    {
        return $this->get($key, []) !== $key;
    }

    /**
     * Get the translation for the given key.
     *
     * @param  string  $key
     * @param  array  $replace
     * @param  string|null  $locale
     * @param  bool  $fallback
     * @return string|array
     */
    public function get($key, array $replace = [])
    {
        $line = Arr::get($this->loaded, $key);

        if (is_array($line)) {
            return $line;
        }
        // If the line doesn't exist, we will return back the key which was requested as
        // that will be quick to spot in the UI if language keys are wrong or missing
        // from the application's language files. Otherwise we can return the line.
        return $this->makeReplacements($line ?: $key, $replace);
    }

    /**
     * Add translation lines to the given locale.
     */
    public function addLines(array $lines)
    {
        foreach ($lines as $key => $value) {
            Arr::set($this->loaded, $key, $value);
        }
    }

    /**
     * Make the place-holder replacements on a line.
     *
     * @param  string  $line
     * @param  array  $replace
     * @return string
     */
    protected function makeReplacements($line, array $replace)
    {
        if (empty($replace)) {
            return $line;
        }

        $replace = $this->sortReplacements($replace);

        foreach ($replace as $key => $value) {
            $line = str_replace(
                [':' . $key, ':' . Str::upper($key), ':' . Str::ucfirst($key)],
                [$value, Str::upper($value), Str::ucfirst($value)],
                $line
            );
        }

        return $line;
    }

    /**
     * Sort the replacements array.
     */
    protected function sortReplacements(array $replace): array
    {
        return Arr::sort($replace, function ($value, $key) {
            return mb_strlen($key) * -1;
        });
    }

    /**
     * Determine if the given group has been loaded.
     *
     * @param  string  $namespace
     * @param  string  $group
     * @param  string  $locale
     * @return bool
     */
    protected function isLoaded($namespace, $group, $locale)
    {
        return isset($this->loaded[$namespace][$group][$locale]);
    }

    /**
     * Set the loaded translation groups.
     *
     * @param  array  $loaded
     * @return void
     */
    public function setLoaded(array $loaded)
    {
        $this->loaded = $loaded;
    }

    /**
     * Get the language line loader implementation.
     *
     */
    public function getLoader()
    {
        return $this->loader;
    }

    /**
     * Get the fallback locale being used.
     *
     * @return string
     */
    public function getFallback()
    {
        return $this->fallback;
    }

    /**
     * Set the fallback locale being used.
     *
     * @param  string  $fallback
     * @return void
     */
    public function setFallback($fallback)
    {
        $this->fallback = $fallback;
    }
}
