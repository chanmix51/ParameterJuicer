# ParameterJuicer

[![Build Status](https://travis-ci.org/chanmix51/ParameterJuicer.svg?branch=master)](https://travis-ci.org/chanmix51/ParameterJuicer)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/chanmix51/ParameterJuicer/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/chanmix51/ParameterJuicer/?branch=master)
[![License](https://poser.pugx.org/chanmix51/parameter-juicer/license.svg)](https://packagist.org/packages/chanmix51/parameter-juicer)
[![Monthly Downloads](https://poser.pugx.org/chanmix51/parameter-juicer/d/monthly.png)](https://packagist.org/packages/chanmix51/parameter-juicer)

This is the **experimental** branch of Parameter Juicer. If you want to use it in for your projects, it is advised to use the 1.x branches.

How to extract the juice from your parameters, CSV, forms etc. data.
ParameterJuicer is a simple data validator and cleaner for PHP 8.x extensively
unit tested.

It features:
- cleaners and validators are any callable
- default values can be scalars or callable
- extra field strategies
- one pass validation errors collection

Simple example of an anonymous juicer:
```php
$juicer = (new ParameterJuicer)
    ->addField('a_string')
        ->addCleaner('a_string', function($v) { return trim(strtolower($v)); })
        ->addValidator('a_string', function($v) { return (strlen($v) !== 0) ? null : 'cannot be empty'; });
try {
    $juicer->squash(['a_string' => ' Pika CHU ']);
    // ↑ returns ['a_string' => 'pika chu']

    $juicer->squash(['a_string' => '   ']);
    // ↑ throws a ValidationException
} catch (ValidationException $e) {
    printf($e);
    // ↑ Validation failed
    // [a_string] - cannot be empty
}
```

It is possible to create dedicated classes to validate and clean structures with embedded structures (see below).

## Install

`composer require chanmix51/parameter-juicer`

## Usage

### Responsibilities

The **cleaners** are responsible for casting data in the expected type & format. If the casting is impossible, the cleaner can throw an exception and the field is discarded.

The **validators** are responsible for ensuring the business rules for the data are respected. Most of the time, it is about data being in a defined range of values.

### Anonymous definition

Here is a fast and simple example of an anonymous juicer. It cleans and
validates the data according to the given definition.

```php
        use Chanmix51\ParameterJuicer\ParameterJuicer as Juicer;
        use Chanmix51\ParameterJuicer\Exception\ValidationException;

        $turn_to_integer = function($v):int { return (int) $v; };
        $must_be_between_1_and_10 = function(int $value) {
            if (10 < $value || 1 > $value) {
                return sprintf(
                        "must be between 1 and 10 (%d given).",
                        $value
                    );
            }};

        $juicer = (new Juicer)
            ->addField('pika')
                ->addCleaner('pika', $turn_to_integer)
                ->addValidator('pika', $must_be_between_1_and_10)
                ->setDefaultValue('pika', 9) // ← when not set
            ->addField('chu')
                ->addCleaner('chu', function($v) { return trim($v); })
                ->setDefaultValue('chu', function() { return 10; })
                        // ↑ use a callable to have a lazy loaded default value
            ->addField('not mandatory', false)   // ← not mandatory
            ->setStrategy(Juicer::STRATEGY_IGNORE_EXTRA_VALUES)
            ;            // ↑ extra values are removed


            try {
                // ↓ return ["pika" => 9, "chu" => '']
                $juicer->squash(['chu' => null, 'whatever' => 'a']);

                // ↓ return ["pika" => 3, "chu" => "a"]
                $juicer->squash(['pika' => '3', 'chu' => ' a ', 'whatever' => 'a']);

                // ↓ throw a ValidationException because "chu" is mandatory
                $juicer->squash(['pika' => '3', 'whatever' => 'a']);
            } catch (ValidationException $e) {
                // Get the validation errors from the exception (see below)
            }
```

### Extra fields strategies

There are 3 strategies to handle extra data not defined in the plan:

1. `ParameterJuicer::STRATEGY_ACCEPT_EXTRA_VALUES` (0) let the extra data untouched (be aware they ARE untrusted data).
1. `ParameterJuicer::STRATEGY_IGNORE_EXTRA_VALUES` (1) discard extra data (this is the default strategy).
1. `ParameterJuicer::STRATEGY_REFUSE_EXTRA_VALUES` (2) treat extra fields as anomalies and trigger the `ValidationException`.

### Using form cleaners and validators

Each validator only sees the values it is responsible for, it makes the validation simple and easy to maintain. But there are some cases where validation rules must compare fields with other fields (like comparing password and password confirmation).

```php
$juicer = (new Juicer)
    ->addField('login')
        ->addCleaner('login', function($v) { return strtolower(trim($v)); })
        ->addValidator('login', function($v) { return $v === '' ? 'must not be empty' : null; })
    ->addField('password')
        ->addCleaner('password', 'trim')
        ->addValidator('password', function($v) { return strlen($v) < 3 ? 'must not be less than 3 chars' : null; })
    ->addField('repeat_password')
        ->addCleaner('repeat_password', 'trim')
    ->addFormValidator(function($values) {
        if ($values['password'] != $values['repeat_password']) {
            return 'passwords do not match';
        }
    });
```
### Form validation strategies

By default, form validation is not triggered if the fields validation fails.
It is possible to always launch form validation using the `setFormValidationStrategy` method:

 1. `ParameterJuicer::FORM_VALIDATORS_CONDITIONAL` (default)
 1. `ParameterJuicer::FORM_VALIDATORS_ALWAYS`

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

    public function mustNotBeEmptyString($value)
    {
        return (strlen($value) !== 0) ? null : 'must no be an empty string';
    }

    public function mustBeANumberStrictlyPositive($value)
    {
        return ($value > 0)
            ? null
            : printf("must be strictly positive (%f given)", $value);
    }
}

$trusted_data = (new PikaChuJuicer)
    ->squash($untrusted_data)
    ;
```

This is particularly useful because it makes cleaners and validators to be unit-testable in addition to the juicer being usable in different portions of the code.

### Using a juicer class to clean & validate nested data.

It may happen a dataset embeds another dataset that already has its own Juicer class.

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

### Local form validation or nested validation?

It is also possible to perform for cleaning & validation by adding validators to a nested juicer:

```php
$juicer = (new ParameterJuicer)
    ->addField('my_form')
        ->addJuicer('my_form', (new PasswordFormJuicer)
        ->addValidator('my_form', function($val) {
            return $values['pass'] === $values['repass']
                ? null
                : 'pass & repass do not match';
        });
try {
    $clean_data = $juicer->squash(['my_form' => $form_data]);
} catch (ValidationException $e) {
    …
}
```

Where to write such validations is a matter of context. If the rules are set in a Form(Cleaner|Validator) they belong to this type and they wille always be run whatever the outside context is. If the rules are in another juicer they are rules added to the original context. 

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

Validators follow a binary logic: either the data is good or not. When a value is considered acceptable by the rules, the validator returns null. In the case a validation rule fails, validators must throw a ValidationException with the error message. It is also possible to just return the error message, it is wrapped in a ValidationException. This exception is collected by the juicer and all the values are validated. At the end of the process, if exceptions have been collected, they are all grouped in the same `ValidationException` instance which is then thrown so users get all the validation messages at once.

```php
$validator = function($value) {
    if (preg_match("/pika/", $value)) {
        throw new ValidationException("must NOT contain 'pika'");
    }
}
```

```php
$validator = function($value) {
    return (preg_match("/pika/", $value))
        ? "must NOT contain 'pika'"
        : null;
}
```

The Juicer automatically cares about associating validation errors with field names, this name is prepend to the validation error message. Validation error message are kept short, with no starting uppercase and no final dot.

## Validation exception

A juicer either produces clean values or a `ValidationException` in case of the validation fails. The `ValidationException` can store an nest exceptions. Every time a validation condition fail (mandatory field, extra field strategy or validator), it adds a `ValidationException` in a global exception. It is necessary to catch this exception to make it possible either to fetch the stored exception or to directly fetch the errors:

```php
try {
    $my_juicer->squash($data);
} catch (ValidationException $e) {
    printf($e); // ← this calls $e->getFancyMessage()
}
```
Assuming a set is nesting a juicer in a field `pikachu` the output would be like the following:

```
validation failed
  [pikachu] - validation failed
      [pika] - must not be empty
      [chu] - missing mandatory field
  [me] - must be strictly positive (-1 given)
```

The `getExceptions()` method returns an array of the validation errors indexed by field name, values are arrays of `ValidationException` instances:

```php
foreach ($exception->getExceptions() as $field_name => $exceptions) {
    printf("Field '%s' has %d errors.\n", $field_name, count($exceptions));
}
```

## How to contribute

1. Create a test case using the unit tests to ensure your interface is usable
   by average humans like me.
1. Code your feature and make sure all your tests are green.
1. Create a PR on Github and wait several years I care.
