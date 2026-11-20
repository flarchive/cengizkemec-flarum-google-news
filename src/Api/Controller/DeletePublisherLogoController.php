<?php

/*
 * This file is part of cengizkemec/flarum-google-news.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cengizkemec\GoogleNews\Api\Controller;

use Cengizkemec\GoogleNews\Services\CacheService;
use Flarum\Api\Controller\AbstractDeleteController;
use Flarum\Http\RequestUtil;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;
use Illuminate\Contracts\Filesystem\Filesystem;
use Psr\Http\Message\ServerRequestInterface;

class DeletePublisherLogoController extends AbstractDeleteController
{
    protected Filesystem $assets;

    public function __construct(
        protected SettingsRepositoryInterface $settings,
        FilesystemFactory $filesystems
    ) {
        $this->assets = $filesystems->disk('flarum-assets');
    }

    protected function delete(ServerRequestInterface $request): void
    {
        RequestUtil::getActor($request)->assertAdmin();

        $path = $this->settings->get('cengizkemec-google-news.publisher_logo');

        $this->settings->set('cengizkemec-google-news.publisher_logo', null);

        if ($path && $this->assets->exists($path)) {
            $this->assets->delete($path);
        }

        resolve(CacheService::class)->flush();
    }
}
