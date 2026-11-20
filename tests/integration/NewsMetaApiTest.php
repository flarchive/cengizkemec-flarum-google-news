<?php

namespace Cengizkemec\GoogleNews\Tests\integration;

use PHPUnit\Framework\Attributes\Test;

class NewsMetaApiTest extends GoogleNewsTestCase
{
    #[Test]
    public function administrators_can_update_news_settings(): void
    {
        $this->seedArticle();

        $request = $this->requestWithJsonBody(
            $this->requestAsUser($this->request('PATCH', '/api/googleNewsMeta/1'), 1),
            [
                'data' => [
                    'type' => 'googleNewsMeta',
                    'id' => '1',
                    'attributes' => [
                        'headline' => 'Admin headline',
                        'isNews' => true,
                    ],
                ],
            ]
        );

        $response = $this->send($request);

        $this->assertEquals(200, $response->getStatusCode());

        $body = json_decode((string) $response->getBody(), true);

        $this->assertEquals('Admin headline', $body['data']['attributes']['headline']);
    }

    #[Test]
    public function regular_users_cannot_update_news_settings(): void
    {
        $this->seedArticle();

        $request = $this->requestWithJsonBody(
            $this->requestAsUser($this->request('PATCH', '/api/googleNewsMeta/1'), 2),
            [
                'data' => [
                    'type' => 'googleNewsMeta',
                    'id' => '1',
                    'attributes' => [
                        'headline' => 'Not allowed',
                    ],
                ],
            ]
        );

        $response = $this->send($request);

        $this->assertEquals(403, $response->getStatusCode());
    }

    #[Test]
    public function administrators_can_create_news_settings(): void
    {
        $this->seedArticle(['meta' => false]);

        $request = $this->requestWithJsonBody(
            $this->requestAsUser($this->request('POST', '/api/googleNewsMeta'), 1),
            [
                'data' => [
                    'type' => 'googleNewsMeta',
                    'attributes' => [
                        'isNews' => true,
                        'includeInSitemap' => true,
                        'headline' => 'Created headline',
                    ],
                    'relationships' => [
                        'discussion' => [
                            'data' => ['type' => 'discussions', 'id' => '1'],
                        ],
                    ],
                ],
            ]
        );

        $response = $this->send($request);

        $this->assertEquals(201, $response->getStatusCode());

        $row = $this->database()->table('google_news_meta')->where('discussion_id', 1)->first();

        $this->assertNotNull($row);
        $this->assertSame('Created headline', $row->headline);
        $this->assertEquals(1, $row->is_news);
    }

    #[Test]
    public function invalid_cover_image_urls_are_rejected(): void
    {
        $this->seedArticle();

        $request = $this->requestWithJsonBody(
            $this->requestAsUser($this->request('PATCH', '/api/googleNewsMeta/1'), 1),
            [
                'data' => [
                    'type' => 'googleNewsMeta',
                    'id' => '1',
                    'attributes' => [
                        'coverImage' => 'javascript:alert(1)',
                    ],
                ],
            ]
        );

        $response = $this->send($request);

        $this->assertEquals(422, $response->getStatusCode());
    }
}
