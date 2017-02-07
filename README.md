# ParameterJuicer

How to extract the juice from your parameters, CSV, forms etc. data. This is a simple parser, validator and cleaner for data.

## Install

`composer require chanmix51/parameterJuicer`

## Usage

```php
        $juicer = $this->newTestedInstance()
            ->addField('pika')
            ->addValidator('pika', function($v) {
            if (!is_int($v)) throw new ValidationException(
                sprintf(
                    "Field must be an integer ('%s' detected).",
                    gettype($v)
                )
            );
        })
            ->addValidator('pika', function($v) {
            if (10 < $v || 1 > $v) throw new ValidationException(
                sprintf(
                    "%d must be between 1 and 10.",
                    $v
                )
            );
        })
            ->addField('chu')
            ->addField('not mandatory', false)
            ->validateAndClean($data, ParameterJuicer::STRATEGY_IGNORE_EXTRA_VALUES)
            ;
```

## How to contribute

1. Create a test case ysing the unit tests to ensure your interface is usable by average humans like me.
1. Code your feature and make sure all your tests are green.
1. Create a PR on github and wait several years I care.
