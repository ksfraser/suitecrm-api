# SuiteCRM API - Refactored

A modern, SOLID-compliant SuiteCRM REST API client written in PHP. This refactored version follows best practices from the LLM.md development principles.

## Features

- **SOLID Principles**: Single Responsibility, Open/Closed, Liskov Substitution, Interface Segregation, Dependency Inversion
- **Dependency Injection**: Clean architecture with proper dependency management
- **Comprehensive Error Handling**: Specific exception types for different error conditions
- **Service Layer**: Business logic separated from data access
- **Framework Agnostic**: Can be used with any PHP framework or standalone
- **Unit Tested**: Comprehensive test coverage with PHPUnit
- **PSR Compliant**: Follows PHP-FIG standards for autoloading and coding style
- **Immutable Configuration**: Thread-safe configuration objects

## Installation

### Composer (Recommended)

```bash
composer require ksfii/suitecrm-api
```

### Manual Installation

1. Clone or download the repository
2. Include the autoloader: `require_once 'path/to/suiteAPI/src/autoload.php';`

## Quick Start

### Basic Usage

```php
<?php

use SuiteAPI\Core\SuiteCrmApiFactory;

// Create API instance from environment variables
$setup = SuiteCrmApiFactory::createFullSetup();

$api = $setup['api'];
$contactService = $setup['contactService'];

// Login
$api->login('your-username', 'your-password');

// Use the contact service
$contactId = $contactService->createContact([
    'first_name' => 'John',
    'last_name' => 'Doe',
    'email' => 'john.doe@example.com',
    'phone_work' => '(555) 123-4567'
]);

// Logout
$api->logout();
```

### Manual Configuration

```php
<?php

use SuiteAPI\Core\SuiteCrmConfig;
use SuiteAPI\Core\SuiteCrmApiFactory;

// Create configuration
$config = new SuiteCrmConfig(
    'https://your-suitecrm.com/service/v4_1/rest.php',
    'username',
    'password',
    60, // timeout
    false, // debug
    true // SSL verify
);

// Create API instance
$api = SuiteCrmApiFactory::createApi($config);
$contactService = SuiteCrmApiFactory::createContactService($api);
```

### Environment Variables

Set these environment variables for automatic configuration:

```bash
export SUITE_CRM_URL="https://your-suitecrm.com/service/v4_1/rest.php"
export SUITE_CRM_USERNAME="your-username"
export SUITE_CRM_PASSWORD="your-password"
export SUITE_CRM_TIMEOUT="30"
export SUITE_CRM_DEBUG="false"
export SUITE_CRM_SSL_VERIFY="true"
```

## Architecture

### Core Components

- **`SuiteCrmApiInterface`**: Contract for API implementations
- **`SuiteCrmRestApi`**: REST API implementation
- **`SuiteCrmConfig`**: Immutable configuration
- **`HttpClientInterface`**: HTTP client abstraction
- **`CurlHttpClient`**: cURL implementation

### Service Layer

- **`ContactService`**: Business logic for contact operations
- **Additional services**: Extend for other modules (Accounts, Opportunities, etc.)

### Exception Hierarchy

```
SuiteApiException
├── AuthenticationException
├── ConnectionException
├── ValidationException
└── RecordNotFoundException
```

## API Reference

### SuiteCrmApiInterface

```php
interface SuiteCrmApiInterface {
    public function login(string $username, string $password): bool;
    public function logout(): bool;
    public function createRecord(string $module, array $data): ?string;
    public function updateRecord(string $module, string $id, array $data): bool;
    public function getRecord(string $module, string $id, array $fields = []): ?array;
    public function searchRecords(string $module, array $query = [], array $fields = [], int $limit = 20, int $offset = 0): array;
    public function deleteRecord(string $module, string $id): bool;
    public function isAuthenticated(): bool;
    public function getLastResponse(): ?array;
}
```

### ContactService

```php
class ContactService {
    public function createContact(array $contactData): ?string;
    public function updateContact(string $contactId, array $contactData): bool;
    public function findContactByEmail(string $email): ?array;
    public function getContactDetails(string $contactId): ?array;
    public function searchContacts(string $searchTerm, int $limit = 20): array;
}
```

## Examples

See `examples.php` for comprehensive usage examples including:

- Basic setup and authentication
- Manual configuration
- Contact management (CRUD operations)
- Error handling patterns
- Low-level API operations

Run examples:
```bash
php examples.php
```

## Testing

### Running Tests

```bash
# Install development dependencies
composer install --dev

# Run all tests
composer test

# Run specific test
./vendor/bin/phpunit tests/Unit/SuiteCrmConfigTest.php
```

### Code Quality

```bash
# Static analysis
composer analyze

# Code style checking
composer lint

# Fix code style issues
composer lint-fix
```

## Documentation

Generate API documentation:

```bash
composer docs
```

Documentation will be generated in the `docs/api/` directory.

## Development Principles

This codebase follows the principles outlined in `LLM.md`:

- **SOLID Principles**: Each class has a single responsibility
- **Dependency Injection**: Dependencies are injected, not hardcoded
- **Interface Segregation**: Specific interfaces for different concerns
- **Error Handling**: Specific exception types for different errors
- **Unit Testing**: All code is covered by unit tests
- **Documentation**: Comprehensive phpDocumentor blocks
- **PSR Compliance**: Follows PHP-FIG standards

## Contributing

1. Follow PSR-12 coding standards
2. Write unit tests for new code
3. Update documentation
4. Follow the established architectural patterns

## License

MIT License - see LICENSE file for details.

## Requirements

- PHP 7.4 or higher
- cURL extension
- JSON extension
- Composer (for dependency management)

## Changelog

### 1.0.0
- Initial release with refactored SuiteCRM API
- SOLID principles implementation
- Comprehensive test coverage
- Service layer architecture
- Dependency injection container