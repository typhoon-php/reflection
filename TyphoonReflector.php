<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use PhpParser\Parser;
use Psr\SimpleCache\CacheInterface;
use Typhoon\ChangeDetector\FileChangeDetector;
use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\ClassConstantId;
use Typhoon\DeclarationId\ClassId;
use Typhoon\DeclarationId\DeclarationId;
use Typhoon\DeclarationId\MethodId;
use Typhoon\DeclarationId\PropertyId;
use Typhoon\PhpStormReflectionStubs\PhpStormStubsLocator;
use Typhoon\Reflection\Cache\InMemoryCache;
use Typhoon\Reflection\Exception\ClassDoesNotExist;
use Typhoon\Reflection\Exception\FileNotReadable;
use Typhoon\Reflection\Internal\CleanUp;
use Typhoon\Reflection\Internal\Data;
use Typhoon\Reflection\Internal\DataStorage;
use Typhoon\Reflection\Internal\PhpParserReflector\FindAnonymousClassVisitor;
use Typhoon\Reflection\Internal\PhpParserReflector\PhpParser;
use Typhoon\Reflection\Internal\PhpParserReflector\ReflectPhpParserNode;
use Typhoon\Reflection\Internal\PhpParserReflector\SymbolReflectingVisitor;
use Typhoon\Reflection\Internal\ResolveAttributesRepeated;
use Typhoon\Reflection\Internal\ResolveClassInheritance;
use Typhoon\Reflection\Internal\ResolveParametersIndex;
use Typhoon\Reflection\Locator\AnonymousClassLocator;
use Typhoon\Reflection\Locator\ComposerLocator;
use Typhoon\Reflection\Locator\Locators;
use Typhoon\Reflection\Locator\NativeReflectionClassLocator;
use Typhoon\Reflection\Locator\NativeReflectionFunctionLocator;
use Typhoon\TypeContext\NodeVisitor\TypeContextVisitor;
use Typhoon\TypedMap\TypedMap;
use function Typhoon\DeclarationId\classId;

/**
 * @api
 */
final class TyphoonReflector implements Reflector
{
    private function __construct(
        private readonly PhpParser $phpParser,
        private readonly Locator $locator,
        private readonly DataStorage $storage,
    ) {}

    /**
     * @param ?list<Locator> $locators
     */
    public static function build(
        ?array $locators = null,
        CacheInterface $cache = new InMemoryCache(),
        ?Parser $phpParser = null,
    ): self {
        return new self(
            phpParser: new PhpParser($phpParser),
            locator: new Locators($locators ?? self::defaultLocators()),
            storage: new DataStorage($cache),
        );
    }

    /**
     * @return list<Locator>
     */
    public static function defaultLocators(): array
    {
        $locators = [];

        if (class_exists(PhpStormStubsLocator::class)) {
            $locators[] = new PhpStormStubsLocator();
        }

        if (ComposerLocator::isSupported()) {
            $locators[] = new ComposerLocator();
        }

        $locators[] = new NativeReflectionClassLocator();
        $locators[] = new NativeReflectionFunctionLocator();
        $locators[] = new AnonymousClassLocator();

        return $locators;
    }

    /**
     * @template T of object
     * @param string|class-string<T>|T $nameOrObject
     * @return ClassReflection<T>
     */
    public function reflectClass(string|object $nameOrObject): ClassReflection
    {
        /** @var ClassReflection<T> */
        return $this->reflect(classId($nameOrObject));
    }

    public function reflect(DeclarationId $id): Reflection
    {
        return match (true) {
            $id instanceof ClassId => new ClassReflection(
                id: $id,
                data: $this->doReflect($id) ?? throw new ClassDoesNotExist($id->name),
                reflector: $this,
            ),
            $id instanceof AnonymousClassId => new ClassReflection(
                id: $id,
                data: $this->doReflectAnonymous($id) ?? throw new ClassDoesNotExist($id->name),
                reflector: $this,
            ),
            $id instanceof PropertyId => $this->reflect($id->class)->property($id->name) ?? throw new \LogicException('Does not exist'),
            $id instanceof ClassConstantId => $this->reflect($id->class)->constant($id->name) ?? throw new \LogicException('Does not exist'),
            $id instanceof MethodId => $this->reflect($id->class)->method($id->name) ?? throw new \LogicException('Does not exist'),
        };
    }

    private function doReflect(ClassId $id): ?TypedMap
    {
        $data = $this->storage->get($id);

        if ($data !== null) {
            return $data;
        }

        $resource = $this->locator->locate($id);

        if ($resource === null) {
            return null;
        }

        $typeContextVisitor = new TypeContextVisitor($resource->data[Data::File()] ?? null);
        PhpParser::traverse($this->phpParser->parse($resource->code), [
            $typeContextVisitor,
            new SymbolReflectingVisitor(
                code: $resource->code,
                data: $resource->data,
                typeContextProvider: $typeContextVisitor,
                storage: $this->storage,
                hooks: [
                    new ReflectPhpParserNode(),
                    ...$resource->hooks,
                    new ResolveAttributesRepeated(),
                    new ResolveParametersIndex(),
                    new ResolveClassInheritance($this),
                    new CleanUp(),
                ],
            ),
        ]);

        $data = $this->storage->get($id);

        $this->storage->commit();

        return $data;
    }

    private function doReflectAnonymous(AnonymousClassId $id): ?TypedMap
    {
        $data = $this->storage->get($id);

        if ($data !== null) {
            return $data;
        }

        $code = @file_get_contents($id->file);

        if ($code === false) {
            throw new FileNotReadable($id->file);
        }

        $typeContextVisitor = new TypeContextVisitor(file: $id->file);
        $finder = new FindAnonymousClassVisitor($typeContextVisitor, $id);

        PhpParser::traverse($this->phpParser->parse($code), [$typeContextVisitor, $finder]);

        if ($finder->data === null) {
            return null;
        }

        $data = $finder->data
            ->with(Data::File(), $id->file)
            ->with(Data::UnresolvedChangeDetectors(), [FileChangeDetector::fromFileAndContents($id->file, $code)]);

        $hooks = [
            new ReflectPhpParserNode(),
            new ResolveAttributesRepeated(),
            new ResolveParametersIndex(),
            new ResolveClassInheritance($this),
            new CleanUp(),
        ];

        foreach ($hooks as $hook) {
            $data = $hook->reflect($id, $data);
        }

        $this->storage->save($id, $data);

        return $data;
    }
}
