# Contributing to Runtime Insight

Thank you for considering contributing to Runtime Insight! This document provides guidelines and instructions for contributing.

## Code of Conduct

By participating in this project, you agree to maintain a respectful and inclusive environment for everyone.

## How Can I Contribute?

### Reporting Bugs

Before creating bug reports, please check existing issues to avoid duplicates.

**When creating a bug report, include:**

- Clear, descriptive title
- Steps to reproduce the issue
- Expected vs actual behavior
- PHP version and framework version
- Relevant configuration
- Stack trace if applicable

### Suggesting Features

Feature suggestions are welcome! Please:

- Check if the feature has already been suggested
- Provide a clear use case
- Explain how it fits the project's philosophy
- Consider implementation complexity

### Pull Requests

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Make your changes
4. Run tests and static analysis
5. Commit with clear messages
6. Push to your fork
7. Open a Pull Request

## Development Setup

### Prerequisites

- PHP 8.2+
- Composer 2.x
- Git

### Installation

```bash
# Clone your fork
git clone https://github.com/YOUR_USERNAME/runtime-insight.git
cd runtime-insight

# Install dependencies
composer install
```

### Running Tests

```bash
# Run all tests
composer test

# Run with coverage
composer test:coverage

# Run specific test file
./vendor/bin/phpunit tests/Unit/ConfigTest.php
```

### Static Analysis

```bash
# Run PHPStan
composer analyse
```

### Code Style

We use PHP-CS-Fixer with PER-CS2.0 standard.

```bash
# Check code style
composer cs-check

# Fix code style
composer cs-fix
```

## Coding Standards

### PHP Standards

- Follow PER-CS2.0 coding standard
- Use strict types in all files
- Type all parameters and return values
- Use readonly properties where appropriate
- Prefer composition over inheritance

### File Structure

```php
<?php

declare(strict_types=1);

namespace ClarityPHP\RuntimeInsight;

use ExternalDependency;
use function array_map;
use const PHP_EOL;

final class MyClass
{
    // Properties first
    private readonly string $property;
    
    // Constructor
    public function __construct(string $property)
    {
        $this->property = $property;
    }
    
    // Public methods
    public function doSomething(): void
    {
        //
    }
    
    // Protected methods
    
    // Private methods
}
```

### Documentation

- Add PHPDoc blocks for public methods
- Document complex logic with inline comments
- Update README.md and USAGE.md for user-facing changes

### Testing

- Write tests for all new features
- Maintain or improve code coverage
- Use descriptive test method names
- Follow Arrange-Act-Assert pattern

```php
public function test_it_explains_null_pointer_exception(): void
{
    // Arrange
    $exception = new \TypeError('Call to member function on null');
    
    // Act
    $explanation = $this->analyzer->analyze($exception);
    
    // Assert
    $this->assertStringContainsString('null', $explanation->getCause());
}
```

## Commit Messages

Follow conventional commits:

```
type(scope): description

[optional body]

[optional footer]
```

**Types:**
- `feat`: New feature
- `fix`: Bug fix
- `docs`: Documentation
- `style`: Code style (formatting, etc.)
- `refactor`: Code refactoring
- `test`: Tests
- `chore`: Maintenance

**Examples:**
```
feat(laravel): add artisan command for error explanation
fix(openai): handle rate limiting errors gracefully
docs(readme): add installation instructions
test(context): add unit tests for context builder
```

## Pull Request Process

1. **Update documentation** if needed
2. **Add tests** for new features
3. **Ensure CI passes** (tests, static analysis, code style)
4. **Request review** from maintainers
5. **Address feedback** promptly
6. **Squash commits** if requested

### PR Title Format

Use the same format as commit messages:

```
feat(laravel): add exception handler integration
```

## Release Process

Releases are managed by maintainers. Version numbers follow [Semantic Versioning](https://semver.org/):

- **MAJOR**: Breaking changes
- **MINOR**: New features (backward compatible)
- **PATCH**: Bug fixes (backward compatible)

## Getting Help

- Open an issue for questions
- Join our Discord community
- Check existing documentation

## Recognition

Contributors are recognized in:
- Release notes
- CONTRIBUTORS.md file
- GitHub insights

Thank you for contributing! 🎉

