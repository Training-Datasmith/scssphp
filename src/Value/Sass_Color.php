<?php

declare (strict_types=1);
/**
 * SCSSPHP
 *
 * @copyright 2012-2020 Leaf Corcoran
 *
 * @license http://opensource.org/licenses/MIT MIT
 *
 * @link http://scssphp.github.io/scssphp
 */
namespace Scss_Php\Scss_Php\Value;

use Scss_Php\Scss_Php\Exception\Sass_Script_Exception;
use Scss_Php\Scss_Php\Util\Error_Util;
use Scss_Php\Scss_Php\Util\Number_Util;
use Scss_Php\Scss_Php\Visitor\Value_Visitor;
/**
 * A SassScript color.
 */
final class Sass_Color extends Value
{
    /**
     * Creates a RGB color
     *
     * @throws \OutOfRangeException if values are outside the expected range.
     */
    public static function rgb(int $red, int $green, int $blue, float $alpha = 1.0): Sass_Color
    {
        return self::rgb_internal($red, $green, $blue, $alpha);
    }
    /**
     * Like {@see rgb} but also takes a color format.
     *
     * @internal
     *
     * @throws \OutOfRangeException if values are outside the expected range.
     */
    public static function rgb_internal(int $red, int $green, int $blue, float $alpha = 1.0, ?Color_Format $format = null): Sass_Color
    {
        $alpha = Number_Util::fuzzy_assert_range($alpha, 0, 1, 'alpha');
        Error_Util::check_int_in_interval($red, 0, 255, 'red');
        Error_Util::check_int_in_interval($green, 0, 255, 'green');
        Error_Util::check_int_in_interval($blue, 0, 255, 'blue');
        return new self($red, $green, $blue, null, null, null, $alpha, $format);
    }
    /**
     * @throws \OutOfRangeException if values are outside the expected range.
     */
    public static function hsl(float $hue, float $saturation, float $lightness, float $alpha = 1.0): Sass_Color
    {
        return self::hsl_internal($hue, $saturation, $lightness, $alpha);
    }
    /**
     * Like {@see hsl} but also takes a color format.
     *
     * @internal
     *
     * @throws \OutOfRangeException if values are outside the expected range.
     */
    public static function hsl_internal(float $hue, float $saturation, float $lightness, float $alpha = 1.0, ?Color_Format $format = null): Sass_Color
    {
        $alpha = Number_Util::fuzzy_assert_range($alpha, 0, 1, 'alpha');
        $hue = fmod($hue, 360);
        if ($hue < 0) {
            $hue += 360;
        }
        $saturation = Number_Util::fuzzy_assert_range($saturation, 0, 100, 'saturation');
        $lightness = Number_Util::fuzzy_assert_range($lightness, 0, 100, 'lightness');
        return new self(null, null, null, $hue, $saturation, $lightness, $alpha, $format);
    }
    public static function hwb(float $hue, float $whiteness, float $blackness, float $alpha = 1.0): Sass_Color
    {
        $hue = fmod($hue, 360);
        if ($hue < 0) {
            $hue += 360;
        }
        $scaled_hue = $hue / 360;
        $scaled_whiteness = Number_Util::fuzzy_assert_range($whiteness, 0, 100, 'whiteness') / 100;
        $scaled_blackness = Number_Util::fuzzy_assert_range($blackness, 0, 100, 'blackness') / 100;
        $sum = $scaled_whiteness + $scaled_blackness;
        if ($sum > 1) {
            $scaled_whiteness /= $sum;
            $scaled_blackness /= $sum;
        }
        $factor = 1 - $scaled_whiteness - $scaled_blackness;
        $to_rgb = function (float $hue) use ($factor, $scaled_whiteness): int {
            $channel = self::hue_to_rgb(0, 1, $hue) * $factor + $scaled_whiteness;
            return Number_Util::fuzzy_round($channel * 255);
        };
        return self::rgb($to_rgb($scaled_hue + 1 / 3), $to_rgb($scaled_hue), $to_rgb($scaled_hue - 1 / 3), $alpha);
    }
    /**
     * This must always provide non-null values for either RGB or HSL values.
     * If they are all provided, they are expected to be in sync and this not
     * revalidated. This constructor does not revalidate ranges either.
     * Use named factories when this cannot be guaranteed.
     */
    private function __construct(
        /**
         * This color's red channel, between `0` and `255`.
         */
        private ?int $red,
        /**
         * This color's green channel, between `0` and `255`.
         */
        private ?int $green,
        /**
         * This color's blue channel, between `0` and `255`.
         */
        private ?int $blue,
        /**
         * This color's hue, between `0` and `360`.
         */
        private ?float $hue,
        /**
         * This color's saturation, a percentage between `0` and `100`.
         */
        private ?float $saturation,
        /**
         * This color's lightness, a percentage between `0` and `100`.
         */
        private ?float $lightness,
        /**
         * This color's alpha channel, between `0` and `1`.
         */
        private readonly float $alpha,
        private readonly ?Color_Format $format = null
    )
    {
    }
    public function get_red(): int
    {
        if (\is_null($this->red)) {
            $this->hsl_to_rgb();
            assert(!\is_null($this->red));
        }
        return $this->red;
    }
    public function get_green(): int
    {
        if (\is_null($this->green)) {
            $this->hsl_to_rgb();
            assert(!\is_null($this->green));
        }
        return $this->green;
    }
    public function get_blue(): int
    {
        if (\is_null($this->blue)) {
            $this->hsl_to_rgb();
            assert(!\is_null($this->blue));
        }
        return $this->blue;
    }
    public function get_hue(): float
    {
        if (\is_null($this->hue)) {
            $this->rgb_to_hsl();
            assert(!\is_null($this->hue));
        }
        return $this->hue;
    }
    public function get_saturation(): float
    {
        if (\is_null($this->saturation)) {
            $this->rgb_to_hsl();
            assert(!\is_null($this->saturation));
        }
        return $this->saturation;
    }
    public function get_lightness(): float
    {
        if (\is_null($this->lightness)) {
            $this->rgb_to_hsl();
            assert(!\is_null($this->lightness));
        }
        return $this->lightness;
    }
    public function get_whiteness(): float
    {
        return min($this->get_red(), $this->get_green(), $this->get_blue()) / 255 * 100;
    }
    public function get_blackness(): float
    {
        return 100 - max($this->get_red(), $this->get_green(), $this->get_blue()) / 255 * 100;
    }
    public function get_alpha(): float
    {
        return $this->alpha;
    }
    /**
     * The format in which this color was originally written and should be
     * serialized in expanded mode, or `null` if the color wasn't written in a
     * supported format.
     *
     * @internal
     */
    public function get_format(): ?Color_Format
    {
        return $this->format;
    }
    public function accept(Value_Visitor $visitor)
    {
        return $visitor->visit_color($this);
    }
    public function assert_color(?string $name = null): Sass_Color
    {
        return $this;
    }
    public function change_rgb(?int $red = null, ?int $green = null, ?int $blue = null, ?float $alpha = null): Sass_Color
    {
        return self::rgb($red ?? $this->get_red(), $green ?? $this->get_green(), $blue ?? $this->get_blue(), $alpha ?? $this->alpha);
    }
    public function change_hsl(?float $hue = null, ?float $saturation = null, ?float $lightness = null, ?float $alpha = null): Sass_Color
    {
        return self::hsl($hue ?? $this->get_hue(), $saturation ?? $this->get_saturation(), $lightness ?? $this->get_lightness(), $alpha ?? $this->alpha);
    }
    public function change_hwb(?float $hue = null, ?float $whiteness = null, ?float $blackness = null, ?float $alpha = null): Sass_Color
    {
        return self::hwb($hue ?? $this->get_hue(), $whiteness ?? $this->get_whiteness(), $blackness ?? $this->get_blackness(), $alpha ?? $this->alpha);
    }
    public function change_alpha(float $alpha): Sass_Color
    {
        return new self($this->red, $this->green, $this->blue, $this->hue, $this->saturation, $this->lightness, Number_Util::fuzzy_assert_range($alpha, 0, 1, 'alpha'));
    }
    public function plus(Value $other): Value
    {
        if (!$other instanceof Sass_Color && !$other instanceof Sass_Number) {
            return parent::plus($other);
        }
        throw new Sass_Script_Exception("Undefined operation \"{$this} + {$other}\".");
    }
    public function minus(Value $other): Value
    {
        if (!$other instanceof Sass_Color && !$other instanceof Sass_Number) {
            return parent::minus($other);
        }
        throw new Sass_Script_Exception("Undefined operation \"{$this} - {$other}\".");
    }
    public function divided_by(Value $other): Value
    {
        if (!$other instanceof Sass_Color && !$other instanceof Sass_Number) {
            return parent::divided_by($other);
        }
        throw new Sass_Script_Exception("Undefined operation \"{$this} / {$other}\".");
    }
    public function modulo(Value $other): Value
    {
        if (!$other instanceof Sass_Color && !$other instanceof Sass_Number) {
            return parent::modulo($other);
        }
        throw new Sass_Script_Exception("Undefined operation \"{$this} % {$other}\".");
    }
    public function equals(object $other): bool
    {
        return $other instanceof Sass_Color && $this->get_red() === $other->get_red() && $this->get_green() === $other->get_green() && $this->get_blue() === $other->get_blue() && $this->alpha === $other->alpha;
    }
    private function rgb_to_hsl(): void
    {
        $scaled_red = $this->get_red() / 255;
        $scaled_green = $this->get_green() / 255;
        $scaled_blue = $this->get_blue() / 255;
        $min = min($scaled_red, $scaled_green, $scaled_blue);
        $max = max($scaled_red, $scaled_green, $scaled_blue);
        $delta = $max - $min;
        if ($delta == 0) {
            $this->hue = 0;
        } elseif ($max == $scaled_red) {
            $this->hue = fmod(60 * ($scaled_green - $scaled_blue) / $delta, 360);
        } elseif ($max == $scaled_green) {
            $this->hue = fmod(120 + 60 * ($scaled_blue - $scaled_red) / $delta, 360);
        } else {
            $this->hue = fmod(240 + 60 * ($scaled_red - $scaled_green) / $delta, 360);
        }
        if ($this->hue < 0) {
            $this->hue += 360;
        }
        $this->lightness = 50 * ($max + $min);
        if ($max == $min) {
            $this->saturation = 0;
        } elseif ($this->lightness < 50) {
            $this->saturation = 100 * $delta / ($max + $min);
        } else {
            $this->saturation = 100 * $delta / (2 - $max - $min);
        }
    }
    private function hsl_to_rgb(): void
    {
        $scaled_hue = $this->get_hue() / 360;
        $scaled_saturation = $this->get_saturation() / 100;
        $scaled_lightness = $this->get_lightness() / 100;
        if ($scaled_lightness <= 0.5) {
            $m2 = $scaled_lightness * ($scaled_saturation + 1);
        } else {
            $m2 = $scaled_lightness + $scaled_saturation - $scaled_lightness * $scaled_saturation;
        }
        $m1 = $scaled_lightness * 2 - $m2;
        $this->red = Number_Util::fuzzy_round(self::hue_to_rgb($m1, $m2, $scaled_hue + 1 / 3) * 255);
        $this->green = Number_Util::fuzzy_round(self::hue_to_rgb($m1, $m2, $scaled_hue) * 255);
        $this->blue = Number_Util::fuzzy_round(self::hue_to_rgb($m1, $m2, $scaled_hue - 1 / 3) * 255);
    }
    private static function hue_to_rgb(float $m1, float $m2, float $hue): float
    {
        if ($hue < 0) {
            $hue += 1;
        } elseif ($hue > 1) {
            $hue -= 1;
        }
        if ($hue < 1 / 6) {
            return $m1 + ($m2 - $m1) * $hue * 6;
        }
        if ($hue < 1 / 2) {
            return $m2;
        }
        if ($hue < 2 / 3) {
            return $m1 + ($m2 - $m1) * (2 / 3 - $hue) * 6;
        }
        return $m1;
    }
}