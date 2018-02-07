<?php
/*
 * This file is part of Chanmix51’s ParameterJuicer package.
 *
 * (c) 2017 Grégoire HUBERT <hubert.greg@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Chanmix51\ParameterJuicer;

use Chanmix51\ParameterJuicer\Exception\ValidationException;
use Chanmix51\ParameterJuicer\Exception\CleanerRemoveFieldException;
use Chanmix51\ParameterJuicer\ParameterJuicerInterface;

/**
 * ParameterJuicer
 *
 * Cleaner and validator for data set.
 *
 * A "cleaner" is a callable that takes a data and returns the data tranformed.
 * It can throw a CleanerRemoveFieldException if the field is to be unset.
 *
 * A "default value" is set when the field is NOT PRESENT. In the case the
 * field exists and has no value (null), the default value does not apply.
 *
 * A "validator" is a callable that throws a ValidationException when the given
 * data is detected as invalid.
 *
 * @package   ParameterJuicer
 * @copyright 2017 Grégoire HUBERT
 * @author    Grégoire HUBERT <hubert.greg@gmail.com>
 * @license   X11 {@link http://opensource.org/licenses/mit-license.php}
 *
 * @see       ParameterJuicerInterface
 */
class ParameterJuicer implements ParameterJuicerInterface
{
    const STRATEGY_IGNORE_EXTRA_VALUES = 0;
    const STRATEGY_REFUSE_EXTRA_VALUES = 1;
    const STRATEGY_ACCEPT_EXTRA_VALUES = 2;

    /** @var  array     list of validators, must be callables */
    protected $validators = [];

    /** @var  array     list of cleaners, must be callables */
    protected $cleaners = [];

    /** @var  array     list of fields, this gives an information if the field
                        is mandatory or optional. */
    protected $fields = [];

    /** @var  array     list of default values */
    protected $default_values = [];

    /** @var  array     list of form cleaners, must be callables */
    protected $form_cleaners = [];

    /** @var  array     list of form validators, must be callables */
    protected $form_validators = [];

    /** @var  int       strategy of this juicer */
    public $strategy = self::STRATEGY_IGNORE_EXTRA_VALUES;

    /**
     * addField
     *
     * Declare a new field with no validators nor cleaner. It can be declared
     * if the field is optional or mandatory.
     * If the field already exists, it is overriden.
     */
    public function addField(string $name, bool $is_mandatory = true): self
    {
        $this->fields[$name] = $is_mandatory;

        return $this;
    }

    /**
     * addFields
     *
     * Declare several fields at once.Existing fields are overriden.
     */
    public function addFields(array $fields, $are_mandatory = true): self
    {
        array_merge($this->fields, array_fill_keys($fields, $are_mandatory));

        return $this;
    }

    /**
     * removeField
     *
     * Remove an existing field with all validators or cleaners associated to
     * it if any. It throws an exception if the field does not exist.
     *
     * @throws \InvalidArgumentException
     */
    public function removeField(string $name): self
    {
        $this->checkFieldExists($name);
        unset($this->fields[$name]);

        if (isset($this->validators[$name])) {
            unset($this->validators[$name]);
        }

        if (isset($this->cleaners[$name])) {
            unset($this->cleaners[$name]);
        }

        return $this;
    }

    /**
     * addValidator
     *
     * Add a new validator associated to a key. If the field is not already
     * declared, it is created.
     *
     * @throws \InvalidArgumentException
     */
    public function addValidator(string $name, callable $validator): self
    {
        $this
            ->checkFieldExists($name)
            ->validators[$name][] = $validator
            ;

        return $this;
    }

    /**
     * addCleaner
     *
     * Add a new cleaner associated to a key.
     *
     * @throws \InvalidArgumentException
     */
    public function addCleaner(string $name, callable $cleaner): self
    {
        $this
            ->checkFieldExists($name)
            ->cleaners[$name][] = $cleaner
            ;

        return $this;
    }

    /**
     * setDefaultValue
     *
     * Set a default value for a field. If the field is not set or its value is
     * null, this value will be set instead. This is triggered AFTER the
     * cleaners which is useful because some cleanders can return null and then
     * default value is applied.
     *
     * @throws \InvalidArgumentException
     */
    public function setDefaultValue(string $name, $value): self
    {
        if (false === is_callable($value)) {
            $value = function () use ($value) {
                return $value;
            };
        }

        $this
            ->checkFieldExists($name)
            ->default_values[$name] = $value
            ;

        return $this;
    }

    /**
     * addFormCleaner
     *
     * Add a new cleaner associated to the whole set of values.
     */
    public function addFormCleaner(callable $cleaner): self
    {
        $this->form_cleaners[] = $cleaner;

        return $this;
    }

    /**
     * addFormValidator
     *
     * Add a new validator to the whole set of values.
     */
    public function addFormValidator(callable $validator): self
    {
        $this->form_validators[] = $validator;

        return $this;
    }

