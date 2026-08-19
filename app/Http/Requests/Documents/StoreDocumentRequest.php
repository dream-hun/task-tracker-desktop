<?php

namespace App\Http\Requests\Documents;

use App\Concerns\DocumentValidationRules;
use App\Enums\DocumentType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDocumentRequest extends FormRequest
{
    use DocumentValidationRules;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(DocumentType::class)],
            ...$this->documentRules($this->documentType()),
        ];
    }

    /**
     * Get the type of the document being created, falling back to an invoice.
     */
    public function documentType(): DocumentType
    {
        return DocumentType::tryFrom($this->string('type')->toString()) ?? DocumentType::Invoice;
    }
}
