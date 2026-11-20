# Flarum Google News

A professional, publisher-independent Google News integration for [Flarum](https://flarum.org) 2.0+.

Flarum Google News helps public Flarum content become easier for Google to crawl and understand. It adds a Google News sitemap, structured data, RSS/Atom feeds, canonical URLs and server-rendered SEO metadata. It integrates optionally with [FriendsOfFlarum Blog](https://github.com/FriendsOfFlarum/blog) and continues to work without it.

> This extension provides technical compatibility only. It does not guarantee Google News inclusion, indexing, ranking or Discover visibility. Publisher registration and editorial decisions remain with Google.

## Features

- **Google News sitemap** at `/news-sitemap.xml`
  - Last configured publishing window (48 hours by default)
  - Maximum 1,000 entries, as required by Google
  - Publication name, language, title, URL and publication date
  - Strict XML escaping and visibility checks
- **General content sitemap** at `/google-news-sitemap.xml`
  - Does not replace an existing `/sitemap.xml`
  - Includes public blog posts and published news articles
  - Canonical URLs and last modification dates
- **RSS 2.0 and Atom 1.0 feeds**
  - `/news-feed.xml`
  - `/news-atom.xml`
  - Title, URL, description, author, category, cover image, GUID, language and modification date
- **Schema.org JSON-LD**
  - `NewsArticle`, `BlogPosting`, `Article`
  - `Organization`, `WebSite`, `BreadcrumbList`
  - ISO 8601 dates, absolute URLs, publisher logo support
  - Safe JSON encoding that cannot break out of the script tag
- **SEO metadata**
  - Canonical URLs
  - Meta description and robots metadata
  - Open Graph and Twitter Cards
  - Article publication/modification time, author and section
  - Compatibility mode for existing SEO extensions
- **Optional FoF Blog integration**
  - Detects FoF Blog automatically
  - Uses blog summaries, featured images, categories and `/blog/{slug}` URLs
  - Excludes pending-review articles
  - Regular blog posts use `BlogPosting`; explicitly marked news uses `NewsArticle`
  - General forum discussions are never automatically classified as news
- **Per-article publishing controls**
  - News flag
  - Structured data flag
  - News sitemap and feed inclusion
  - Custom headline, description, category, language, cover image and author
- **Complete admin panel**
  - Dedicated Google News page
  - Publisher, sitemap, feed, structured data and SEO settings
  - System status, cache diagnostics, recent errors and Google resource links
- **English and Turkish localization**
- **Caching and automatic invalidation**
  - Cached sitemaps and feeds
  - Invalidation on discussion, post, blog metadata, news metadata and settings changes
  - No mandatory cron jobs
- **Security**
  - Administrator-only configuration
  - Flarum authorization policies for per-article settings
  - CSRF protection through Flarum's middleware
  - Validation for URLs, languages and file uploads
  - XML and JSON-LD injection prevention

## Requirements

- PHP 8.3 or newer
- Flarum 2.0 or newer
- `ext-json` and `ext-libxml`
- Optional: `fof/blog` 2.0 (beta or stable when available)
- Optional: `flarum/tags` (installed by FoF Blog)
- Optional: `fof/seo` (compatibility mode detects its output)

## Installation

```bash
composer require cengizkemec/flarum-google-news
```

Then enable **Google News** in the Flarum administration panel.

If you install from a Git checkout, build the frontend assets once:

```bash
cd js
npm install
npm run build
```

## Configuration

Open **Administration → Extensions → Google News**. The page is organized into:

### General settings

- Enable or disable the extension
- Enable or disable FoF Blog integration
- Default content language
- Publisher name, website, country and logo
- Default author

All publisher values are configurable. No publisher name, domain, logo or locale is hardcoded. Empty values fall back to the forum title, forum description, forum URL and forum locale where appropriate.

### Sitemap settings

- Google News sitemap
- General content sitemap
- Cache duration
- Publishing window (1–48 hours; capped at Google's 48-hour limit)
- Maximum news entries (up to 1,000)
- Maximum content sitemap URLs

### Feed settings

- RSS feed
- Atom feed
- Feed item limit
- Feed description
- Include regular blog posts in feeds

### Structured data settings

- `NewsArticle`
- `BlogPosting`
- `Article`
- `Organization`
- `WebSite`
- `BreadcrumbList`
- Compatibility mode for other SEO extensions (tested with `fof/seo` 4.x)

### SEO settings

- Open Graph
- Open Graph type (`article` or `website`)
- Twitter Cards
- Canonical metadata
- Meta descriptions
- Robots metadata

### Default publishing behavior

These defaults apply to newly created news metadata:

- Mark as news
- Enable structured data
- Include in the news sitemap
- Include in feeds

Administrators and users with the `googleNews.manage` permission can override them per article.

## Public endpoints

| Type | URL |
| --- | --- |
| Google News sitemap | `/news-sitemap.xml` |
| General content sitemap | `/google-news-sitemap.xml` |
| RSS 2.0 | `/news-feed.xml` |
| Atom 1.0 | `/news-atom.xml` |

The general content sitemap is deliberately separate from `/sitemap.xml` so it does not conflict with existing sitemap extensions. Add it to your sitemap index if you want it crawled.

## FoF Blog integration

When FoF Blog is installed and enabled:

- Articles are detected through the public `Discussion::blogMeta` relationship.
- Canonical URLs use `/blog/{slug}`.
- Blog summaries become descriptions when no custom news description is set.
- Featured images become cover images when no custom cover image is set.
- Blog categories become the default news section.
- Articles pending review are excluded from all public outputs.
- Regular blog posts use `BlogPosting` structured data.
- Articles explicitly marked as news use `NewsArticle`.

If FoF Blog is not installed, disabled, or integration is turned off:

- The extension continues to work.
- Discussions explicitly marked as news can still be published through the Google News sitemap, feeds and structured data.
- Canonical URLs use Flarum's `/d/{slug}` route.
- No FoF Blog classes are required at runtime for the extension to boot.

## Per-article settings

Administrators and moderators with the `googleNews.manage` permission can open **Google News settings** from:

- The FoF Blog article controls menu
- The regular discussion controls menu, for standalone forums

Available settings:

- Mark as a news article
- Enable structured data
- Include in the Google News sitemap
- Include in RSS/Atom feeds
- Custom headline
- Custom description
- News category
- Content language
- Custom cover image
- Custom author name and URL

Regular forum discussions are not automatically treated as news. They require an explicit news flag.

## Caching

Generated documents are cached using Flarum's cache store. The cache is invalidated when:

- A discussion is created, updated, hidden, restored, renamed or deleted
- A post is created, edited, hidden, restored or deleted
- FoF Blog metadata changes
- News metadata changes
- Relevant settings change

The status page shows the cache store, cached documents, entry counts, last successful refresh, staleness and recent errors.

## Google Search Central resources

The administration page links to:

- [Google Search Console](https://search.google.com/search-console)
- [Google Publisher Center](https://publishercenter.google.com/)
- [Google News sitemap documentation](https://developers.google.com/search/docs/crawling-indexing/sitemaps/news-sitemap)

## Troubleshooting

### The sitemap or feed returns 404

Check that:

- The extension is enabled.
- The specific endpoint is enabled in the ACP.
- Flarum's URL rewriting is configured correctly.
- Your web server does not have a physical file or rule intercepting the path.

### FoF Blog is not detected

- Confirm `fof/blog` is installed and enabled.
- Confirm **Enable FoF Blog integration** is on.
- Clear Flarum's cache after enabling or updating extensions.

### Articles do not appear in the news sitemap

Check the following:

- The article is public and not hidden or private.
- The article is marked as news.
- **Include in Google News sitemap** is enabled.
- The original publication date is within the configured window (maximum 48 hours).
- The first post is visible.
- FoF Blog pending review is not enabled for the article.

### Structured data is missing

- Confirm the relevant structured data type is enabled.
- If another SEO extension is enabled, compatibility mode may intentionally skip duplicate output. Disable compatibility mode only after verifying there is no conflict.
- Confirm the per-article structured data toggle is enabled.

### Changes do not appear immediately

Use **Clear generated caches** on the Google News status page, or wait for the configured cache duration. Content events normally invalidate the cache automatically.

### Google still does not include the site in Google News

Technical compatibility is necessary but not sufficient. Google decides independently whether to include a publisher or article. Use Google Publisher Center and Search Console, and follow Google's editorial and technical guidelines.

## Known limitations

- Google News inclusion, indexing, ranking and Discover visibility are not guaranteed.
- The extension does not register publishers with Google automatically.
- Google Publisher Center integration is a documentation link, not an API integration.
- One publisher identity is configured per forum.
- Flarum stores a single article author; custom author fields override that value but do not create multi-author databases.
- The news sitemap intentionally follows Google's 48-hour window. Older articles remain available through the general content sitemap and feeds.
- Existing sitemap extensions are not replaced. The general content sitemap uses a separate URL.
- Images must be publicly accessible and served with valid image content types.
- The extension does not modify `robots.txt`.
- Google's documentation and eligibility rules can change. Review the linked Search Central documentation periodically.

## Development

```bash
composer install
cd js && npm install && npm run build
```

Run syntax checks and tests:

```bash
# PHP syntax
find src migrations -name '*.php' -exec php -l {} \;

# Unit tests
composer test:unit

# Integration tests (SQLite by default)
composer test:setup
composer test:integration
```

The integration suite uses `flarum/testing` and an isolated SQLite database. It is not run against production data.

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md). Bug reports and pull requests are welcome.

Security reports: see [SECURITY.md](SECURITY.md) and contact **mail@cengizkemec.com.tr** privately.

Project homepage: [cengizkemec.com.tr](https://cengizkemec.com.tr/)  
Source code: [github.com/cengizkemec/flarum-google-news](https://github.com/cengizkemec/flarum-google-news)

## License

MIT. See [LICENSE](LICENSE).
