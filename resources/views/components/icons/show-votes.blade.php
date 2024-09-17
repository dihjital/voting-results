@props([
    'class' => '',
    'title' => '',
])

<i 
    {{ $attributes->merge([
            'class' => "fa-solid fa-eye-slash text-gray-600 dark:text-gray-500 " . $class,
        ]) 
    }}
    title="{{ $title ?: __('Current votes will NOT be shown during voting') }}">
</i>