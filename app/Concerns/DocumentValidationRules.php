<?php

declare(strict_types=1);

namespace App\Concerns;

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Models\Document;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;
use Stringable;

trait DocumentValidationRules
{
    /**
     * Get the validation rules used to validate invoices and quotations.
     *
     * @return array<string, array<int, ValidationRule|Stringable|array<mixed>|string>>
     */
    protected function documentRules(DocumentType $type): array
    {
        return [
            'status' => $this->statusRules($type),
            'client_name' => ['required', 'string', 'max:255'],
            'client_email' => ['nullable', 'email', 'max:255'],
            'client_address' => ['nullable', 'string', 'max:1000'],
            'issue_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:issue_date'],
            'currency' => ['required', 'string', Rule::in(Document::CURRENCIES)],
            'tax_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'discount' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01', 'max:99999'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0', 'max:99999999'],
        ];
    }

    /**
     * Get the validation rules used to validate the status of the given document type.
     *
     * @return array<int, ValidationRule|Stringable|array<mixed>|string>
     */
    protected function statusRules(DocumentType $type): array
    {
        return ['required', Rule::enum(DocumentStatus::class)->only($type->statuses())];
    }
}
