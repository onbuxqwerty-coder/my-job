<x-mail::message>
# Запрошення на співбесіду

Привіт, **{{ $candidateName }}**!

Ми раді запросити вас на співбесіду на позицію **{{ $vacancyTitle }}** у компанії **{{ $companyName }}**.

<x-mail::panel>
**Дата та час:** {{ $scheduledAt }}
**Тривалість:** {{ $duration }} хв
**Формат:** {{ $type }}
@if($meetingLink)
**Посилання:** {{ $meetingLink }}
@endif
@if($officeAddress)
**Адреса:** {{ $officeAddress }}
@endif
</x-mail::panel>

@if($notes)
**Примітки:**
{{ $notes }}
@endif

Будь ласка, підтвердіть вашу участь:

<x-mail::button :url="$confirmUrl" color="success">
Підтвердити участь
</x-mail::button>

Якщо ви не зможете прийти, скасуйте запрошення:

<x-mail::button :url="$cancelUrl" color="error">
Скасувати
</x-mail::button>

З повагою,
**{{ config('app.name') }}**
</x-mail::message>
