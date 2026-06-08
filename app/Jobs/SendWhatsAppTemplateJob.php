<?php

namespace App\Jobs;

use App\Models\WhatsAppLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Sends a WhatsApp Cloud API template message and records the outcome in whatsapp_logs.
 */
class SendWhatsAppTemplateJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * @param  list<string>  $bodyParameters  Ordered BODY variable values for the Meta template.
     * @param  string|null  $templateLanguageCode  e.g. en_US for Meta sample hello_world; null uses config whatsapp.default_locale.
     */
    public function __construct(
        public string $recipientE164,
        public string $eventType,
        public string $templateName,
        public array $bodyParameters,
        public ?string $referenceType = null,
        public ?int $referenceId = null,
        public ?string $recipientDisplayName = null,
        public ?string $templateLanguageCode = null,
    ) {
        $this->onQueue('default');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $driver = (string) config('whatsapp.driver', 'log');
        $log = WhatsAppLog::query()->create([
            'event_type' => $this->eventType,
            'reference_type' => $this->referenceType,
            'reference_id' => $this->referenceId,
            'recipient_name' => $this->recipientDisplayName,
            'recipient_number' => $this->recipientE164,
            'template_name' => $this->templateName,
            'message_id' => null,
            'status' => WhatsAppLog::STATUS_QUEUED,
            'error_message' => null,
            'sent_at' => null,
        ]);

        try {
            if ($driver === 'log') {
                Log::info('WhatsApp (log driver): template dispatch', [
                    'to' => $this->recipientE164,
                    'template' => $this->templateName,
                    'event' => $this->eventType,
                    'body' => $this->bodyParameters,
                ]);
                $messageId = 'log:'.Str::uuid()->toString();
                $log->update([
                    'message_id' => $messageId,
                    'status' => WhatsAppLog::STATUS_SENT,
                    'sent_at' => now(),
                ]);

                return;
            }

            $phoneId = config('whatsapp.cloud.phone_number_id');
            $token = config('whatsapp.cloud.access_token');
            $version = config('whatsapp.cloud.graph_version', 'v25.0');
            if (! is_string($phoneId) || $phoneId === '' || ! is_string($token) || $token === '') {
                throw new \RuntimeException('WhatsApp Cloud API is not configured (WABA_PHONE_NUMBER_ID / WABA_ACCESS_TOKEN).');
            }

            $url = sprintf('https://graph.facebook.com/%s/%s/messages', $version, $phoneId);
            $locale = $this->templateLanguageCode ?? (string) config('whatsapp.default_locale', 'en');
            $templateBlock = [
                'name' => $this->templateName,
                'language' => [
                    'code' => $locale,
                ],
            ];
            $components = $this->buildBodyComponents();
            if ($components !== []) {
                $templateBlock['components'] = $components;
            }
            $payload = [
                'messaging_product' => 'whatsapp',
                'to' => $this->recipientE164,
                'type' => 'template',
                'template' => $templateBlock,
            ];

            $response = Http::withToken($token)
                ->acceptJson()
                ->timeout(45)
                ->post($url, $payload);

            if (! $response->successful()) {
                $body = $response->json();
                $err = is_array($body) ? ($body['error']['message'] ?? $response->body()) : $response->body();
                throw new \RuntimeException(is_string($err) ? $err : 'WhatsApp API error.');
            }

            $json = $response->json();
            $messageId = null;
            if (is_array($json) && isset($json['messages'][0]['id']) && is_string($json['messages'][0]['id'])) {
                $messageId = $json['messages'][0]['id'];
            }

            $log->update([
                'message_id' => $messageId,
                'status' => WhatsAppLog::STATUS_SENT,
                'sent_at' => now(),
            ]);
        } catch (Throwable $e) {
            report($e);
            $log->update([
                'status' => WhatsAppLog::STATUS_FAILED,
                'error_message' => $e->getMessage(),
                'sent_at' => now(),
            ]);
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function buildBodyComponents(): array
    {
        if ($this->bodyParameters === []) {
            return [];
        }

        $parameters = [];
        foreach ($this->bodyParameters as $text) {
            $parameters[] = [
                'type' => 'text',
                'text' => (string) $text,
            ];
        }

        return [
            [
                'type' => 'body',
                'parameters' => $parameters,
            ],
        ];
    }
}
