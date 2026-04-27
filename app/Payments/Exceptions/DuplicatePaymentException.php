<?php

declare(strict_types=1);

namespace App\Payments\Exceptions;

class DuplicatePaymentException extends \RuntimeException
{
    public function __construct(public readonly string $externalEventId)
    {
        parent::__construct("Event {$externalEventId} already processed.");
    }
}
