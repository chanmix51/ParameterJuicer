<?php declare(strict_types=1);

namespace Chanmix51\ParameterJuicer\Tests\Fixtures;

class Position
{
    private function __construct(private float $latitude, private float $longitude)
    {
    }

    public static function new(float $latitude, float $longitude): self
    {
        if ($latitude < -90 || $latitude > 90) {
            throw new \DomainException(
                sprintf("'%s' is not a valid latitude", $latitude)
            );
        } elseif ($longitude < -180 || $longitude > 180) {
            throw new \DomainException(
                sprintf("'%s' is not a valid longitude", $longitude)
            );
        }

        return new Position($latitude, $longitude);
    }
}
