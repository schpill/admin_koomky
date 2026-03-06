<?php

namespace App\Http\Requests\Api\V1\Campaigns;

use App\Models\Campaign;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCampaignTestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $emails = $this->input('emails');
        $phones = $this->input('phones');
        $email = $this->input('email');
        $phone = $this->input('phone');

        $payload = [];

        if ($emails === null && is_string($email) && $email !== '') {
            $payload['emails'] = [$email];
        }

        if ($phones === null && is_string($phone) && $phone !== '') {
            $payload['phones'] = [$phone];
        }

        if ($payload !== []) {
            $this->merge($payload);
        }
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $campaign = $this->route('campaign');
        $campaignType = $campaign instanceof Campaign ? $campaign->type : 'email';

        return [
            'emails' => [
                'nullable',
                Rule::requiredIf($campaignType === 'email'),
                'array',
                'min:1',
                'max:5',
            ],
            'emails.*' => ['email', 'max:255'],
            'phones' => [
                'nullable',
                Rule::requiredIf($campaignType === 'sms'),
                'array',
                'min:1',
                'max:3',
            ],
            'phones.*' => ['string', 'max:20'],
        ];
    }
}
