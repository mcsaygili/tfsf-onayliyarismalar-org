<?php

namespace App\Services;

use App\Models\NotificationTemplate;
use Illuminate\Notifications\Messages\MailMessage;
use RuntimeException;
use Symfony\Component\Mime\Email;

class NotificationTemplateRenderer
{
    /** @param array<string, scalar|null> $variables */
    public function mail(
        string $templateKey,
        string $locale,
        array $variables,
        ?string $actionUrl = null,
        ?string $dispatchId = null,
        ?string $competitionId = null,
    ): MailMessage {
        $template = NotificationTemplate::query()->with('translations')->where('key', $templateKey)->first();
        if (! $template || ! $template->is_active) {
            throw new RuntimeException("Bildirim şablonu kullanılamıyor: {$templateKey}");
        }

        $translation = $template->translation($locale);
        if (! $translation) {
            throw new RuntimeException("Bildirim şablonu çevirisi bulunamadı: {$templateKey}/{$locale}");
        }

        $mail = (new MailMessage)
            ->subject($this->replace($translation->subject, $variables));

        if (filled($translation->greeting)) {
            $mail->greeting($this->replace($translation->greeting, $variables));
        }

        foreach (preg_split('/\R{2,}/', $this->replace($translation->body, $variables)) ?: [] as $paragraph) {
            if (filled($paragraph)) {
                $mail->line($paragraph);
            }
        }

        if ($actionUrl && filled($translation->action_label)) {
            $mail->action($this->replace($translation->action_label, $variables), $actionUrl);
        }

        if ($dispatchId) {
            $mail->withSymfonyMessage(function (Email $message) use ($dispatchId, $templateKey, $competitionId): void {
                $message->getHeaders()->addTextHeader('X-TFSF-Dispatch-ID', $dispatchId);
                $message->getHeaders()->addTextHeader('X-TFSF-Template-Key', $templateKey);
                if ($competitionId) {
                    $message->getHeaders()->addTextHeader('X-TFSF-Competition-ID', $competitionId);
                }
            });
        }

        return $mail;
    }

    /** @param array<string, scalar|null> $variables */
    private function replace(string $content, array $variables): string
    {
        foreach ($variables as $key => $value) {
            $content = preg_replace('/{{\s*'.preg_quote($key, '/').'\s*}}/u', (string) $value, $content) ?? $content;
        }

        return $content;
    }
}
