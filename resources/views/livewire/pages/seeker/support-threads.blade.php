<?php

declare(strict_types=1);

use App\Enums\SupportThreadStatus;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    #[Computed]
    public function threads(): \Illuminate\Database\Eloquent\Collection
    {
        return auth()->user()
            ->hasMany(\App\Models\SupportThread::class, 'user_id')
            ->getQuery()
            ->with(['messages' => fn ($q) => $q->latest()->limit(1)])
            ->withCount(['messages', 'messages as unread_count' => fn ($q) => $q->where('is_read', false)])
            ->orderByDesc('last_message_at')
            ->get();
    }
};
?>

<div class="max-w-3xl mx-auto px-4 py-8">

    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl font-semibold text-gray-900">Мої звернення</h1>
        <a href="{{ route('contacts') }}"
           class="text-sm bg-green-600 hover:bg-green-700 text-white font-medium px-4 py-2 rounded-lg transition">
            + Нове звернення
        </a>
    </div>

    @if($this->threads->isEmpty())
        <div class="text-center py-16 text-gray-400">
            <svg class="w-12 h-12 mx-auto mb-3 opacity-30" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                      d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
            </svg>
            <p class="text-sm">Звернень поки немає</p>
            <a href="{{ route('contacts') }}" class="text-green-600 text-sm hover:underline mt-1 inline-block">
                Написати у підтримку →
            </a>
        </div>
    @else
        <div class="space-y-3">
            @foreach($this->threads as $thread)
                <a href="{{ route('seeker.message.detail', $thread->id) }}"
                   class="block bg-white border border-gray-100 rounded-xl p-4 hover:border-gray-300 transition shadow-sm">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2 mb-1">
                                @if($thread->unread_count > 0)
                                    <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-red-500 text-white text-[10px] font-bold shrink-0">
                                        {{ $thread->unread_count }}
                                    </span>
                                @endif
                                <p class="text-sm font-medium text-gray-900 truncate">{{ $thread->subject }}</p>
                            </div>
                            @if($thread->messages->first())
                                <p class="text-xs text-gray-400 truncate">{{ $thread->messages->first()->body }}</p>
                            @endif
                        </div>
                        <div class="shrink-0 text-right">
                            <span @class([
                                'inline-block text-[11px] font-medium px-2 py-0.5 rounded-full',
                                'bg-green-50 text-green-700' => $thread->status === SupportThreadStatus::Open,
                                'bg-gray-100 text-gray-500'  => $thread->status === SupportThreadStatus::Closed,
                            ])>
                                {{ $thread->status->label() }}
                            </span>
                            <p class="text-[11px] text-gray-400 mt-1">
                                {{ $thread->last_message_at?->diffForHumans() }}
                            </p>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    @endif

</div>