    /**
     * addJuicer
     *
     * Add a juicer to clean a validate a subset of data.
     *
     * @throws \InvalidArgumentException
     */
    public function addJuicer(string $name, ParameterJuicerInterface $juicer): self
    {
        return $this
            ->addCleaner($name, [$juicer, 'clean'])
            ->addValidator($name, [$juicer, 'validate'])
            ;
    }

    /**
     * setStrategy
     *
     * Set the extra fields strategy for this juicer.
     */
    public function setStrategy(int $strategy): self
    {
        $this->strategy = $strategy;

        return $this;
    }

    /**
     * squash
     *
     * Clean & validate the given data according to the definition.
     */
    public function squash(array $values): array
    {
        $values = $this->clean($values);
        $this->validate($values);

        return $values;
    }

    /**
     * validate
     *
     * Trigger validation on values.
     *
     * @see     ParameterJuicerInterface
     */
    public function validate(array $values)
    {
        $exception = new ValidationException("validation failed");

        if ($this->strategy === self::STRATEGY_REFUSE_EXTRA_VALUES) {
            $this->refuseExtraFields($values, $exception);
        }
        $this->validateFields($values, $exception);
        $this->validateForm($values, $exception);

        if ($exception->hasExceptions()) {
            throw $exception;
        }
    }

    /**
     * refuseExtraFields
     *
     * Fill the exception with refused extra fields if any.
     */
    private function refuseExtraFields(array $values, ValidationException $exception): self
    {
        $diff = array_keys(array_diff_key($values, $this->fields));

        foreach ($diff as $field_name) {
            $exception->addException(
                $field_name,
                new ValidationException("extra field is refused by validation strategy")
            );
        }

        return $this;
    }

    /**
     * validateFields
     *
     * Check mandatory fields and launch validators.
     */
    private function validateFields(array $values, ValidationException $exception): self
    {
        foreach ($this->fields as $field => $is_mandatory) {
            $is_set = isset($values[$field]) || array_key_exists($field, $values);

            if ($is_mandatory && !$is_set) {
                $exception->addException(
                    $field,
                    new ValidationException("missing mandatory field")
                );
            } elseif ($is_set && isset($this->validators[$field])) {
                $this->launchValidatorsFor(
                    $field,
                    $values[$field],
                    $exception
                );
            }
        }

        return $this;
    }

    /**
     * validateForm
     *
     * form wide validation
     */
    private function validateForm(array $values, ValidationException $exception): self
    {
        return $this->launchValidators($this->form_validators, '', $values, $exception);
    }

    /**
     * setDefaultValues
     *
     * Apply default values. When a field is not present in the values, the
     * default value is set.
     */
    private function setDefaultValues(array $values): array
    {
        foreach ($this->default_values as $field => $default_value) {
            if (!isset($values[$field]) && !array_key_exists($field, $values)) {
                $values[$field] = call_user_func($default_value);
            }
        }

        return $values;
    }

    /**
     * clean
     *
     * Clean and return values.
     *
     * @see     ParameterJuicerInterface
     */
    public function clean(array $values): array
    {
        if ($this->strategy === self::STRATEGY_IGNORE_EXTRA_VALUES) {
            $values = array_intersect_key($values, $this->fields);
        }

        return $this->setDefaultValues($this->triggerCleaning($values));
    }

    /**
     * triggerCleaning
     *
     * Launch cleaners on the values.
     */
    private function triggerCleaning(array $values): array
    {
        foreach ($this->cleaners as $field_name => $cleaners) {
            if (isset($values[$field_name]) || array_key_exists($field_name, $values)) {
                foreach ($cleaners as $cleaner) {
                    try {
                        $values[$field_name] =
                            call_user_func($cleaner, $values[$field_name])
                            ;
                    } catch (CleanerRemoveFieldException $e) {
                        unset($values[$field_name]);
                    }
                }
            }
        }

        foreach ($this->form_cleaners as $cleaner) {
            $values = call_user_func($cleaner, $values);
        }

        return $values;
    }

    /**
     * checkFieldExists
     *
     * Throw an exception if the field does not exist.
     *
     * @throws  \InvalidArgumentException
     */
    private function checkFieldExists(string $name): self
    {
        if (!isset($this->fields[$name])) {
            throw new \InvalidArgumentException(
                sprintf(
                    "Field '%s' is not declared, fields are {%s}.",
                    $name,
                    join(', ', array_keys($this->fields))
                )
            );
        }

        return $this;
    }

    /**
     * launchValidatorsFor
     *
     * Triger validators for the given field if any.
     */
    private function launchValidatorsFor(string $field, $value, ValidationException $exception): self
    {
        try {
            $this->launchValidators($this->validators[$field], $field, $value, $exception);
        } catch (ValidationException $e) {
            $exception->addException($field, $e);
        }

        return $this;
    }

    /**
     * launchValidators
     *
     * Apply validators against the given value.
     *
     * @throws  \RuntimeException if the callable fails.
     */
    private function launchValidators(array $validators, string $field, $value, ValidationException $exception): self
    {
        foreach ($validators as $validator) {
            if (($return = call_user_func($validator, $value)) !== null) {
                throw new ValidationException((string) $return);
            }
        }

        return $this;
    }
}
