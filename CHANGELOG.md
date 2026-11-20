# Changelog

All notable changes to this project are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and the project uses
[Semantic Versioning](https://semver.org/).

## [1.0.0] - 2026-11-20

### Added

- Flarum 2.0 extension package for Google News integration.
- Google News sitemap at `/news-sitemap.xml`, limited to 1,000 eligible entries
  published within the configured 1–48 hour window.
- General content sitemap at `/google-news-sitemap.xml` without replacing
  existing sitemap routes.
- RSS 2.0 feed at `/news-feed.xml` and Atom 1.0 feed at `/news-atom.xml`.
- Schema.org JSON-LD for `NewsArticle`, `BlogPosting`, `Article`,
  `Organization`, `WebSite` and `BreadcrumbList`.
- Server-rendered canonical URLs, meta descriptions, robots metadata, Open Graph
  and Twitter Card tags.
- Optional integration with FriendsOfFlarum Blog, including article
  detection, canonical blog URLs, summaries, featured images, categories,
  pending-review exclusion and automatic schema type selection.
- Standalone news publishing controls for forums without FoF Blog.
- Per-article Google News settings with authorization checks.
- Dedicated Google News administration page and system status panel.
- Publisher logo upload with MIME, file-size and image validation.
- Cache support with automatic invalidation.
- English and Turkish localization.
- PHPUnit unit and Flarum integration tests.
- MIT license and open-source project documentation.

[1.0.0]: https://github.com/cengizkemec/flarum-google-news/releases/tag/v1.0.0
