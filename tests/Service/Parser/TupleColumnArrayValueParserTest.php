<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Service\Parser;

/**
 * @covers \Lmc\ApiFilter\Service\Parser\TupleColumnArrayValueParser
 */
class TupleColumnArrayValueParserTest extends AbstractParserTestCase
{
    protected function setUp(): void
    {
        $this->parser = new TupleColumnArrayValueParser($this->mockFilterFactory());
    }

    public function provideNotSupportedColumnAndValue(): array
    {
        return self::CASE_TUPLE_COLUMN_AND_TUPLE_VALUE
            + self::CASE_TUPLE_COLUMN_WITH_FILTER_AND_TUPLE_VALUE
            + self::CASE_SCALAR_COLUMN_AND_ARRAY_VALUE
            + self::CASE_TUPLE_COLUMN_AND_TUPLE_VALUE_IMPLICIT_FILTERS
            + self::CASE_SCALAR_COLUMN_AND_TUPLE_VALUE
            + self::CASE_TUPLE_COLUMN_AND_SCALAR_VALUE
            + self::CASE_SCALAR_COLUMN_AND_SCALAR_VALUE
            + self::CASE_SCALAR_COLUMN_AND_ARRAY_VALUES;
    }

    public function provideParseableColumnAndValue(): array
    {
        return self::CASE_TUPLE_COLUMN_AND_ARRAY_VALUE;
    }

    /**
     * @test
     */
    public function shouldNotCreateFilterForInInTuple(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Tuples are not allowed in IN filter.');

        foreach ($this->parser->parse('(col1,col2)', ['in' => '([1;2],[3;4])']) as $item) {
            $this->fail('This should not get here.');
        }
    }

    /**
     * @test
     */
    public function shouldNotCreateFilterForFilterInBothColumnAndValue(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Filters can be specified either in columns or in values - not in both');

        foreach ($this->parser->parse('(col1[gt],col2[lt])', ['gte' => '(1,3)']) as $item) {
            $this->fail('This should not get here.');
        }
    }
}
