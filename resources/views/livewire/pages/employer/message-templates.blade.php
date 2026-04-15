<?php

declare(strict_types=1);

use App\Enums\MessageType;
use App\Models\MessageTemplate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public ?int $editingId = null;

    public string $name      = '';
    public string $type      = '';
    public string $subject   = '';
    public string $body      = '';
    public bool   $showForm  = false;

    public function openCreate(): void
    {
        $this->reset('editingId', 'name', 'type', 'subject', 'body');
        $this->showForm = true;
    }

    public function openEdit(int $id): void
    {
        $tpl = $this->resolveTemplate($id);
        if (!$tpl) {
            return;
        }

        $this->editingId = $id;
        $this->name      = $tpl->name;
        $this->type      = $tpl->type->value;
        $this->subject   = $tpl->subject;
        $this->body      = $tpl->body;
        $this->showForm  = true;
    }

    public function save(): void
    {
        $this->validate([
            'name'    => 'required|string|max:255',
            'type'    => 'required|in:' . implode(',', array_column(MessageType::cases(), 'value')),
            'subject' => 'required|string|max:255',
            'body'    => 'required|string',
        ]);

        $companyId = auth()->user()->company->id;

        $data = [
            'company_id' => $companyId,
            'name'       => $this->name,
            'type'       => $this->type,
            'subject'    => $this->subject,
            'body'       => $this->body,
        ];

        if ($this->editingId) {
            $this->resolveTemplate($this->editingId)?->update($data);
        } else {
            MessageTemplate::create($data);
        }

        $this->reset('editingId', 'name', 'type', 'subject', 'body');
        $this->showForm = false;
    }

    public function toggle(int $id): void
    {
        $tpl = $this->resolveTemplate($id);
        $tpl?->update(['is_active' => !$tpl->is_active]);
    }

    public function delete(int $id): void
    {
        $this->resolveTemplate($id)?->delete();
    }

    public function cancel(): void
    {
        $this->reset('editingId', 'name', 'type', 'subject', 'body');
        $this->showForm = false;
    }

    private function resolveTemplate(int $id): ?MessageTemplate
    {
        return MessageTemplate::where('company_id', auth()->user()->company->id)->find($id);
    }

    #[Computed]
    public function templates(): \Illuminate\Database\Eloquent\Collection
    {
        return MessageTemplate::where('company_id', auth()->user()->company->id)
            ->orderBy('type')
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function messageTypes(): array
    {
        return MessageType::cases();
    }

    #[Computed]
    public function availableVars(): array
    {
        return [
            '{candidateName}' => 'Ім\'я кандидата',
            '{vacancyName}'   => 'Назва вакансії',
            '{companyName}'   => 'Назва компанії',
            '{hrName}'        => 'Ім\'я HR',
            '{hrEmail}'       => 'Email HR',
        ];
    }
}; ?>

<div class="min-h-screen seeker-dashboard-bg dark:bg-gray-900">
    <x-employer-tabs />

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        {{-- Sub-header --}}
        <div class="flex items-center justify-between mb-8">
            <h2 class="text-lg font-semibold text-gray-900">Шаблони повідомлень</h2>
            <button wire:click="openCreate"
                    class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-xl hover:bg-blue-700">
                + Новий шаблон
            </button>
        </div>

        {{-- Variables hint --}}
        <div class="bg-blue-50 border border-blue-100 rounded-xl p-4 mb-6 text-xs text-blue-700">
            <p class="font-semibold mb-1">Доступні змінні в шаблонах:</p>
            <div class="flex flex-wrap gap-2">
                @foreach($this->availableVars as $var => $desc)
                    <span class="font-mono bg-white border border-blue-200 rounded px-1.5 py-0.5" title="{{ $desc }}">{{ $var }}</span>
                @endforeach
            </div>
        </div>

        {{-- Form --}}
        @if($showForm)
            <div class="bg-white rounded-2xl border employer-card-border p-6 mb-6">
                <h2 class="text-base font-semibold text-gray-900 mb-5">
                    {{ $editingId ? 'Редагувати шаблон' : 'Новий шаблон' }}
                </h2>

                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Назва шаблону <span class="text-red-500">*</span></label>
                            <input type="text" wire:model="name"
                                   class="w-full border border-gray-300 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"/>
                            @error('name') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Тип <span class="text-red-500">*</span></label>
                            <select wire:model="type"
                                    class="w-full border border-gray-300 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Оберіть тип...</option>
                                @foreach($this->messageTypes as $mt)
                                    <option value="{{ $mt->value }}">{{ $mt->label() }}</option>
                                @endforeach
                            </select>
                            @error('type') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Тема листа <span class="text-red-500">*</span></label>
                        <input type="text" wire:model="subject"
                               placeholder="Запрошення на позицію {vacancyName}"
                               class="w-full border border-gray-300 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"/>
                        @error('subject') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Текст повідомлення <span class="text-red-500">*</span></label>
                        <textarea wire:model="body" rows="8"
                                  placeholder="Привіт {candidateName},&#10;&#10;Дякуємо за вашу заявку на позицію {vacancyName} у {companyName}..."
                                  class="w-full border border-gray-300 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none font-mono"></textarea>
                        @error('body') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex gap-3 pt-2">
                        <button wire:click="save"
                                class="px-6 py-2 bg-blue-600 text-white text-sm font-medium rounded-xl hover:bg-blue-700 transition-colors">
                            {{ $editingId ? 'Зберегти зміни' : 'Створити шаблон' }}
                        </button>
                        <button wire:click="cancel"
                                class="px-6 py-2 text-gray-600 text-sm font-medium hover:text-gray-800 transition-colors">
                            Скасувати
                        </button>
                    </div>
                </div>
            </div>
        @endif

        {{-- Templates list --}}
        <div class="bg-white rounded-2xl border employer-card-border overflow-hidden">
            @if($this->templates->isEmpty())
                <div class="p-12 text-center text-gray-400">
                    <p>Шаблонів ще немає.</p>
                    <button wire:click="openCreate" class="mt-3 text-sm text-blue-600 hover:underline">
                        Створити перший шаблон →
                    </button>
                </div>
            @else
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-100">
                        <tr>
                            <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Шаблон</th>
                            <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Тип</th>
                            <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Статус</th>
                            <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Дії</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($this->templates as $tpl)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <p class="font-medium text-gray-900">{{ $tpl->name }}</p>
                                    <p class="text-xs text-gray-400 mt-0.5">{{ Str::limit($tpl->subject, 60) }}</p>
                                </td>
                                <td class="px-6 py-4 text-gray-500">{{ $tpl->type->label() }}</td>
                                <td class="px-6 py-4">
                                    @if($tpl->is_active)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">Активний</span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-500">Вимкнено</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-end gap-3">
                                        <button wire:click="openEdit({{ $tpl->id }})"
                                                class="text-xs text-gray-500 hover:text-blue-600 font-medium">Редагувати</button>
                                        <button wire:click="toggle({{ $tpl->id }})"
                                                class="text-xs text-gray-500 hover:text-gray-800 font-medium">
                                            {{ $tpl->is_active ? 'Вимкнути' : 'Увімкнути' }}
                                        </button>
                                        <button wire:click="delete({{ $tpl->id }})"
                                                wire:confirm="Видалити шаблон «{{ $tpl->name }}»?"
                                                class="text-xs text-red-500 hover:text-red-700 font-medium">Видалити</button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
</div>
