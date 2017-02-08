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

use Chanmix51\ParameterJuicer\ValidationException;

/**
 * ParameterJuicer
 *
 * @package   ParameterJuicer
 * @copyright 2017 Grégoire HUBERT
 * @author    Grégoire HUBERT <hubert.greg@gmail.com>
 * @license   X11 {@link http://opensource.org/licenses/mit-license.php}
 */
class ParameterJuicer
{
    const STRATEGY_IGNORE_EXTRA_VALUES = 0;
    const STRATEGY_REFUSE_EXTRA_VALUES = 1;
    const STRATEGY_ACCEPT_EXTRA_VALUES = 2;

    /** @var  array     list of validators, must be callables */
    protected $validators   = [];

    /** @var  array     list of cleaners, must be callables */
    protected $cleaners     = [];

    /** @var  array     list of fields, this gives an information if the field
                        is mandatory or optional. */
    protected $fields       = [];

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
     * addValidator
     *
     * Add a new validator associated to a key. If the field is not already declared, it is created.
     */
    public function addValidator(string $name, callable $validator): self
    {
        $this
            ->checkFieldExists($name)
            ->validators[$name][]  = $validator
            ;

        return $this;
    }

    /**
     * addCleaner
     *
     * Add a new cleaner associated to a key.
     */
    public function addCleaner($name, callable $cleaner): self
    {
        $this
            ->checkFieldExists($name)
            ->cleaners[$name][] = $cleaner
            ;

        return $this;
    }

    /**
     * validateAndClean
     *
     * Validate and clean the given data according to the definition.
     */
    public function validateAndClean(array $values, $strategy = 0): array
    {
        $exception = new ValidationException;

        if ($strategy <> self::STRATEGY_ACCEPT_EXTRA_VALUES) {
            $values = $this->applyStrategy($exception, $values, $strategy);
        } 

        return $this
            ->validate($values, $exception)
            ->clean($values)
            ;
    }

    /**
     * validate
     *
     * Trigger validation on values.
     */
    protected function validate(array $values, ValidationException $exception = null): self
    {
        $exception = isset($exception)
            ? $exception
            : new ValidationException
            ;

        foreach ($this->fields as $field => $is_mandatory) {
            $is_set = isset($values[$field]);

            if ($is_mandatory && !$is_set) {
                $exception->addMessage(sprintf("Missing field '%s' is mandatory.", $field));

                continue;
            }

            if ($is_set && isset($this->validators[$field])) {
                foreach ($this->validators[$field] as $validator) {
                    try {
                        if (call_user_func($validator, $values[$field]) === false) {
                            throw new \RuntimeException(
                                sprintf("One of the validators for the field '%s' has a PHP error.", $field)
                            );
                        }
                    } catch (ValidationException $e) {
                        $exception->addMessage($e->getMessage());
                    }
                }
            }
        }

        if ($exception->hasMessages()) {
            throw $exception;
        }

        return $this;
    }

    /**
     * clean
     *
     * Clean and return values.
     */
    protected function clean(array $values): array
    {
        foreach ($this->cleaners as $field_name => $cleaners) {
            if (isset($values[$field_name])) {
                foreach ($cleaners as $cleaner) {
                    $values[$field_name] = call_user_func($cleaner, $values[$field_name]);
                }
            }
        }

        return $values;
    }

    /**
     * applyStrategy
     *
     * Apply extra values strategies.
     *
     * @throws RuntimeException if no valid stratgies were provided.
     */
    private function applyStrategy(ValidationException $exception, array $values, int $strategy): array
    {
        $diff_keys = array_keys(
            array_diff_key(
                $values,
                $this->fields
            )
        );

        if (count($diff_keys) === 0 || $strategy === self::STRATEGY_ACCEPT_EXTRA_VALUES)
            return $values;

        if ($strategy === self::STRATEGY_REFUSE_EXTRA_VALUES) {
            foreach ($diff_keys as $key) {
                $exception->addMessage(
                    sprintf(
                        "Extra field '%s' is present with STRATEGY_REFUSE_EXTRA_VALUES.",
                        $key
                    )
                );
            }

            return $values;
        }

        if ($strategy === self::STRATEGY_IGNORE_EXTRA_VALUES) {
            return array_diff_key($values, array_flip($diff_keys));
        }

        throw new \RuntimeException(
            sprintf(
                "Unknown strategy %d, available strategies are [STRATEGY_IGNORE_EXTRA_VALUES, STRATEGY_ACCEPT_EXTRA_VALUES, STRATEGY_REFUSE_EXTRA_VALUES].",
                $strategy
            )
        );
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
}

