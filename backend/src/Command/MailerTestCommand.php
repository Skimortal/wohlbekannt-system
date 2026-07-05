<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

/**
 * Sends a plain test email via the configured MAILER_DSN — to verify SMTP
 * credentials/connectivity without creating a throwaway user or triggering
 * the real password-reset flow.
 *
 *   php bin/console app:mailer-test office@example.com
 */
#[AsCommand(name: 'app:mailer-test', description: 'Send a test email via the configured mailer to check SMTP.')]
class MailerTestCommand extends Command
{
    public function __construct(private readonly MailerInterface $mailer)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('to', InputArgument::REQUIRED, 'Empfänger-Adresse');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $to = (string) $input->getArgument('to');
        $from = $_SERVER['MAILER_FROM'] ?? $_ENV['MAILER_FROM'] ?? 'noreply@wohlbekannt.at';

        $email = (new Email())
            ->from($from)
            ->to($to)
            ->subject('wohlbekannt.at — SMTP-Test')
            ->text(
                "Dies ist eine automatische Testmail des wohlbekannt-Systems.\n".
                "Wenn du sie erhältst, funktioniert der SMTP-Versand (z. B. für \"Passwort vergessen\").\n"
            );

        try {
            $this->mailer->send($email);
        } catch (\Throwable $e) {
            $io->error(sprintf('Versand fehlgeschlagen: %s', $e->getMessage()));

            return Command::FAILURE;
        }

        $io->success(sprintf('Testmail an %s (von %s) versendet.', $to, $from));

        return Command::SUCCESS;
    }
}
