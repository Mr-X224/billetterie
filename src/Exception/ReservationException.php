<?php

declare(strict_types=1);

namespace App\Exception;

/** Règle métier de réservation non respectée (message affichable à l'utilisateur). */
class ReservationException extends \DomainException
{
}