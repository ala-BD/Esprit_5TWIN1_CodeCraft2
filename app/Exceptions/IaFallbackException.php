<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Levée par IaClient quand l'appel à l'IA échoue : porte la raison du passage au plan B.
 */
class IaFallbackException extends RuntimeException
{
    public function __construct(public readonly string $raison, string $detail = '')
    {
        parent::__construct($detail !== '' ? $detail : $raison);
    }
}
