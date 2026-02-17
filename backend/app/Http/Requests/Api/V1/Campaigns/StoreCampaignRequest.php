<?php

namespace App\Http\Requests\Api\V1\Campaigns;

use App\Models\Campaign;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $isUpdate = $this->isMethod('PUT') || $this->isMethod('PATCH');
        $required = $isUpdate ? 'sometimes' : 'required';
        $routeCampaign = $this->route('campaign');
        $routeCampaignType = $routeCampaign instanceof Campaign ? $routeCampaign->type : null;

        return [
            'name' => [$required, 'string', 'max:255'],
            'type' => [$required, Rule::in(['email', 'sms'])],
            'segment_id' => [
                'nullable',
                'uuid',
                Rule::exists('segments', 'id')->where(function ($query): void {
                    $query->where('user_id', $this->user()?->id);
                }),
            ],
            'template_id' => [
                'nullable',
                'uuid',
                Rule::exists('campaign_templates', 'id')->where(function ($query): void {
                    $query->where('user_id', $this->user()?->id);
                }),
            ],
            'status' => ['nullable', Rule::in(['draft', 'scheduled', 'sending', 'sent', 'paused', 'cancelled'])],
            'subject' => [
                'nullable',
                'string',
                'max:255',
                Rule::requiredIf(fn (): bool => (string) $this->input('type', $routeCampaignType ?? '') === 'email' && ! $isUpdate),
            ],
            'content' => [$required, 'string', 'min:1'],
            'scheduled_at' => ['nullable', 'date', 'after:now'],
            'settings' => ['nullable', 'array'],
            'settings.throttle_rate_per_minute' => ['nullable', 'integer', 'min:1', 'max:2000'],
            'attachments' => ['nullable', 'array'],
            'attachments.*.filename' => ['required_with:attachments', 'string', 'max:255'],
            'attachments.*.path' => ['required_with:attachments', 'string', 'max:500'],
            'attachments.*.mime_type' => ['required_with:attachments', 'string', 'max:100'],
            'attachments.*.size_bytes' => ['required_with:attachments', 'integer', 'min:1'],
        ];
    }
}
