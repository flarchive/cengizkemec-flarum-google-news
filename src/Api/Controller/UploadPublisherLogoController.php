<?php

/*
 * This file is part of cengizkemec/flarum-google-news.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cengizkemec\GoogleNews\Api\Controller;

use Cengizkemec\GoogleNews\Services\CacheService;
use Flarum\Admin\LogoValidator;
use Flarum\Api\Controller\UploadImageController;
use Intervention\Image\Interfaces\EncodedImageInterface;
use Psr\Http\Message\UploadedFileInterface;

class UploadPublisherLogoController extends UploadImageController
{
    protected string $filePathSettingKey = 'cengizkemec-google-news.publisher_logo';
    protected string $filenamePrefix = 'google-news-logo';
    protected string $fileExtension = 'png';
    protected ?string $validator = LogoValidator::class;

    const MAX_WIDTH = 600;
    const MAX_HEIGHT = 600;

    protected function makeImage(UploadedFileInterface $file): EncodedImageInterface
    {
        return $this->imageManager
            ->read($file->getStream()->getMetadata('uri'))
            ->scaleDown(self::MAX_WIDTH, self::MAX_HEIGHT)
            ->toPng();
    }

    protected function afterStore(\Psr\Http\Message\ServerRequestInterface $request, UploadedFileInterface $file): void
    {
        resolve(CacheService::class)->flush();
    }
}
