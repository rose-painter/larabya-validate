<?php
namespace larabya\validate;

class ValidatorFactory{
    /**
     * @var bool
     * 是否在最终的数据中排除那些没有进行验证的键值对
     */
    protected $excludeUnvalidatedArrayKeys = true;

    /**
     * The IoC container instance.
     */
    protected $container;

    /**
     * The Validator resolver instance.
     *
     * @var \Closure
     */
    protected $resolver;

    public function __construct(Translator $translator = null, $container = null)
    {
        $this->container = $container;
        $this->translator = $translator ? : $this->getDefaultTranslator();
    }

    private function getDefaultTranslator()
    {
        return  new Translator(require __DIR__.'/Config/validation.php');
    }

    public function make(array $data, array $rules, array $messages = [], array $attributes = []) {
        $validator = $this->resolve(
            $data, $rules, $messages, $attributes
        );
        // Next we'll set the IoC container instance of the validator, which is used to
        // resolve out class based validator extensions. If it is not set then these
        // types of extensions will not be possible on these validation instances.
        if (! is_null($this->container)) {
            $validator->setContainer($this->container);
        }

        $validator->excludeUnvalidatedArrayKeys = $this->excludeUnvalidatedArrayKeys;

        return $validator;
    }

    /**
     * Resolve a new Validator instance.
     *
     * @param  array  $data
     * @param  array  $rules
     * @param  array  $messages
     * @param  array  $attributes
     * @return \larabya\validate\Validator
     */
    protected function resolve(array $data, array $rules, array $messages, array $attributes)
    {
        if (is_null($this->resolver)) {
            return new Validator($this->translator, $data, $rules, $messages, $attributes);
        }

        return call_user_func($this->resolver, $this->translator, $data, $rules, $messages, $attributes);
    }
}