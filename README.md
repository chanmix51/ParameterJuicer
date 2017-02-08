# ParameterJuicer

How to extract the juice from your parameters, CSV, forms etc. data. This is a
simple parser, validator and cleaner for data.

## Install

`composer require chanmix51/parameterJuicer`

## Usage

Here is a fast and simple example of an anonymous juicer. It cleans and
validate the data according to the given definition.

```php
        use Chanmix51\ParameterJuicer\ParameterJuicer as Juicer;

        $juicer = ($new Juicer)
            ->addCleaner('pika')
                ->addCleaner('pika', function($v) { return (int) $v; })
                ->addValidator('pika', function($k, $v) {
                    if (10 < $v || 1 > $v) {
                        throw new ValidationException(
                            sprintf(
                                "Field '%s' must be between 1 and 10 (%d given).",
                                $k,
                                $v
                            )
                        );
                    }
                })
            ->addField('chu')
            ->addField('not mandatory', false)
            ;

            $clean_data = $juicer
                ->squash(['pika' => '3', 'whatever' => 'a'], Juicer::STRATEGY_IGNORE_EXTRA_VALUES)
            ; // returns ['pika' => 3]
```

### Extra fields strategies

There are 3 strategies to handle extra data not defined in the plan:

1. `ParameterJuicer::STRATEGY_ACCEPT_EXTRA_VALUES` (0) let the extra data untouched (be aware they might be untrusted data).
1. `ParameterJuicer::STRATEGY_IGNORE_EXTRA_VALUES` (1) discard extra data.
1. `ParameterJuicer::STRATEGY_REFUSE_EXTRA_VALUES` (2) treat extra fields are anomalies and trigger the `ValidationException`.

### Custom Juicer class

It is possible to embed cleaning & validation rules in a dedicated class:

```php
class PikaChuJuicer extends Juicer
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
        ;
    }

    protected function doTrimAndLowerString($value): string
    {
        return strtolower(trim($value));
    }

    protected function mustNotBeEmptyString($name, $value)
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

    protected function mustBeANumberStrictlyPositive($name, $value)
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

$trusted_data = (new PikaChuJuicer)>squash($untrusted_data);
```

### Using a juicer class to clean & validate nested data.

It may happen a data set embeds a data that already has a Juicer class.

```php
$juicer = (new Juicer)
    ->addField('pokemon_id')
    ->addField('pika_chu')
        ->addJuicer('pika_chu', new PikaChuJuicer, Juicer::STRATEGY_REFUSE_EXTRA_VALUES)
        ->addValidator('pika_chu', … // add an extra validator on this field)
    ;
```

## How to contribute

1. Create a test case using the unit tests to ensure your interface is usable
   by average humans like me.
1. Code your feature and make sure all your tests are green.
1. Create a PR on Github and wait several years I care.
