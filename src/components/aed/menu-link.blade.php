@props(['active' => false, 'icon' => '' ])
@php $class['active'] = ($active) ? 'active' : ''; @endphp

<a {{ $attributes->merge(['class' => 'menu-link '.$class['active'], 'role' => 'button' ]) }}>
    @if ($icon != '') <i class="{{ $icon }}"></i> @endif
    <span>{{ $slot }}</span>
</a>
