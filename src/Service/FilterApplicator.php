<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Service;

use Lmc\ApiFilter\Applicator\ApplicatorInterface;
use Lmc\ApiFilter\Assertion;
use Lmc\ApiFilter\Entity\Filterable;
use Lmc\ApiFilter\Filter\FilterFunction;
use Lmc\ApiFilter\Filter\FilterIn;
use Lmc\ApiFilter\Filter\FilterInterface;
use Lmc\ApiFilter\Filter\FilterWithOperator;
use Lmc\ApiFilter\Filter\FunctionParameter;
use Lmc\ApiFilter\Filters\FiltersInterface;
use MF\Collection\Mutable\Generic\PrioritizedCollection;

class FilterApplicator
{
    /** @var PrioritizedCollection|ApplicatorInterface[] */
    private $applicators;
    /** @var Functions */
    private $functions;
    /** @var FiltersInterface */
    private $filters;

    public function __construct(Functions $functions)
    {
        $this->functions = $functions;
        $this->applicators = new PrioritizedCollection(ApplicatorInterface::class);
    }

    public function registerApplicator(ApplicatorInterface $applicator, int $priority): void
    {
        $this->applicators->add($applicator, $priority);
    }

    public function setFilters(FiltersInterface $filters): void
    {
        $this->filters = $filters;
    }

    public function apply(FilterInterface $filter, Filterable $filterable): Filterable
    {
        Assertion::notNull($this->filters, 'Filters must be set before applying.');
        $applicator = $this->findApplicatorFor($filterable);

        if ($filter instanceof FilterWithOperator) {
            return $applicator->applyFilterWithOperator($filter, $filterable);
        } elseif ($filter instanceof FilterIn) {
            return $applicator->applyFilterIn($filter, $filterable);
        } elseif ($filter instanceof FilterFunction) {
            return $applicator->applyFilterFunction($filter, $filterable, $this->getParametersForFunction($filter));
        } elseif ($filter instanceof FunctionParameter) {
            return $filterable;
        }

        throw new \InvalidArgumentException(sprintf('Unsupported filter given "%s".', get_class($filter)));
    }

    private function findApplicatorFor(Filterable $filterable): ApplicatorInterface
    {
        foreach ($this->applicators as $applicator) {
            if ($applicator->supports($filterable)) {
                return $applicator;
            }
        }

        $filterableValue = $filterable->getValue();
        throw new \InvalidArgumentException(
            sprintf(
                'Unsupported filterable of type "%s".',
                is_object($filterableValue) ? get_class($filterableValue) : gettype($filterableValue)
            )
        );
    }

    private function getParametersForFunction(FilterFunction $filter): array
    {
        $parameters = [];
        foreach ($this->functions->getParametersFor($filter->getColumn()) as $parameter) {
            $parameters[] = $this->filters->getFunctionParameter($parameter);
        }

        return $parameters;
    }

    public function getPreparedValue(FilterInterface $filter, Filterable $filterable): array
    {
        $applicator = $this->findApplicatorFor($filterable);

        return $filter instanceof FilterFunction
            ? $applicator->getPreparedValuesForFunction(
                $this->getParametersForFunction($filter),
                $this->functions->getParameterDefinitionsFor($filter->getColumn())
            )
            : $applicator->getPreparedValue($filter);
    }

    public function applyAll(FiltersInterface $filters, Filterable $filterable): Filterable
    {
        return $filters->applyAllTo($filterable, $this);
    }

    public function getPreparedValues(FiltersInterface $filters, Filterable $filterable): array
    {
        return $filters->getPreparedValues(
            $this->findApplicatorFor($filterable),
            function (FilterFunction $filterFunction) {
                return $this->getParametersForFunction($filterFunction);
            },
            function (FilterFunction $filterFunction) {
                return $this->functions->getParameterDefinitionsFor($filterFunction->getColumn());
            }
        );
    }
}
