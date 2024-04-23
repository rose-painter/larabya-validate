<?php
namespace larabya\validate;

use InvalidArgumentException;
use larabya\validate\Contracts\ImplicitRule;
use larabya\validate\Contracts\RuleContract;
use larabya\validate\Contracts\TranslatorContract;
use larabya\validate\Utils\Arr;
use larabya\validate\Utils\Str;
use stdClass;

class Validator {

    use Concerns\FormatsMessages,
        Concerns\ValidatesAttributes;

    protected $translator;
    /**
     * The current placeholder for dots in rule keys.
     *
     * @var string
     */
    protected $dotPlaceholder;

    /**
     * The failed validation rules.
     *
     * @var array
     */
    protected $failedRules = [];
    protected $data = [];
    /**
     * The initial rules provided.
     *
     * @var array
     */
    protected $initialRules;
    protected $rules = [];
    /**
     * The array of wildcard attributes with their asterisks expanded.
     *
     * @var array
     */
    protected $implicitAttributes = [];
    protected $errors = [];

    protected $stopOnFirstFailure = false;

    /**
     * The cached data for the "distinct" rule.
     *
     * @var array
     */
    protected $distinctValues = [];

    /**
     * All of the registered "after" callbacks.
     *
     * @var array
     */
    protected $after = [];

    /**
     * The array of custom error messages.
     *
     * @var array
     */
    public $customMessages = [];

    /**
     * The message bag instance.
     *
     * @var MessageBag
     */
    protected $messages;

    /**
     * The array of custom attribute names.
     *
     * @var array
     */
    public $customAttributes = [];

    /**
     * The numeric related validation rules.
     *
     * @var string[]
     */
    protected $numericRules = ['Numeric', 'Integer'];
    /**
     * Attributes that should be excluded from the validated data.
     *
     * @var array
     */
    protected $excludeAttributes = [];
    /**
     * The validation rules which depend on other fields as parameters.
     *
     * @var string[]
     */
    protected $dependentRules = [
        'After',
        'AfterOrEqual',
        'Before',
        'BeforeOrEqual',
        'Confirmed',
        'Different',
        'ExcludeIf',
        'ExcludeUnless',
        'ExcludeWith',
        'ExcludeWithout',
        'Gt',
        'Gte',
        'Lt',
        'Lte',
        'AcceptedIf',
        'DeclinedIf',
        'RequiredIf',
        'RequiredIfAccepted',
        'RequiredUnless',
        'RequiredWith',
        'RequiredWithAll',
        'RequiredWithout',
        'RequiredWithoutAll',
        'PresentIf',
        'PresentUnless',
        'PresentWith',
        'PresentWithAll',
        'Prohibited',
        'ProhibitedIf',
        'ProhibitedUnless',
        'Prohibits',
        'MissingIf',
        'MissingUnless',
        'MissingWith',
        'MissingWithAll',
        'Same',
        'Unique',
    ];
    /**
     * The validation rules that can exclude an attribute.
     *
     * @var string[]
     */
    protected $excludeRules = ['Exclude', 'ExcludeIf', 'ExcludeUnless', 'ExcludeWith', 'ExcludeWithout'];
    /**
     * The validation rules that imply the field is required.
     *
     * @var string[]
     */
    protected $implicitRules = [
        'Accepted',
        'AcceptedIf',
        'Declined',
        'DeclinedIf',
        'Filled',
        'Missing',
        'MissingIf',
        'MissingUnless',
        'MissingWith',
        'MissingWithAll',
        'Present',
        'PresentIf',
        'PresentUnless',
        'PresentWith',
        'PresentWithAll',
        'Required',
        'RequiredIf',
        'RequiredIfAccepted',
        'RequiredUnless',
        'RequiredWith',
        'RequiredWithAll',
        'RequiredWithout',
        'RequiredWithoutAll',
    ];
    /**
     * The size related validation rules.
     *
     * @var string[]
     */
    protected $sizeRules = ['Size', 'Between', 'Min', 'Max', 'Gt', 'Lt', 'Gte', 'Lte'];
    /**
     * The array of fallback error messages.
     *
     * @var array
     */
    public $fallbackMessages = [];
    /**
     * Indicates that unvalidated array keys should be excluded, even if the parent array was validated.
     *
     * @var bool
     */
    public $excludeUnvalidatedArrayKeys = false;
    /**
     * All of the custom replacer extensions.
     *
     * @var array
     */
    public $replacers = [];
    /**
     * The exception to throw upon failure.
     *
     * @var string
     */
    protected $exception = ValidationException::class;
    /**
     * @var
     */
    protected $container;

