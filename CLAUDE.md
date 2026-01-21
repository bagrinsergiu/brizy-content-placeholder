# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Brizy Content Placeholders SDK is a PHP library for extracting and replacing template placeholders in content. It supports complex features like nested placeholders, looping constructs, custom attributes, and fallback values.

## Docker Setup

All development and testing must be run inside the Docker container. The image is configured with PHP 8.2 and Xdebug.

### Building the Docker Image
```bash
# Build the Docker image (from project root)
./bin/build-image
```

This creates an image tagged `brizy-content-placeholder` with the following setup:
- **Base**: PHP 8.2 CLI
- **Extensions**: Xdebug 3.3.0
- **Tools**: Composer, Git
- **Working directory**: `/opt/project`

### Running Commands in Docker

All commands run inside the container with the project directory mounted to `/opt/project`:

```bash
docker run -it --rm -v $(pwd):/opt/project brizy-content-placeholder COMMAND
```

### Common Development Commands

#### Running Tests
```bash
# Run all tests
docker run -it --rm -v $(pwd):/opt/project brizy-content-placeholder ./vendor/bin/phpunit

# Run tests in a specific class
docker run -it --rm -v $(pwd):/opt/project brizy-content-placeholder ./vendor/bin/phpunit tests/BrizyPlaceholders/ExtractorTest.php

# Run a specific test method
docker run -it --rm -v $(pwd):/opt/project brizy-content-placeholder ./vendor/bin/phpunit tests/BrizyPlaceholders/ExtractorTest.php --filter testMethodName

# Run with verbose output
docker run -it --rm -v $(pwd):/opt/project brizy-content-placeholder ./vendor/bin/phpunit -v

# Run with code coverage
docker run -it --rm -v $(pwd):/opt/project brizy-content-placeholder ./vendor/bin/phpunit --coverage-html coverage
```

#### Dependency Management
```bash
# Install dependencies
docker run -it --rm -v $(pwd):/opt/project brizy-content-placeholder composer install

# Update dependencies
docker run -it --rm -v $(pwd):/opt/project brizy-content-placeholder composer update

# Show installed packages
docker run -it --rm -v $(pwd):/opt/project brizy-content-placeholder composer show
```

#### Code Quality
```bash
# Check PHP syntax in a file
docker run -it --rm -v $(pwd):/opt/project brizy-content-placeholder php -l lib/SomeFile.php

# Run all tests with coverage report
docker run -it --rm -v $(pwd):/opt/project brizy-content-placeholder ./vendor/bin/phpunit --coverage-text
```

#### Development/Debugging
```bash
# Open an interactive shell in the container
docker run -it --rm -v $(pwd):/opt/project brizy-content-placeholder /bin/bash

# Run PHP directly
docker run -it --rm -v $(pwd):/opt/project brizy-content-placeholder php -v

# Run Composer commands
docker run -it --rm -v $(pwd):/opt/project brizy-content-placeholder composer require some/package
```

### Volumes

The key volume needed is:
- **Project root** → `/opt/project` (required)

The vendor directory is included in the image or installed on first run, so it doesn't need to be mounted separately.

### Xdebug Configuration

Xdebug is installed but disabled by default. To enable it:

1. Xdebug configuration is in `dev/conf.d/xdebug.ini`
2. Remote debugging is configured to connect to `host.docker.internal:9003`
3. Uncomment the enable line in the Dockerfile if needed for IDE debugging

## Architecture Overview

### Core Design Pattern: Registry + Extractor + Replacer

The library follows a three-stage pipeline:

1. **Registry**: Stores available `PlaceholderInterface` implementations. Placeholders register themselves and indicate which placeholder names they handle.

2. **Extractor**: Uses lexical analysis (via `phplrt/lexer`) to parse content and identify all placeholder tokens. Returns `ContentPlaceholder` objects containing:
   - Placeholder name
   - Full placeholder string with attributes
   - Parsed key-value attributes (supports arrays: `attr[key]="value"`)
   - Content between opening and closing tags (for loop placeholders)
   - Unique identifier (UID)

3. **Replacer**: Orchestrates the replacement process:
   - Extracts placeholders via `Extractor`
   - Calls `ContextInterface::afterExtract()` hook for custom processing
   - Executes `PlaceholderInterface::getValue()` for each placeholder sequentially
   - Applies fallback values via `PlaceholderInterface::shouldFallbackValue()` and `getFallbackValue()`
   - Replaces UIDs with final values in original content
   - Includes PSR-3 logging support for error handling

### Key Classes and Responsibilities

| Class | Purpose |
|-------|---------|
| `PlaceholderInterface` | Contract that all placeholders must implement |
| `AbstractPlaceholder` | Base implementation with default fallback handling and serialization support |
| `ContentPlaceholder` | Data model for extracted placeholder metadata (name, attributes, content, UID) |
| `Extractor` | Lexical analysis to extract placeholders from content |
| `Replacer` | Orchestrator that coordinates extraction and replacement with error handling |
| `Registry` | Manages available placeholder instances |
| `ContextInterface` | Allows custom context injection (database, request, etc.) |
| `EmptyContext` | Null object pattern implementation for cases without custom context |
| `PlaceholderDependency` | Data model for tracking placeholder data dependencies |

### Context and Hooks

`ContextInterface` provides an `afterExtract()` hook called after extraction but before replacement. This allows:
- Modification of extracted placeholder data
- Pre-loading related resources (e.g., database queries)
- Custom extraction validation

