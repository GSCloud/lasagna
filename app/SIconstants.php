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
    const SPEED_OF_LIGHT = 299792458; // m/s
    const PLANCK_CONSTANT = 6.62607015e-34; // J⋅s
    const ELEMENTARY_CHARGE = 1.602176634e-19; // C
    const BOLTZMANN_CONSTANT = 1.380649e-23; // J/K
    const AVOGADRO_CONSTANT = 6.02214076e23; // mol^-1
    const CAESIUM_HYPERFINE_FREQUENCY = 9192631770; // Hz

    // others
    const PI = M_PI; // π
    public static $GOLDEN_RATIO;

    /**
     * Returns the mathematical constant Golden Ratio (φ).
     *
     * @return float The Golden Ratio (φ).
     */
    public static function goldenRatio()
    {
        return (1 + sqrt(5)) / 2;
    }
}
