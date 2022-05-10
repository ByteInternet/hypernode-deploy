<?php
/**
 * @author Hipex <info@hipex.io>
 * @copyright (c) Hipex B.V. 2018
 */

namespace HipexDeploy\Stdlib;

use ReflectionClass;
use ReflectionException;
use Symfony\Component\Finder\Finder;

class ClassFinder extends Finder
{
    /**
     * @var string
     */
    protected $implements;

    /**
     * @var string
     */
    protected $subclassOff;

    /**
     * @var string
     */
    protected $namespace;

    /**
     * ClassFinder constructor.
     * @param string $namespace
     */
    public function __construct(string $namespace)
    {
        parent::__construct();

        $this->name('*.php');
        $this->ignoreDotFiles(true);
        $this->ignoreVCS(true);
        $this->namespace = $namespace;
    }

    /**
     * @param string $interface
     * @return $this
     */
    public function implements(string $interface): self
    {
        $this->implements = $interface;
        return $this;
    }

    /**
     * @param string $class
     * @return $this
     */
    public function subclassOff(string $class): self
    {
        $this->subclassOff = $class;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        foreach (parent::getIterator() as $file) {
            $className = $file->getRelativePathname();
            $className = substr($className, 0, -4);
            $className = str_replace('/', '\\', $className);
            $className = rtrim($this->namespace, '\\') . '\\' . $className;

            try {
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
