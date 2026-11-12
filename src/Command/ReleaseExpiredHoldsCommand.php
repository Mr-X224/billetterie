<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\ReservationService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:release-expired-holds', description: 'Libère les places dont la réservation temporaire a expiré')]
final class ReleaseExpiredHoldsCommand extends Command
{
    public function __construct(private readonly ReservationService $reservations)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $count = $this->reservations->releaseExpired();
        $output->writeln(sprintf('%d commande(s) expirée(s), places libérées.', $count));

        return Command::SUCCESS;
    }
}