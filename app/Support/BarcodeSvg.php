<?php

namespace App\Support;

/**
 * Renders a simple Code 39 style barcode as inline SVG for DomPDF (GRN labels).
 */
class BarcodeSvg
{
    /**
     * @var array<string, string>
     */
    protected static array $code39 = [
        '0' => 'nnnwwnwnn', '1' => 'wnnwnnnnw', '2' => 'nnwwnnnnw', '3' => 'wnwwnnnnn',
        '4' => 'nnnwwnnnw', '5' => 'wnnwwnnnn', '6' => 'nnwwwnnnn', '7' => 'nnnwnwnnw',
        '8' => 'wnnwnwnnn', '9' => 'nnwwnwnnn', 'A' => 'wnnnnwnnw', 'B' => 'nnwnnwnnw',
        'C' => 'wnwnnwnnn', 'D' => 'nnnnwwnnw', 'E' => 'wnnnwwnnn', 'F' => 'nnwnwwnnn',
        'G' => 'nnnnnwwnw', 'H' => 'wnnnnwwnn', 'I' => 'nnwnnwwnn', 'J' => 'nnnnwwwnn',
        'K' => 'wnnnnnnww', 'L' => 'nnwnnnnww', 'M' => 'wnwnnnnwn', 'N' => 'nnnnwnnww',
        'O' => 'wnnnwnnwn', 'P' => 'nnwnwnnwn', 'Q' => 'nnnnnnwww', 'R' => 'wnnnnnwwn',
        'S' => 'nnwnnnwwn', 'T' => 'nnnnwnwwn', 'U' => 'wwnnnnnnw', 'V' => 'nwwnnnnnw',
        'W' => 'wwwnnnnnn', 'X' => 'nwnnwnnnw', 'Y' => 'wwnnwnnnn', 'Z' => 'nwwnwnnnn',
        '-' => 'nwnnnnwnw', '.' => 'wwnnnnwnn', ' ' => 'nwwnnnwnn', '*' => 'nwnnwnwnn',
    ];

    public static function code39(string $text, int $barWidth = 2, int $height = 40): string
    {
        $encoded = '*'.strtoupper(preg_replace('/[^A-Z0-9\-\. ]/', '', $text) ?? '').'*';
        $x = 0;
        $bars = '';
        $len = strlen($encoded);

        for ($i = 0; $i < $len; $i++) {
            $char = $encoded[$i];
            $pattern = self::$code39[$char] ?? self::$code39['-'];
            for ($j = 0; $j < 9; $j++) {
                $isBar = $pattern[$j] === 'w';
                $w = $isBar ? $barWidth * 3 : $barWidth;
                if ($isBar) {
                    $bars .= '<rect x="'.$x.'" y="0" width="'.$w.'" height="'.$height.'" fill="#000"/>';
                }
                $x += $w;
            }
            $x += $barWidth;
        }

        return '<svg xmlns="http://www.w3.org/2000/svg" width="'.$x.'" height="'.($height + 14).'" viewBox="0 0 '.$x.' '.($height + 14).'">'
            .$bars
            .'<text x="'.($x / 2).'" y="'.($height + 12).'" text-anchor="middle" font-family="DejaVu Sans, sans-serif" font-size="9">'
            .htmlspecialchars($text, ENT_QUOTES, 'UTF-8')
            .'</text></svg>';
    }
}
