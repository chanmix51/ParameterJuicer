<?php
/*
 * This file is part of Chanmix51’s ParameterJuicer package.
 *
 * (c) 2017 Grégoire HUBERT <hubert.greg@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Chanmix51\ParameterJuicer\Tests\Unit;

use Chanmix51\ParameterJuicer\ParameterJuicer as Juicer;

use Chanmix51\ParameterJuicer\Exception\ValidationException;
use Chanmix51\ParameterJuicer\Exception\CleanerRemoveFieldException;
use Chanmix51\ParameterJuicer\Tests\Fixtures\PokemonJuicer;
use Chanmix51\ParameterJuicer\Tests\Fixtures\PikaChuJuicer;
use Chanmix51\ParameterJuicer\Tests\Fixtures\Position;

use \Atoum;

/**
 * ParameterJuicer
 *
 * ParameterJuicer test class
 *
 * @package     ParameterJuicer
 * @copyright   2017 Grégoire HUBERT
 * @author      Grégoire HUBERT <hubert.greg@gmail.com>
 * @license     X11 {@link http://opensource.org/licenses/mit-license.php}
 *
 * @see         Atoum
 */
class ParameterJuicer extends Atoum
{
    /**
     * Test empty validation works with STRATEGY_ACCEPT_EXTRA_VALUES.
     */
    public function testEmptyValidationWithAcceptStrategy()
    {
        $juicer = ($this->newTestedInstance())
            ->setStrategy(Juicer::STRATEGY_ACCEPT_EXTRA_VALUES)
            ;
        $this
            ->assert("Testing an empty validator & values and STRATEGY_ACCEPT_EXTRA_VALUES.")
            ->given($data = [])
                ->array($juicer->squash($data))
                    ->isEqualTo([])
            ->assert("Testing an empty validator with values and STRATEGY_ACCEPT_EXTRA_VALUES.")
            ->given($data = ['pika' => 'chu'])
                ->array($juicer->squash($data))
                    ->isEqualTo(['pika' => 'chu'])
                    ;
    }

    /**
     * Empty validator with an ignore strategy returns empty sets.
     */
    public function testEmptyValidationWithIgnoreStrategy()
    {
        $juicer = ($this->newTestedInstance())
            ->setStrategy(Juicer::STRATEGY_IGNORE_EXTRA_VALUES)
            ;
        $this
            ->assert("Testing an empty validator and STRATEGY_IGNORE_EXTRA_VALUES.")
            ->given($data = [])
                ->array($juicer->squash($data))
                    ->isEqualTo([])
            ->given($data = ['pika' => 'chu'])
                ->array($juicer->squash($data))
                    ->isEqualTo([])
            ->assert("Checking defaut strategy is STRATEGY_IGNORE_EXTRA_VALUES.")
                ->array($juicer->squash($data))
                    ->isEqualTo([])
            ;
    }

    /**
     * This must refuse every values set but empty ones.
     */
    public function testEmptyValidationWithRefuseStrategy()
    {
        $juicer = ($this->newTestedInstance())
            ->setStrategy(Juicer::STRATEGY_REFUSE_EXTRA_VALUES)
            ;
        $this
            ->assert("Testing an empty validator & values and STRATEGY_REFUSE_EXTRA_VALUES.")
            ->given($data = [])
                ->array($juicer->squash($data))
                    ->isEqualTo([])
            ->assert("Testing an empty validator with values and STRATEGY_REFUSE_EXTRA_VALUES.")
            ->given($data = ['pika' => 'chu'])
                ->exception(function () use ($juicer, $data) {
                    return $juicer->squash($data);
                })
                    ->isInstanceOf('Chanmix51\ParameterJuicer\Exception\ValidationException')
                    ;
    }

    /**
     * Data provider for testMandatoryFieldsValidationFail
     */
    public function butPikaOrChu(): array
    {
        return [
            [[]],
            [['pika' => 'yeaaaah']],
            [['chu' => 'yeaaaah']],
        ];
    }

    /**
     * Missing mandatory fields must fail validation.
     *
     * @dataProvider butPikaOrChu
     */
    public function testMandatoryFieldsValidationFail(array $data)
    {
        $juicer = $this->newTestedInstance()
            ->addField('pika')
            ->addValidator('pika', function ($v) {
            })
            ->addField('chu')
            ;
        $this
            ->assert("Checking validation with missing mandatory data.")
                ->exception(function () use ($juicer, $data) {
                    return $juicer->squash($data);
                })
                    ->isInstanceOf('Chanmix51\ParameterJuicer\Exception\ValidationException')
            ;
    }

