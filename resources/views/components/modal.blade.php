@props([
    'show' => false,
    'maxWidth' => 'md',
    'title' => null,
    'type' => 'default',
])

@php
$maxWidth = [
    'sm' => 'max-w-sm',
    'md' => 'max-w-md',
    'lg' => 'max-w-lg',
    'xl' => 'max-w-xl',
    '2xl' => 'max-w-2xl',
][$maxWidth];

$typeClasses = [
    'default' => 'text-gray-900 dark:text-gray-100',
    'warning' => 'text-amber-900 dark:text-amber-100',
    'danger' => 'text-red-900 dark:text-red-100',
    'success' => 'text-emerald-900 dark:text-emerald-100',
][$type] ?? 'text-gray-900 dark:text-gray-100';

$iconClasses = [
    'default' => 'text-blue-500',
    'warning' => 'text-amber-500', 
    'danger' => 'text-red-500',
    'success' => 'text-emerald-500',
][$type] ?? 'text-blue-500';
@endphp

@if ($show)
    <div class="fixed inset-0 bg-gray-500 dark:bg-gray-900 bg-opacity-75 dark:bg-opacity-75 flex items-center justify-center z-50 p-4">
        <div class="bg-white dark:bg-zinc-900 rounded-xl shadow-2xl {{ $maxWidth }} w-full transform transition-all">
            {{-- Header --}}
            @if ($title)
                <div class="px-6 py-4 border-b border-gray-200 dark:border-zinc-700">
                    <div class="flex items-center space-x-3">
                        {{-- Icon based on type --}}
                        <div class="flex-shrink-0">
                            @if ($type === 'danger')
                                <svg class="w-6 h-6 {{ $iconClasses }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
                                </svg>
                            @elseif ($type === 'warning')
                                <svg class="w-6 h-6 {{ $iconClasses }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            @elseif ($type === 'success')
                                <svg class="w-6 h-6 {{ $iconClasses }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            @else
                                <svg class="w-6 h-6 {{ $iconClasses }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            @endif
                        </div>
                        <h3 class="text-lg font-semibold {{ $typeClasses }}">
                            {{ $title }}
                        </h3>
                    </div>
                </div>
            @endif

            {{-- Body --}}
            <div class="px-6 py-4">
                <div class="{{ $typeClasses }}">
                    {{ $slot }}
                </div>
            </div>

            {{-- Footer --}}
            @isset($footer)
                <div class="px-6 py-4 bg-gray-50 dark:bg-zinc-800 border-t border-gray-200 dark:border-zinc-700 rounded-b-xl">
                    <div class="flex items-center justify-end space-x-3">
                        {{ $footer }}
                    </div>
                </div>
            @endisset
        </div>
    </div>
@endif