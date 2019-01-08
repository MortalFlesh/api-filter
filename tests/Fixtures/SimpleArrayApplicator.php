<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Fixtures;

use Lmc\ApiFilter\Applicator\ApplicatorInterface;
use Lmc\ApiFilter\Entity\Filterable;
use Lmc\ApiFilter\Entity\ParameterDefinition;
use Lmc\ApiFilter\Filter\FilterFunction;
use Lmc\ApiFilter\Filter\FilterIn;
use Lmc\ApiFilter\Filter\FilterInterface;
use Lmc\ApiFilter\Filter\FilterWithOperator;
use Lmc\ApiFilter\Filter\FunctionParameter;

class SimpleArrayApplicator implements ApplicatorInterface
{
    public function supports(Filterable $filterable): bool
    {
        return is_array($filterable->getValue());
    }

    /**
     * Apply filter with operator to filterable and returns the result
     *
     * @example
     * $filter = new FilterWithOperator('title', 'foo', '=', 'eq');
     * $sql = 'SELECT * FROM table';
     *
     * $simpleSqlApplicator->applyFilterWithOperator($filter, $sql);      // SELECT * FROM table WHERE title = :title_eq
     * $preparedValues = $simpleSqlApplicator->getPreparedValue($filter); // ['title_eq' => 'foo']
     */
    public function applyFilterWithOperator(FilterWithOperator $filter, Filterable $filterable): Filterable
    {
        $array = $filterable->getValue();
        $array[] = [$filter->getColumn(), $filter->getOperator(), $filter->getValue()->getValue()];

        return new Filterable($array);
    }

    /**
     * Apply IN filter to filterable and returns the result
     *
     * @example
     * $filter = new FilterIn('id', [1, 2]);
     * $sql = 'SELECT * FROM table';
     *
     * $sql = $simpleSqlApplicator->applyFilterIn($filter, $sql);         // SELECT * FROM table WHERE id IN (:id_in_0, :id_in_1)
     * $preparedValues = $simpleSqlApplicator->getPreparedValue($filter); // ['id_in_0' => 1, 'id_in_1' => 2]
     */
    public function applyFilterIn(FilterIn $filter, Filterable $filterable): Filterable
    {
        $array = $filterable->getValue();
        $array[] = [$filter->getColumn(), 'in', $filter->getValue()->getValue()];

        return new Filterable($array);
    }

    /**
     * Apply filter with defined function to filterable and returns the result
     *
     * @example
     * $filter = new FilterFunction(
     *      'fullName',
     *      new Value(functionction ($filterable, FunctionParameter $firstName, FunctionParameter $surname) {
     *          $filterable = $simpleSqlApplicator->applyFilterWithOperator(
     *              new FilterWithOperator($firstName->getColumn(), $firstName->getValue(), '=', $firstName->getTitle()),
     *              $filterable
     *          );
     *
     *          $filterable = $simpleSqlApplicator->applyFilterWithOperator(
     *              new FilterWithOperator($surname->getColumn(), $surname->getValue(), '=', $surname->getTitle()),
     *              $filterable
     *          );
     *
     *          return $filterable;
     *      })
     * );
     * $sql = 'SELECT * FROM table';
     * $parameters = [
     *      new FunctionParameter('firstName', 'Jon'),
     *      new FunctionParameter('surname', 'Snow'),
     * ]
     *
     * $sql = $simpleSqlApplicator->applyFilterFunction($filter, $sql, $parameters);      // SELECT * FROM table WHERE firstName = :firstName_fun AND surname = :surname_fun
     * $preparedValues = $simpleSqlApplicator->getPreparedValuesForFunction($parameters); // ['firstName_fun' => 'Jon', 'surname_fun' => 'Snow']
     *
     * @param FunctionParameter[] $parameters
     */
    public function applyFilterFunction(FilterFunction $filter, Filterable $filterable, array $parameters): Filterable
    {
        $function = $filter->getValue()->getValue();

        $array = $filterable->getValue();
        $array[] = [$filter->getColumn() => $function()];

        return new Filterable($array);
    }

    /**
     * Prepared values for applied filter
     *
     * @example
     * $filterWithOperator = new FilterWithOperator('title', 'foo', '=', 'eq');
     * $preparedValues = $simpleSqlApplicator->getPreparedValue($filter);  // ['title_eq' => 'foo']
     *
     * @example
     * $filter = new FilterIn('id', [1, 2]);
     * $preparedValues = $simpleSqlApplicator->getPreparedValue($filter);  // ['id_in_0' => 1, 'id_in_1' => 2]
     */
    public function getPreparedValue(FilterInterface $filter): array
    {
        throw new \Exception(sprintf('Method %s is not implemented yet.', __METHOD__));
    }

    /**
     * Prepared values for applied function
     *
     * For example
     * @see applyFilterFunction()
     *
     * @param FunctionParameter[] $parameters
     * @param ParameterDefinition[] $parametersDefinitions
     */
    public function getPreparedValuesForFunction(array $parameters, array $parametersDefinitions = []): array
    {
        throw new \Exception(sprintf('Method %s is not implemented yet.', __METHOD__));
    }
}
