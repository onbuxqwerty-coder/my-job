<?php

declare(strict_types=1);

namespace App\Enums;

enum MessageType: string
{
    case Invitation = 'invitation';
    case Message    = 'message';
    case Offer      = 'offer';
    case Rejection  = 'rejection';

    public function label(): string
    {
        return match($this) {
            self::Invitation => 'Запрошення на співбесіду',
            self::Message    => 'Стандартне повідомлення',
            self::Offer      => 'Пропозиція про роботу',
            self::Rejection  => 'Відхилення',
        };
    }
}
