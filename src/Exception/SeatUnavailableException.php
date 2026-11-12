<?php

declare(strict_types=1);

namespace App\Exception;

/** Une place demandée est déjà réservée ou vendue. */
final class SeatUnavailableException extends ReservationException
{
}