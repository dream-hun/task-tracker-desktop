<?php

namespace App\Http\Controllers;

use App\Http\Requests\Documents\UpdateDocumentStatusRequest;
use App\Models\Document;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class DocumentStatusController extends Controller
{
    /**
     * Update the status of the given invoice or quotation.
     */
    public function __invoke(UpdateDocumentStatusRequest $request, Document $document): RedirectResponse
    {
        Gate::authorize('update', $document);

        $document->update($request->validated());

        return back();
    }
}
