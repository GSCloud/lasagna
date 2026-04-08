<?php
/**
 * GSC Tesseract
 * php version 8.2
 *
 * @category CMS
 * @package  Framework
 * @author   Fred Brooker <git@gscloud.cz>
 * @license  MIT https://gscloud.cz/LICENSE.txt
 * @link     https://github.com/GSCloud/lasagna
 */

declare (strict_types = 1);
namespace GSC;

/**
 * SI constants class
 * 
 * @category CMS
 * @package  Framework
 * @author   Fred Brooker <git@gscloud.cz>
 * @license  MIT https://gscloud.cz/LICENSE.txt
 * @link     https://github.com/GSCloud/lasagna
 */
class SIconstants
{
    const ABSOLUTE_ZERO = -273.15; // °C
    const ASTRONOMICAL_UNIT = 149597870700; // m
    const AVOGADRO_CONSTANT = 6.02214076e23; // mol^-1
    const BAR = 100000; // Pa
    const BOLTZMANN_CONSTANT = 1.380649e-23; // J/K
    const CAESIUM_HYPERFINE_FREQUENCY = 9192631770; // Hz, 133Cs frq of 9.2 GHz
    const E = M_E;
    const ELEMENTARY_CHARGE = 1.602176634e-19; // C
    const FINE_STRUCTURE_CONSTANT = 0.0072973525693;
    const GRAVITATIONAL_CONSTANT = 6.67430e-11; // m^3⋅kg^-1⋅s^-2
    const HERTZ_TIME = 1 / self::CAESIUM_HYPERFINE_FREQUENCY;
    const HUBBLE_CONSTANT = 2.2685e-18; // s^-1 (70 km/s/Mpc)
    const PARSEC = 3.085677581e16; // m
    const PI = M_PI; // π
    const PLANCK_CONSTANT = 6.62607015e-34; // J⋅s
    const PLANCK_LENGTH = 1.616255e-35; // m
    const PLANCK_TIME = 5.391247e-44; // s
    const SPEED_OF_LIGHT = 299792458; // m/s
    const STANDARD_GRAVITY = 9.80665; // m/s²
    const STANDARD_PITCH_A4 = 440.0; // Hz
    const STEFAN_BOLTZMANN_CONSTANT = 5.670374419e-8; // W⋅m^-2⋅K^-4
    public static float $GOLDEN_RATIO;
    public static float $TAU; // τ = 2π

    /**
     * Bootstrapping constants that require calculation
     * 
     * @category CMS
     * @package  Framework
     * @author   Fred Brooker <git@gscloud.cz>
     * @license  MIT https://gscloud.cz/LICENSE.txt
     * @link     https://github.com/GSCloud/lasagna
     * 
     * @return void
     */
    public static function setup(): void
    {
        self::$GOLDEN_RATIO = (1 + \sqrt(5)) / 2;
        self::$TAU = 2 * M_PI;
    }
}