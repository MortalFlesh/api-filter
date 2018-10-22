<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Service;

use Assert\Assertion;
use Lmc\ApiFilter\Entity\Filterable;
use Lmc\ApiFilter\Entity\Parameter;
use Lmc\ApiFilter\Filter\FunctionParameter;
use Lmc\ApiFilter\Filters\Filters;
use Lmc\ApiFilter\Filters\FiltersInterface;
use MF\Collection\Immutable\Generic\IMap;
use MF\Collection\Immutable\Generic\Map;

class FunctionCreator
{
    /** @var FilterFactory */
    private $filterFactory;

    public function __construct(FilterFactory $filterFactory)
    {
        $this->filterFactory = $filterFactory;
    }

    public function getParameterNames(array $parameters): array
    {
        return $this
            ->normalizeParameters($parameters)
            ->filter(function ($name, Parameter $parameter) {
                return !$parameter->hasDefaultValue();
            })
            ->keys()
            ->toArray();
    }

    public function createByParameters(FilterApplicator $applicator, array $parameters): callable
    {
        $normalizeParameters = $this->normalizeParameters($parameters);

        return function ($filterable, FunctionParameter ...$parameters) use ($normalizeParameters, $applicator) {
            return $applicator
                ->applyAll(
                    $this->createFiltersFromParameters($parameters, $normalizeParameters),
                    new Filterable($filterable)
                )
                ->getValue();
        };
    }

    /** @return Parameter[]|IMap IMap<string, Parameter> */
    private function normalizeParameters(array $parameters): IMap
    {
        $normalizeParameters = new Map('string', Parameter::class);

        foreach ($parameters as $parameter) {
            $this->assertParameter($parameter);
            if (is_string($parameter)) {
                $parameter = new Parameter($parameter);
            } else {
                $parameter = $parameter instanceof Parameter
                    ? $parameter
                    : Parameter::createFromArray($parameter);
            }

            $normalizeParameters = $normalizeParameters->set($parameter->getName(), $parameter);
        }

        return $normalizeParameters;
    }

    private function assertParameter($parameter): void
    {
        Assertion::true(
            is_string($parameter) || $parameter instanceof Parameter || is_array($parameter),
            sprintf(
                'Parameter for function creator must be either string, array or instance of Lmc\ApiFilter\Entity\Parameter but "%s" given.',
                is_object($parameter) ? get_class($parameter) : gettype($parameter)
            )
        );
    }

    /**
     * @param FunctionParameter[] $parameters
     * @param Parameter[]|IMap $parameterDefinitions IMap<string, Parameter>
     */
    private function createFiltersFromParameters(array $parameters, IMap $parameterDefinitions): FiltersInterface
    {
        $filters = new Filters();
        /** @var Parameter $definition */
        foreach ($parameterDefinitions as $definition) {
            if ($definition->hasDefaultValue()) {
                $value = $definition->getDefaultValue();
                $title = $definition->getTitleForDefaultValue();
            } else {
                $parameter = $this->getParameterByDefinition($parameters, $definition->getName());
                $value = $parameter->getValue();
                $title = $parameter->getTitle();
            }

            $filter = $this->filterFactory->createFilter($definition->getColumn(), $definition->getFilter(), $value);
            $filter->setFullTitle($title);

            $filters->addFilter($filter);
        }

        return $filters;
    }

    /** @param FunctionParameter[] $parameters */
    private function getParameterByDefinition(array $parameters, string $name): FunctionParameter
    {
        foreach ($parameters as $parameter) {
            if ($parameter->getColumn() === $name) {
                return $parameter;
            }
        }

        throw new \InvalidArgumentException(sprintf('Parameter "%s" is required and must have a value.', $name));
    }

    /**
     * @return Parameter[]
     */
    public function getParameterDefinitions(array $parameters): array
    {
        // todo - add test
        return $this
            ->normalizeParameters($parameters)
            ->values()
            ->toArray();
    }
}
