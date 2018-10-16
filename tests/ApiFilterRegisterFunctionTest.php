<?php declare(strict_types=1);

namespace Lmc\ApiFilter;

use Doctrine\ORM\QueryBuilder;
use Lmc\ApiFilter\Applicator\SqlApplicator;
use Lmc\ApiFilter\Constant\Priority;
use Lmc\ApiFilter\Entity\Parameter;
use Lmc\ApiFilter\Entity\Value;
use Lmc\ApiFilter\Exception\ApiFilterException;
use Lmc\ApiFilter\Filter\FilterFunction;
use Lmc\ApiFilter\Filter\FilterIn;
use Lmc\ApiFilter\Filter\FilterWithOperator;
use Lmc\ApiFilter\Filter\FunctionParameter;
use Lmc\ApiFilter\Fixtures\SimpleArrayApplicator;
use Lmc\ApiFilter\Fixtures\SimpleClient;

class ApiFilterRegisterFunctionTest extends AbstractTestCase
{
    /** @var ApiFilter */
    private $apiFilter;
    /** @var QueryBuilder */
    private $queryBuilder;

    protected function setUp(): void
    {
        $this->queryBuilder = $this->setUpQueryBuilder();

        $this->apiFilter = new ApiFilter();
        $this->apiFilter->registerApplicator(new SqlApplicator(), Priority::HIGHEST);
    }

    /**
     * @test
     * @dataProvider provideDeclareFunction
     */
    public function shouldDeclareFunction(
        string $functionName,
        array $parameters,
        array $queryParameters,
        array $expectedDql,
        array $expectedPreparedValues
    ): void {
        $this->markTestSkipped('todo later');

        [$queryBuilderWithFilters, $preparedValues] = $this->apiFilter
            ->declareFunction($functionName, $parameters)
            ->applyFunction($functionName, $queryParameters, $this->queryBuilder);

        $this->assertInstanceOf(QueryBuilder::class, $queryBuilderWithFilters);
        $this->assertDqlWhere($expectedDql, $queryBuilderWithFilters);
        $this->assertSame($expectedPreparedValues, $preparedValues);
    }

