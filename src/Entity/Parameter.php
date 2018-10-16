<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Entity;

use Lmc\ApiFilter\Constant\Filter;

class Parameter
{
    /** @var string */
    private $name;
    /** @var string */
    private $filter;
    /** @var string */
    private $column;
    /** @var ?Value */
    private $defaultValue;

    public static function equalToDefaultValue(string $name, Value $defaultValue): self
    {
        return new self($name, null, null, $defaultValue);
    }

    public function __construct(
        string $name,
        ?string $filter = Filter::EQUALS,
        ?string $column = null,
        ?Value $defaultValue = null
    ) {
        $this->name = $name;
        $this->filter = $filter ?? Filter::EQUALS;
        $this->column = $column ?? $name;
        $this->defaultValue = $defaultValue;
    }
}
