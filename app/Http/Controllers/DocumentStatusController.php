<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Documents\UpdateDocumentStatusRequest;
use App\Models\Document;
use Illuminate\Http\RedirectResponse;

final class DocumentStatusController extends Controller
{
    /**
     * Update the status of the given invoice or quotation.
     */
    public function __invoke(UpdateDocumentStatusRequest $request, Document $document): RedirectResponse
    {
        $document->update($request->validated());

        return back();
    }
}
