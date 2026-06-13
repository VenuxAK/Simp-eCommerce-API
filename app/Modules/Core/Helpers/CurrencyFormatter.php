<?php

namespace App\Modules\Core\Helpers;

/**
 * Formats currency values for display.
 *
 * Uses `\NumberFormatter` (PHP intl extension) when available for
 * proper locale-aware formatting. Falls back to manual formatting
 * when intl is not installed.
 */
class CurrencyFormatter
{
    /**
     * Format an amount in the given currency.
     *
     * @param  float   $amount              Numeric amount to format.
     * @param  string  $currency            ISO 4217 currency code (default: MMK).
     * @param  bool    $useMyanmarNumerals  Whether to use Myanmar numerals.
     * @return string                       e.g. "12,345 Ks" or "၁၂,၃၄၅ ကျပ်".
     */
    public static function format(float $amount, string $currency = 'MMK', bool $useMyanmarNumerals = false): string
    {
        if (class_exists(\NumberFormatter::class)) {
            $formatter = new \NumberFormatter('my_MM', \NumberFormatter::CURRENCY);
            $formatted = $formatter->formatCurrency($amount, $currency);

            if ($useMyanmarNumerals) {
                return $formatted;
            }

            return self::toLatinNumerals($formatted);
        }

        // Fallback when intl extension is not available.
        $formatted = number_format($amount, 0) . ' Ks';

        if ($useMyanmarNumerals) {
            return self::toMyanmarNumerals($formatted);
        }

        return $formatted;
    }

    /**
     * Convert Myanmar numerals to Latin numerals.
     */
    private static function toLatinNumerals(string $text): string
    {
        return str_replace(
            ['၀', '၁', '၂', '၃', '၄', '၅', '၆', '၇', '၈', '၉'],
            ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'],
            $text,
        );
    }

    /**
     * Convert Latin numerals to Myanmar numerals.
     */
    private static function toMyanmarNumerals(string $text): string
    {
        return str_replace(
            ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'],
            ['၀', '၁', '၂', '၃', '၄', '၅', '၆', '၇', '၈', '၉'],
            $text,
        );
    }
}
