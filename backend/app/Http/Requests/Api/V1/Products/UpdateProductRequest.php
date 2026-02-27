<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Products;

use App\Enums\ProductPriceType;
use App\Enums\ProductType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

/**
 * @property-read string|null $name
 * @property-read string|null $type
 * @property-read float|null $price
 * @property-read string|null $price_type
 * @property-read float|null $vat_rate
 * @property-read string|null $currency_code
 * @property-read int|null $duration
 * @property-read string|null $duration_unit
 * @property-read string|null $description
 * @property-read string|null $short_description
 * @property-read string|null $sku
 * @property-read array<int, string>|null $tags
 * @property-read bool|null $is_active
 */
class UpdateProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'type' => ['sometimes', 'string', new Enum(ProductType::class)],
            'price' => ['sometimes', 'numeric', 'min:0'],
            'price_type' => ['sometimes', 'string', new Enum(ProductPriceType::class)],
            'vat_rate' => ['sometimes', 'numeric', 'between:0,100'],
            'currency_code' => ['sometimes', 'string', 'size:3'],
            'duration' => ['nullable', 'sometimes', 'integer', 'min:1'],
            'duration_unit' => ['nullable', 'sometimes', 'string', \Illuminate\Validation\Rule::in(['hours', 'days', 'weeks', 'months'])],
            'description' => ['nullable', 'sometimes', 'string', 'max:5000'],
            'short_description' => ['nullable', 'sometimes', 'string', 'max:500'],
            'sku' => ['nullable', 'sometimes', 'string', 'max:100'],
            'tags' => ['nullable', 'sometimes', 'array'],
            'tags.*' => ['string', 'max:50'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('is_active')) {
            $this->merge([
                'is_active' => filter_var($this->is_active, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? true,
            ]);
        }
    }
}
