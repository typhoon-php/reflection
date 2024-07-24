<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal;

use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\AnonymousFunctionId;
use Typhoon\DeclarationId\ConstantId;
use Typhoon\DeclarationId\NamedClassId;
use Typhoon\DeclarationId\NamedFunctionId;
use Typhoon\Reflection\Exception\LocatorErrored;
use Typhoon\Reflection\Locator\AnonymousLocator;
use Typhoon\Reflection\Locator\ConstantLocator;
use Typhoon\Reflection\Locator\NamedClassLocator;
use Typhoon\Reflection\Locator\NamedFunctionLocator;
use Typhoon\Reflection\Locator\Resource;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class Locators
{
    /**
     * @var list<ConstantLocator>
     */
    private array $constantLocators = [];

    /**
     * @var list<NamedFunctionLocator>
     */
    private array $namedFunctionLocators = [];

    /**
     * @var list<NamedClassLocator>
     */
    private array $namedClassLocators = [];

    /**
     * @var list<AnonymousLocator>
     */
    private array $anonymousLocators = [];

    /**
     * @param iterable<ConstantLocator|NamedFunctionLocator|NamedClassLocator|AnonymousLocator> $locators
     */
    public function __construct(iterable $locators)
    {
        foreach ($locators as $locator) {
            $this->add($locator);
        }
    }

    public function locate(ConstantId|NamedFunctionId|AnonymousFunctionId|NamedClassId|AnonymousClassId $id): ?Resource
    {
        $locators = match (true) {
            $id instanceof ConstantId => $this->constantLocators,
            $id instanceof NamedFunctionId => $this->namedFunctionLocators,
            $id instanceof NamedClassId => $this->namedClassLocators,
            $id instanceof AnonymousFunctionId,
            $id instanceof AnonymousClassId => $this->anonymousLocators,
        };

        foreach ($locators as $locator) {
            try {
                /** @psalm-suppress PossiblyInvalidArgument */
                $resource = $locator->locate($id);
            } catch (\Throwable $exception) {
                throw new LocatorErrored($id, $exception);
            }

            if ($resource !== null) {
                return $resource;
            }
        }

        return null;
    }

    public function with(ConstantLocator|NamedFunctionLocator|NamedClassLocator|AnonymousLocator $locator): self
    {
        $copy = clone $this;
        $copy->add($locator);

        return $copy;
    }

    private function add(ConstantLocator|NamedFunctionLocator|NamedClassLocator|AnonymousLocator $locator): void
    {
        if ($locator instanceof ConstantLocator) {
            $this->constantLocators[] = $locator;
        }

        if ($locator instanceof NamedFunctionLocator) {
            $this->namedFunctionLocators[] = $locator;
        }

        if ($locator instanceof NamedClassLocator) {
            $this->namedClassLocators[] = $locator;
        }

        if ($locator instanceof AnonymousLocator) {
            $this->anonymousLocators[] = $locator;
        }
    }
}
