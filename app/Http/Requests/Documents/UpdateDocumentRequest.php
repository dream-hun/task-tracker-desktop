<?php

namespace App\Http\Requests\Documents;

use App\Concerns\DocumentValidationRules;
use App\Models\Document;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateDocumentRequest extends FormRequest
{
    use DocumentValidationRules;

    /**
     * Get the validation rules that apply to the request.
     *
     * The type of a document is fixed once it is numbered, so it is not accepted here.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Document $document */
        $document = $this->route('document');

        return $this->documentRules($document->type);
    }
}
