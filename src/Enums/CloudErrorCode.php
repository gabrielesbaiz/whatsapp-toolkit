<?php

declare(strict_types=1);

namespace Gabrielesbaiz\WhatsappToolkit\Enums;

use Gabrielesbaiz\WhatsappToolkit\Exceptions\AuthenticationException;
use Gabrielesbaiz\WhatsappToolkit\Exceptions\CloudApiException;
use Gabrielesbaiz\WhatsappToolkit\Exceptions\MediaException;
use Gabrielesbaiz\WhatsappToolkit\Exceptions\RateLimitException;
use Gabrielesbaiz\WhatsappToolkit\Exceptions\RecipientException;
use Gabrielesbaiz\WhatsappToolkit\Exceptions\ReEngagementRequiredException;
use Gabrielesbaiz\WhatsappToolkit\Exceptions\TemplateException;
use Gabrielesbaiz\WhatsappToolkit\Exceptions\TransientException;

/**
 * The Meta error codes worth telling apart.
 *
 * The point of this enum is the retry decision. Retrying an expired token or a
 * rejected template is pure waste — the second attempt fails identically and
 * spends quota doing it — while a 130429 genuinely wants another go later.
 */
enum CloudErrorCode: int
{
    case AuthException = 0;
    case Unknown = 1;
    case ServiceUnavailable = 2;
    case ApiMethod = 3;
    case TooManyCalls = 4;
    case PermissionDenied = 10;
    case AccessTokenExpired = 190;
    case TemporarilyBlocked = 368;
    case RateLimitHit = 130429;
    case ReEngagementRequired = 131047;
    case MessageUndeliverable = 131026;
    case RecipientNotInAllowedList = 131030;
    case SpamRateLimitHit = 131048;
    case UnsupportedMessageType = 131051;
    case MediaDownloadError = 131052;
    case MediaUploadError = 131053;
    case PairRateLimitHit = 131056;
    case TemplateParamCountMismatch = 132000;
    case TemplateNotExists = 132001;
    case TemplateTextTooLong = 132005;
    case TemplateFormatPolicyViolation = 132007;
    case TemplateParamFormatMismatch = 132012;
    case TemplatePaused = 132015;
    case TemplateDisabled = 132016;
    case PhoneNumberNotRegistered = 133010;

    public function isRetryable(): bool
    {
        return in_array($this, [
            self::Unknown,
            self::ServiceUnavailable,
            self::TooManyCalls,
            self::RateLimitHit,
            self::PairRateLimitHit,
        ], true);
    }

    /**
     * @return class-string<CloudApiException>
     */
    public function exceptionClass(): string
    {
        return match ($this) {
            self::AuthException, self::ApiMethod, self::PermissionDenied,
            self::AccessTokenExpired, self::TemporarilyBlocked => AuthenticationException::class,

            self::TooManyCalls, self::RateLimitHit,
            self::SpamRateLimitHit, self::PairRateLimitHit => RateLimitException::class,

            self::ReEngagementRequired => ReEngagementRequiredException::class,

            self::MessageUndeliverable, self::RecipientNotInAllowedList,
            self::UnsupportedMessageType, self::PhoneNumberNotRegistered => RecipientException::class,

            self::MediaDownloadError, self::MediaUploadError => MediaException::class,

            self::TemplateParamCountMismatch, self::TemplateNotExists, self::TemplateTextTooLong,
            self::TemplateFormatPolicyViolation, self::TemplateParamFormatMismatch,
            self::TemplatePaused, self::TemplateDisabled => TemplateException::class,

            self::Unknown, self::ServiceUnavailable => TransientException::class,
        };
    }

    /**
     * A sentence naming the actual fix, because Meta's own message rarely does.
     */
    public function hint(): ?string
    {
        return match ($this) {
            self::AccessTokenExpired => 'The access token has expired. The token offered by the app dashboard quickstart lasts 24 hours; production needs a System User token.',
            self::ReEngagementRequired => 'Outside the 24-hour customer service window only an approved template can be delivered.',
            self::TemplateParamCountMismatch => 'The number of parameters does not match the approved template body.',
            self::TemplateNotExists => 'No approved template with that name and language exists. The language code must match the approved variant exactly.',
            self::TemplatePaused, self::TemplateDisabled => 'The template was paused or disabled for quality reasons in the WhatsApp Manager.',
            self::RecipientNotInAllowedList => 'In development mode, only numbers added to the app allow list can receive messages.',
            self::PhoneNumberNotRegistered => 'The business phone number is not registered for Cloud API use.',
            self::RateLimitHit, self::PairRateLimitHit, self::TooManyCalls => 'Throughput limit reached. Slow the send rate or queue the message for later.',
            default => null,
        };
    }
}
