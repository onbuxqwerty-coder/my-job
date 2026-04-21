<?php

declare(strict_types=1);

namespace App\Livewire\ResumeSteps;

use App\Models\Resume;
use Livewire\Component;

class NotificationsStep extends Component
{
    public Resume $resume;
    public array  $formData      = [];
    public array  $notifications = [];

    public function mount(Resume $resume, array $formData = []): void
    {
        $this->resume        = $resume;
        $this->formData      = $formData;
        $this->notifications = $formData['notifications'] ?? [
            'site'     => true,
            'email'    => false,
            'sms'      => false,
            'telegram' => false,
            'viber'    => false,
            'whatsapp' => false,
        ];
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.resume-steps.notifications-step');
    }

    public function toggleChannel(string $channel): void
    {
        $this->notifications[$channel] = !($this->notifications[$channel] ?? false);

        $this->dispatch('updateFormData',
            section: 'notifications',
            key: $channel,
            value: $this->notifications[$channel],
        );
    }
}
