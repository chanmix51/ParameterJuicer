# ParameterJuicer

[![Build Status](https://travis-ci.org/chanmix51/ParameterJuicer.svg?branch=master)](https://travis-ci.org/chanmix51/ParameterJuicer)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/chanmix51/ParameterJuicer/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/chanmix51/ParameterJuicer/?branch=master)
[![Monthly Downloads](https://poser.pugx.org/chanmix51/parameter-juicer/d/monthly.png)](https://packagist.org/packages/chanmix51/parameter-juicer) [![License](https://poser.pugx.org/chanmix51/parameter-juicer/license.svg)](https://packagist.org/packages/chanmix51/parameter-juicer)

How to extract the juice from your parameters, CSV, forms etc. data. This is a
simple parser, validator and cleaner for data.

## Install

`composer require chanmix51/parameter-juicer`

## Usage

### Anonymous definition

Here is a fast and simple example of an anonymous juicer. It cleans and
validates the data according to the given definition.

```php
        use Chanmix51\ParameterJuicer\ParameterJuicer as Juicer;
        use Chanmix51\ParameterJuicer\Exception\ValidationException;

        $turn_to_integer = function($v):int { return (int) $v; };
        $must_be_between_1_and_10 = function(string $field, int $value) {
            if (10 < $value || 1 > $value) {
                throw new ValidationException(
                    sprintf(
                        "Field '%s' must be between 1 and 10 (%d given).",
                        $field,
                        $value
                    )
                );
            }
        };

        $juicer = (new Juicer)
            ->addField('pika')
                ->addCleaner('pika', $turn_to_integer)
                ->addValidator('pika', $must_be_between_1_and_10)
                ->setDefaultValue('pika', 9) // ← when not set
            ->addField('chu')
                ->addCleaner('chu', function($v) { return trim($v); })
            ->addField('not mandatory', false)   // ← not mandatory
            ->setStrategy(Juicer::STRATEGY_IGNORE_EXTRA_VALUES)
            ;            // ↑ extra values are removed

            // throw a ValidationException because "chu" is mandatory
            $juicer->squash(['pika' => '3', 'whatever' => 'a']);

            // return ["pika" => 9, "chu" => '']
            $juicer->squash(['chu' => null, 'whatever' => 'a']);

            // return ["pika" => 3, "chu" => ""]
            $juicer->squash(['pika' => '3', 'chu' => '', 'whatever' => 'a']);
```

### Extra fields strategies

There are 3 strategies to handle extra data not defined in the plan:

1. `ParameterJuicer::STRATEGY_ACCEPT_EXTRA_VALUES` (0) let the extra data untouched (be aware they ARE untrusted data).
1. `ParameterJuicer::STRATEGY_IGNORE_EXTRA_VALUES` (1) discard extra data (this is the default strategy).
1. `ParameterJuicer::STRATEGY_REFUSE_EXTRA_VALUES` (2) treat extra fields as anomalies and trigger the `ValidationException`.

### Custom Juicer class

It is possible to embed cleaning & validation rules in a dedicated class:

```php
class PikaChuJuicer extends ParameterJuicer
{
    public function __construct()
    {
        $this
            ->addField('pika')
                ->addCleaner('pika', [$this, 'doTrimAndLowerString'])
                ->addValidator('pika', [$this, 'mustNotBeEmptyString'])
            ->addField('chu', false)
                ->addCleaner('chu', function($v) { return $v + 0; })
                ->addValidator('chu', [$this, 'mustBeANumberStrictlyPositive'])
            ->setStrategy(ParameterJuicer::STRATEGY_REFUSE_EXTRA_VALUES)
        ;
    }

    public function doTrimAndLowerString($value): string
    {
        return strtolower(trim($value));
    }

    public function mustNotBeEmptyString($name, $value)
    {
        if (strlen($value) === 0) {
            throw new ValidationException(
                sprintf(
                    "Field '%s' is an empty string.",
                    $name
                )
            );
        }
    }

    public function mustBeANumberStrictlyPositive($name, $value)
    {
        if ($value <= 0) {
            throw new ValidationException(
                sprintf(
                    "Field '%s' must be strictly positive (%f given).",
                    $name,
                    $value
                )
            );
        }
    }
}

$trusted_data = (new PikaChuJuicer)
    ->squash($untrusted_data)
    ;
```

This is particularly useful because it makes cleaners and validators to be unit-testable in addition to the juicer being usable in different portions of the code.

### Using a juicer class to clean & validate nested data.

It may happen a dataset embeds in a field another dataset that already has its own Juicer class.

```php
$juicer = (new Juicer)
    ->addField('pokemon_id')
    ->addField('pika_chu')
        ->addJuicer(
            'pika_chu',                      // ↓ change this juicer’s strategy
            (new PikaChuJuicer)->setStrategy(Juicer::STRATEGY_IGNORE_EXTRA_VALUES)
            )
        ->addValidator('pika_chu', … // ← add an extra validator on this field)
    ;
```

## Writing cleaners and validators.

### Cleaners

Cleaners and validators can be everything callable. They have different purpose, the cleaners *transform* the data prior to validation. The validation *indicates* if the data is valid or not. Cleaners must return the value or throw a `CleanerRemoveFieldException` if the field is to be removed.

```php
$cleaner = function($value) {
    $value = trim($value);

    if ($value === '') {
        throw new CleanerRemoveFieldException;
    }

    return $value;
}
```

In the example above, null or empty strings discard the field so a default value can be applied if set. If the field is unset with no default value and is mandatory, this will raise a validation exception in the end (see Validators below).

### Validators

Validators just indicate if the data respect the validation rules or not. When rules are not respected by a value, a `ValidationException` is thrown. This exception is collected by the juicer and all the values are validated. At the end of the process, if exceptions have been collected, they are all grouped in the same `ValidationException` instance which is then thrown so users get all the validation messages at once.

```php
$validator = function($field_name, $value) {
    if (!preg_match("/pika/", $value)) {
        throw new ValidationException(
            sprintf(
                "Field '%s' must NOT contain 'pika'.",
                $field_name
                )
            );
    }
}
```


## How to contribute

1. Create a test case using the unit tests to ensure your interface is usable
   by average humans like me.
1. Code your feature and make sure all your tests are green.
1. Create a PR on Github and wait several years I care.
