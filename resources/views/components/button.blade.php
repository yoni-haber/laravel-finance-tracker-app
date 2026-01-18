@props([
    'type' => 'button',
    'variant' => 'primary',
    'size' => 'md',
    'loading' => false,
    'loadingText' => 'Loading...',
])

@php
$baseClasses = 'inline-flex items-center justify-center font-medium rounded-lg focus:outline-none focus:ring-2 focus:ring-offset-2 transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed';

$sizeClasses = [
    'sm' => 'px-3 py-1.5 text-sm',
    'md' => 'px-4 py-2 text-sm',
    'lg' => 'px-6 py-3 text-base',
][$size];

$variantClasses = [
    'primary' => 'bg-blue-600 hover:bg-blue-700 text-white focus:ring-blue-500',
    'secondary' => 'bg-gray-200 hover:bg-gray-300 text-gray-900 focus:ring-gray-500 dark:bg-zinc-700 dark:hover:bg-zinc-600 dark:text-gray-100 dark:focus:ring-zinc-500',
    'danger' => 'bg-red-600 hover:bg-red-700 text-white focus:ring-red-500',
    'success' => 'bg-emerald-600 hover:bg-emerald-700 text-white focus:ring-emerald-500',
    'warning' => 'bg-amber-600 hover:bg-amber-700 text-white focus:ring-amber-500',
][$variant];
@endphp

<button 
    type="{{ $type }}"
    class="{{ $baseClasses }} {{ $sizeClasses }} {{ $variantClasses }}"
    {{ $loading ? 'disabled' : '' }}
    {{ $attributes }}
>
    @if ($loading)
        <svg class="animate-spin -ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        {{ $loadingText }}
    @else
        {{ $slot }}
    @endif
</button>