    public function __construct(Translator $translator, array $data, array $rules,
                                array $messages = [], array $attributes = [])
    {
        $this->dotPlaceholder = Str::random();

        $this->initialRules = $rules;
        $this->translator = $translator;
        $this->customMessages = $messages;
        $this->data = $this->parseData($data);
        $this->customAttributes = $attributes;

        $this->setRules($rules);
    }

    /**
     * Set the IoC container instance.
     */
    public function setContainer($container)
    {
        $this->container = $container;
    }

    /**
     * Get the Translator implementation.
     */
    public function getTranslator(): TranslatorContract
    {
        return $this->translator;
    }

    /**
     * Set the Translator implementation.
     */
    public function setTranslator(TranslatorContract $translator)
    {
        $this->translator = $translator;
    }

    /**
     * Set the validation rules.
     *
     * @param  array  $rules
     * @return $this
     */
    public function setRules(array $rules)
    {
        foreach ($rules as $key => $value) {
            $assoc = [str_replace('\.', $this->dotPlaceholder, $key) => $value];
            foreach ($assoc as $mapKey => $mapValue) {
                $rules[$mapKey] = $mapValue;
            }
        }

        $this->initialRules = $rules;

        $this->rules = [];

        $this->addRules($rules);

        return $this;
    }

    /**
     * Parse the given rules and merge them into current rules.
     */
    public function addRules(array $rules)
    {
        // The primary purpose of this parser is to expand any "*" rules to the all
        // of the explicit rules needed for the given data. For example the rule
        // names.* would get expanded to names.0, names.1, etc. for this data.
        $response = (new ValidationRuleParser($this->data))
            ->explode($rules);

        $this->rules = array_merge_recursive(
            $this->rules,
            $response->rules
        );

        $this->implicitAttributes = array_merge(
            $this->implicitAttributes,
            $response->implicitAttributes
        );
    }

    /**
     * Get the validation rules.
     *
     * @return array
     */
    public function getRules()
    {
        return $this->rules;
    }

    /**
     * Get the data under validation.
     *
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Set the data under validation.
     *
     * @param  array  $data
     * @return $this
     */
    public function setData(array $data)
    {
        $this->data = $this->parseData($data);

        $this->setRules($this->initialRules);

        return $this;
    }

    /**
     * Get the value of a given attribute.
     *
     * @param  string  $attribute
     * @return mixed
     */
    public function getValue($attribute)
    {
        return Arr::get($this->data, $attribute);
    }

    /**
     * Run the validator's rules against its data.
     *
     * @return array
     *
     * @throws ValidationException
     */
    public function validate() {
        throw_if($this->fails(), $this->exception, $this);

        return $this->validated();
    }

    /**
     * Run the validator's rules against its data.
     *
     * @param  string  $errorBag
     * @return array
     *
     * @throws \larabya\validate\ValidationException
     */
    public function validateWithBag(string $errorBag)
    {
        try {
            return $this->validate();
        } catch (ValidationException $e) {
            $e->errorBag = $errorBag;

            throw $e;
        }
    }

    /**
     * Get the attributes and values that were validated.
     *
     * @return array
     *
     * @throws ValidationException
     */
    public function validated()
    {
        throw_if($this->invalid(), $this->exception, $this);

        $results = [];

        $missingValue = new stdClass;

        foreach ($this->getRules() as $key => $rules) {
            $value = data_get($this->getData(), $key, $missingValue);

            if ($this->excludeUnvalidatedArrayKeys &&
                in_array('array', $rules) &&
                $value !== null &&
                ! empty(preg_grep('/^'.preg_quote($key, '/').'\.+/', array_keys($this->getRules())))) {
                continue;
            }

            if ($value !== $missingValue) {
                Arr::set($results, $key, $value);
            }
        }

        return $this->replacePlaceholders($results);
    }

