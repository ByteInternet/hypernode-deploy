<?php

namespace Hypernode\Deploy\Stdlib;

use ReflectionClass;
use ReflectionException;
use Symfony\Component\Finder\Finder;

class ClassFinder extends Finder
{
    /**
     * @var class-string|null
     */
    protected ?string $implements = null;

    /**
     * @var class-string|null
     */
    protected ?string $subclassOff = null;

    /**
     * @var string
     */
    protected string $namespace;

    public function __construct(string $namespace)
    {
        parent::__construct();

        $this->name('*.php');
        $this->ignoreDotFiles(true);
        $this->ignoreVCS(true);
        $this->namespace = $namespace;
    }

    /**
     * @param class-string $interface
     * @return $this
     */
    public function implements(string $interface): self
    {
        $this->implements = $interface;
        return $this;
    }

    /**
     * @param class-string $class
     * @return $this
     */
    public function subclassOff(string $class): self
    {
        $this->subclassOff = $class;
        return $this;
    }

    public function getClasses(): \Generator
    {
        foreach (parent::getIterator() as $file) {
            $className = $file->getRelativePathname();
            $className = substr($className, 0, -4);
            $className = str_replace('/', '\\', $className);
            $className = rtrim($this->namespace, '\\') . '\\' . $className;

            try {
                /** @var class-string $className */
                $reflection = new ReflectionClass($className);
            } catch (ReflectionException $e) {
                continue;
            }

            if (!$reflection->isInstantiable()) {
                continue;
            }

            if ($this->implements && !$reflection->implementsInterface($this->implements)) {
                continue;
            }

            if ($this->subclassOff && !$reflection->isSubclassOf($this->subclassOff)) {
                continue;
            }

            yield $className;
        }
    }
}
