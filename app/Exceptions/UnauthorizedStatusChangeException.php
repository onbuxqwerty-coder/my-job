<?php

declare(strict_types=1);

namespace App\Exceptions;

class UnauthorizedStatusChangeException extends \Exception
{
    public function __construct()
    {
        parent::__construct('Actor is not allowed to set this status.');
    }
}
