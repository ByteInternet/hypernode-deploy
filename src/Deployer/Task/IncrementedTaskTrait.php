<?php

namespace Hypernode\Deploy\Deployer\Task;

trait IncrementedTaskTrait
{
    /**
     * @var array
     */
    private $registeredTasks = [];

    /**
     * @var integer
     */
    private $counter = 0;

    /**
     * @return string
     */
    abstract protected function getIncrementalNamePrefix(): string;

    /**
     * @param string|null $identifier
     * @return string
     */
    protected function getTaskName(string $identifier = null): string
    {
        $name = $this->getIncrementalNamePrefix();
        if (!empty($identifier)) {
            $name .= $identifier . ':';
        }
        $name .= $this->counter++;

        $this->registeredTasks[] = $name;

        return $name;
    }

    /**
     * @return string[]
     */
    protected function getRegisteredTasks(): array
    {
        return $this->registeredTasks;
    }
}
