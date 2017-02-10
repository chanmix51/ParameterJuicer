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

    /** @var  int       strategy of this juicer */
    public    $strategy = self::STRATEGY_IGNORE_EXTRA_VALUES;

    /**
     * getName
     *
     * @see     ParameterJuicerInterface
     */
    public function getName(): string
    {
        return 'anonymous';
    }

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
        $this
            ->checkFieldExists($name)
            ->default_values[$name] = $value
            ;

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
        $this->validate($this->getName(), $values);

        return $values;
    }

    /**
     * validate
     *
     * Trigger validation on values.
     *
     * @see     ParameterJuicerInterface
     */
    public function validate(string $name, array $values): ParameterJuicerInterface
    {
        $exception = new ValidationException;

        if ($this->strategy === self::STRATEGY_REFUSE_EXTRA_VALUES) {
            $this->refuseExtraFields($values, $exception);
        }
        $this->validateFields($values, $exception);

        if ($exception->hasExceptions()) {
            throw $exception;
        }

        return $this;
    }

    /**
     * refuseExtraFields
     *
     * Fill the exception with refused extra fields if any.
     */
    private function refuseExtraFields(array $values, ValidationException $exception): self
    {
        $diff = array_keys(array_diff_key($values, $this->fields));

        foreach ($diff as $fied_name) {
            $exception->addException(new ValidationException(
                sprintf("Extra field '%s' is refused by validation strategy.", $fied_name)
            ));
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
                    new ValidationException(
                        sprintf(
                            "Missing field '%s' is mandatory.",
                            $field
                        )
                    )
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
     * setDefaultValues
     *
     * Apply default values. When a field is not present in the values, the
     * default value is set.
     */
    private function setDefaultValues(array $values): array
    {
        foreach ($this->default_values as $field => $default_value) {
            if (!isset($values[$field]) && !array_key_exists($field, $values)) {
                $values[$field] = $default_value;
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
     *
     * @throws  \RuntimeException if the callable fails.
     */
    private function launchValidatorsFor(string $field, $value, ValidationException $exception): self
    {
        foreach ($this->validators[$field] as $validator) {
            try {
                if (call_user_func($validator, $field, $value) === false) {
                    throw new \RuntimeException(
                        sprintf("One of the validators for the field '%s' has a PHP error.", $field)
                    );
                }
            } catch (ValidationException $e) {
                $exception->addException($e);
            }
        }

        return $this;
    }
}