Example:
```php
class PageContext implements ContextInterface {
    public function afterExtract(&$contentPlaceholders, &$instancePlaceholders, $contentAfterExtractor) {
        // Pre-load related data, validate placeholders, etc.
    }
}
```

### Placeholder Syntax and Features

- **Basic**: `{{placeholder_name}}`
- **With attributes**: `{{placeholder_name attr="value"}}`
- **Array attributes**: `{{placeholder_name arr[key]="value"}}`
- **URL-encoded attributes**: Automatically decoded during extraction
- **With content (looping)**: `{{loop_name}}...{{end_loop_name}}`
- **Fallback values**: `{{placeholder_name _fallback="default"}}`

### Registry Pattern and Lazy Loading

The `Registry` class uses a factory pattern for placeholder management:
- Placeholders are registered via `registerPlaceholderName()` with a callable factory
- Instances are created lazily on first access via `getPlaceholderSupportingName()`
- Once created, placeholder instances are cached for reuse
- `getPlaceholders()` returns all registered placeholder instances

This approach allows for efficient placeholder management without requiring all instances to be created upfront, which is beneficial when dealing with many placeholder types.

## Testing

### Test Structure
- **Unit tests**: `tests/BrizyPlaceholders/` - Tests for core classes
- **Sample implementations**: `tests/Sample/` - Example placeholder implementations used for testing
- **Fixtures**: `tests/data/` - Real-world HTML test cases

### Testing Patterns
- **Data providers**: Parameterized test data for testing multiple scenarios
- **Mock placeholders**: `TestPlaceholder`, `LoopPlaceholder` for integration tests
- **Fixture-based tests**: Large HTML files for complex parsing scenarios

### Key Testing Scenarios
1. Basic extraction and replacement
2. Placeholders with single and multiple attributes
3. Nested and recursive placeholders
4. Loop placeholders with content iteration
5. Fallback value handling
6. Complex HTML with large content (up to 1.2 MB)
7. URL-encoded attribute values
8. Array-style attributes (`attr[key]="value"`)

### Writing New Tests
When adding tests, follow these patterns:
- Extend `PHPUnit\Framework\TestCase`
- Use data providers for parameterized tests: `@dataProvider methodNameProvider`
- For complex scenarios, add HTML fixture files to `tests/data/`
- Use `Extractor::extractIgnoringRegistry()` to test extraction without registry matching

## Important Technical Notes

### Placeholder Name Constraints
- Can contain letters, numbers, underscores, and hyphens
- Names are case-sensitive

### Attribute Parsing
- URL-encoded attribute values are automatically decoded (e.g., `%7B%7B` → `{{`)
- Array attributes use square bracket syntax: `attr[key]="value"` parses as nested arrays
- Quotes within attribute values must be escaped: `attr="value\"quoted"`

### Performance Considerations
- The `Extractor` sets PCRE backtrack limit to 9,000,000 to prevent regex failures on very large content
- Loop placeholders can significantly impact performance if iterating over large datasets
- Use the `Extractor::stripPlaceholders()` method to remove placeholders without replacement

### Recursion and Nesting
- The `Replacer` correctly handles recursive calls (e.g., in loop placeholders)
- Extracted placeholder UIDs prevent issues with nested replacements
- Loop placeholders can call `replacePlaceholders()` recursively on their content

### Fallback Handling
`AbstractPlaceholder` provides default fallback logic:
1. Calls `shouldFallbackValue($value, $context, $placeholder)`
2. If true and value is empty, returns `getFallbackValue()`
3. Custom implementations can override this in `getValue()`

## Current Development

**Active branch**: `v3.x`
**Main branch**: `master` (use for PRs)

**Recent work** focused on:
- Caching for placeholder instances via lazy registration
- Adding `getPlaceholders()` method to Registry
- Renaming placeholder registration methods for clarity
- Removing unused methods from PlaceholderInterface

## Dependencies

### Runtime
- **php**: >=7.1.0
- **phplrt/lexer**: ^2.3.6 - Lexical analysis for placeholder tokenization
- **phplrt/compiler**: ^2.3.6 - Pattern compilation
- **psr/log**: ^1 || ^2 || ^3 - PSR-3 logging interface (optional)

### Development
- **php**: >=8.0
- **phpunit/phpunit**: ^9 - Testing framework
- **phpspec/prophecy-phpunit**: ^2 - Mocking framework

## File Organization

```
lib/                          # Source code (PSR-4 namespace: BrizyPlaceholders\)
├── PlaceholderInterface.php   # Main placeholder contract
├── AbstractPlaceholder.php    # Base implementation
├── ContentPlaceholder.php     # Extracted placeholder data model
├── Extractor.php              # Lexical analysis and extraction
├── Replacer.php               # Replacement orchestrator with coroutines
├── Registry.php               # Placeholder management
├── ContextInterface.php       # Custom context contract
├── EmptyContext.php           # Null object context
└── PlaceholderDependency.php  # Dependency tracking model

tests/                        # Tests (PSR-4 namespace: BrizyPlaceholdersTests\)
├── BrizyPlaceholders/        # Unit tests for core classes
├── Sample/                   # Example placeholder implementations
│   ├── TestPlaceholder.php
│   └── LoopPlaceholder.php
└── data/                     # HTML fixture files for testing

dev/                          # Development environment
├── Dockerfile               # PHP 8.2 with Xdebug
└── conf.d/                  # PHP configuration

bin/                          # Build scripts
└── build-image              # Docker image build helper
```
