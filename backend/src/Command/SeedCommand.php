<?php

namespace App\Command;

use App\Entity\Article;
use App\Entity\CompanySettings;
use App\Entity\NumberRange;
use App\Enum\TaxCategory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Idempotent seed: company settings (RS Wohlbekannt OG), number ranges, and a
 * starter service catalog taken from the customer's example quote AN-1032.
 */
#[AsCommand(name: 'app:seed', description: 'Seed company settings, number ranges and the service catalog.')]
class SeedCommand extends Command
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $this->seedCompany($io);
        $this->seedNumberRanges($io);
        $this->seedCatalog($io);

        $this->em->flush();
        $io->success('Seed abgeschlossen.');

        return Command::SUCCESS;
    }

    private function seedCompany(SymfonyStyle $io): void
    {
        $repo = $this->em->getRepository(CompanySettings::class);
        if ($repo->count([]) > 0) {
            $io->writeln('CompanySettings: bereits vorhanden, übersprungen.');

            return;
        }

        $c = new CompanySettings();
        $c->setLegalName('RS Wohlbekannt OG');
        $addr = $c->getAddress();
        $addr->street = 'Huberangerweg 7';
        $addr->postalCode = '6175';
        $addr->city = 'Kematen in Tirol';
        $addr->countryCode = 'AT';
        $c->setPhone('0670 777 6030');
        $c->setEmail('bar@wohlbekannt.at');
        $c->setWeb('www.wohlbekannt.at');
        $c->setCompanyRegisterNumber('575074y');
        $c->setVatId('ATU77877638');
        $c->setTaxNumber('81 467/7969');
        $c->setManagingDirector('Johannes Reiter');
        $c->setBankName('Tiroler Sparkasse');
        $c->setIban('AT942050303303234318');
        $c->setBic('SPIHAT22XXX');
        $c->setLogoPath('wohlbekannt-logo.svg');
        $c->setDefaultCurrency('EUR');
        $c->setDefaultVatRate('20.00');
        $c->setDefaultPaymentTermsDays(14);
        $c->setDefaultQuoteValidityDays(30);
        $c->setQuoteIntroText(
            "Sehr geehrte Damen und Herren,\n\n".
            'vielen Dank für Ihre Anfrage. Gerne unterbreiten wir Ihnen das gewünschte freibleibende Angebot.'
        );
        $c->setQuoteOutroText(
            "Für Rückfragen stehen wir Ihnen jederzeit gerne zur Verfügung.\n".
            "Wir bedanken uns sehr für Ihr Vertrauen.\n\n".
            "Mit freundlichen Grüßen\nJohannes Reiter"
        );
        $c->setInvoiceIntroText("Sehr geehrte Damen und Herren,\n\nwir erlauben uns, folgende Leistungen in Rechnung zu stellen.");
        $c->setInvoiceOutroText("Vielen Dank für Ihr Vertrauen.\n\nMit freundlichen Grüßen\nJohannes Reiter");

        $this->em->persist($c);
        $io->writeln('CompanySettings: angelegt.');
    }

    private function seedNumberRanges(SymfonyStyle $io): void
    {
        $repo = $this->em->getRepository(NumberRange::class);
        // key => [prefix, nextValue, padding]
        $ranges = [
            NumberRange::KEY_QUOTE => ['AN-', 1033, 4],
            NumberRange::KEY_INVOICE => ['RE-', 1, 4],
            NumberRange::KEY_CREDIT_NOTE => ['GS-', 1, 4],
            NumberRange::KEY_CANCELLATION => ['ST-', 1, 4],
            NumberRange::KEY_CUSTOMER => ['', 1053, 4],
        ];
        foreach ($ranges as $key => [$prefix, $next, $padding]) {
            if (null !== $repo->findOneBy(['rangeKey' => $key])) {
                continue;
            }
            $r = new NumberRange($key, $prefix);
            $r->setNextValue($next);
            $r->setPadding($padding);
            $this->em->persist($r);
        }
        $io->writeln('NumberRanges: sichergestellt.');
    }

    private function seedCatalog(SymfonyStyle $io): void
    {
        $repo = $this->em->getRepository(Article::class);
        // name => [description, unit, unitPriceCents (gross), category]
        $items = [
            ['Sektempfang 2h', "Wir bereiten euch den Sektempfang für eure Gäste. Elegant und klassisch.\nDer Sektempfang wird mit hochwertigen Sektgläsern organisiert.", 'Personen', 2400, 'Empfang'],
            ['Fahrtkostenpauschale Innsbruck/Innsbruck-Land', "An- und Abfahrt,\nGläser,\nBereitstellung mobile Bar", 'pauschal', 16800, 'Logistik'],
            ['Bierpaket 20L (vom Fass)', "Das Bierpaket wird nach Verbrauch verrechnet. Zusätzlich kommen wir mit den passenden Bierkrügl.\nWir empfehlen mit einem 20l Fass zu starten und können dann mit 10l Fässern weiter zapfen.", 'pauschal', 9500, 'Getränke'],
            ['Doppelmagnum Bouvet Crémant', '', 'Stk', 9500, 'Getränke'],
            ['Aperitivempfang 2h', "Das Besondere für eure Gäste. Ihr könnt aus bis zu drei Aperitiv-Varianten auswählen, serviert im ikonischen wohlbekannt Edelstahlbecher.", 'Personen', 2990, 'Empfang'],
        ];
        foreach ($items as [$name, $desc, $unit, $price, $cat]) {
            if (null !== $repo->findOneBy(['name' => $name])) {
                continue;
            }
            $a = new Article();
            $a->setName($name);
            $a->setDescription('' !== $desc ? $desc : null);
            $a->setUnit($unit);
            $a->setUnitPrice($price);
            $a->setVatRate('20.00');
            $a->setTaxCategory(TaxCategory::STANDARD);
            $a->setCategory($cat);
            $this->em->persist($a);
        }
        $io->writeln('Leistungskatalog: sichergestellt.');
    }
}
