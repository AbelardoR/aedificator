@props(['active' => false, 'icon' => '' ])
@php $class['active'] = ($active) ? 'active' : ''; @endphp

<a {{ $attributes->merge(['class' => 'menu-link '.$class['active'], 'role' => 'button' ]) }}>
    @if ($icon != '') {!! $icon !!} @endif
    <span>{{ $slot }}</span>
</a>
