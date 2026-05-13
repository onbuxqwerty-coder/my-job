<?php

declare(strict_types=1);

use App\Enums\SupportThreadStatus;
use App\Enums\UserRole;
use App\Models\SupportThread;
use App\Services\SupportService;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public int    $threadId;
    public string $body = '';

    public function mount(int $threadId): void
    {
        $thread = SupportThread::where('user_id', auth()->id())->findOrFail($threadId);
        $this->threadId = $thread->id;

        app(SupportService::class)->markThreadRead($thread, auth()->user());
    }

    public function getThread(): SupportThread
    {
        return SupportThread::with(['messages.sender'])->findOrFail($this->threadId);
    }

    public function reply(SupportService $service): void
    {
        $this->validate(['body' => ['required', 'string', 'min:2', 'max:5000']]);

        $thread = SupportThread::where('user_id', auth()->id())->findOrFail($this->threadId);

        abort_if($thread->status === SupportThreadStatus::Closed, 403, 'Звернення закрито.');

        $service->reply(thread: $thread, sender: auth()->user(), body: $this->body);

        $this->body = '';
    }
};
?>

<div class="min-h-screen seeker-dashboard-bg dark:bg-gray-900">
<x-seeker-tabs />
<div class="max-w-3xl mx-auto px-4 py-8">

    @php $thread = $this->getThread(); @endphp

    {{-- Header --}}
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('seeker.messages') }}"
           class="text-gray-400 hover:text-gray-600 transition">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        <div class="min-w-0">
            <h1 class="text-lg font-semibold text-gray-900 truncate">{{ $thread->subject }}</h1>
            <span @class([
                'text-xs font-medium px-2 py-0.5 rounded-full',
                'bg-green-50 text-green-700' => $thread->status === SupportThreadStatus::Open,
                'bg-gray-100 text-gray-500'  => $thread->status === SupportThreadStatus::Closed,
            ])>
                {{ $thread->status->label() }}
            </span>
        </div>
    </div>

    {{-- Messages --}}
    <div class="space-y-4 mb-6">
        @foreach($thread->messages as $msg)
            @php $isMe = $msg->sender_id === auth()->id(); @endphp
            <div @class(['flex', 'justify-end' => $isMe, 'justify-start' => ! $isMe])>
                <div @class([
                    'max-w-[80%] rounded-2xl px-4 py-3',
                    'bg-green-600 text-white rounded-br-sm'  => $isMe,
                    'bg-white border border-gray-100 text-gray-800 rounded-bl-sm shadow-sm' => ! $isMe,
                ])>
                    @if(! $isMe)
                        <p class="text-[11px] font-semibold mb-1 opacity-70">
                            {{ $msg->sender?->name ?? 'Підтримка My Job' }}
                        </p>
                    @endif
                    <p class="text-sm leading-relaxed whitespace-pre-wrap">{{ $msg->body }}</p>
                    <p @class([
                        'text-[11px] mt-1',
                        'text-green-200' => $isMe,
                        'text-gray-400'  => ! $isMe,
                    ])>
                        {{ $msg->created_at->format('d.m.Y H:i') }}
                    </p>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Reply form --}}
    @if($thread->status === SupportThreadStatus::Open)
        <div class="bg-white border border-gray-100 rounded-2xl p-4 shadow-sm">
            <textarea
                wire:model="body"
                rows="3"
                placeholder="Написати відповідь..."
                class="w-full text-sm border border-gray-200 rounded-xl px-3 py-2.5 resize-none
                       focus:outline-none focus:ring-2 focus:ring-green-300"
            ></textarea>
            @error('body')
                <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
            @enderror
            <div class="flex justify-end mt-2">
                <button wire:click="reply"
                        class="bg-green-600 hover:bg-green-700 text-white text-sm font-medium
                               px-5 py-2 rounded-lg transition">
                    Надіслати
                </button>
            </div>
        </div>
    @else
        <div class="text-center py-4 text-sm text-gray-400 bg-gray-50 rounded-xl border border-gray-100">
            Звернення закрито. Якщо потрібна допомога — <a href="{{ route('contacts') }}" class="text-green-600 hover:underline">відкрийте нове</a>.
        </div>
    @endif

</div>
</div>
