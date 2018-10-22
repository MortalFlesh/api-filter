<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Service;

use Lmc\ApiFilter\AbstractTestCase;
use Lmc\ApiFilter\Applicator\SqlApplicator;
use Lmc\ApiFilter\Entity\Parameter;
use Lmc\ApiFilter\Entity\Value;
use Lmc\ApiFilter\Filter\FunctionParameter;
use Lmc\ApiFilter\Filters\Filters;

/** @covers \Lmc\ApiFilter\Service\FunctionCreator */
class FunctionCreatorTest extends AbstractTestCase
{
    /** @var FunctionCreator */
    private $functionCreator;
    /** @var FilterApplicator */
    private $filterApplicator;
    /** @var Functions */
    private $functions;

    protected function setUp(): void
    {
        $this->functions = new Functions();
        $this->filterApplicator = new FilterApplicator($this->functions);

        $this->filterApplicator->registerApplicator(new SqlApplicator(), 1);

        $this->functionCreator = new FunctionCreator(
            new FilterFactory()
        );
    }

    /**
     * @test
     * @dataProvider provideParameters
     */
    public function shouldTransformParametersIntoFunctionParameterNames(array $parameters, array $expected): void
    {
        $result = $this->functionCreator->getParameterNames($parameters);

        $this->assertSame($expected, $result);
    }

    public function provideParameters(): array
    {
        return [
            // parameters, expected names
            'empty' => [[], []],
            'by array of names' => [['firstName', 'surname'], ['firstName', 'surname']],
            'by array of explicit definitions' => [[['firstName', 'eq'], ['surname', 'eq']], ['firstName', 'surname']],
            'by array of Parameters' => [
                [new Parameter('firstName', 'eq'), new Parameter('surname', 'eq')],
                ['firstName', 'surname'],
            ],
            'by mixed' => [
                [new Parameter('firstName', 'eq'), 'middleName', ['surname', 'eq']],
                ['firstName', 'middleName', 'surname'],
            ],
            'by mixed with defaults' => [
                [
                    Parameter::equalToDefaultValue('firstName', new Value('Jon')),
                    'middleName',
                    ['surname', null, null, 'Snow'],
                ],
                ['middleName'],
            ],
        ];
    }

    /**
     * @test
     */
    public function shouldCreateFunctionWithImplicitFiltersAndApplyIt(): void
    {
        $sql = 'SELECT * FROM person';

        $firstName = new FunctionParameter('firstName', new Value('Jon'));
        $surname = new FunctionParameter('surname', new Value('Snow'));
        $this->filterApplicator->setFilters(Filters::from([$firstName, $surname]));

        $parameters = ['firstName', 'surname'];
        $functionWithImplicitFilters = $this->functionCreator->createByParameters($this->filterApplicator, $parameters);
        $this->functions->register('fullName', $parameters, $functionWithImplicitFilters);

        $result = $functionWithImplicitFilters($sql, $firstName, $surname);
        $this->assertSame(
            'SELECT * FROM person WHERE 1 AND firstName = :firstName_fun AND surname = :surname_fun',
            $result
        );

        $this->assertSame('firstName_fun', $firstName->getTitle());
        $this->assertSame('surname_fun', $surname->getTitle());
    }

    /**
     * @test
     * @dataProvider providePerfectWifeParameters
     */
    public function shouldCreateFunctionWithExplicitFiltersAndApplyIt(array $parameters): void
    {
        $sql = 'SELECT * FROM person';
        $expectedResult = 'SELECT * FROM person WHERE 1 ' .
            'AND age > :ageFrom_fun AND age < :ageTo_fun ' .
            'AND size IN (:size_fun_0, :size_fun_1) ' .
            'AND gender = :gender_fun';

        $ageFrom = new FunctionParameter('ageFrom', new Value(18));
        $ageTo = new FunctionParameter('ageTo', new Value(30));
        $size = new FunctionParameter('size', new Value(['DD', 'D']));
        $this->filterApplicator->setFilters(Filters::from([$ageFrom, $ageTo, $size]));

        $functionWithImplicitFilters = $this->functionCreator->createByParameters($this->filterApplicator, $parameters);
        $this->functions->register('fullName', ['ageFrom', 'ageTo', 'size'], $functionWithImplicitFilters);

        $result = $functionWithImplicitFilters($sql, $ageFrom, $ageTo, $size);
        $this->assertSame($expectedResult, $result);

        $this->assertSame('ageFrom_fun', $ageFrom->getTitle());
        $this->assertSame('ageTo_fun', $ageTo->getTitle());
        $this->assertSame('size_fun', $size->getTitle());
    }

    public function providePerfectWifeParameters(): array
    {
        return [
            // parameters
            'by array' => [
                [
                    ['ageFrom', 'gt', 'age'],
                    ['ageTo', 'lt', 'age'],
                    ['size', 'in'],
                    ['gender', null, null, 'girl'],
                ],
            ],
            'by parameters' => [
                [
                    new Parameter('ageFrom', 'gt', 'age'),
                    new Parameter('ageTo', 'lt', 'age'),
                    new Parameter('size', 'in'),
                    new Parameter('gender', null, null, new Value('girl')),
                ],
            ],
            'by array + parameters' => [
                [
                    ['ageFrom', 'gt', 'age'],
                    new Parameter('ageTo', 'lt', 'age'),
                    ['size', 'in'],
                    Parameter::equalToDefaultValue('gender', new Value('girl')),
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider provideInvalidParameters
     */
    public function shouldNotCreateFunctionWithInvalidParameter(array $parameters, string $expectedMessage): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedMessage);

        $this->functionCreator->createByParameters($this->filterApplicator, $parameters);
    }

    public function provideInvalidParameters(): array
    {
        return [
            // parameters, expectedMessage
            'int' => [
                [1],
                'Parameter for function creator must be either string, array or instance of Lmc\ApiFilter\Entity\Parameter but "integer" given.',
            ],
            'applicator' => [
                [new Value('foo')],
                'Parameter for function creator must be either string, array or instance of Lmc\ApiFilter\Entity\Parameter but "Lmc\ApiFilter\Entity\Value" given.',
            ],
        ];
    }

    /**
     * @test
     */
    public function shouldNotApplyFunctionWithoutAllParameters(): void
    {
        $firstName = new FunctionParameter('firstName', new Value('Jon'));
        $this->filterApplicator->setFilters(Filters::from([$firstName]));

        $parameters = ['firstName', 'surname'];
        $functionWithImplicitFilters = $this->functionCreator->createByParameters($this->filterApplicator, $parameters);
        $this->functions->register('fullName', $parameters, $functionWithImplicitFilters);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Parameter "surname" is required and must have a value.');

        $functionWithImplicitFilters('SELECT * FROM person', $firstName);
    }
}
