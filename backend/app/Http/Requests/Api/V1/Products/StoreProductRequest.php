<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Products;

use App\Enums\ProductType;
use App\Enums\ProductPriceType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

/**
 * @property-read string $name
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
class StoreProductRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', new Enum(ProductType::class)],
            'price' => ['required', 'numeric', 'min:0'],
            'price_type' => ['required', 'string', new Enum(ProductPriceType::class)],
            'vat_rate' => ['required', 'numeric', 'between:0,100'],
            'currency_code' => ['required', 'string', 'size:3'],
            'duration' => ['nullable', 'integer', 'min:1'],
            'duration_unit' => ['nullable', 'string', Rule::in(['hours', 'days', 'weeks', 'months'])],
            'description' => ['nullable', 'string', 'max:5000'],
            'short_description' => ['nullable', 'string', 'max:500'],
            'sku' => ['nullable', 'string', 'max:100'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:50'],
            'is_active' => ['boolean'],
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
