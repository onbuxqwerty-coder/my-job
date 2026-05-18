<?php

declare(strict_types=1);

use App\Enums\SupportThreadStatus;
use App\Models\SupportThread;
use App\Services\SupportService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public string $body = '';

    public function mount(): void
    {
        $thread = SupportThread::where('user_id', auth()->id())->latest()->first();
        if ($thread) {
            app(SupportService::class)->markThreadRead($thread, auth()->user());
        }
    }

    public function send(SupportService $service): void
    {
        $this->validate(['body' => ['required', 'string', 'min:1', 'max:5000']]);
        $service->sendMessage(auth()->user(), $this->body);
        $this->reset('body');
    }

    #[Computed]
    public function thread(): ?SupportThread
    {
        return SupportThread::where('user_id', auth()->id())
            ->with(['messages.sender'])
            ->latest()
            ->first();
    }
};
?>

<div class="min-h-screen seeker-dashboard-bg dark:bg-gray-900">
<x-seeker-tabs />
<div class="max-w-[900px] mx-auto px-4 py-8">

    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl font-semibold text-gray-900 dark:text-white">Підтримка</h1>
        @if($this->thread)
            <span @class([
                'text-xs font-medium px-2.5 py-0.5 rounded-full',
                'bg-green-50 text-green-700' => $this->thread->status === SupportThreadStatus::Open,
                'bg-gray-100 text-gray-500'  => $this->thread->status === SupportThreadStatus::Closed,
            ])>
                {{ $this->thread->status->label() }}
            </span>
        @endif
    </div>

    {{-- Chat window --}}
    <div class="bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-2xl shadow-sm overflow-hidden mb-4">
        @if(!$this->thread || $this->thread->messages->isEmpty())
            <div class="text-center py-16 text-gray-400">
                <svg class="w-10 h-10 mx-auto mb-3 opacity-30" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                </svg>
                <p class="text-sm">Напишіть нам — відповімо якомога швидше</p>
            </div>
        @else
            <div class="p-4 space-y-4 max-h-[520px] overflow-y-auto" id="chat-messages">
                @foreach($this->thread->messages as $msg)
                    @php $isMe = $msg->sender_id === auth()->id(); @endphp
                    <div @class(['flex', 'justify-end' => $isMe, 'justify-start' => !$isMe])>
                        <div @class([
                            'max-w-[80%] rounded-2xl px-4 py-3',
                            'bg-blue-600 text-white rounded-br-sm'                                          => $isMe,
                            'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-100 rounded-bl-sm' => !$isMe,
                        ])>
                            @if(!$isMe)
                                <p class="text-[11px] font-semibold mb-1 opacity-60">
                                    {{ $msg->sender?->name ?? 'Підтримка My Job' }}
                                </p>
                            @endif
                            <p class="text-sm leading-relaxed whitespace-pre-wrap">{{ $msg->body }}</p>
                            <p @class(['text-[11px] mt-1.5', 'text-blue-200' => $isMe, 'text-gray-400 dark:text-gray-400' => !$isMe])>
                                {{ $msg->created_at->format('d.m H:i') }}
                            </p>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Reply / closed --}}
    @if(!$this->thread || $this->thread->status === SupportThreadStatus::Open)
        <div class="bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-2xl p-4 shadow-sm">
            <textarea
                wire:model="body"
                rows="3"
                placeholder="Написати повідомлення..."
                class="w-full text-sm border border-gray-200 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 rounded-xl px-3 py-2.5 resize-none focus:outline-none focus:ring-2 focus:ring-blue-300"
                wire:keydown.ctrl.enter="send"
            ></textarea>
            @error('body')
                <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
            @enderror
            <div class="flex justify-between items-center mt-2">
                <p class="text-[11px] text-gray-400">Ctrl+Enter для відправки</p>
                <button
                    wire:click="send"
                    wire:loading.attr="disabled"
                    class="bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white text-sm font-medium px-5 py-2 rounded-lg transition"
                >
                    <span wire:loading.remove wire:target="send">Надіслати</span>
                    <span wire:loading wire:target="send">...</span>
                </button>
            </div>
        </div>
    @else
        <div class="text-center py-4 text-sm text-gray-400 bg-gray-50 dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700">
            Звернення закрито. Відкрийте нове через <a href="{{ route('contacts') }}" class="text-blue-600 hover:underline">контактну форму</a>.
        </div>
    @endif

</div>
</div>

<script>
    function scrollChat() {
        const el = document.getElementById('chat-messages');
        if (el) el.scrollTop = el.scrollHeight;
    }
    document.addEventListener('DOMContentLoaded', scrollChat);
    document.addEventListener('livewire:updated', scrollChat);
</script>
