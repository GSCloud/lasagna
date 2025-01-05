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

namespace GSC;

/**
 * SI constants interface
 * 
 * @category CMS
 * @package  Framework
 * @author   Fred Brooker <git@gscloud.cz>
 * @license  MIT https://gscloud.cz/LICENSE.txt
 * @link     https://github.com/GSCloud/lasagna
 */
interface ISIconstants
{
}

/**
 * SI constants class
 * 
 * @category CMS
 * @package  Framework
 * @author   Fred Brooker <git@gscloud.cz>
 * @license  MIT https://gscloud.cz/LICENSE.txt
 * @link     https://github.com/GSCloud/lasagna
 */
class SIconstants implements ISIconstants
{
    // SI Base Units
    const AVOGADRO_CONSTANT = 6.02214076e23; // mol^-1
    const BOLTZMANN_CONSTANT = 1.380649e-23; // J/K
    const PLANCK_CONSTANT = 6.62607015e-34; // J⋅s

    const CAESIUM_HYPERFINE_FREQUENCY = 9192631770; // Hz, 133Cs frq of 9.2 GHz
    const ELEMENTARY_CHARGE = 1.602176634e-19; // C
    const SPEED_OF_LIGHT = 299792458; // m/s

    // Frequency
    const HERTZ_TIME = 1 / self::CAESIUM_HYPERFINE_FREQUENCY; // sec

    // others
    const PI = M_PI; // π
    public static $GOLDEN_RATIO = null;

    /**
     * Returns the mathematical constant Golden Ratio (φ).
     *
     * @return float The Golden Ratio (φ).
     */
    public static function goldenRatio()
    {
        return (1 + sqrt(5)) / 2;
    }

    /**
     * Set missing constants that need to be calculated
     *
     * @return void
     */
    public static function setup()
    {
        self::$GOLDEN_RATIO = self::goldenRatio();
    }
}