    /**
     * Returns the data which was valid.
     *
     * @return array
     */
    public function valid()
    {
        if (! $this->messages) {
            $this->passes();
        }

        return array_diff_key(
            $this->data, $this->attributesThatHaveMessages()
        );
    }

    /**
     * Returns the data which was invalid.
     *
     * @return array
     */
    public function invalid()
    {
        if (! $this->messages) {
            $this->passes();
        }

        $invalid = array_intersect_key(
            $this->data, $this->attributesThatHaveMessages()
        );

        $result = [];

        $failed = Arr::only(Arr::dot($invalid), array_keys($this->failed()));

        foreach ($failed as $key => $failure) {
            Arr::set($result, $key, $failure);
        }

        return $result;
    }

    /**
     * Generate an array of all attributes that have messages.
     *
     * @return array
     */
    protected function attributesThatHaveMessages()
    {
        return collect($this->messages()->toArray())->map(function ($message, $key) {
            return explode('.', $key)[0];
        })->unique()->flip()->all();
    }

    /**
     * Get the failed validation rules.
     *
     * @return array
     */
    public function failed()
    {
        return $this->failedRules;
    }

    /**
     * Parse the data array, converting dots and asterisks.
     *
     * @param  array  $data
     * @return array
     */
    public function parseData(array $data)
    {
        $newData = [];

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $value = $this->parseData($value);
            }

