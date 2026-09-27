<?php

declare(strict_types=1);

namespace Gabrielesbaiz\WhatsappToolkit\Enums;

enum MessageStatus: string
{
    case Accepted = 'accepted';
    case Sent = 'sent';
    case Delivered = 'delivered';
    case Read = 'read';
    case Failed = 'failed';
    case Deleted = 'deleted';
}
