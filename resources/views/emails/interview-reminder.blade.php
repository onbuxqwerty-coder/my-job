<x-mail::message>
# Нагадування про співбесіду

Привіт, **{{ $candidateName }}**!

Нагадуємо, що через **{{ $reminderLabel }}** відбудеться ваша співбесіда на позицію **{{ $vacancyTitle }}**.

<x-mail::panel>
**Дата та час:** {{ $scheduledAt }}
**Формат:** {{ $type }}
@if($meetingLink)
**Посилання:** {{ $meetingLink }}
@endif
@if($officeAddress)
**Адреса:** {{ $officeAddress }}
@endif
</x-mail::panel>

Якщо ви не зможете прийти:

<x-mail::button :url="$cancelUrl" color="error">
Скасувати участь
</x-mail::button>

З повагою,
**{{ config('app.name') }}**
</x-mail::message>
