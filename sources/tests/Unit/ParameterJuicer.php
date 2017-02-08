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

use Chanmix51\ParameterJuicer\ValidationException;

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
     * testEmptyValidation
     *
     * Test empty validation works with STRATEGY_ACCEPT_EXTRA_VALUES.
     */
    public function testEmptyValidationWithAcceptStrategy()
    {
        $juicer = $this->newTestedInstance();
        $this
            ->assert("Testing an empty validator & values and STRATEGY_ACCEPT_EXTRA_VALUES")
            ->given($data = [])
                ->array($juicer->validateAndClean($data, 2))
                    ->isEqualTo([])
            ->assert("Testing an empty validator with values and STRATEGY_ACCEPT_EXTRA_VALUES")
            ->given($data = ['pika' => 'chu'])
                ->array($juicer->validateAndClean($data, 2))
                    ->isEqualTo(['pika' => 'chu'])
                    ;
    }

    /**
     * testEmptyValidationWithIgnoreStrategy
     *
     * Empty validator with an ignore strategy returns empty sets.
     */
    public function testEmptyValidationWithIgnoreStrategy()
    {
        $juicer = $this->newTestedInstance();
        $this
            ->assert("Testing an empty validator and STRATEGY_IGNORE_EXTRA_VALUES.")
            ->given($data = [])
                ->array($juicer->validateAndClean($data, 0))
                    ->isEqualTo([])
            ->given($data = ['pika' => 'chu'])
                ->array($juicer->validateAndClean($data, 0))
                    ->isEqualTo([])
            ;
    }

    /**
     * testEmptyValidationWithRefuseStrategy
     *
     * This must refuse every values set but empty ones.
     */
    public function testEmptyValidationWithRefuseStrategy()
    {
        $juicer = $this->newTestedInstance();
        $this
            ->assert("Testing an empty validator & values and STRATEGY_REFUSE_EXTRA_VALUES.")
            ->given($data = [])
                ->array($juicer->validateAndClean($data, 1))
                    ->isEqualTo([])
            ->assert("Testing an empty validator with values and STRATEGY_REFUSE_EXTRA_VALUES.")
            ->given($data = ['pika' => 'chu'])
                ->exception(function() use ($juicer, $data) { return $juicer->validateAndClean($data, 1); })
                    ->isInstanceOf('Chanmix51\ParameterJuicer\ValidationException')
                    ;
    }

    /**
     * butPikaOrChu
     *
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
     * testMandatoryFieldsValidationFail
     *
     * Missing mandatory fields must fail validation.
     * @dataProvider butPikaOrChu
     */
    public function testMandatoryFieldsValidationFail(array $data)
    {
        $juicer = $this->newTestedInstance()
            ->addField('pika')
            ->addValidator('pika', function($v) { })
            ->addField('chu')
            ;
        $this
            ->assert("Checking validation with missing mandatory data.")
                ->exception(function() use ($juicer, $data) { return $juicer->validateAndClean($data, 2); })
                    ->isInstanceOf('Chanmix51\ParameterJuicer\ValidationException')
                    ->message->contains("Missing field")
            ;
    }

    /**
     * testMandatoryFieldValidation
     *
     * Testing validation & mandatory fields
     */
    public function testMandatoryFieldValidation()
    {
        $validate_int = function($v) {
            if (!is_int($v)) throw new ValidationException(
                sprintf(
                    "Field must be an integer ('%s' detected).",
                    gettype($v)
                )
            );
        };
        $validate_range = function($v) {
            if (10 < $v || 1 > $v) throw new ValidationException(
                sprintf(
                    "%d must be between 1 and 10.",
                    $v
                )
            );
        };
        $juicer = $this->newTestedInstance()
            ->addField('pika')
            ->addValidator('pika', $validate_int)
            ->addValidator('pika', $validate_range)
            ->addField('chu')
            ->addField('not mandatory', false)
            ;
        $this
            ->assert("Checking validation with all mandatory data.")
            ->given($data = ['pika' => 9, 'chu' => 'there'])
                ->array($juicer->validateAndClean($data, 1))
                ->isEqualTo($data)
            ->assert("Checking validation with some wrong mandatory data (1/2).")
            ->given($data['pika'] = 19)
                ->exception(function() use ($juicer, $data) { return $juicer->validateAndClean($data, 1); })
                    ->isInstanceOf('Chanmix51\ParameterJuicer\ValidationException')
                    ->message->contains('must be between 1 and 10')
            ->assert("Checking validation with some wrong mandatory data (2/2).")
            ->given($data['pika'] = 'chu')
                ->exception(function() use ($juicer, $data) { return $juicer->validateAndClean($data, 1); })
                    ->isInstanceOf('Chanmix51\ParameterJuicer\ValidationException')
                    ->message->contains('Field must be an integer')
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
            ->addCleaner('pika', function($v) { return trim($v); })
            ->addCleaner('pika', function($v) { return strtolower($v); })
            ;
        $this
            ->assert("Checking cleaners are called,")
                ->array($juicer->validateAndClean(['pika' => ' This Is It  ']))
                    ->isEqualTo(['pika' => 'this is it'])
            ;
    }
}
