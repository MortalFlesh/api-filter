<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Service\Parser;

use Lmc\ApiFilter\Service\Functions;

/**
 * @covers \Lmc\ApiFilter\Service\Parser\FunctionParser
 * @covers \Lmc\ApiFilter\Service\Parser\FunctionParser\AbstractFunctionParser
 */
class FunctionParserTest extends AbstractParserTestCase
{
    /** @var FunctionParser */
    protected $parser;
    /** @var Functions */
    private $functions;

    protected function setUp(): void
    {
        $this->functions = new Functions();

        $this->parser = new FunctionParser($this->mockFilterFactory(), $this->functions);

        $this->functions->register('fullName', ['firstName', 'surname'], $this->createBlankCallback('fullName'));
        $this->functions->register(
            'perfectWife',
            ['ageFrom', 'ageTo', 'size'],
            $this->createBlankCallback('perfectWife')
        );
        $this->functions->register('sql', ['query'], $this->createBlankCallback('sql'));
    }

    /**
     * @param mixed $rawValue Value from query parameters
     *
     * @test
     * @dataProvider provideParseableColumnAndValue
     */
    public function shouldSupportColumnAndValue(string $rawColumn, $rawValue): void
    {
        $this->parser->setQueryParameters([$rawColumn => $rawValue]);
        parent::shouldSupportColumnAndValue($rawColumn, $rawValue);
    }

    /**
     * @param mixed $rawValue Value from query parameters
     *
     * @test
     * @dataProvider provideNotSupportedColumnAndValue
     */
    public function shouldNotSupportColumnAndValue(string $rawColumn, $rawValue): void
    {
        $this->parser->setQueryParameters([$rawColumn => $rawValue]);
        parent::shouldNotSupportColumnAndValue($rawColumn, $rawValue);
    }

    public function provideNotSupportedColumnAndValue(): array
    {
        return self::CASE_SCALAR_COLUMN_AND_TUPLE_VALUE
            + self::CASE_TUPLE_COLUMN_AND_TUPLE_VALUE
            + self::CASE_TUPLE_COLUMN_AND_TUPLE_VALUE_IMPLICIT_FILTERS
            + self::CASE_SCALAR_COLUMN_AND_SCALAR_VALUE
            + self::CASE_SCALAR_COLUMN_AND_ARRAY_VALUE
            + self::CASE_SCALAR_COLUMN_AND_ARRAY_VALUES
            + self::CASE_TUPLE_COLUMN_WITH_FILTER_AND_TUPLE_VALUE
            + self::CASE_TUPLE_COLUMN_AND_SCALAR_VALUE
            + self::CASE_TUPLE_COLUMN_AND_ARRAY_VALUE;
    }

    /**
     * @param mixed $rawValue Value from query parameters
     *
     * @test
     * @dataProvider provideParseableColumnAndValue
     */
    public function shouldParseColumnAndValue(string $rawColumn, $rawValue, array $expected): void
    {
        $this->parser->setQueryParameters([$rawColumn => $rawValue]);
        parent::shouldParseColumnAndValue($rawColumn, $rawValue, $expected);
    }

