<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Filter;

use Lmc\ApiFilter\AbstractTestCase;
use Lmc\ApiFilter\Entity\Value;

class FilterFunctionTest extends AbstractTestCase
{
    /**
     * @test
     */
    public function shouldCreateFilterInWithProperDefaults(): void
    {
        $filterFunction = new FilterFunction('fullName', new Value($this->createBlankCallback('fullName')));

        $this->assertSame('fun', $filterFunction->getTitle());
    }

    /**
     * @test
     */
    public function shouldCreateFilterFunction(): void
    {
        $filterFunction = new FilterFunction(
            'fooFunction',
            new Value(function () {
                return 'fooBar';
            }),
            'foo'
        );

        $this->assertSame('fooFunction', $filterFunction->getColumn());
        $this->assertSame('foo', $filterFunction->getTitle());
        $this->assertSame('fooBar', $filterFunction->getValue()->getValue()());
    }

    /**
     * @test
     */
    public function shouldNotCreateFilterFunction(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Value for filter function must be callable. "not-callable" given.');

        new FilterFunction('column', new Value('not-callable'));
    }
}