    /**
     * Testing validation & mandatory fields
     */
    public function testMandatoryFieldValidation()
    {
        $validate_int = function ($v) {
            if (!is_int($v)) {
                return
                    sprintf(
                        "must be an integer ('%s' detected)",
                        gettype($v)
                    );
            }
        };
        $validate_range = function ($v) {
            if (10 < $v || 1 > $v) {
                return
                    sprintf(
                        "must be between 1 and 10 (%d given)",
                        $v
                    );
            }
        };
        $juicer = $this->newTestedInstance()
            ->addField('pika')
            ->addValidator('pika', $validate_int)
            ->addValidator('pika', $validate_range)
            ->addField('chu')
            ->addField('not mandatory', false)
            ->setStrategy(Juicer::STRATEGY_REFUSE_EXTRA_VALUES)
            ;
        $this
            ->assert("Checking validation with all mandatory data.")
            ->given($data = ['pika' => 9, 'chu' => 'there'])
                ->array($juicer->squash($data))
                ->isEqualTo($data)
            ->assert("Checking validation with some wrong mandatory data (1/3).")
            ->given($data['pika'] = 19)
                ->exception(function () use ($juicer, $data) {
                    return $juicer->squash($data);
                })
                    ->isInstanceOf('Chanmix51\ParameterJuicer\Exception\ValidationException')
            ->assert("Checking validation with some wrong mandatory data (2/3).")
            ->given($data['pika'] = 'chu')
                ->exception(function () use ($juicer, $data) {
                    return $juicer->squash($data);
                })
                    ->isInstanceOf('Chanmix51\ParameterJuicer\Exception\ValidationException')
            ->assert("Checking validation with some wrong mandatory data (3/3).")
            ->given($data['pika'] = null)
                ->exception(function () use ($juicer, $data) {
                    return $juicer->squash($data);
                })
                    ->isInstanceOf('Chanmix51\ParameterJuicer\Exception\ValidationException')
            ;
            ;
    }

    /**
     * Null set fields must be considered as being present hence trigger
     * cleaning and validation. They must not be set with default value.
     */
    public function testMandatoryWithNull()
    {
        $juicer = $this
            ->newTestedInstance()
            ->addField('pika')
                ->setDefaultValue('pika', 'chu')
                ->addCleaner('pika', function ($v) {
                    return trim($v);
                })
                ->addValidator('pika', function ($v) {
                    if (strlen($v) === 0) {
                        return 'must not be empty';
                    }
                })
            ;

        $this
            ->assert('A field in the set with no value must not trigger default value.')
                ->exception(function () use ($juicer) {
                    $juicer->squash(['pika' => null]);
                })
                    ->isInstanceOf('Chanmix51\ParameterJuicer\Exception\ValidationException')
            ->assert('A field not set must get a default value.')
                ->array($juicer->squash([]))
                    ->isEqualTo(['pika' => 'chu'])
            ;
    }

    /**
     * testDefaultValues
     *
     * Test default values with mandatory fields and cleaners behaviors.
     */
    public function testDefaultValues()
    {
        $this
            ->assert('A mandatory field with a default value is OK when not provided.')
            ->given($juicer = $this->newTestedInstance()->addField('pika')->setDefaultValue('pika', function () {
                return 'chu';
            }))
                ->array($juicer->squash([]))
                    ->isEqualTo(['pika' => 'chu'])
            ->assert('Default value does not apply when field is set.')
                ->array($juicer->squash(['pika' => 'not chu']))
                    ->isEqualTo(['pika' => 'not chu'])
            ->assert('When the field exist but has not value, the default value does NOT apply.')
            ->given($juicer->addCleaner('pika', function ($v) {
                $v = trim($v);
                return strlen($v) === 0 ? null : $v;
            }))
                ->array($juicer->squash(['pika' => '   ']))
                    ->isEqualTo(['pika' => null])
            ->assert('Default value can not be a callable')
            ->given($juicer->setDefaultValue('pika', 'chuu'))
                ->array($juicer->squash([]))
                    ->isEqualTo(['pika' => 'chuu'])
            ;
    }

