<?php

namespace App\Support;

/**
 * Converts numeric amounts to Indian Rupees in words (SRS §21.2 amount in words).
 */
class NumberToWords
{
    /**
     * @var list<string>
     */
    private const ONES = [
        '', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine',
        'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen',
        'Seventeen', 'Eighteen', 'Nineteen',
    ];

    /**
     * @var list<string>
     */
    private const TENS = [
        '', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety',
    ];

    /**
     * Convert a decimal amount to rupees and paise in words.
     */
    public static function rupees(string|float|int $amount): string
    {
        $normalized = number_format((float) $amount, 2, '.', '');
        [$rupees, $paise] = explode('.', $normalized);

        $rupeeWords = self::convertInteger((int) $rupees);
        $paiseWords = (int) $paise > 0 ? self::convertInteger((int) $paise).' Paise' : '';

        if ($rupeeWords === '') {
            $rupeeWords = 'Zero';
        }

        $result = 'Rupees '.$rupeeWords.' Only';
        if ($paiseWords !== '') {
            $result = 'Rupees '.$rupeeWords.' and '.$paiseWords.' Only';
        }

        return $result;
    }

    private static function convertInteger(int $number): string
    {
        if ($number === 0) {
            return '';
        }

        $parts = [];

        if ($number >= 10000000) {
            $crores = (int) floor($number / 10000000);
            $parts[] = self::convertBelowThousand($crores).' Crore';
            $number %= 10000000;
        }

        if ($number >= 100000) {
            $lakhs = (int) floor($number / 100000);
            $parts[] = self::convertBelowThousand($lakhs).' Lakh';
            $number %= 100000;
        }

        if ($number >= 1000) {
            $thousands = (int) floor($number / 1000);
            $parts[] = self::convertBelowThousand($thousands).' Thousand';
            $number %= 1000;
        }

        if ($number > 0) {
            $parts[] = self::convertBelowThousand($number);
        }

        return trim(implode(' ', $parts));
    }

    private static function convertBelowThousand(int $number): string
    {
        $words = [];

        if ($number >= 100) {
            $words[] = self::ONES[(int) floor($number / 100)].' Hundred';
            $number %= 100;
        }

        if ($number >= 20) {
            $words[] = self::TENS[(int) floor($number / 10)];
            $number %= 10;
        }

        if ($number > 0) {
            $words[] = self::ONES[$number];
        }

        return trim(implode(' ', $words));
    }
}
