# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Commands

- Build: `composer run build`
- Format/Lint: `composer run format` (uses Laravel Pint)
- Test all: `composer run test` (uses Pest PHP)
- Test single: `vendor/bin/pest tests/TestName.php`
- Test coverage: `composer run test-coverage`

## Code Style Guidelines

- PHP 8.2+ required
- Follow PSR-4 autoloading standards
- Use Laravel Pint for code formatting
- Use strict typing when possible
- Use camelCase for methods and variables
- Use PascalCase for classes
- Always use proper docblocks and typehints
- Properly handle exceptions with try/catch blocks
- Use dependency injection over static methods
- Follow Laravel coding standards for consistency
- Tests should use Pest PHP's expectation syntax
