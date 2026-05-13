<x-mail::message>
# Нова відповідь у зверненні

Привіт, **{{ $recipientName }}**!

У вашому зверненні **«{{ $subject }}»** є нова відповідь:

{{ $body }}

<x-mail::button :url="$threadUrl">
Перейти до звернення
</x-mail::button>

З повагою,
**Команда My Job**
</x-mail::message>