            $newData[$key] = $value;
        }

        return $newData;
    }

    /**
     * Validate a given attribute against a rule.
     *
     * @param  string  $attribute
     * @param  string  $rule
     * @return void
     */
    protected function validateAttribute($attribute, $rule)
    {
        $this->currentRule = $rule;

        [$rule, $parameters] = ValidationRuleParser::parse($rule);

        if ($rule === '') {
            return;
        }

        // First we will get the correct keys for the given attribute in case the field is nested in
        // an array. Then we determine if the given rule accepts other field names as parameters.
        // If so, we will replace any asterisks found in the parameters with the correct keys.
        if ($this->dependsOnOtherFields($rule)) {
            $parameters = $this->replaceDotInParameters($parameters);

            if ($keys = $this->getExplicitKeys($attribute)) {
                $parameters = $this->replaceAsterisksInParameters($parameters, $keys);
            }
        }

        $value = $this->getValue($attribute);

        // If we have made it this far we will make sure the attribute is validatable and if it is
        // we will call the validation method with the attribute. If a method returns false the
        // attribute is invalid and we will add a failure message for this failing attribute.
        $validatable = $this->isValidatable($rule, $attribute, $value);

        if ($rule instanceof RuleContract) {
            return $validatable
                ? $this->validateUsingCustomRule($attribute, $value, $rule)
                : null;
        }

        $method = "validate{$rule}";

        $flag = ! $this->$method($attribute, $value, $parameters, $this);

        if ($validatable && $flag) {
            $this->addFailure($attribute, $rule, $parameters);
        }
    }

    /**
     * Validate an attribute using a custom rule object.
     *
     * @param mixed $value
     * @return void
     */
    protected function validateUsingCustomRule(string $attribute, $value, RuleContract $rule)
    {
        $attribute = $this->replacePlaceholderInString($attribute);

        $value = is_array($value) ? $this->replacePlaceholders($value) : $value;

        if (!$rule->passes($attribute, $value)) {
            $this->failedRules[$attribute][get_class($rule)] = [];

            $messages = $rule->message();

            $messages = $messages ? (array) $messages : [get_class($rule)];

            foreach ($messages as $message) {
                $this->messages->add($attribute, $this->makeReplacements(
                    $message,
                    $attribute,
                    get_class($rule),
                    []
                ));
            }
        }
    }

    /**
     * Replace the placeholders used in data keys.
     *
     * @param  array  $data
     * @return array
     */
    protected function replacePlaceholders($data)
    {
        $originalData = [];

        foreach ($data as $key => $value) {
            $originalData[$this->replacePlaceholderInString($key)] = is_array($value)
                ? $this->replacePlaceholders($value)
                : $value;
        }

        return $originalData;
    }

    /**
     * Replace the placeholders in the given string.
     *
     * @param  string  $value
     * @return string
     */
    protected function replacePlaceholderInString(string $value)
    {
        return str_replace(
            [$this->dotPlaceholder, '__asterisk__'],
            ['.', '*'],
            $value
        );
    }

    /**
     * Determine if the given rule depends on other fields.
     *
     * @param  string  $rule
     * @return bool
     */
    protected function dependsOnOtherFields($rule)
    {
        return in_array($rule, $this->dependentRules);
    }

    /**
     * Replace each field parameter which has an escaped dot with the dot placeholder.
     *
     * @param  array  $parameters
     * @return array
     */
    protected function replaceDotInParameters(array $parameters)
    {
        return array_map(function ($field) {
            return str_replace('\.', $this->dotPlaceholder, $field);
        }, $parameters);
    }

    /**
     * Get the explicit keys from an attribute flattened with dot notation.
     *
     * E.g. 'foo.1.bar.spark.baz' -> [1, 'spark'] for 'foo.*.bar.*.baz'
     *
     * @param  string  $attribute
     * @return array
     */
    protected function getExplicitKeys($attribute)
    {
        $pattern = str_replace('\*', '([^\.]+)', preg_quote($this->getPrimaryAttribute($attribute), '/'));

        if (preg_match('/^'.$pattern.'/', $attribute, $keys)) {
            array_shift($keys);

            return $keys;
        }

        return [];
    }

    /**
     * Replace each field parameter which has asterisks with the given keys.
     *
     * @return array
     */
    protected function replaceAsterisksInParameters(array $parameters, array $keys)
    {
        return array_map(function ($field) use ($keys) {
            return vsprintf(str_replace('*', '%s', $field), $keys);
        }, $parameters);
    }

    /**
     * Determine if the attribute is validatable.
     *
     * @param  object|string  $rule
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    protected function isValidatable($rule, $attribute, $value)
    {
        if (in_array($rule, $this->excludeRules)) {
            return true;
        }

        return $this->presentOrRuleIsImplicit($rule, $attribute, $value) &&
            $this->passesOptionalCheck($attribute) &&
            $this->isNotNullIfMarkedAsNullable($rule, $attribute) &&
            $this->hasNotFailedPreviousRuleIfPresenceRule($rule, $attribute);
    }

    /**
     * Determine if the field is present, or the rule implies required.
     *
     * @param  object|string  $rule
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    protected function presentOrRuleIsImplicit($rule, $attribute, $value)
    {
        if (is_string($value) && trim($value) === '') {
            return $this->isImplicit($rule);
        }

        return $this->validatePresent($attribute, $value) ||
            $this->isImplicit($rule);
    }

    /**
     * Determine if a given rule implies the attribute is required.
     *
     * @param  object|string  $rule
     * @return bool
     */
    protected function isImplicit($rule)
    {
        return $rule instanceof ImplicitRule ||
            in_array($rule, $this->implicitRules);
    }

    /**
     * Determine if the attribute passes any optional check.
     *
     * @param  string  $attribute
     * @return bool
     */
    protected function passesOptionalCheck($attribute)
    {
        if (! $this->hasRule($attribute, ['Sometimes'])) {
            return true;
        }

        $data = ValidationData::initializeAndGatherData($attribute, $this->data);

        return array_key_exists($attribute, $data)
            || array_key_exists($attribute, $this->data);
    }

    /**
     * Determine if the attribute fails the nullable check.
     *
     * @param  string  $rule
     * @param  string  $attribute
     * @return bool
     */
    protected function isNotNullIfMarkedAsNullable($rule, $attribute)
    {
        if ($this->isImplicit($rule) || ! $this->hasRule($attribute, ['Nullable'])) {
            return true;
        }

        return ! is_null(Arr::get($this->data, $attribute, 0));
    }

    /**
     * Determine if it's a necessary presence validation.
     *
     * This is to avoid possible database type comparison errors.
     *
     * @param  string  $rule
     * @param  string  $attribute
     * @return bool
     */
    protected function hasNotFailedPreviousRuleIfPresenceRule($rule, $attribute)
    {
        return in_array($rule, ['Unique', 'Exists']) ? ! $this->messages->has($attribute) : true;
    }

    public function addFailure($attribute, $rule, $parameters = [])
    {
        if (! $this->messages) {
            $this->passes();
        }

        $attributeWithPlaceholders = $attribute;

        $attribute = $this->replacePlaceholderInString($attribute);

        if (in_array($rule, $this->excludeRules)) {
            $this->excludeAttribute($attribute);
            return;
        }

        $this->messages->add($attribute, $this->makeReplacements(
            $this->getMessage($attributeWithPlaceholders, $rule), $attribute, $rule, $parameters
        ));

        $this->failedRules[$attribute][$rule] = $parameters;
    }

    /**
     * Add the given attribute to the list of excluded attributes.
     */
    protected function excludeAttribute(string $attribute)
    {
        $this->excludeAttributes[] = $attribute;

        $this->excludeAttributes = array_unique($this->excludeAttributes);
    }

    /**
     * Determine if the attribute should be excluded.
     *
     * @param  string  $attribute
     * @return bool
     */
    protected function shouldBeExcluded($attribute)
    {
        foreach ($this->excludeAttributes as $excludeAttribute) {
            if ($attribute === $excludeAttribute ||
                Str::startsWith($attribute, $excludeAttribute.'.')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Remove the given attribute.
     *
     * @param  string  $attribute
     * @return void
     */
    protected function removeAttribute($attribute)
    {
        Arr::forget($this->data, $attribute);
        Arr::forget($this->rules, $attribute);
    }

    /**
     * Determine if the data passes the validation rules.
     *
     * @return bool
     */
    public function passes(): bool
    {
        $this->messages = new MessageBag;

        [$this->distinctValues, $this->failedRules] = [[], []];

        // We'll spin through each rule, validating the attributes attached to that
        // rule. Any error messages will be added to the containers with each of
        // the other error messages, returning true if we don't have messages.
        foreach ($this->rules as $attribute => $rules) {
            if ($this->shouldBeExcluded($attribute)) {
                $this->removeAttribute($attribute);

                continue;
            }

            if ($this->stopOnFirstFailure && $this->messages->isNotEmpty()) {
                break;
            }

            foreach ($rules as $rule) {
                $this->validateAttribute($attribute, $rule);

                if ($this->shouldBeExcluded($attribute)) {
                    break;
                }
            }
        }

        // Here we will spin through all of the "after" hooks on this validator and
        // fire them off. This gives the callbacks a chance to perform all kinds
        // of other validation that needs to get wrapped up in this operation.
        foreach ($this->after as $after) {
            $after();
        }

        return $this->messages->isEmpty();
    }

    public function fails(): bool
    {
        return ! $this->passes();
    }

    /**
     * Add an after validation callback.
     *
     * @param  callable|array|string  $callback
     * @return $this
     */
    public function after($callback)
    {
        $this->after[] = function () use ($callback) {
            return $callback($this);
        };

        return $this;
    }

    /**
     * Determine if the given attribute has a rule in the given set.
     *
     * @param  string  $attribute
     * @param  string|array  $rules
     * @return bool
     */
    public function hasRule($attribute, $rules)
    {
        return ! is_null($this->getRule($attribute, $rules));
    }

    /**
     * Get a rule and its parameters for a given attribute.
     *
     * @param  string  $attribute
     * @param  string|array  $rules
     * @return array|null
     */
    protected function getRule($attribute, $rules)
    {
        if (!array_key_exists($attribute, $this->rules)) {
            return;
        }

        $rules = (array) $rules;

        foreach ($this->rules[$attribute] as $rule) {
            [$rule, $parameters] = ValidationRuleParser::parse($rule);

            if (in_array($rule, $rules)) {
                return [$rule, $parameters];
            }
        }
    }

    /**
     * Get the exception to throw upon failed validation.
     *
     * @return string
     */
    public function getException()
    {
        return $this->exception;
    }

    /**
     * Set the exception to throw upon failed validation.
     *
     * @param  string  $exception
     * @return $this
     *
     * @throws \InvalidArgumentException
     */
    public function setException($exception)
    {
        if (! is_a($exception, ValidationException::class, true)) {
            throw new InvalidArgumentException(
                sprintf('Exception [%s] is invalid. It must extend [%s].', $exception, ValidationException::class)
            );
        }

        $this->exception = $exception;

        return $this;
    }

    public function messages()
    {
        if (! $this->messages) {
            $this->passes();
        }

        return $this->messages;
    }

    /**
     * An alternative more semantic shortcut to the message container.
     */
    public function errors()
    {
        return $this->messages();
    }
}