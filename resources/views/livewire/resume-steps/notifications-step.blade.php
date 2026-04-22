<div class="px-6 py-6 space-y-6">
    <div>
        <h2 class="text-xl font-bold text-gray-900">Сповіщення</h2>
        <p class="text-sm text-gray-600 mt-1">Виберіть, як ви хочете отримувати пропозиції від роботодавців</p>
    </div>

    <div class="space-y-3">
        @foreach ([
            'site'     => ['label' => 'На сайті My Job',  'icon' => '🌐'],
            'email'    => ['label' => 'На email',          'icon' => '📧'],
            'sms'      => ['label' => 'SMS',               'icon' => '📱'],
            'telegram' => ['label' => 'Telegram',          'icon' => '✈️'],
            'viber'    => ['label' => 'Viber',             'icon' => '💬'],
            'whatsapp' => ['label' => 'WhatsApp',          'icon' => '💚'],
        ] as $channel => $meta)
            <label class="flex items-center gap-4 cursor-pointer p-4 border border-gray-200 rounded-lg hover:bg-blue-50 hover:border-blue-200 transition
                {{ ($notifications[$channel] ?? false) ? 'border-blue-300 bg-blue-50' : '' }}">
                <input
                    type="checkbox"
                    wire:change="toggleChannel('{{ $channel }}')"
                    @checked($notifications[$channel] ?? false)
                    class="w-4 h-4 rounded border-gray-300 text-blue-600"
                />
                <span class="text-lg">{{ $meta['icon'] }}</span>
                <span class="text-sm font-semibold text-gray-900">{{ $meta['label'] }}</span>
                @if ($channel === 'site')
                    <span class="ml-auto text-xs text-green-600 font-medium">За замовчуванням</span>
                @endif
            </label>
        @endforeach
    </div>

    <div class="p-4 bg-blue-50 border border-blue-200 rounded-lg">
        <p class="text-sm text-blue-900">
            <strong>Інформація:</strong> Вибрані канали будуть використовуватись для надсилання пропозицій від роботодавців.
        </p>
    </div>
</div>
