<?php declare(strict_types=1);

namespace Lmc\ApiFilter\Exception;

class ApiFilterException extends \Exception
{
    public static function createFrom(\Throwable $exception): self
    {
        return new self($exception->getMessage(), 0, $exception);
    }
}
