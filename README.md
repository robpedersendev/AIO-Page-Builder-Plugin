# Private Plugin Base

A private-distribution WordPress plugin base built to WordPress-style engineering standards.

## Requirements

- WordPress 6.6 or later
- PHP 8.1 or later

## Installation

1. Upload the plugin directory to `wp-content/plugins/`, or clone into that path.
2. Activate the plugin via **Plugins** in the WordPress admin.

## Development

- **Start local environment:** `npm run wp-env:start`
- **Stop environment:** `npm run wp-env:stop`
- **Lint PHP:** `npm run lint:php`
- **Auto-fix PHP:** `npm run fix:php`
- **Run tests:** `npm run test:php` or `composer run phpunit`
- **Static analysis:** `npm run analyse:php` or `composer run phpstan`

## Documentation

- [Engineering Standard](docs/standards/ENGINEERING_STANDARD.md)
- [Security Standard](docs/standards/SECURITY_STANDARD.md)
- [Reporting Exception](docs/standards/REPORTING_EXCEPTION.md)
- [Portability and Uninstall](docs/standards/PORTABILITY_AND_UNINSTALL.md)
- [Decision Log](docs/decisions/DECISION_LOG.md)
- [Definition of Done](docs/qa/DEFINITION_OF_DONE.md)
- [Release Checklist](docs/qa/RELEASE_CHECKLIST.md)

## License

GPLv2 or later. See [LICENSE](LICENSE) if present.
