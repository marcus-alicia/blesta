<?php
/**
 * Color helper
 *
 * Create color contrasts and convert color values to various formats
 *
 * @package minPHP
 * @subpackage minPHP.helpers.color
 */
class Color
{
    /**
     * @var array A representation of the current color
     */
    private $color = [0,0,0];

    /**
     * Sets the current color to the given hex or HTML hex value
     *
     * @param string $color The given color in hex or HTMl hex value
     * @return Color $this
     */
    public function hex($color)
    {
        $this->color = $this->toRgb($color);
        return $this;
    }

    /**
     * Sets the current color to the given RGB value
     *
     * @param array $color The given color in a numerically indexed array where
     *  index 0 is red, index 1 is green, index 2 is blue
     * @return Color $this
     */
    public function rgb(array $color)
    {
        $this->color = $color;
        return $this;
    }

    /**
     * Finds the constrast of the current color using the 50/50 method then sets
     * it as the internal color
     *
     * @return Color $this
     */
    public function contrast50()
    {

        if (empty($this->color)) {
            return $this;
        }

        // Convert color to hex
        $hex = $this->asHex();

        // Find contrast color, set as new color
        return $this->hex((hexdec($hex) > 0xffffff / 2) ? '000' : 'fff');
    }

    /**
     * Finds the constrast of the current color in YIQ space then sets it as the
     * internal color
     *
     * @return Color $this
     */
    public function contrastYiq()
    {
        if (empty($this->color)) {
            return $this;
        }

        // Convert color to yiq
        $yiq = (($this->color[0] * 299) + ($this->color[1] * 587) + ($this->color[2] * 114)) / 1000;

        // Find constrast color, set as new color
        return $this->hex(($yiq >= 128) ? '000' : 'fff');
    }

    /**
     * Convert the internal color to HTML hex color
     *
     * @return string The HTML hex color for the current color
     */
    public function asHtml()
    {
        if (empty($this->color)) {
            return null;
        }

        return '#' . $this->asHex();
    }

    /**
     * Convert the internal color to hex
     *
     * @return string The hex color for the current color
     */
    public function asHex()
    {
        if (empty($this->color)) {
            return null;
        }

        return sprintf('%02x%02x%02x', $this->color[0], $this->color[1], $this->color[2]);
    }

    /**
     * Convert the internal color to an rgb array
     *
     * @return array The rgb color for the current color
     */
    public function asRgb()
    {
        if (empty($this->color)) {
            return null;
        }

        return $this->color;
    }

    /**
     * Convert a color from one format to rgb
     *
     * @param mixed $color The color to make rgb
     * @param string $from The format to convert from
     * @return array An rgb array
     */
    private function toRgb($color, $from = 'hex')
    {
        $rgb = [];
        switch ($from) {
            case 'hex':
                $color = trim($color, '#');
                $length = strlen($color);

                if ($length == 6) {
                    $hex = str_split($color, 2);
                } else {
                    $hex = str_split($color);
                    foreach ($hex as &$val) {
                        $val .= $val;
                    }
                }

                if (count($hex) == 3) {
                    $rgb = [hexdec($hex[0]), hexdec($hex[1]), hexdec($hex[2])];
                }
                break;
            case 'rgb':
                $rgb = $color;
                break;
        }

        return $rgb;
    }
}
