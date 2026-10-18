{{-- The PHPstats mark: rising bars and a fitted line on PHP purple. --}}
@props(['size' => 28])

<svg width="{{ $size }}" height="{{ $size }}" viewBox="0 0 32 32" fill="none" aria-hidden="true" {{ $attributes }}>
    <defs>
        <linearGradient id="phpstats-mark" x1="0" y1="0" x2="32" y2="32" gradientUnits="userSpaceOnUse">
            <stop stop-color="#9397cb" />
            <stop offset="1" stop-color="#6d28d9" />
        </linearGradient>
    </defs>
    <rect width="32" height="32" rx="8" fill="url(#phpstats-mark)" />
    <rect x="7" y="18" width="4" height="7" rx="1.2" fill="white" fill-opacity=".55" />
    <rect x="14" y="13" width="4" height="12" rx="1.2" fill="white" fill-opacity=".75" />
    <rect x="21" y="9" width="4" height="16" rx="1.2" fill="white" fill-opacity=".95" />
    <path d="M6 17.5 26 6.5" stroke="#fde68a" stroke-width="1.8" stroke-linecap="round" />
</svg>
