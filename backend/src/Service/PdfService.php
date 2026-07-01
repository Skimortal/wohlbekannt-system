<?php

namespace App\Service;

use App\Entity\CompanySettings;
use App\Entity\Invoice;
use App\Entity\Quote;
use App\Repository\CompanySettingsRepository;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;
use Twig\Environment;

/**
 * Renders documents to PDF with mpdf (pure PHP — runs on shared hosting, no
 * external service). The Twig templates produce mpdf-friendly HTML (table-based
 * layout); the footer is repeated on every page via SetHTMLFooter.
 */
class PdfService
{
    public function __construct(
        private readonly Environment $twig,
        private readonly CompanySettingsRepository $settingsRepo,
        private readonly string $assetsDir,
    ) {
    }

    public function renderQuote(Quote $quote): string
    {
        $settings = $this->settingsRepo->findOneBy([]) ?? new CompanySettings();

        $html = $this->twig->render('pdf/quote.html.twig', [
            'quote' => $quote,
            'company' => $settings,
            'logoSrc' => $this->logoSrc($settings),
        ]);
        $footer = $this->twig->render('pdf/_footer.html.twig', ['company' => $settings]);

        return $this->htmlToPdf($html, $footer);
    }

    public function renderInvoice(Invoice $invoice): string
    {
        $settings = $this->settingsRepo->findOneBy([]) ?? new CompanySettings();

        $html = $this->twig->render('pdf/invoice.html.twig', [
            'invoice' => $invoice,
            'company' => $settings,
            'logoSrc' => $this->logoSrc($settings),
        ]);
        $footer = $this->twig->render('pdf/_footer.html.twig', ['company' => $settings]);

        return $this->htmlToPdf($html, $footer);
    }

    private function logoSrc(CompanySettings $settings): ?string
    {
        $name = $settings->getLogoPath();
        if (!$name) {
            return null;
        }
        $path = rtrim($this->assetsDir, '/').'/pdf/'.basename($name);

        return is_file($path) ? $path : null;
    }

    private function htmlToPdf(string $html, string $footer): string
    {
        $tmp = dirname($this->assetsDir).'/var/mpdf';
        if (!is_dir($tmp)) {
            @mkdir($tmp, 0775, true);
        }

        $mpdf = new Mpdf([
            'format' => 'A4',
            'margin_top' => 16,
            'margin_bottom' => 30,
            'margin_left' => 16,
            'margin_right' => 16,
            'tempDir' => $tmp,
        ]);
        $mpdf->SetHTMLFooter($footer);
        $mpdf->WriteHTML($html);

        return $mpdf->Output('', Destination::STRING_RETURN);
    }
}
