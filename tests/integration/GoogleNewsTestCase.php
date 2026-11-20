<?php

namespace Cengizkemec\GoogleNews\Tests\integration;

use Carbon\Carbon;
use Flarum\Testing\integration\TestCase;

abstract class GoogleNewsTestCase extends TestCase
{
    protected int $nextDiscussionId = 1;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('cengizkemec-google-news');
    }

    /**
     * @return array{discussion_id: int, slug: string}
     */
    protected function seedArticle(array $options = []): array
    {
        $id = $options['id'] ?? $this->nextDiscussionId++;
        $slug = $options['slug'] ?? ($id.'-test-article');
        $createdAt = $options['created_at'] ?? Carbon::now()->subHours(1);
        $createdAt = $createdAt instanceof Carbon ? $createdAt : Carbon::parse($createdAt);
        $postId = 1000 + $id;

        $data = [
            'users' => [
                [
                    'id' => 2,
                    'username' => 'normal',
                    'email' => 'normal@machine.local',
                    'is_email_confirmed' => 1,
                    'password' => '$2y$10$LO59tiT7uggl6Oe23o/O6.utnF6ipngYjvMvaxo1TciKqBttDNKim',
                    'joined_at' => Carbon::now()->toDateTimeString(),
                    'preferences' => '{}',
                ],
                [
                    'id' => 3,
                    'username' => 'author',
                    'email' => 'author@machine.local',
                    'is_email_confirmed' => 1,
                    'password' => '$2y$10$LO59tiT7uggl6Oe23o/O6.utnF6ipngYjvMvaxo1TciKqBttDNKim',
                    'joined_at' => Carbon::now()->toDateTimeString(),
                    'preferences' => '{}',
                ],
            ],
            'discussions' => [
                [
                    'id' => $id,
                    'title' => $options['title'] ?? 'Test article '.$id,
                    'comment_count' => 1,
                    'participant_count' => 1,
                    'created_at' => $createdAt->toDateTimeString(),
                    'user_id' => 3,
                    'first_post_id' => $postId,
                    'last_posted_at' => $createdAt->toDateTimeString(),
                    'last_posted_user_id' => 3,
                    'last_post_id' => $postId,
                    'last_post_number' => 1,
                    'hidden_at' => ! empty($options['hidden']) ? Carbon::now()->toDateTimeString() : null,
                    'hidden_user_id' => ! empty($options['hidden']) ? 1 : null,
                    'slug' => trim(substr($slug, strlen((string) $id), 200), '-'),
                    'is_private' => ! empty($options['private']) ? 1 : 0,
                ],
            ],
            'posts' => [
                [
                    'id' => $postId,
                    'discussion_id' => $id,
                    'number' => 1,
                    'created_at' => $createdAt->toDateTimeString(),
                    'user_id' => 3,
                    'type' => 'comment',
                    'content' => $options['content'] ?? '<t><p>Test article body.</p></t>',
                    'edited_at' => $options['edited_at'] ?? null,
                    'edited_user_id' => null,
                    'hidden_at' => ! empty($options['post_hidden']) ? Carbon::now()->toDateTimeString() : null,
                    'hidden_user_id' => null,
                    'is_private' => ! empty($options['post_private']) ? 1 : 0,
                ],
            ],
        ];

        if (($options['meta'] ?? true) !== false) {
            $data['google_news_meta'] = [
                [
                    'discussion_id' => $id,
                    'is_news' => $options['is_news'] ?? true,
                    'enable_structured_data' => $options['enable_structured_data'] ?? true,
                    'include_in_sitemap' => $options['include_in_sitemap'] ?? true,
                    'include_in_feed' => $options['include_in_feed'] ?? true,
                    'headline' => $options['headline'] ?? null,
                    'description' => $options['description'] ?? 'A test news description.',
                    'category' => $options['category'] ?? 'News',
                    'language' => $options['language'] ?? 'en',
                    'cover_image' => $options['cover_image'] ?? null,
                    'author_name' => $options['author_name'] ?? null,
                    'author_url' => $options['author_url'] ?? null,
                    'created_at' => Carbon::now()->toDateTimeString(),
                    'updated_at' => Carbon::now()->toDateTimeString(),
                ],
            ];
        }

        $this->prepareDatabase($data);

        if (! empty($options['blog'])) {
            $this->prepareDatabase([
                'blog_meta' => [
                    [
                        'id' => $id,
                        'discussion_id' => $id,
                        'featured_image' => $options['cover_image'] ?? null,
                        'summary' => $options['summary'] ?? 'A blog summary.',
                        'is_featured' => 0,
                        'is_sized' => 0,
                        'is_pending_review' => ! empty($options['pending']) ? 1 : 0,
                    ],
                ],
            ]);
        }

        return ['discussion_id' => $id, 'slug' => $id.'-'.trim(substr($slug, strlen((string) $id), 200), '-')];
    }

    protected function withBlog(): void
    {
        $this->extension('fof-blog', 'flarum-tags', 'flarum-lock');
    }
}
