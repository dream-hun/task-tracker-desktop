<?php

declare(strict_types=1);

namespace App\Http\Requests\Documents;

use App\Concerns\DocumentValidationRules;
use App\Models\Document;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateDocumentStatusRequest extends FormRequest
{
    use DocumentValidationRules;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Document $document */
        $document = $this->route('document');

        return ['status' => $this->statusRules($document->type)];
    }
}
