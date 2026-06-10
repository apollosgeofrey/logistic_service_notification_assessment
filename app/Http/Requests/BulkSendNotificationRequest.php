<?php

namespace App\Http\Requests;

use App\Models\Notification;
use App\Models\Subscriber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class BulkSendNotificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'channel' => ['required', Rule::in([Notification::CHANNEL_EMAIL, Notification::CHANNEL_SMS])],
            'content' => ['required', 'string'],
            'priority' => ['required', Rule::in([
                Notification::PRIORITY_CRITICAL,
                Notification::PRIORITY_DEFAULT,
                Notification::PRIORITY_MARKETING,
            ])],
            'subscriber_ids' => ['required', 'array', 'min:1'],
            'subscriber_ids.*' => ['integer', 'distinct', 'exists:subscribers,id'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $channel = $this->input('channel');
            $contactField = $channel === Notification::CHANNEL_EMAIL ? 'email' : 'phone';

            $subscribers = Subscriber::query()
                ->whereIn('id', $this->input('subscriber_ids', []))
                ->get()
                ->keyBy('id');

            foreach ($this->input('subscriber_ids', []) as $index => $subscriberId) {
                $subscriber = $subscribers->get($subscriberId);

                if ($subscriber && empty($subscriber->{$contactField})) {
                    $validator->errors()->add(
                        "subscriber_ids.{$index}",
                        "Subscriber {$subscriberId} does not have a {$contactField} for the {$channel} channel."
                    );
                }
            }
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function idempotencyPayload(): array
    {
        return $this->only(['channel', 'content', 'priority', 'subscriber_ids']);
    }
}
