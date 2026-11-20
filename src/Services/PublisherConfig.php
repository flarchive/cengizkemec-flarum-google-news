<?php

/*
 * This file is part of cengizkemec/flarum-google-news.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cengizkemec\GoogleNews\Services;

use Cengizkemec\GoogleNews\Support\Url;
use Flarum\Http\UrlGenerator;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Filesystem\Cloud;
use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;

/**
 * Resolves publisher information from the ACP, falling back to forum values.
 *
 * No publisher name, domain, logo or locale is ever hardcoded.
 */
class PublisherConfig
{
    protected string $baseUrl;

    /** @var Cloud */
    protected $assets;

    public function __construct(
        protected Settings $settings,
        protected SettingsRepositoryInterface $repository,
        protected UrlGenerator $url,
        FilesystemFactory $filesystem
    ) {
        $this->baseUrl = rtrim($url->to('forum')->base(), '/');
        $this->assets = $filesystem->disk('flarum-assets');
    }

    public function baseUrl(): string
    {
        return $this->baseUrl;
    }

    public function name(): string
    {
        $name = $this->settings->string('publisher_name');

        if ($name === '') {
            $name = trim((string) $this->repository->get('forum_title'));
        }

        return $name !== '' ? $name : 'Forum';
    }

    public function website(): string
    {
        $website = $this->settings->string('publisher_website');

        return Url::absolute($website, $this->baseUrl) ?? $this->baseUrl;
    }

    public function logoUrl(): ?string
    {
        $external = $this->settings->string('publisher_logo_url');

        if ($external !== '') {
            return Url::absolute($external, $this->baseUrl);
        }

        $path = $this->settings->string('publisher_logo');

        if ($path === '') {
            return null;
        }

        if (preg_match('#^https?://#i', $path)) {
            return Url::absolute($path, $this->baseUrl);
        }

        return $this->assets->url($path);
    }

    public function country(): ?string
    {
        $country = strtoupper($this->settings->string('publisher_country'));

        return preg_match('/^[A-Z]{2}$/', $country) ? $country : null;
    }

    public function defaultLanguage(): string
    {
        $configured = $this->settings->defaultLanguage();

        if ($configured !== null) {
            return Url::language($configured);
        }

        $forumLocale = (string) $this->repository->get('default_locale', 'en');

        return Url::language($forumLocale);
    }

    public function defaultAuthor(): string
    {
        $author = $this->settings->string('default_author');

        return $author !== '' ? $author : $this->name();
    }

    public function feedDescription(): string
    {
        $description = $this->settings->string('feeds.description');

        if ($description !== '') {
            return $description;
        }

        $forumDescription = trim((string) $this->repository->get('forum_description'));

        return $forumDescription !== ''
            ? $forumDescription
            : 'Latest news and articles from '.$this->name();
    }

    public function logoMimeType(): ?string
    {
        $url = $this->logoUrl();

        if ($url === null) {
            return null;
        }

        $path = parse_url($url, PHP_URL_PATH) ?: '';
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return match ($extension) {
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'webp' => 'image/webp',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml',
            default => null,
        };
    }
}