    /**
     * testCleaner
     *
     * Simple test for cleaners.
     */
    public function testCleaner()
    {
        $juicer = $this->newTestedInstance()
            ->addField('pika')
            ->addCleaner('pika', function ($v) {
                return trim($v);
            })
            ->addCleaner('pika', function ($v) {
                return strtolower($v);
            })
            ;
        $this
            ->assert("Checking cleaners are called,")
            ->given($data = ['pika' => ' This Is It  '])
                ->array($juicer->squash($data))
                    ->isEqualTo(['pika' => 'this is it'])
            ;
    }

    /**
     * testCleanBeforeValidate
     *
     * Check cleaning is executed before validation.
     */
    public function testCleanBeforeValidate()
    {
        $juicer = $this->newTestedInstance()
            ->addField('pika')
            ->addCleaner('pika', function ($v) {
                return trim($v);
            })
            ->addValidator('pika', function ($v) {
                if (strlen($v) === 0) {
                    return 'is an empty string';
                }
            })
        ;

        $this
            ->assert('Check it cleans first and then validate.')
            ->given($data = ['pika' => '   '])
                ->exception(function () use ($juicer, $data) {
                    return $juicer->squash($data);
                })
                    ->isInstanceOf('Chanmix51\ParameterJuicer\Exception\ValidationException')
            ;
    }

    /**
     * provideCompleteUseThatPass
     *
     * Data provider for completeUseThatPass
     */
    public function provideCompleteUseThatPass(): array
    {
        return [
            [['pika' => ' Ah ah!'], Juicer::STRATEGY_ACCEPT_EXTRA_VALUES, ['pika' => 'ah ah!']],
            [['pika' => ' b  b   ', 'chu' => '3.141596'], Juicer::STRATEGY_ACCEPT_EXTRA_VALUES, ['pika' => 'b  b', 'chu' => 3.141596]],
            [['pika' => 'cCc', 'extra' => 'oï'], Juicer::STRATEGY_IGNORE_EXTRA_VALUES, ['pika' => 'ccc']]
        ];
    }

    /**
     * More complex scenarios that pass.
     * @dataProvider provideCompleteUseThatPass
     */
    public function testCompleteUseThatPass(array $input, int $strategy, array $expected)
    {
        $this
            ->assert("Ignoring extra fields with cleaning & validation.")
            ->given($juicer = (new PikaChuJuicer)->setStrategy($strategy))
                ->array($juicer->squash($input))
                    ->isEqualTo($expected)
            ;
    }

    /**
     * Quick test for embeded juicers.
     */
    public function testEmbededValidators()
    {
        $this
            ->assert('Checking embeded validation works.')
            ->given($juicer = new PokemonJuicer)
                ->array($juicer->squash(
                    ['pokemon_id' => '1 azerty', 'pika_chu' => ['pika' => ' AaA ', 'chu' => '2']]
                ))
                ->isEqualTo(
                    ['pokemon_id' => 1, 'pika_chu' => ['pika' => 'aaa', 'chu' => 2]]
                )
            ;
    }

    /**
     * Check mixing embedded validation with custom validators
     */
    public function testAddValidatorsWithEmbedded()
    {
        $this
            ->assert('Checking mixing embedded validation with custom validators.')
            ->given($cleanString = function ($str) {
                return trim($str);
            })
            ->given($mustBeNonEmptyString = function ($str) {
                return $str !== '' ? null : 'must not be empty nor blank';
            })
            ->given(
                $juicer = ($this->newTestedInstance())
                    ->addField('my_form')
                        ->addJuicer('my_form', ($this->newTestedInstance())
                            ->addField('pass')
                                ->addCleaner('pass', $cleanString)
                                ->addValidator('pass', $mustBeNonEmptyString)
                            ->addField('repass')
                                ->addCleaner('repass', $cleanString)
                                ->addValidator('repass', $mustBeNonEmptyString))
                    ->addValidator('my_form', function ($values) {
                        return $values['pass'] === $values['repass']
                        ? null
                        : 'pass & repass do not match';
                    })
            )
            ->exception(function () use ($juicer) {
                return $juicer->squash(['my_form' => ['pass' => 'pika', 'repass' => 'not pika']]);
            })
            ->isInstanceOf('\Chanmix51\ParameterJuicer\Exception\ValidationException')
            ;
    }

