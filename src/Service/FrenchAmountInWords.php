<?php

namespace App\Service;

/**
 * Writes a TND amount in French words, e.g. 120.500 => "cent vingt dinars et cinq cents millimes".
 * No dependency on ext-intl.
 */
class FrenchAmountInWords
{
    private const UNITS = [
        'zéro', 'un', 'deux', 'trois', 'quatre', 'cinq', 'six', 'sept', 'huit', 'neuf',
        'dix', 'onze', 'douze', 'treize', 'quatorze', 'quinze', 'seize',
    ];

    private const TENS = [
        2 => 'vingt', 3 => 'trente', 4 => 'quarante', 5 => 'cinquante', 6 => 'soixante',
    ];

    /**
     * Amount in dinars (3 decimals = millimes) => "x dinars et y millimes".
     */
    public function amount(float $amount): string
    {
        $totalMillimes = (int) round($amount * 1000);
        $dinars = intdiv($totalMillimes, 1000);
        $millimes = $totalMillimes % 1000;

        // "un million de dinars", but "deux millions cinq cent mille dinars"
        $de = $dinars >= 1_000_000 && $dinars % 1_000_000 === 0 ? ' de' : '';
        $text = $this->number($dinars).$de.' '.($dinars > 1 ? 'dinars' : 'dinar');

        if ($millimes > 0) {
            $text .= ' et '.$this->number($millimes).' '.($millimes > 1 ? 'millimes' : 'millime');
        }

        return $text;
    }

    public function number(int $n): string
    {
        if ($n === 0) {
            return 'zéro';
        }

        $parts = [];
        $billions = intdiv($n, 1_000_000_000);
        $millions = intdiv($n % 1_000_000_000, 1_000_000);
        $thousands = intdiv($n % 1_000_000, 1000);
        $rest = $n % 1000;

        if ($billions > 0) {
            $parts[] = $this->below1000($billions, true).' milliard'.($billions > 1 ? 's' : '');
        }
        if ($millions > 0) {
            $parts[] = $this->below1000($millions, true).' million'.($millions > 1 ? 's' : '');
        }
        if ($thousands > 0) {
            // "mille" is invariable, and we say "mille" not "un mille"
            $parts[] = $thousands === 1 ? 'mille' : $this->below1000($thousands, false).' mille';
        }
        if ($rest > 0) {
            $parts[] = $this->below1000($rest, true);
        }

        return implode(' ', $parts);
    }

    /**
     * @param bool $final whether the group ends the number (so "quatre-vingts" / "deux cents" take their "s")
     */
    private function below1000(int $n, bool $final): string
    {
        $hundreds = intdiv($n, 100);
        $rest = $n % 100;
        $out = '';

        if ($hundreds > 0) {
            $out = $hundreds === 1 ? 'cent' : self::UNITS[$hundreds].' cent';
            if ($hundreds > 1 && $rest === 0 && $final) {
                $out .= 's';
            }
        }

        if ($rest > 0) {
            $out .= ($out !== '' ? ' ' : '').$this->below100($rest, $final);
        }

        return $out;
    }

    private function below100(int $n, bool $final): string
    {
        if ($n <= 16) {
            return self::UNITS[$n];
        }
        if ($n < 20) {
            return 'dix-'.self::UNITS[$n - 10];
        }

        $ten = intdiv($n, 10);
        $unit = $n % 10;

        if ($ten === 7 || $ten === 9) {
            // 70-79 = soixante + 10-19, 90-99 = quatre-vingt + 10-19
            $base = $ten === 7 ? 'soixante' : 'quatre-vingt';
            if ($ten === 7 && $unit === 1) {
                return 'soixante et onze';
            }

            return $base.'-'.$this->below100(10 + $unit, $final);
        }

        if ($ten === 8) {
            return $unit === 0 ? 'quatre-vingt'.($final ? 's' : '') : 'quatre-vingt-'.self::UNITS[$unit];
        }

        $word = self::TENS[$ten];
        if ($unit === 0) {
            return $word;
        }

        return $word.($unit === 1 ? ' et un' : '-'.self::UNITS[$unit]);
    }
}
