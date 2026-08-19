<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

final class DocumentPrintController extends Controller
{
    /**
     * Show a printable version of the given invoice or quotation.
     */
    public function __invoke(Request $request, Document $document): Response
    {
        Gate::authorize('view', $document);

        $document->load(['items', 'convertedFrom']);

        $user = $request->user() ?? throw new AuthenticationException;

        return Inertia::render('documents/print', [
            'document' => $document,
            'issuer' => [
                'name' => $user->name,
                'email' => $user->email,
            ],
        ]);
    }
}