    /**
     * This tests that the order is the following:
     * 1 - clean
     * 2 - apply default value
     * 3 - validate
     */
    public function testCleanerAndValidatorWorkflow()
    {
        $juicer = $this->newTestedInstance()
            ->addField('pika')
            ->addCleaner('pika', function ($v) {
                $v = preg_replace('/[^\w]+/', '', strtolower(trim($v)));
                if ($v === '') {
                    throw new CleanerRemoveFieldException;
                }
                return $v;
            })
            ->setDefaultValue('pika', 'default value')
            ->addValidator(
                'pika',
                function ($v) {
                    if (strlen(trim($v)) === 0) {
                        throw new ValidationException('can not be white string');
                    }
                }
            )
            ->addField('chu')
            ->addCleaner(
                'chu',
                function ($v) {
                    $v = (int) $v;
                    if ($v === 5) {
                        throw new CleanerRemoveFieldException;
                    }
                    return $v;
                }
            )
        ;
        $e = $this
            ->assert('Testing the juicer workflow.')
            ->given($data = ['pika' => ' - - ', 'chu' => 0])
                ->array($juicer->squash($data))
                    ->isEqualTo(['pika' => 'default value', 'chu' => 0])
            ->given($data = ['pika' => 'whatever', 'chu' => 5])
                ->exception(function () use ($juicer, $data) {
                    return $juicer->squash($data);
                })
                    ->isInstanceOf('Chanmix51\ParameterJuicer\Exception\ValidationException')
                    ->getValue()
                ;
        $this
            ->given($exception = $e->getExceptions()['chu'][0])
            ->exception($exception)->message->contains('missing mandatory field')
            ;
    }

    /**
     * Test form cleaners and validators
     */
    public function testFormFields()
    {
        $this
            ->assert('Testing form cleaners')
            ->given(
                $juicer = (new PikaChuJuicer)
                    ->addFormCleaner(function ($fields) {
                        if (isset($fields['pika']) && $fields['pika'] === 'aaa' && isset($fields['chu'])) {
                            unset($fields['chu']);
                        }

                        return $fields;
                    })
                    ->addFormValidator(function ($fields) {
                        if ($fields['pika'] === 'aab') {
                            if ($fields['chu'] % 2 === 0) {
                                return 'cannot be even on a AAB pika code';
                            }
                        }
                    })
                    ->addFormValidator(function ($fields) {
                        if ($fields['pika'] === 'aad' && $fields['chu'] !== 0) {
                            throw new ValidationException('AAD pika with a non zero chu');
                        }
                    })
            )
                ->array($juicer->squash(
                    ['pika' => ' AaA ', 'chu' => '2']
                ))
                ->isEqualTo(
                    ['pika' => 'aaa']
                )
                ->array($juicer->squash(
                    ['pika' => ' Aab ', 'chu' => '3']
                ))
                ->isEqualTo(
                    ['pika' => 'aab', 'chu' => 3]
                )
            ;

        try {
            $juicer->squash(['pika' => ' Aab ', 'chu' => '2']);
            $exception = null;
        } catch (ValidationException $e) {
            $exception = $e;
        }
        $this
            ->assert('Form validation strings throw validation exceptions.')
            ->exception($exception)
                ->isInstanceOf('Chanmix51\ParameterJuicer\Exception\ValidationException')
            ->assert('Form validation exceptions are also nested.')
            ->boolean($exception->hasExceptions())
                ->isTrue()
            ->exception($exception->getExceptions()[''][0])
                ->message->contains('cannot be even')
            ;
        try {
            $juicer->squash(['pika' => ' Aad ', 'chu' => '2']);
            $exception = null;
        } catch (ValidationException $e) {
            $exception = $e;
        }
        $this
            ->assert('When form validators throw exceptions, they are nested.')
            ->exception($exception)
                ->isInstanceOf('Chanmix51\ParameterJuicer\Exception\ValidationException')
            ->boolean($exception->hasExceptions())
                ->isTrue()
            ->exception($exception->getExceptions()[''][0])
                ->message->contains('non zero chu')
            ;
    }

