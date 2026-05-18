<x-filament-panels::page>
@php
    $thread   = $record->load('user', 'messages.sender');
    $messages = $thread->messages;
    $isOpen   = $record->status === \App\Enums\SupportThreadStatus::Open;
@endphp

<div class="max-w-2xl space-y-5">

    {{-- ── Інформація про відправника ─────────────────────────────────────── --}}
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-5 shadow-sm">
        <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-4">Відправник</h3>

        <dl class="grid grid-cols-2 gap-x-8 gap-y-3 text-sm">
            <div>
                <dt class="text-[11px] text-gray-400 mb-0.5">Ім'я</dt>
                <dd class="font-semibold text-gray-900 dark:text-white">{{ $thread->user?->name ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-[11px] text-gray-400 mb-0.5">Email</dt>
                <dd class="text-gray-700 dark:text-gray-300">{{ $thread->user?->email ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-[11px] text-gray-400 mb-0.5">Роль</dt>
                <dd>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-blue-50 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300">
                        {{ $thread->role->label() }}
                    </span>
                </dd>
            </div>
            <div>
                <dt class="text-[11px] text-gray-400 mb-0.5">Статус</dt>
                <dd>
                    <span @class([
                        'inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium',
                        'bg-green-50 text-green-700 dark:bg-green-900/30 dark:text-green-400' => $isOpen,
                        'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400'       => ! $isOpen,
                    ])>
                        {{ $record->status->label() }}
                    </span>
                </dd>
            </div>
            <div>
                <dt class="text-[11px] text-gray-400 mb-0.5">Створено</dt>
                <dd class="text-gray-600 dark:text-gray-400">{{ $record->created_at->format('d.m.Y H:i') }}</dd>
            </div>
            <div>
                <dt class="text-[11px] text-gray-400 mb-0.5">Остання активність</dt>
                <dd class="text-gray-600 dark:text-gray-400">{{ $record->last_message_at?->format('d.m.Y H:i') ?? '—' }}</dd>
            </div>
        </dl>
    </div>

    {{-- ── Переписка ───────────────────────────────────────────────────────── --}}
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
            <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-widest">Переписка</h3>
            <span class="text-[11px] text-gray-400">{{ $messages->count() }} повідомл.</span>
        </div>

        @if($messages->isEmpty())
            <div class="px-5 py-12 text-center text-sm text-gray-400">Повідомлень поки немає</div>
        @else
            <div class="divide-y divide-gray-50 dark:divide-gray-700/60">
                @foreach($messages as $msg)
                    @php $isAdmin = $msg->sender?->role === \App\Enums\UserRole::Admin; @endphp

                    <div class="px-5 py-4 group {{ $isAdmin ? 'bg-blue-50/30 dark:bg-blue-900/10' : '' }}">

                        @if($editingMessageId === $msg->id)
                            {{-- Режим редагування --}}
                            <div class="mb-2">
                                <textarea
                                    wire:model="editBody"
                                    rows="3"
                                    class="w-full text-sm border border-blue-300 dark:border-blue-600 rounded-lg px-3 py-2
                                           resize-none focus:outline-none focus:ring-2 focus:ring-blue-400
                                           dark:bg-gray-900 dark:text-white"
                                ></textarea>
                                @error('editBody')
                                    <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="flex gap-2">
                                <button wire:click="saveEdit"
                                        class="text-xs font-semibold px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                                    Зберегти
                                </button>
                                <button wire:click="cancelEdit"
                                        class="text-xs font-semibold px-3 py-1.5 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-600 dark:text-gray-300 rounded-lg transition">
                                    Скасувати
                                </button>
                            </div>
                        @else
                            {{-- Режим перегляду --}}
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2 mb-1.5">
                                        <span @class([
                                            'text-xs font-semibold',
                                            'text-blue-700 dark:text-blue-400' => $isAdmin,
                                            'text-gray-700 dark:text-gray-300' => ! $isAdmin,
                                        ])>
                                            {{ $msg->sender?->name ?? 'Невідомо' }}
                                            @if($isAdmin)
                                                <span class="font-normal text-blue-400">(Адмін)</span>
                                            @endif
                                        </span>
                                        <span class="text-[11px] text-gray-400">
                                            {{ $msg->created_at->format('d.m.Y H:i') }}
                                        </span>
                                    </div>
                                    <p class="text-sm text-gray-800 dark:text-gray-200 whitespace-pre-wrap leading-relaxed">{{ $msg->body }}</p>
                                </div>

                                {{-- Кнопки дій (з'являються при наведенні) --}}
                                <div class="flex items-center gap-0.5 opacity-0 group-hover:opacity-100 transition-opacity shrink-0 mt-0.5">
                                    <button wire:click="startEdit({{ $msg->id }})"
                                            title="Редагувати"
                                            class="p-1.5 text-gray-300 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded-lg transition">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                  d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </button>
                                    <button wire:click="deleteMessage({{ $msg->id }})"
                                            wire:confirm="Видалити повідомлення?"
                                            title="Видалити"
                                            class="p-1.5 text-gray-300 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                  d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- ── Форма відповіді ─────────────────────────────────────────────────── --}}
    @if($isOpen)
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-5 shadow-sm">
            <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-3">Відповідь</h3>

            <textarea
                wire:model="replyBody"
                rows="4"
                placeholder="Написати відповідь..."
                class="w-full text-sm border border-gray-200 dark:border-gray-600 dark:bg-gray-900 dark:text-white
                       rounded-xl px-3 py-2.5 resize-none focus:outline-none focus:ring-2 focus:ring-blue-300"
                wire:keydown.ctrl.enter="sendReply"
            ></textarea>

            @error('replyBody')
                <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
            @enderror

            <div class="flex justify-between items-center mt-3">
                <p class="text-[11px] text-gray-400">Ctrl+Enter для відправки</p>
                <button
                    wire:click="sendReply"
                    wire:loading.attr="disabled"
                    class="bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white text-sm font-semibold px-6 py-2 rounded-lg transition"
                >
                    <span wire:loading.remove wire:target="sendReply">Надіслати</span>
                    <span wire:loading wire:target="sendReply">...</span>
                </button>
            </div>
        </div>
    @else
        <div class="text-center py-4 text-sm text-gray-400 bg-gray-50 dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700">
            Звернення закрито. Відкрийте знову через кнопку вгорі.
        </div>
    @endif

</div>
</x-filament-panels::page>
