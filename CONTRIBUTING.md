# Contributing

Thank you for considering a contribution to Flarum Google News.

## Development setup

1. Clone the repository.
2. Install PHP dependencies:

   ```bash
   composer install
   ```

3. Install and build the JavaScript assets:

   ```bash
   cd js
   npm install
   npm run build
   ```

4. Run the test suites:

   ```bash
   composer test:unit
   composer test:setup
   composer test:integration
   ```

The integration suite uses `flarum/testing` with an isolated SQLite database by
default. Configure `DB_DRIVER`, `DB_HOST`, `DB_DATABASE`, `DB_USERNAME` and
`DB_PASSWORD` to use another database. Never point tests at production data.

## Coding standards

- PHP 8.3+, PSR-4 autoloading and PSR-12 style.
- Keep controllers thin and put reuseable logic in services.
- Do not hardcode publisher names, domains, locales, logos or community-specific
  configuration.
- Escape every XML, HTML and JSON-LD value.
- Keep public endpoints limited to public content.
- Add or update tests for behavior changes.
- Keep the extension operational when `fof/blog` is not installed.

## Pull requests

- Describe the problem and the chosen solution.
- Include tests where practical.
- Update `README.md`, `CHANGELOG.md` and localization files when behavior or
  settings change.
- Do not include credentials, publisher-specific data or generated local files.

## Reporting bugs

Include:

- Flarum version
- PHP version
- Extension version
- Installed FoF Blog version, if any
- Steps to reproduce
- Expected and actual behavior
- Relevant logs with secrets removed

## Security

Please do not open a public issue for a security vulnerability. Contact the
maintainer privately at mail@cengizkemec.com.tr and include enough detail to
reproduce the issue.

## License

By contributing, you agree that your contribution is licensed under the MIT
License.
