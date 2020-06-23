<?php declare(strict_types=1);

namespace Phact\Container\Builder;

use Phact\Container\Exceptions\NotFoundException;
use Psr\Container\ContainerInterface;

class ArgumentsBuilder
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    public function setContainer(ContainerInterface $container): void
    {
        $this->container = $container;
    }

    /**
     * Build arguments by dependencies and parameters
     *
     * @param DependencyInterface[] $dependencies
     * @param ParameterInterface[] $parameters
     * @return array
     * @throws NotFoundException
     */
    public function buildArguments(array $dependencies, array $parameters): array
    {
        $arguments = [];
        if (count($dependencies) > 0) {
            $arguments = $this->buildArgumentsFromDependencies($dependencies, $parameters);
        } else {
            foreach ($parameters as $parameter) {
                $arguments[] = $this->makeArgumentByParameter($parameter);
            }
        }
        return $arguments;
    }

    /**
     * @param DependencyInterface[] $dependencies
     * @param ParameterInterface[] $parameters
     * @return array
     * @throws NotFoundException
     */
    protected function buildArgumentsFromDependencies(array $dependencies, array $parameters): array
    {
        $arguments = [];
        $usedParameters = [];

        foreach ($dependencies as $key => $dependency) {
            /** @var ParameterInterface $parameter */
            $parameter = null;
            if (isset($parameters[$key])) {
                $parameter = $parameters[$key];
                $usedParameters[] = $key;
                $arguments[] = $this->makeArgumentByParameter($parameter);
            } elseif (isset($parameters[$dependency->getName()])) {
                $parameter = $parameters[$dependency->getName()];
                $usedParameters[] = $dependency->getName();
                $arguments[] = $this->makeArgumentByParameter($parameter);
            } else {
                $arguments[] = $this->makeArgumentByDependency($dependency);
            }
        }

        $arguments = $this->appendUnusedParamsToArguments($parameters, $arguments, $usedParameters);

        return $arguments;
    }


    /**
     * @param array $parameters
     * @param array $usedParameters
     * @param array $arguments
     * @return array
     * @throws NotFoundException
     */
    protected function appendUnusedParamsToArguments(
        array $parameters,
        array $arguments,
        array $usedParameters = []
    ): array {
        foreach ($parameters as $key => $parameter) {
            if (!in_array($key, $usedParameters, true)) {
                $arguments[] = $this->makeArgumentByParameter($parameter);
            }
        }
        return $arguments;
    }

    /**
     * @param ParameterInterface $parameter
     * @return mixed
     * @throws NotFoundException
     */
    protected function makeArgumentByParameter(ParameterInterface $parameter)
    {
        switch ($parameter->getType()) {
            case ParameterInterface::TYPE_REFERENCE_REQUIRED:
                return $this->retrieveRequiredDependencyFromContainer($parameter->getValue());

            case ParameterInterface::TYPE_REFERENCE_OPTIONAL:
                return $this->retrieveOptionalDependencyFromContainer($parameter->getValue());
        }
        return $parameter->getValue();
    }

    /**
     * @param DependencyInterface $dependency
     * @return mixed
     * @throws NotFoundException
     */
    protected function makeArgumentByDependency(DependencyInterface $dependency)
    {
        switch ($dependency->getType()) {
            case DependencyInterface::TYPE_REQUIRED:
                return $this->retrieveRequiredDependencyFromContainer($dependency->getValue());

            case DependencyInterface::TYPE_OPTIONAL:
                return $this->retrieveOptionalDependencyFromContainer($dependency->getValue());
        }
        return $dependency->getValue();
    }

    /**
     * @param $id
     * @return mixed
     * @throws NotFoundException
     */
    protected function retrieveRequiredDependencyFromContainer($id)
    {
        if ($this->container && $this->container->has($id)) {
            return $this->container->get($id);
        }
        throw new NotFoundException("There is no referenced classes of {$id} found");
    }

    /**
     * @param $id
     * @return mixed
     */
    protected function retrieveOptionalDependencyFromContainer($id)
    {
        if ($this->container && $this->container->has($id)) {
            return $this->container->get($id);
        }
        return null;
    }
}
