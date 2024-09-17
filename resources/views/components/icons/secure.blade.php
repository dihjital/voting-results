@props([
    'class' => '',
    'title' => '',
])

<i 
    {{ $attributes->merge([
            'class' => "fa-solid fa-user-secret text-gray-600 dark:text-gray-500 " . $class,
        ]) 
    }}
    title="{{ $title ?: __('A valid e-mail is required to vote for this question') }}">
</i>