    /**
     * Ensure default values operates seamlessly with form fields.
     */
    public function testFormFieldsWithDefaultValues()
    {
        $this
            ->assert('Checking fields removal in the form cleaning phase still makes default values to apply.')
            ->given($juicer = $this->newTestedInstance()
                ->addField('first')
                    ->setDefaultValue('first', 'default')
                    ->addValidator('first', function ($v) {
                        return (strlen(trim($v)) === 0) ? 'must not be empty or blank' : null;
                    })
                ->addFormCleaner(function ($v) {
                    unset($v['first']);
                    return $v;
                }))
            ->array($juicer->squash([]))
                ->isEqualTo(['first' => 'default'])
            ->array($juicer->squash(['first' => 'whatever']))
                ->isEqualTo(['first' => 'default'])
            ->array($juicer->squash(['first' => '   ']))
                ->isEqualTo(['first' => 'default'])
            ->array($juicer->squash([]))
                ->isEqualTo(['first' => 'default'])
            ;
    }

    /**
     * Form validators are triggered or not depending on form validation
     * strategy.
     */
    public function testFormValidatorStrategies()
    {
        $turn_to_integer = function ($v) {
            return (int) ($v + 0);
        };
        $must_be_positive = function ($v) {
            return ($v > 0) ? null : 'must be strictly positive';
        };
        $juicer = $this->newTestedInstance()
            ->addField('min')
                ->addCleaner('min', $turn_to_integer)
                ->addValidator('min', $must_be_positive)
            ->addField('max')
                ->addCleaner('max', $turn_to_integer)
                ->addValidator('max', $must_be_positive)
            ->addFormValidator(function ($values) {
                return ($values['min'] >= $values['max'])
                    ? '"min" must be strictly lesser than "max"'
                    : null;
            })
            ;

        try {
            $juicer->squash(['min' => 0, 'chu' => '2']);
            $exception = null;
        } catch (ValidationException $e) {
            $exception = $e;
        }
        $this
            ->assert('Form validators are not triggered by default using the FORM_VALIDATORS_CONDITIONAL strategy when field validation fails.')
            ->exception($exception)
                ->isInstanceOf('Chanmix51\ParameterJuicer\Exception\ValidationException')
            ->array($exception->getExceptions())
                ->hasSize(2)
                ->hasKeys(['min', 'max'])
            ;
        try {
            $juicer->squash(['min' => 3, 'max' => 1]);
            $exception = null;
        } catch (ValidationException $e) {
            $exception = $e;
        }
        $this
            ->assert('Form validators are only triggered by default using the FORM_VALIDATORS_CONDITIONAL strategy when field validation passes.')
            ->exception($exception)
                ->isInstanceOf('Chanmix51\ParameterJuicer\Exception\ValidationException')
            ->array($exception->getExceptions())
                ->hasSize(1)
                ->hasKeys([''])
            ;
        try {
            $juicer->setFormValidationStrategy(Juicer::FORM_VALIDATORS_ALWAYS)
                ->squash(['min' => 2, 'max' => 0]);
            $exception = null;
        } catch (ValidationException $e) {
            $exception = $e;
        }
        $this
            ->assert('Form validators are always triggered when using FORM_VALIDATORS_ALWAYS strategy.')
            ->exception($exception)
                ->isInstanceOf('Chanmix51\ParameterJuicer\Exception\ValidationException')
            ->array($exception->getExceptions())
                ->hasSize(2)
                ->hasKeys(['max', ''])
            ;
    }

    /**
     * Check the nested juicers are squashed during the cleaning phase
     */
    public function testSquashSubJuicers()
    {
        $castToFloat = function($v): float { return (float) $v; };
        $juicer = $this->newTestedInstance()
            ->addField('position')
                ->addJuicer(
                    'position',
                    $this->newTestedInstance()
                        ->addField('latitude')
                            ->addCleaner('latitude', $castToFloat)
                        ->addField('longitude')
                            ->addCleaner('longitude', $castToFloat)
                )
                ->addCleaner('position', function($v) {
                    try {
                        return Position::new($v['latitude'], $v['longitude']);
                    } catch (\DomainException $e) {
                        return null;
                    }
                })
            ;

        $this
            ->assert('Check the subform is squashed before being cleaned in the form.')
            ->given($data = $juicer->squash(['position' => ['latitude' => '0.1', 'longitude' => '-99.1234']]))
            ->array($data)
                ->object['position']->isEqualTo(Position::new(0.1, -99.1234))
        ;
    }
}
