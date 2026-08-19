<?php

declare(strict_types=1);

namespace App\Http\Requests\Tasks;

use App\Concerns\TaskValidationRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class StoreTaskRequest extends FormRequest
{
    use TaskValidationRules;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->taskRules();
    }
}
