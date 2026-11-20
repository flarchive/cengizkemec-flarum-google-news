<?php

namespace Cengizkemec\GoogleNews\Tests\unit;

use Cengizkemec\GoogleNews\Services\Settings;
use Flarum\Settings\SettingsRepositoryInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class SettingsTest extends TestCase
{
    #[Test]
    public function an_empty_checkbox_value_is_false_not_the_default(): void
    {
        // Flarum persists an unchecked admin checkbox as an empty string.
        $settings = new Settings($this->repository([
            'cengizkemec-google-news.enabled' => '',
            'cengizkemec-google-news.feeds.atom_enabled' => '',
            'cengizkemec-google-news.schema.compatibility_mode' => '',
        ]));

        $this->assertFalse($settings->isEnabled());
        $this->assertFalse($settings->bool('feeds.atom_enabled', true));
        $this->assertFalse($settings->bool('schema.compatibility_mode', true));
    }

    #[Test]
    public function a_missing_setting_uses_the_default(): void
    {
        $settings = new Settings($this->repository([]));

        $this->assertTrue($settings->isEnabled());
        $this->assertTrue($settings->bool('feeds.atom_enabled', true));
        $this->assertFalse($settings->bool('defaults.is_news', false));
    }

    #[Test]
    public function truthy_checkbox_values_are_true(): void
    {
        $settings = new Settings($this->repository([
            'cengizkemec-google-news.enabled' => '1',
            'cengizkemec-google-news.feeds.atom_enabled' => '1',
        ]));

        $this->assertTrue($settings->isEnabled());
        $this->assertTrue($settings->bool('feeds.atom_enabled', false));
    }

    private function repository(array $values): SettingsRepositoryInterface
    {
        return new class($values) implements SettingsRepositoryInterface {
            public function __construct(private array $values)
            {
            }

            public function all(): array
            {
                return $this->values;
            }

            public function get(string $key, mixed $default = null): mixed
            {
                return array_key_exists($key, $this->values) ? $this->values[$key] : $default;
            }

            public function set(string $key, mixed $value): void
            {
                $this->values[$key] = $value;
            }

            public function delete(string $keyLike): void
            {
                $this->values = [];
            }
        };
    }
}
