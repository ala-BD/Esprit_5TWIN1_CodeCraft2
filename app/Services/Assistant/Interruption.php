<?php

namespace App\Services\Assistant;

use RuntimeException;

/**
 * L'action ne peut pas aboutir (cible introuvable, donnée invalide, accès refusé…).
 * Le message est destiné à être lu à l'utilisateur.
 */
class Interruption extends RuntimeException
{
}
