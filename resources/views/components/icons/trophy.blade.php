@props([
    'class' => '',
    'title' => '',
])

<i 
    {{ $attributes->merge([
            'class' => "fa-solid fa-trophy text-gray-600 dark:text-gray-500 " . $class,
        ]) 
    }}
    title="{{ $title ?: __('This question belongs to a quiz') }}">
</i>