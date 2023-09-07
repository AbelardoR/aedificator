<button {{ $attributes->merge(['type' => 'submit', 'class' => 'app-btn-delete']) }}>
    {{ $slot }}
</button>
