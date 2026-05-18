<x-filament-panels::page>
@php
    $thread   = $record->load('user', 'messages.sender');
    $messages = $thread->messages;
    $isOpen   = $record->status === \App\Enums\SupportThreadStatus::Open;
@endphp

<div style="max-width:680px; display:flex; flex-direction:column; gap:16px;">

    {{-- ── Відправник ──────────────────────────────────────────────────────── --}}
    <x-filament::section heading="Відправник">
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px 40px; font-size:13px;">
            <div>
                <div style="font-size:11px; color:#9ca3af; margin-bottom:2px;">Ім'я</div>
                <div style="font-weight:600;">{{ $thread->user?->name ?? '—' }}</div>
            </div>
            <div>
                <div style="font-size:11px; color:#9ca3af; margin-bottom:2px;">Email</div>
                <div>{{ $thread->user?->email ?? '—' }}</div>
            </div>
            <div>
                <div style="font-size:11px; color:#9ca3af; margin-bottom:2px;">Роль</div>
                <div>
                    <x-filament::badge color="info" size="sm">{{ $record->role->label() }}</x-filament::badge>
                </div>
            </div>
            <div>
                <div style="font-size:11px; color:#9ca3af; margin-bottom:2px;">Статус</div>
                <div>
                    <x-filament::badge color="{{ $isOpen ? 'success' : 'gray' }}" size="sm">
                        {{ $record->status->label() }}
                    </x-filament::badge>
                </div>
            </div>
            <div>
                <div style="font-size:11px; color:#9ca3af; margin-bottom:2px;">Створено</div>
                <div>{{ $record->created_at->format('d.m.Y H:i') }}</div>
            </div>
            <div>
                <div style="font-size:11px; color:#9ca3af; margin-bottom:2px;">Остання активність</div>
                <div>{{ $record->last_message_at?->format('d.m.Y H:i') ?? '—' }}</div>
            </div>
        </div>
    </x-filament::section>

    {{-- ── Переписка ───────────────────────────────────────────────────────── --}}
    <x-filament::section>
        <x-slot name="heading">
            Переписка
            <span style="font-size:11px; font-weight:400; color:#9ca3af; margin-left:8px;">
                {{ $messages->count() }} повідомл.
            </span>
        </x-slot>

        @if($messages->isEmpty())
            <p style="text-align:center; color:#9ca3af; font-size:13px; padding:24px 0;">
                Повідомлень поки немає
            </p>
        @else
            <div style="display:flex; flex-direction:column; gap:0;">
                @foreach($messages as $i => $msg)
                    @php $isAdmin = $msg->sender?->role === \App\Enums\UserRole::Admin; @endphp

                    <div
                        style="padding:14px 0; {{ $i > 0 ? 'border-top:1px solid var(--gray-200, #e5e7eb);' : '' }}
                               {{ $isAdmin ? 'background:rgba(59,130,246,.04); margin:0 -20px; padding:14px 20px;' : '' }}"
                        x-data="{ hover: false }"
                        @mouseenter="hover = true"
                        @mouseleave="hover = false"
                    >
                        @if($editingMessageId === $msg->id)
                            {{-- Режим редагування --}}
                            <textarea
                                wire:model="editBody"
                                rows="3"
                                style="width:100%; border:1px solid #3b82f6; border-radius:8px; padding:8px 12px;
                                       font-size:13px; resize:vertical; box-sizing:border-box; outline:none;
                                       font-family:inherit; background:inherit; color:inherit; margin-bottom:8px;"
                            ></textarea>
                            @error('editBody')
                                <p style="color:#ef4444; font-size:11px; margin-bottom:8px;">{{ $message }}</p>
                            @enderror
                            <div style="display:flex; gap:8px;">
                                <button wire:click="saveEdit"
                                        style="font-size:12px; font-weight:600; padding:5px 14px;
                                               background:#2563eb; color:#fff; border:none; border-radius:7px; cursor:pointer;">
                                    Зберегти
                                </button>
                                <button wire:click="cancelEdit"
                                        style="font-size:12px; font-weight:600; padding:5px 14px;
                                               background:#f3f4f6; color:#374151; border:none; border-radius:7px; cursor:pointer;">
                                    Скасувати
                                </button>
                            </div>
                        @else
                            {{-- Режим перегляду --}}
                            <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:12px;">
                                <div style="flex:1; min-width:0;">
                                    <div style="display:flex; align-items:center; gap:8px; margin-bottom:6px;">
                                        <span style="font-size:12px; font-weight:600;
                                                     color:{{ $isAdmin ? '#3b82f6' : '#374151' }};">
                                            {{ $msg->sender?->name ?? 'Невідомо' }}
                                            @if($isAdmin)
                                                <span style="font-weight:400; color:#93c5fd;">(Адмін)</span>
                                            @endif
                                        </span>
                                        <span style="font-size:11px; color:#9ca3af;">
                                            {{ $msg->created_at->format('d.m.Y H:i') }}
                                        </span>
                                    </div>
                                    <p style="font-size:13px; line-height:1.6; white-space:pre-wrap; margin:0;">{{ $msg->body }}</p>
                                </div>

                                {{-- Кнопки дій --}}
                                <div style="display:flex; gap:2px; flex-shrink:0; margin-top:2px;"
                                     x-show="hover">
                                    <button wire:click="startEdit({{ $msg->id }})"
                                            title="Редагувати"
                                            style="padding:5px; border:none; background:transparent; cursor:pointer;
                                                   border-radius:6px; color:#9ca3af; line-height:1;"
                                            onmouseover="this.style.background='#eff6ff'; this.style.color='#2563eb';"
                                            onmouseout="this.style.background='transparent'; this.style.color='#9ca3af';">
                                        <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                  d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </button>
                                    <button wire:click="deleteMessage({{ $msg->id }})"
                                            wire:confirm="Видалити повідомлення?"
                                            title="Видалити"
                                            style="padding:5px; border:none; background:transparent; cursor:pointer;
                                                   border-radius:6px; color:#9ca3af; line-height:1;"
                                            onmouseover="this.style.background='#fef2f2'; this.style.color='#dc2626';"
                                            onmouseout="this.style.background='transparent'; this.style.color='#9ca3af';">
                                        <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
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
    </x-filament::section>

    {{-- ── Форма відповіді ─────────────────────────────────────────────────── --}}
    @if($isOpen)
        <x-filament::section heading="Відповідь">
            <textarea
                wire:model="replyBody"
                rows="4"
                placeholder="Написати відповідь..."
                wire:keydown.ctrl.enter="sendReply"
                style="width:100%; border:1px solid #d1d5db; border-radius:10px; padding:10px 12px;
                       font-size:13px; resize:vertical; box-sizing:border-box; outline:none;
                       font-family:inherit; background:inherit; color:inherit; margin-bottom:8px;"
                onfocus="this.style.borderColor='#3b82f6'; this.style.boxShadow='0 0 0 2px rgba(59,130,246,.2)';"
                onblur="this.style.borderColor='#d1d5db'; this.style.boxShadow='none';"
            ></textarea>

            @error('replyBody')
                <p style="color:#ef4444; font-size:11px; margin-bottom:8px;">{{ $message }}</p>
            @enderror

            <div style="display:flex; justify-content:space-between; align-items:center;">
                <span style="font-size:11px; color:#9ca3af;">Ctrl+Enter для відправки</span>
                <button
                    wire:click="sendReply"
                    wire:loading.attr="disabled"
                    style="background:#2563eb; color:#fff; font-size:13px; font-weight:600;
                           padding:8px 22px; border:none; border-radius:8px; cursor:pointer;"
                    onmouseover="this.style.background='#1d4ed8';"
                    onmouseout="this.style.background='#2563eb';"
                >
                    <span wire:loading.remove wire:target="sendReply">Надіслати</span>
                    <span wire:loading wire:target="sendReply">...</span>
                </button>
            </div>
        </x-filament::section>
    @else
        <x-filament::section>
            <p style="text-align:center; color:#9ca3af; font-size:13px; padding:8px 0;">
                Звернення закрито. Відкрийте знову через кнопку вгорі.
            </p>
        </x-filament::section>
    @endif

</div>
</x-filament-panels::page>
