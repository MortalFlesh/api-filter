<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Service;

use Assert\Assertion;
use Lmc\ApiFilter\Entity\Filterable;
use Lmc\ApiFilter\Filter\FilterInterface;
use Lmc\ApiFilter\Filters\FiltersInterface;
use MF\Collection\Mutable\Generic\IMap;
use MF\Collection\Mutable\Generic\Map;

class Functions
{
    /** @var IMap<string,callable>|callable[] */
    private $functions;
    /** @var IMap<string,array>|array[] */
    private $functionParameters;

    public function __construct()
    {
        $this->functions = new Map('string', 'callable');
        $this->functionParameters = new Map('string', 'array');
    }

    public function register(string $functionName, array $parameters, callable $function): void
    {
        Assertion::notEmpty($functionName, 'Function name must be defined.');
        Assertion::notEmpty($parameters, sprintf('Function "%s" must have some parameters.', $functionName));

        $this->functions[$functionName] = $function;
        $this->functionParameters[$functionName] = $parameters;
    }

    /** @param FiltersInterface|FilterInterface[] $filters */
    public function execute(string $functionName, FiltersInterface $filters, Filterable $filterable): Filterable
    {
        $this->assertRegistered($functionName);
        $function = $this->functions[$functionName];
        $parameters = $this->functionParameters[$functionName];

        $functionParameters = $filters->filterByColumns($parameters);
        var_dump([
            'function' => $functionName,
            'parameters' => $parameters,
            'filters for params' => iterator_to_array($functionParameters),
        ]);

        // todo - parser must be implemented before this
        $this->assertFiltersByParameters($parameters, $functionParameters);

        $applied = $function($filterable->getValue(), ...$functionParameters);

        return new Filterable($applied);
    }

    private function assertRegistered(string $functionName): void
    {
        Assertion::true(
            $this->functions->containsKey($functionName),
            sprintf('Function "%s" is not registered.', $functionName)
        );
    }

    private function assertFiltersByParameters(array $parameters, FiltersInterface $filterByParameters): void
    {
        Assertion::same(
            count($filterByParameters),
            count($parameters),
            'There are not filters (%s) for parameters (%s).'
        );
    }

    public function isFunctionRegistered(string $functionName): bool
    {
        return $this->functions->containsKey($functionName);
    }

    public function getFunctionNamesByParameter(string $possiblyParameter): iterable
    {
        foreach ($this->functionParameters as $functionName => $parameters) {
            foreach ($parameters as $parameter) {
                if ($parameter === $possiblyParameter) {
                    yield $functionName;
                    continue 2;
                }
            }
        }
    }

    public function getFunctionNamesByAllParameters(array $possiblyParameters): iterable
    {
        $sortedPossibleParameters = $this->sort($possiblyParameters);

        foreach ($this->functionParameters as $functionName => $parameters) {
            if ($this->sort($parameters) === $sortedPossibleParameters) {
                yield $functionName;
            }
        }
    }

    private function sort(array $array): array
    {
        sort($array);

        return $array;
    }

    public function getParametersFor(string $functionName): array
    {
        $this->assertRegistered($functionName);

        return $this->functionParameters[$functionName];
    }

    public function getFunction(string $functionName): callable
    {
        $this->assertRegistered($functionName);

        return $this->functions[$functionName];
    }
}
