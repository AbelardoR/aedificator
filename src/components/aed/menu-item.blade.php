@props(['active' => false, 'icon' => '', 'hasChild' => false, 'hidden' => false ])

@php
$class['has-chilren'] = ($hasChild) ? 'has-child' : '';
$class['active'] = ($active) ? 'active' : '';
@endphp

<div {{ $hidden ? 'hidden' : '' }} {{ $attributes->merge(['class' => 'menu-item '.trim(implode(' ', $class))]) }}>
    @if ($icon != '') <i class="{{ $icon }}"></i> @endif
    {{ $slot }}

@isset($submenu)
    <button class="trigger-sub-menu"><i class="fa-solid fa-angle-down"></i></button>
    <div class="sub-menu">
        {{ $submenu }}
    </div>
@endisset

</div>
