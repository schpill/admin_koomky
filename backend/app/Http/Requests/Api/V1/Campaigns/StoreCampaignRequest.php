<?php

namespace App\Http\Requests\Api\V1\Campaigns;

use App\Models\Campaign;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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
        $isAbTest = filter_var($this->input('is_ab_test', false), FILTER_VALIDATE_BOOLEAN);
        $isEmailCampaign = (string) $this->input('type', $routeCampaignType ?? '') === 'email';
        $requiresRootSubject = $isEmailCampaign && ! $isAbTest && ! $isUpdate;
        $requiresRootContent = ! ($isEmailCampaign && $isAbTest);

        return [
            'name' => [$required, 'string', 'max:255'],
            'type' => [$required, Rule::in(['email', 'sms'])],
            'is_ab_test' => ['nullable', 'boolean'],
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
                Rule::requiredIf(fn (): bool => $requiresRootSubject),
            ],
            'content' => [
                $requiresRootContent ? $required : 'nullable',
                'string',
                Rule::requiredIf(fn (): bool => $requiresRootContent && ! $isUpdate),
                'min:1',
            ],
            'scheduled_at' => ['nullable', 'date', 'after:now'],
            'use_sto' => ['nullable', 'boolean'],
            'sto_window_hours' => ['nullable', 'integer', 'min:1', 'max:48'],
            'settings' => ['nullable', 'array'],
            'settings.throttle_rate_per_minute' => ['nullable', 'integer', 'min:1', 'max:2000'],
            'attachments' => ['nullable', 'array'],
            'attachments.*.filename' => ['required_with:attachments', 'string', 'max:255'],
            'attachments.*.path' => ['required_with:attachments', 'string', 'max:500'],
            'attachments.*.mime_type' => ['required_with:attachments', 'string', 'max:100'],
            'attachments.*.size_bytes' => ['required_with:attachments', 'integer', 'min:1'],
            'variants' => [
                Rule::requiredIf(fn (): bool => $isEmailCampaign && $isAbTest),
                'array',
                'min:2',
                'max:2',
            ],
            'variants.*.label' => ['required_with:variants', Rule::in(['A', 'B'])],
            'variants.*.subject' => ['nullable', 'string', 'max:255'],
            'variants.*.content' => ['nullable', 'string'],
            'variants.*.send_percent' => ['required_with:variants', 'integer', 'min:1', 'max:99'],
            'ab_winner_criteria' => ['nullable', Rule::in(['open_rate', 'click_rate', 'manual'])],
            'ab_auto_select_after_hours' => ['nullable', 'integer', 'min:1', 'max:72'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $type = (string) $this->input('type');
            $isAbTest = filter_var($this->input('is_ab_test', false), FILTER_VALIDATE_BOOLEAN);

            if (! ($type === 'email' && $isAbTest)) {
                return;
            }

            $variants = $this->input('variants');
            if (! is_array($variants) || $variants === []) {
                return;
            }

            $labels = [];
            $sum = 0;
            foreach ($variants as $variant) {
                if (! is_array($variant)) {
                    continue;
                }
                $labels[] = (string) ($variant['label'] ?? '');
                $sum += (int) ($variant['send_percent'] ?? 0);
            }

            sort($labels);
            if ($labels !== ['A', 'B']) {
                $validator->errors()->add('variants', 'A/B testing requires exactly two variants with labels A and B.');
            }

            if ($sum !== 100) {
                $validator->errors()->add('variants', 'The sum of variants send_percent must equal 100.');
            }

        });
    }
}
