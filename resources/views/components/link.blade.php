@if ($valid)
    <a
        href="{{ $url }}"
        target="_blank"
        rel="noopener noreferrer"
        {{ $attributes }}
    >{{ $slot->isEmpty() ? __('WhatsApp') : $slot }}</a>
@endif
