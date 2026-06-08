<?php

namespace App\Console\Commands;

use App\Jobs\SendWhatsAppTemplateJob;
use App\Models\WhatsAppLog;
use App\Services\WhatsApp\WhatsAppNotificationService;
use Illuminate\Console\Command;

/**
 * Sends a single WhatsApp Cloud API template (default: Meta sample hello_world) for integration testing.
 */
class WhatsAppTestSendCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'whatsapp:test-send
                            {phone=8141760247 : Mobile number (10-digit India or full E.164 digits)}
                            {--template=hello_world : Meta template name}
                            {--locale=en_US : Template language code (hello_world uses en_US)}
                            {--force-cloud : Run Cloud API call even if WHATSAPP_DRIVER=log (still needs WABA_* in .env)}';

    /**
     * @var string
     */
    protected $description = 'Send one WhatsApp template message (e.g. hello_world) to verify WABA credentials.';

    /**
     * Execute the console command.
     */
    public function handle(WhatsAppNotificationService $normalizer): int
    {
        $rawPhone = (string) $this->argument('phone');
        $to = $normalizer->normalizeToE164($rawPhone);
        if ($to === null) {
            $this->components->error('Could not normalise phone number. Use 10 digits (e.g. 8141760247) or full international digits.');

            return self::FAILURE;
        }

        $template = (string) $this->option('template');
        $locale = (string) $this->option('locale');
        $forceCloud = (bool) $this->option('force-cloud');

        $this->components->info("Recipient (E.164 digits, no +): {$to}");
        $this->components->info("Template: {$template}, locale: {$locale}");

        $driver = (string) config('whatsapp.driver', 'log');
        if ($driver === 'log' && ! $forceCloud) {
            $this->components->warn('WHATSAPP_DRIVER=log — no HTTP call to Meta. Use --force-cloud with WABA_* set in .env to hit the API.');
        }

        if ($forceCloud) {
            config(['whatsapp.driver' => 'cloud']);
        }

        $job = new SendWhatsAppTemplateJob(
            $to,
            'manual_test',
            $template,
            [],
            null,
            null,
            'CLI test',
            $locale,
        );

        $beforeId = WhatsAppLog::query()->max('id') ?? 0;
        $job->handle();

        $log = WhatsAppLog::query()->where('id', '>', $beforeId)->orderByDesc('id')->first();
        if ($log === null) {
            $this->components->error('No whatsapp_logs row was written.');

            return self::FAILURE;
        }

        if ($log->status === WhatsAppLog::STATUS_SENT) {
            $this->components->info("Status: {$log->status}".($log->message_id ? " | message_id: {$log->message_id}" : ''));

            return self::SUCCESS;
        }

        $this->components->error("Status: {$log->status}");
        if ($log->error_message) {
            $this->components->error($log->error_message);
        }

        $err = (string) $log->error_message;
        if ($err !== '' && (stripos($err, 'expired') !== false || stripos($err, 'access token') !== false)) {
            $this->newLine();
            $this->components->warn(
                'Meta rejected WABA_ACCESS_TOKEN (short-lived Graph Explorer tokens expire in ~1 hour). '
                .'Create a long-lived or System User token in developers.facebook.com → your app → WhatsApp → API setup, '
                .'put it in .env as WABA_ACCESS_TOKEN, then run: php artisan config:clear'
            );
        }

        return self::FAILURE;
    }
}
