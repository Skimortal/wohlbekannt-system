<?php

namespace App\Service;

use App\Entity\CompanySettings;
use App\Entity\Invoice;
use App\Entity\Quote;
use App\Repository\CompanySettingsRepository;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\Multipart\FormDataPart;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Twig\Environment;

/**
 * Renders documents to PDF via Gotenberg (Chromium HTML -> PDF). The Twig
 * template produces print HTML; the logo is shipped alongside it so Gotenberg
 * can embed it. GOTENBERG_URL is configurable for native (non-docker) deploys.
 */
class PdfService
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly Environment $twig,
        private readonly CompanySettingsRepository $settingsRepo,
        private readonly string $gotenbergUrl,
        private readonly string $assetsDir,
    ) {
    }

    public function renderQuote(Quote $quote): string
    {
        $settings = $this->settingsRepo->findOneBy([]) ?? new CompanySettings();
        $logoFile = $this->logoFile($settings);

        $html = $this->twig->render('pdf/quote.html.twig', [
            'quote' => $quote,
            'company' => $settings,
            'logoName' => $logoFile ? basename($logoFile) : null,
        ]);
        $footer = $this->twig->render('pdf/_footer.html.twig', ['company' => $settings]);

        return $this->htmlToPdf($html, $footer, $logoFile);
    }

    public function renderInvoice(Invoice $invoice): string
    {
        $settings = $this->settingsRepo->findOneBy([]) ?? new CompanySettings();
        $logoFile = $this->logoFile($settings);

        $html = $this->twig->render('pdf/invoice.html.twig', [
            'invoice' => $invoice,
            'company' => $settings,
            'logoName' => $logoFile ? basename($logoFile) : null,
        ]);
        $footer = $this->twig->render('pdf/_footer.html.twig', ['company' => $settings]);

        return $this->htmlToPdf($html, $footer, $logoFile);
    }

    private function logoFile(CompanySettings $settings): ?string
    {
        $name = $settings->getLogoPath();
        if (!$name) {
            return null;
        }
        $path = rtrim($this->assetsDir, '/').'/pdf/'.basename($name);

        return is_file($path) ? $path : null;
    }

    private function htmlToPdf(string $html, ?string $footerHtml, ?string $logoFile): string
    {
        $parts = [
            'index.html' => new DataPart($html, 'index.html', 'text/html'),
            // A4 in inches; margins roughly 18/16/32/16 mm (top/sides/bottom)
            'paperWidth' => '8.27',
            'paperHeight' => '11.69',
            'marginTop' => '0.71',
            'marginBottom' => '1.30',
            'marginLeft' => '0.63',
            'marginRight' => '0.63',
            'printBackground' => 'true',
        ];
        if (null !== $footerHtml) {
            $parts['footer.html'] = new DataPart($footerHtml, 'footer.html', 'text/html');
        }
        if ($logoFile) {
            $parts['logo'] = DataPart::fromPath($logoFile);
        }

        $form = new FormDataPart($parts);

        $response = $this->httpClient->request(
            'POST',
            rtrim($this->gotenbergUrl, '/').'/forms/chromium/convert/html',
            [
                'headers' => $form->getPreparedHeaders()->toArray(),
                // Pass the generator directly: an array body would be re-encoded
                // as application/x-www-form-urlencoded and drop the multipart header.
                'body' => $form->bodyToIterable(),
            ]
        );

        return $response->getContent();
    }
}
