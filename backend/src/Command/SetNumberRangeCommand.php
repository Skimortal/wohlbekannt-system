<?php

namespace App\Command;

use App\Entity\NumberRange;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Sets the "next value" of a number range (e.g. to continue an existing
 * sevDesk sequence). One-off / admin use — deliberately NOT part of app:seed
 * or deploy.sh, otherwise every deploy would reset a running counter.
 *
 *   php bin/console app:set-number-range invoice 1037
 *
 * Refuses to move the counter backwards unless --force is given, so it cannot
 * accidentally re-issue numbers that were already assigned.
 */
#[AsCommand(name: 'app:set-number-range', description: 'Set the next value of a number range (e.g. invoice -> 1037).')]
class SetNumberRangeCommand extends Command
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('key', InputArgument::REQUIRED, 'Nummernkreis: quote|invoice|credit_note|cancellation|customer')
            ->addArgument('next', InputArgument::REQUIRED, 'Nächster zu vergebender Wert (Ganzzahl)')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Auch erlauben, den Zähler zurückzusetzen');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $key = (string) $input->getArgument('key');
        $next = (int) $input->getArgument('next');

        if ($next < 1) {
            $io->error('Der nächste Wert muss >= 1 sein.');

            return Command::FAILURE;
        }

        $range = $this->em->getRepository(NumberRange::class)->findOneBy(['rangeKey' => $key]);
        if (!$range instanceof NumberRange) {
            $io->error(sprintf('Nummernkreis "%s" existiert nicht (erst app:seed ausführen).', $key));

            return Command::FAILURE;
        }

        $current = $range->getNextValue();
        $year = (int) (new \DateTimeImmutable())->format('Y');

        if ($next < $current && !$input->getOption('force')) {
            $io->error(sprintf(
                'Abbruch: Zähler steht bereits bei %d, %d wäre ein Rückschritt (Gefahr doppelter Nummern). Mit --force erzwingen.',
                $current,
                $next
            ));

            return Command::FAILURE;
        }

        $range->setNextValue($next);
        $this->em->flush();

        $io->success(sprintf(
            'Nummernkreis "%s": nächster Wert %d -> %d. Nächste Nummer: %s',
            $key,
            $current,
            $next,
            $range->format($next, $year)
        ));

        return Command::SUCCESS;
    }
}
