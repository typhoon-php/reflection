# Typhoon Reflection

[![PHP Version Requirement](https://img.shields.io/packagist/dependency-v/typhoon/reflection/php)](https://packagist.org/packages/typhoon/reflection)
[![GitHub Release](https://img.shields.io/github/v/release/typhoon-php/reflection)](https://github.com/typhoon-php/reflection/releases)
[![Psalm Level](https://shepherd.dev/github/typhoon-php/reflection/level.svg)](https://shepherd.dev/github/typhoon-php/reflection)
[![Psalm Type Coverage](https://shepherd.dev/github/typhoon-php/reflection/coverage.svg)](https://shepherd.dev/github/typhoon-php/reflection)
[![Code Coverage](https://codecov.io/gh/typhoon-php/reflection/branch/0.4.x/graph/badge.svg)](https://codecov.io/gh/typhoon-php/reflection/tree/0.4.x)
[![Mutation testing badge](https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2Ftyphoon-php%2Freflection%2F0.4.x)](https://dashboard.stryker-mutator.io/reports/github.com/typhoon-php/reflection/0.4.x)

Typhoon Reflection is an alternative to [native PHP Reflection](https://www.php.net/manual/en/book.reflection.php). It
is:

- static (does not run or autoload reflected code),
- fast (due to lazy loading and caching),
- [fully compatible with native reflection](reflection/native_adapters.md),
- supports most of the Psalm and PHPStan phpDoc types,
- can resolve templates,
- does not leak memory and can be safely used
  with [zend.enable_gc=0](https://www.php.net/manual/en/info.configuration.php#ini.zend.enable-gc).

## Installation

```
composer require typhoon/reflection typhoon/phpstorm-reflection-stubs
```

`typhoon/phpstorm-reflection-stubs` is a bridge for `jetbrains/phpstorm-stubs`. Without this package internal classes
and functions are reflected from native reflection without templates.

## Basic Usage

```php
use Typhoon\Reflection\TyphoonReflector;
use Typhoon\Type\types;
use function Typhoon\Type\stringify;

/**
 * @template TTag of non-empty-string
 */
final readonly class Article
{
    /**
     * @param list<TTag> $tags
     */
    public function __construct(
        private array $tags,
    ) {}
}

$reflector = TyphoonReflector::build();
$class = $reflector->reflectClass(Article::class);
$tagsType = $class->properties()['tags']->type();

var_dump(stringify($tagsType)); // "list<TTag#Article>"

$templateResolver = $class->createTemplateResolver([
    types::union(
        types::string('PHP'),
        types::string('Architecture'),
    ),
]);

var_dump(stringify($tagsType->accept($templateResolver))); // "list<'PHP'|'Architecture'>"
```

## Documentation

- [Native reflection adapters](docs/native_adapters.md)
- [Reflecting Types](docs/types.md)
- [Reflecting PHPDoc properties and methods](docs/php_doc_properties_and_methods.md)
- [Implementing custom types](docs/implementing_custom_types.md)
- [Caching](docs/caching.md)

Documentation is still far from being complete. Don't hesitate to create issues to clarify how things work.
