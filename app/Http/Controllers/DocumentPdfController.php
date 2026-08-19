<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Documents\RenderDocumentPdf;
use App\Models\Document;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\HeaderUtils;

final class DocumentPdfController extends Controller
{
    /**
     * Download the given invoice or quotation as a PDF file.
     */
    public function __invoke(Request $request, Document $document, RenderDocumentPdf $render): Response
    {
        Gate::authorize('view', $document);

        return response($render->handle($document, $request->user() ?? throw new AuthenticationException), Response::HTTP_OK, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => HeaderUtils::makeDisposition(
                HeaderUtils::DISPOSITION_ATTACHMENT,
                "{$document->number}.pdf",
            ),
        ]);
    }
}
