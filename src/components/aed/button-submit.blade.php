<button {{ $attributes->merge(['type' => 'submit', 'class' => 'app-btn-submit']) }}>
    {{ $slot }}
</button>