    public function provideParseableColumnAndValue(): array
    {
        return [
            // rawColumn, rawValue, expectedFilters
            'scalar column + tuple value - fullName' => [
                'fullName',
                '(Jon,Snow)',
                [
                    ['fullName', 'function', 'callable'],
                    ['firstName', 'function-parameter', 'Jon'],
                    ['surname', 'function-parameter', 'Snow'],
                ],
            ],
            'tuple column + tuple value - implicit fullName by tuple' => [
                '(firstName,surname)',
                '(Jon,Snow)',
                [
                    ['fullName', 'function', 'callable'],
                    ['firstName', 'function-parameter', 'Jon'],
                    ['surname', 'function-parameter', 'Snow'],
                ],
            ],
            'tuple column + tuple value - implicit perfectWife by tuple' => [
                '(ageFrom, ageTo, size)',
                '(18, 30, [DD; D])',
                [
                    ['perfectWife', 'function', 'callable'],
                    ['ageFrom', 'function-parameter', 18],
                    ['ageTo', 'function-parameter', 30],
                    ['size', 'function-parameter', ['DD', 'D']],
                ],
            ],
            'tuple column + tuple value - explicit perfectWife by tuple' => [
                '(fun, ageFrom, ageTo, size)',
                '(perfectWife, 18, 30, [DD; D])',
                [
                    ['perfectWife', 'function', 'callable'],
                    ['ageFrom', 'function-parameter', 18],
                    ['ageTo', 'function-parameter', 30],
                    ['size', 'function-parameter', ['DD', 'D']],
                ],
            ],
            'scalar column + scalar value - sql' => [
                'sql',
                'SELECT * FROM table',
                [
                    ['sql', 'function', 'callable'],
                    ['query', 'function-parameter', 'SELECT * FROM table'],
                ],
            ],
            'scalar column + scalar value - implicit sql' => [
                'query',
                'SELECT * FROM table',
                [
                    ['sql', 'function', 'callable'],
                    ['query', 'function-parameter', 'SELECT * FROM table'],
                ],
            ],
            'tuple column + tuple value - explicit sql by tuple' => [
                '(fun,query)',
                '(sql, "SELECT * FROM table")',
                [
                    ['sql', 'function', 'callable'],
                    ['query', 'function-parameter', 'SELECT * FROM table'],
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider provideParseableQueryParameters
     */
    public function shouldSupportQueryParameters(array $queryParameters): void
    {
        $this->parser->setQueryParameters($queryParameters);

        foreach ($queryParameters as $column => $value) {
            $this->assertTrue($this->parser->supports($column, $value));
        }
    }

    /**
     * @test
     * @dataProvider provideInsufficientParametersForFunction
     */
    public function shouldNotSupportInsufficientFunctionParameters(array $queryParameters): void
    {
        $this->parser->setQueryParameters($queryParameters);

        foreach ($queryParameters as $column => $value) {
            $this->assertFalse($this->parser->supports($column, $value));
        }
    }

    public function provideInsufficientParametersForFunction(): array
    {
        return [
            // queryParameters
            'missing surname' => [
                ['firstName' => 'Jon'],
            ],
            'mixed between two tuples' => [
                ['(foo,surname)' => '(foo,Snow)', '(firstName,bar)' => '(Jon,bar)'],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider provideParseableQueryParameters
     */
    public function shouldParseQueryParameters(array $queryParameters, array $expected): void
    {
        $this->parser->setQueryParameters($queryParameters);
        $result = [];

        foreach ($queryParameters as $column => $value) {
            foreach ($this->parseColumnAndValue($column, $value) as $item) {
                $result[] = $item;
            }
        }

        $this->assertSame($expected, $result);
    }

    public function provideParseableQueryParameters(): array
    {
        return [
            // queryParameters, expected
            'function' => [
                ['fullName' => '(Jon, Snow)'],
                [
                    ['fullName', 'function', 'callable'],
                    ['firstName', 'function-parameter', 'Jon'],
                    ['surname', 'function-parameter', 'Snow'],
                ],
            ],
            'implicit - tuple' => [
                ['(firstName, surname)' => '(Jon, Snow)'],
                [
                    ['fullName', 'function', 'callable'],
                    ['firstName', 'function-parameter', 'Jon'],
                    ['surname', 'function-parameter', 'Snow'],
                ],
            ],
            'implicit - values' => [
                ['firstName' => 'Jon', 'surname' => 'Snow'],
                [
                    ['fullName', 'function', 'callable'],
                    ['firstName', 'function-parameter', 'Jon'],
                    ['surname', 'function-parameter', 'Snow'],
                ],
            ],
            'explicit - tuple' => [
                ['(fun,firstName, surname)' => '(fullName,Jon, Snow)'],
                [
                    ['fullName', 'function', 'callable'],
                    ['firstName', 'function-parameter', 'Jon'],
                    ['surname', 'function-parameter', 'Snow'],
                ],
            ],
            'explicit - values' => [
                ['fun' => ['fullName'], 'firstName' => 'Jon', 'surname' => 'Snow'],
                [
                    ['fullName', 'function', 'callable'],
                    ['firstName', 'function-parameter', 'Jon'],
                    ['surname', 'function-parameter', 'Snow'],
                ],
            ],
            // multiple functions
            'multiple functions' => [
                ['fullName' => '(Jon, Snow)', 'perfectWife' => '(18, 30, [DD; D])'],
                [
                    ['fullName', 'function', 'callable'],
                    ['firstName', 'function-parameter', 'Jon'],
                    ['surname', 'function-parameter', 'Snow'],
                    ['perfectWife', 'function', 'callable'],
                    ['ageFrom', 'function-parameter', 18],
                    ['ageTo', 'function-parameter', 30],
                    ['size', 'function-parameter', ['DD', 'D']],
                ],
            ],
            'multiple - implicit - tuple' => [
                ['(firstName, surname)' => '(Jon, Snow)', '(ageFrom,ageTo,size)' => '(18, 30, [DD; D])'],
                [
                    ['fullName', 'function', 'callable'],
                    ['firstName', 'function-parameter', 'Jon'],
                    ['surname', 'function-parameter', 'Snow'],
                    ['perfectWife', 'function', 'callable'],
                    ['ageFrom', 'function-parameter', 18],
                    ['ageTo', 'function-parameter', 30],
                    ['size', 'function-parameter', ['DD', 'D']],
                ],
            ],
            'multiple - implicit - values' => [
                ['firstName' => 'Jon', 'surname' => 'Snow', 'ageFrom' => 18, 'ageTo' => 30, 'size' => ['DD', 'D']],
                [
                    ['fullName', 'function', 'callable'],
                    ['firstName', 'function-parameter', 'Jon'],
                    ['surname', 'function-parameter', 'Snow'],
                    ['perfectWife', 'function', 'callable'],
                    ['ageFrom', 'function-parameter', 18],
                    ['ageTo', 'function-parameter', 30],
                    ['size', 'function-parameter', ['DD', 'D']],
                ],
            ],
            'multiple - explicit - tuple' => [
                [
                    '(fun,firstName, surname)' => '(fullName,Jon, Snow)',
                    '(fun,ageFrom,ageTo,size)' => '(perfectWife,18,30,[DD;D])',
                ],
                [
                    ['fullName', 'function', 'callable'],
                    ['firstName', 'function-parameter', 'Jon'],
                    ['surname', 'function-parameter', 'Snow'],
                    ['perfectWife', 'function', 'callable'],
                    ['ageFrom', 'function-parameter', 18],
                    ['ageTo', 'function-parameter', 30],
                    ['size', 'function-parameter', ['DD', 'D']],
                ],
            ],
            'multiple - explicit - values' => [
                [
                    'fun' => ['fullName', 'perfectWife'],
                    'size' => ['DD', 'D'],
                    'firstName' => 'Jon',
                    'surname' => 'Snow',
                    'ageFrom' => 18,
                    'ageTo' => 30,
                ],
                [
                    ['fullName', 'function', 'callable'],
                    ['firstName', 'function-parameter', 'Jon'],
                    ['surname', 'function-parameter', 'Snow'],
                    ['perfectWife', 'function', 'callable'],
                    ['ageFrom', 'function-parameter', 18],
                    ['ageTo', 'function-parameter', 30],
                    ['size', 'function-parameter', ['DD', 'D']],
                ],
            ],
            'sql by single value' => [
                ['sql' => 'SELECT * FROM table'],
                [
                    ['sql', 'function', 'callable'],
                    ['query', 'function-parameter', 'SELECT * FROM table'],
                ],
            ],
            'explicit sql by tuple' => [
                ['(fun,query)' => '(sql, "SELECT * FROM table")'],
                [
                    ['sql', 'function', 'callable'],
                    ['query', 'function-parameter', 'SELECT * FROM table'],
                ],
            ],
            'explicit sql by values' => [
                ['fun' => ['sql'], 'query' => 'SELECT * FROM table'],
                [
                    ['sql', 'function', 'callable'],
                    ['query', 'function-parameter', 'SELECT * FROM table'],
                ],
            ],
            'implicit sql by value' => [
                ['query' => 'SELECT * FROM table'],
                [
                    ['sql', 'function', 'callable'],
                    ['query', 'function-parameter', 'SELECT * FROM table'],
                ],
            ],
        ];
    }

    /**
     * @test
     */
    public function shouldNotSupportWithoutQueryParameters(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Query parameters must be set to FunctionParser.');

        $this->parser->supports('foo', 'bar');
    }

    /**
     * @test
     */
    public function shouldNotParseWithoutQueryParameters(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Query parameters must be set to FunctionParser.');

        foreach ($this->parser->parse('foo', 'bar') as $filter) {
            $this->fail('This should not be reached');
        }
    }

    /**
     * @test
     */
    public function shouldNotParseFunctionDefinedBadly(): void
    {
        // ?fullName=Jon,Snow
        $column = 'fullName';
        $value = 'Jon,Snow';
        $this->parser->setQueryParameters([$column => $value]);

        $this->assertTrue($this->parser->supports($column, $value));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Direct function definition must have a tuple value.');

        foreach ($this->parser->parse($column, $value) as $filter) {
            // just iterate through
            continue;
        }

        $this->fail('This should not be reached');
    }

    /**
     * @test
     */
    public function shouldNotCallOneFunctionTwice(): void
    {
        // ?fullName[]=(Jon,Snow)&fullName[]=(Peter,Parker)
        $column = 'fullName';
        $value = ['(Jon,Snow)', '(Peter,Parker)'];
        $this->parser->setQueryParameters([$column => $value]);

        $this->assertTrue($this->parser->supports($column, $value));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Direct function definition must have a tuple value.');

        foreach ($this->parser->parse($column, $value) as $filter) {
            // just iterate through
            continue;
        }

        $this->fail('This should not be reached');
    }

    /**
     * @test
     */
    public function shouldNotCallOneFunctionTwiceByDifferentDefinitions(): void
    {
        // ?fun[]=fullName&firstName=Jon&surname=Snow&fullName=(Peter,Parker)
        $queryParameters = [
            'fun' => ['fullName'],
            'firstName' => 'Jon',
            'surname' => 'Snow',
            'fullName' => '(Peter,Parker)',
        ];

        $this->parser->setQueryParameters($queryParameters);

        foreach ($queryParameters as $column => $value) {
            $this->assertTrue($this->parser->supports($column, $value));
        }

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('It is not allowed to call one function multiple times.');

        foreach ($queryParameters as $column => $value) {
            foreach ($this->parser->parse($column, $value) as $filter) {
                // just iterate through
                continue;
            }
        }
    }

    /**
     * @test
     */
    public function shouldNotParseFunctionByExplicitValueDefinition(): void
    {
        // ?fun=fullName&firstName=Jon&surname=Snow
        $queryParameters = ['fun' => 'fullName', 'firstName' => 'Jon', 'surname' => 'Snow'];

        $this->parser->setQueryParameters($queryParameters);

        foreach ($queryParameters as $column => $value) {
            $this->assertTrue($this->parser->supports($column, $value));
        }

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Explicit function definition by values must be an array of functions. fullName given.');

        foreach ($queryParameters as $column => $value) {
            foreach ($this->parser->parse($column, $value) as $filter) {
                $this->fail('This should not be reached.');
            }
        }
    }

    /**
     * @test
     */
    public function shouldParseImplicitFunctionWhereThereIsNotOnlyOneOption(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('There is already a function "sql" with parameter "query" registered. Parameters must be unique.');

        $this->functions->register('sql2', ['query'], $this->createBlankCallback('sql2'));
    }
}
