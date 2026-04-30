<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Events\ApplicationStatusChanged;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ApplicationStatusChangedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly ApplicationStatusChanged $event,
    ) {}

    /** @return array<string> */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject("Статус заявки змінено: {$this->event->newStatus->label()}")
            ->line("Статус вашої заявки змінено на: {$this->event->newStatus->label()}");
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'application_id'  => $this->event->application->id,
            'old_status'       => $this->event->oldStatus?->value,
            'new_status'       => $this->event->newStatus->value,
            'changed_by_role'  => $this->event->changedBy->role->value,
        ];
    }
}
