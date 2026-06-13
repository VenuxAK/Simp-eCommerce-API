<?php

namespace Tests\Feature\Api;

use App\Modules\Core\Helpers\CurrencyFormatter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocalizationTest extends TestCase
{
    use RefreshDatabase;

    // ─── Translation Files ─────────────────────────────────────

    public function test_myanmar_translation_file_exists(): void
    {
        $this->assertFileExists(lang_path('my/messages.php'));
    }

    public function test_english_translation_file_exists(): void
    {
        $this->assertFileExists(lang_path('en/messages.php'));
    }

    public function test_myanmar_and_english_have_same_keys(): void
    {
        $en = require lang_path('en/messages.php');
        $my = require lang_path('my/messages.php');

        $enKeys = $this->flattenKeys($en);
        $myKeys = $this->flattenKeys($my);

        $missingInMy = array_diff($enKeys, $myKeys);
        $extraInMy = array_diff($myKeys, $enKeys);

        $this->assertEmpty($missingInMy, 'Myanmar translation is missing keys: ' . implode(', ', $missingInMy));
        $this->assertEmpty($extraInMy, 'Myanmar translation has extra keys: ' . implode(', ', $extraInMy));
    }

    // ─── Locale Switching ──────────────────────────────────────

    public function test_accept_language_sets_locale(): void
    {
        $response = $this->withHeader('Accept-Language', 'my')
            ->getJson('/api/storefront/settings', ['X-Store' => 'nonexistent']);

        // The store is not found so it returns 404, but the locale
        // middleware should have set the locale before the route handler.
        $response->assertNotFound();

        $this->assertEquals('my', app()->getLocale());
    }

    public function test_default_locale_is_english(): void
    {
        $response = $this->getJson('/api/storefront/settings', ['X-Store' => 'nonexistent']);

        $response->assertNotFound();
        $this->assertEquals('en', app()->getLocale());
    }

    public function test_locale_middleware_returns_myanmar_translation(): void
    {
        app()->setLocale('my');

        $translated = __('messages.auth.failed');

        $this->assertStringContainsString('မကိုက်ညီ', $translated);
    }

    public function test_locale_middleware_returns_english_translation(): void
    {
        app()->setLocale('en');

        $translated = __('messages.auth.failed');

        $this->assertStringContainsString('do not match', $translated);
    }

    // ─── CurrencyFormatter ─────────────────────────────────────

    public function test_currency_formatter_formats_mmk(): void
    {
        $formatted = CurrencyFormatter::format(12345);

        $this->assertStringContainsString('12,345', $formatted);
        $this->assertStringContainsString('Ks', $formatted);
    }

    public function test_currency_formatter_handles_zero(): void
    {
        $formatted = CurrencyFormatter::format(0);

        $this->assertStringContainsString('0', $formatted);
    }

    public function test_currency_formatter_handles_large_numbers(): void
    {
        $formatted = CurrencyFormatter::format(1000000);

        $this->assertStringContainsString('1,000,000', $formatted);
    }

    // ─── Helpers ───────────────────────────────────────────────

    private function flattenKeys(array $array, string $prefix = ''): array
    {
        $keys = [];
        foreach ($array as $key => $value) {
            $fullKey = $prefix ? $prefix . '.' . $key : $key;
            if (is_array($value)) {
                $keys = array_merge($keys, $this->flattenKeys($value, $fullKey));
            } else {
                $keys[] = $fullKey;
            }
        }
        return $keys;
    }
}