    public function provideDeclareFunction(): array
    {
        return [
            // functionName, parameters, queryParameters, expectedDQL, expectedPreparedValues
            'implicit equals' => [
                'fullName',
                ['firstName', new Parameter('surname')],
                ['fullName' => '(Jon,Snow)'],
                ['t.firstName = :firstName_eq', 't.surname = :surname_eq'],
                ['firstName_eq' => 'Jon', 'surname_eq' => 'Snow'],
            ],
            'explicit equals' => [
                'fullName',
                [['firstName', 'eq'], new Parameter('surname', 'eq')],
                ['fullName' => '(Jon,Snow)'],
                ['t.firstName = :firstName_eq', 't.surname = :surname_eq'],
                ['firstName_eq' => 'Jon', 'surname_eq' => 'Snow'],
            ],
            'explicit between (with mapping to column)' => [
                'inAge',
                [['ageFrom', 'gt', 'age'], new Parameter('ageTo', 'lt', 'age')],
                ['inAge' => '(18,30)'],
                ['t.age > :age_gt', 't.age < :age_lt'],
                ['age_gt' => 18, 'age_lt' => 30],
            ],
            'explicit with defaults' => [
                'girlInAge',
                [['ageFrom', 'gt', 'age'], ['ageTo', 'lt', 'age'], ['gender', 'eq', 'gender', new Value('female')]],
                ['girlInAge' => '(18,30)'],
                ['t.gender = :gender_eq', 't.age > :age_gt', 't.age < :age_lt'],
                ['gender_eq' => 'female', 'age_gt' => 18, 'age_lt' => 30],
            ],
            'explicit with defaults - by parameters' => [
                'girlInAge',
                [
                    new Parameter('ageFrom', 'gt', 'age'),
                    new Parameter('ageTo', 'lt', 'age'),
                    new Parameter('gender', null, null, new Value('female')),
                ],
                ['girlInAge' => '(18,30)'],
                ['t.gender = :gender_eq', 't.age > :age_gt', 't.age < :age_lt'],
                ['gender_eq' => 'female', 'age_gt' => 18, 'age_lt' => 30],
            ],
            'explicit with defaults - combined all possible declarations' => [
                'girlInAge',
                [
                    ['ageFrom', 'gt', 'age'],
                    new Parameter('ageTo', 'lt', 'age'),
                    Parameter::equalToDefaultValue('gender', new Value('female')),
                ],
                ['girlInAge' => '(18,30)'],
                ['t.gender = :gender_eq', 't.age > :age_gt', 't.age < :age_lt'],
                ['gender_eq' => 'female', 'age_gt' => 18, 'age_lt' => 30],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider provideMultipleFunctionsQueryParameters
     */
    public function shouldRegisterAndExecuteMultipleFunctionsAndOtherFilters(array $queryParameters): void
    {
        $expectedDqlWhere = [
            't.age > :age_gt',
            't.age < :age_lt',
            't.size IN (:size_in)',
            't.firstName = :firstName_eq',
            't.zone = :zone_eq',
            't.bucket = :bucket_eq',
        ];
        $expectedPreparedValues = [
            'age_gt' => 18,
            'age_lt' => 30,
            'size_in' => ['DD', 'D'],
            'firstName_eq' => 'Foo',
            'zone_eq' => 'all',
            'bucket_eq' => 'common',
        ];

        $this->apiFilter
            ->registerFunction(
                'perfectWife',
                ['ageFrom', 'ageTo', 'size'],
                function ($filterable, FunctionParameter $ageFrom, FunctionParameter $ageTo, FunctionParameter $size) {
                    $ageFromFilter = new FilterWithOperator('age', $ageFrom->getValue(), '>', 'gt');
                    $ageToFilter = new FilterWithOperator('age', $ageTo->getValue(), '<', 'lt');
                    $sizeFilter = new FilterIn($size->getColumn(), $size->getValue());

                    $filterable = $this->apiFilter->applyFilter($ageFromFilter, $filterable);
                    $filterable = $this->apiFilter->applyFilter($ageToFilter, $filterable);
                    $filterable = $this->apiFilter->applyFilter($sizeFilter, $filterable);

                    return $filterable;
                }
            )
            // ->declareFunction('perfect', [['ageFrom', 'gt', 'age'], ['ageTo', 'lt', 'age'], ['size', 'in']])
            ->declareFunction('spot', ['zone', 'bucket']);

        $filters = $this->apiFilter->parseFilters($queryParameters);

        $queryBuilderWithFilters = $this->apiFilter->applyFilters($filters, $this->queryBuilder);
        $preparedValues = $this->apiFilter->getPreparedValues($filters, $this->queryBuilder);

        if (!empty($queryParameters)) {
            // application of filters should not change original query builder
            $this->assertNotSame($this->queryBuilder, $queryBuilderWithFilters);
        }

        $this->assertInstanceOf(QueryBuilder::class, $queryBuilderWithFilters);
        $this->assertDqlWhere($expectedDqlWhere, $queryBuilderWithFilters);
        $this->assertSame($expectedPreparedValues, $preparedValues);
    }

    public function provideMultipleFunctionsQueryParameters(): array
    {
        return [
            // queryParameters
            'functions' => [
                [
                    'perfectWife' => '(18, 30, [DD; D])',
                    'firstName' => 'Foo',
                    'spot' => '(all,common)',
                ],
            ],
            'implicit - tuples (with different order of parameters in tuple)' => [
                [
                    '(ageTo,ageFrom,size)' => '(30, 18, [DD; D])',
                    'firstName' => 'Foo',
                    '(zone,bucket)' => '(all,common)',
                ],
            ],
            'explicit - tuples' => [
                [
                    '(fun,ageFrom,ageTo,size)' => '(perfectWife, 18, 30, [DD; D])',
                    'firstName' => 'Foo',
                    '(fun,zone,bucket)' => '(spot, all, common)',
                ],
            ],
            'implicit - values' => [
                [
                    'firstName' => 'Foo',
                    'bucket' => 'common',
                    'ageFrom' => 18,
                    'ageTo' => 30,
                    'zone' => 'all',
                    'size' => ['DD', 'D'],
                ],
            ],
            'explicit - values' => [
                [
                    'fun' => ['perfectWife', 'spot'],
                    'firstName' => 'Foo',
                    'bucket' => 'common',
                    'ageFrom' => 18,
                    'ageTo' => 30,
                    'zone' => 'all',
                    'size' => ['DD', 'D'],
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider provideFullNameQueryParameters
     */
    public function shouldDeclareAndExecuteFunctionWhichUsesApiFilter(array $queryParameters): void
    {
        $sql = 'SELECT * FROM person';
        $expectedSql = 'SELECT * FROM person WHERE 1 AND firstName = :firstName_eq AND surname = :surname_eq';
        $expectedPreparedValues = ['firstName_eq' => 'Jon', 'surname_eq' => 'Snow'];

        [$appliedSql, $preparedValues] = $this->apiFilter
            ->declareFunction('fullName', ['firstName', 'surname'])
            ->applyFunction('fullName', $queryParameters, $sql);

        $this->assertSame($expectedSql, $appliedSql);
        $this->assertSame($expectedPreparedValues, $preparedValues);
    }

    public function provideFullNameQueryParameters(): array
    {
        return [
            // queryParameters
            'function' => [['fullName' => '(Jon,Snow)']],
            'implicit - tuple' => [['(firstName,surname)' => '(Jon,Snow)']],
            'implicit - single values' => [['firstName' => 'Jon', 'surname' => 'Snow']],
            'explicit - tuple' => [['(fun,firstName,surname)' => '(fullName,Jon,Snow)']],
            'explicit - single values' => [['fun' => ['fullName'], 'firstName' => 'Jon', 'surname' => 'Snow']],
        ];
    }

    /**
     * @test
     * @dataProvider provideSqlQueryParameters
     */
    public function shouldRegisterAndExecuteFunctionWhichBypassApiFilter(array $queryParameters): void
    {
        $client = new SimpleClient(['data' => 'some data']);
        $expected = [
            'query' => 'SELECT * FROM table',
            'data' => 'some data',
        ];

        $result = $this->apiFilter
            ->registerFunction(
                'sql',
                ['query'],
                function (SimpleClient $filterable, FunctionParameter $query) {
                    return $filterable->query($query->getValue()->getValue());
                }
            )
            ->executeFunction('sql', $queryParameters, $client);

        $this->assertSame($expected, $result);
    }

    public function provideSqlQueryParameters(): array
    {
        return [
            // queryParameters
            'function' => [['sql' => 'SELECT * FROM table']],
            'explicit - tuple' => [['(fun,query)' => '(sql, "SELECT * FROM table")']],
            'implicit - single values' => [['query' => 'SELECT * FROM table']],
            'explicit - single values' => [['fun' => ['sql'], 'query' => 'SELECT * FROM table']],
        ];
    }

    /**
     * @test
     * @dataProvider provideQueryParametersForQuery
     */
    public function shouldApplyFunctionWithExplicitParameters(array $queryParameters, array $expected): void
    {
        $sqlClient = new SimpleClient(['data' => 'sql data']);
        $elasticClient = new SimpleClient(['data' => 'elastic data']);

        $this->apiFilter
            ->registerFunction(
                'sql',
                ['query'],
                function (SimpleClient $filterable, FunctionParameter $query) {
                    return $filterable->query($query->getValue()->getValue());
                }
            )
            ->registerFunction(
                'elastic',
                ['query'],
                function (SimpleClient $filterable, FunctionParameter $query) {
                    return $filterable->query($query->getValue()->getValue());
                }
            );

        $filters = $this->apiFilter->parseFilters($queryParameters);

        $result = [];
        foreach ($filters as $filter) {
            if ($filter->getColumn() === 'sql') {
                $result['sql'] = $this->apiFilter->applyFilter($filter, $sqlClient);
            }

            if ($filter->getColumn() === 'elastic') {
                $result['elastic'] = $this->apiFilter->applyFilter($filter, $elasticClient);
            }
        }

        $this->assertSame($expected, $result);
    }

    public function provideQueryParametersForQuery(): array
    {
        $sqlQuery = 'SELECT * FROM table';
        $elasticQuery = '{ "match_all": {} }';

        $sqlResult = [
            'query' => $sqlQuery,
            'data' => 'sql data',
        ];
        $elasticResult = [
            'query' => $elasticQuery,
            'data' => 'elastic data',
        ];

        return [
            // queryParameters, expected
            'function' => [
                ['sql' => $sqlQuery],
                ['sql' => $sqlResult],
            ],
            'both functions' => [
                ['sql' => $sqlQuery, 'elastic' => $elasticQuery],
                ['sql' => $sqlResult, 'elastic' => $elasticResult],
            ],
            'explicit - tuple' => [
                ['(fun,query)' => '(elastic, "{ "match_all": {} }")'],
                ['elastic' => $elasticResult],
            ],
            'explicit - tuple - both' => [
                ['(fun,query)' => ['(elastic, "{ "match_all": {} }")', '(sql, "SELECT * FROM table")']],
                ['sql' => $sqlResult, 'elastic' => $elasticResult],
            ],
            'explicit - values' => [
                [
                    'fun' => ['sql'],
                    'query' => $sqlQuery,
                ],
                ['sql' => $sqlResult],
            ],
            'explicit - values - both' => [
                [
                    'fun' => ['sql', 'elastic'],
                    'query' => ['sql' => $sqlQuery, 'elastic' => '{ "match_all": {} }'],
                ],
                ['sql' => $sqlResult, 'elastic' => $elasticResult],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider provideInvalidQueryParametersForQuery
     */
    public function shouldNotApplyFunctionsWhereThereIsNotJustOneOptionOnSingleParameterFunction(
        array $queryParameters,
        string $expectedMessage
    ): void {
        $this->expectException(ApiFilterException::class);
        $this->expectExceptionMessage($expectedMessage);

        $this->apiFilter
            ->declareFunction('sql', ['query'])
            ->declareFunction('elastic', ['query']);

        $this->apiFilter->parseFilters($queryParameters);
    }

    public function provideInvalidQueryParametersForQuery(): array
    {
        return [
            // queryParameters, expectedMessage
            'not just one option' => [
                ['query' => 'some query'],
                '...exception...',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider provideInvalidQueryParameters
     */
    public function shouldNotApplyFunctionsWhereThereIsNotJustOneOption(
        array $queryParameters,
        string $expectedMessage
    ): void {
        $this->expectException(ApiFilterException::class);
        $this->expectExceptionMessage($expectedMessage);

        $this->apiFilter
            ->registerFunction('first', ['param1', 'param2'], $this->getFunctionFirst())
            ->registerFunction('second', ['param1', 'param2'], $this->getFunctionSecond())
            ->registerFunction('+', ['param2', 'param1'], $this->getFunctionPlus());

        $this->apiFilter->parseFilters($queryParameters);
    }

    public function provideInvalidQueryParameters(): array
    {
        return [
            // queryParameters, expectedMessage
            'implicit - values' => [
                ['col1' => 'val1', 'col2' => 'val2'],
                '...exception...',
            ],
            'implicit - tuples' => [
                ['(col1,col2)' => '(val1,val2)'],
                '...exception...',
            ],
        ];
    }

    private function getFunctionFirst(): callable
    {
        return function ($filterable, FunctionParameter $param1, FunctionParameter $param2) {
            return $this->apiFilter->applyFilter($param1, $filterable);
        };
    }

    private function getFunctionSecond(): callable
    {
        return function ($filterable, FunctionParameter $param1, FunctionParameter $param2) {
            return $this->apiFilter->applyFilter($param2, $filterable);
        };
    }

    private function getFunctionPlus(): callable
    {
        return function ($filterable, FunctionParameter $param1, FunctionParameter $param2) {
            return $this->apiFilter->applyFilter(
                new FilterFunction(
                    '+',
                    new Value(
                        function () use ($param1, $param2) {
                            return $param1->getValue()->getValue() + $param2->getValue()->getValue();
                        }
                    )
                ),
                $filterable
            );
        };
    }

    /**
     * @test
     * @dataProvider provideValidQueryParametersForFunctionWithSameDeclaration
     */
    public function shouldApplyFunctionWhereThereIsNotJustOneOption(array $queryParameters, array $expected): void
    {
        $this->apiFilter
            ->registerApplicator(new SimpleArrayApplicator(), Priority::HIGHEST)
            ->registerFunction('first', ['param1', 'param2'], $this->getFunctionFirst())
            ->registerFunction('second', ['param1', 'param2'], $this->getFunctionSecond())
            ->registerFunction('+', ['param2', 'param1'], $this->getFunctionPlus());

        $filters = $this->apiFilter->parseFilters($queryParameters);
        $result = $this->apiFilter->applyFilters($filters, []);

        $this->assertSame($expected, $result);
    }

    public function provideValidQueryParametersForFunctionWithSameDeclaration(): array
    {
        return [
            // queryParameters, expected
            'function' => [
                ['first' => '(one,two)'],
                ['first' => 'one'],
            ],
            'more functions' => [
                ['first' => '(one,two)', 'second' => '(foo,bar)'],
                ['first' => 'one', 'second' => 'bar'],
            ],
            'explicit - tuple' => [
                ['(fun,param1,param2)' => '(+, 1, 5)'],
                ['+' => 6],
            ],
            'explicit - tuple - more' => [
                ['(fun,query)' => ['first' => '(2,5)', '+' => '(2,5)']],
                ['first' => 2, '+' => 7],
            ],
            'explicit - values - all' => [
                [
                    'fun' => ['+', 'first', 'second'],
                    'param1' => 3,
                    'param2' => 5,
                ],
                ['+' => 8, 'first' => 3, 'second' => 5],
            ],
        ];
    }
}
