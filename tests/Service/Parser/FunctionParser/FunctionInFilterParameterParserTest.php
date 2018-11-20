<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Service\Parser\FunctionParser;

/**
 * @group unit
 * @covers \Lmc\ApiFilter\Service\Parser\FunctionParser\FunctionInFilterParameterParser
 */
class FunctionInFilterParameterParserTest extends AbstractFunctionParserTestCase
{
    protected function setUp(): void
    {
        $this->parser = new FunctionInFilterParameterParser($this->mockFilterFactory(), $this->initFunctions());
    }

    public function provideNotQueryParameters(): array
    {
        return self::CASE_EXPLICIT_FUNCTION_DEFINITION
            + self::CASE_EXPLICIT_FUNCTION_DEFINITION_BY_VALUES
            + self::CASE_EXPLICIT_FUNCTION_DEFINITION_BY_TUPLE
            + self::CASE_IMPLICIT_FUNCTION_DEFINITION_BY_TUPLE
            + self::CASE_IMPLICIT_FUNCTION_DEFINITION_BY_VALUES;
    }

    public function provideParseableQueryParameters(): array
    {
        return self::CASE_FUNCTION_IN_FILTER_PARAMETER;
    }
}
