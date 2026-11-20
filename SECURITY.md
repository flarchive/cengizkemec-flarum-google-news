# Security Policy

## Supported versions

| Version | Supported |
| ------- | --------- |
| 1.x     | Yes       |

## Reporting a vulnerability

Please do not open a public GitHub issue for a security vulnerability.

Send a private report to **mail@cengizkemec.com.tr** with:

- A description of the issue
- Steps to reproduce it
- The affected version and Flarum/PHP versions
- Any proof-of-concept or logs, with secrets removed

You can expect an acknowledgement within a reasonable time. Confirmed issues
will be fixed in a patch release and credited unless you request otherwise.

## Security model

Flarum Google News:

- Restricts configuration and system status to Flarum administrators.
- Requires the `googleNews.manage` permission for per-article publishing
  settings.
- Uses Flarum's authorization, CSRF and session middleware.
- Escapes all XML, HTML and JSON-LD output.
- Validates image uploads with Flarum's image validator and stores them on the
  configured Flarum assets disk.
- Exposes only content visible to a guest user through public sitemap and feed
  endpoints.
- Does not make outbound HTTP requests or require Google API credentials.
