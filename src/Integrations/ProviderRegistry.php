<?php

/*
 * This file is part of cengizkemec/flarum-google-news.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cengizkemec\GoogleNews\Integrations;

use Flarum\Discussion\Discussion;

/**
 * Selects the first available provider that supports a discussion.
 */
class ProviderRegistry
{
    /** @var ContentProviderInterface[] */
    protected array $providers;

    public function __construct(ContentProviderInterface ...$providers)
    {
        $this->providers = $providers;
    }

    public function for(Discussion $discussion): ?ContentProviderInterface
    {
        foreach ($this->providers as $provider) {
            if ($provider->isAvailable() && $provider->supports($discussion)) {
                return $provider;
            }
        }

        return null;
    }

    /**
     * @return ContentProviderInterface[]
     */
    public function all(): array
    {
        return $this->providers;
    }

    public function add(ContentProviderInterface $provider): void
    {
        $this->providers[] = $provider;
    }
}
