<?php

declare(strict_types=1);

namespace App\Actions\Documents;

use App\Models\Document;
use App\Models\User;
use Dompdf\Dompdf;
use Dompdf\Options;

final class RenderDocumentPdf
{
    /**
     * Render the document as a PDF file and return its raw bytes.
     */
    public function handle(Document $document, User $issuer): string
    {
        $document->loadMissing('items');

        $dompdf = new Dompdf($this->options());
        $dompdf->setPaper('a4');
        $dompdf->loadHtml(view('documents.pdf', [
            'document' => $document,
            'issuer' => $issuer,
        ])->render(), 'UTF-8');
        $dompdf->render();

        return (string) $dompdf->output();
    }

    /**
     * Render the document without reaching out to the network for assets.
     */
    private function options(): Options
    {
        $options = new Options;
        $options->setIsRemoteEnabled(false);
        $options->setIsPhpEnabled(false);
        $options->setDefaultFont('DejaVu Sans');
        $options->setDefaultPaperSize('a4');

        return $options;
    }
}
