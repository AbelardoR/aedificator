@props(['disabled' => false, 'route_prefix' => '/dashboard/filemanager', 'boxClass' => ''])
@php
    $class['base'] = 'app-input-editor';
    $class['fail'] = ($errors->has($attributes->get('name'))) ? ' fail' : '';
    $class['box'] = $boxClass;
@endphp

<div class="{{ trim(implode(' ', $class)) }}" id="{{ $attributes->get('id').'-box' }}" >
    <!-- This container will become the editable. -->
    <textarea {{ $disabled ? 'disabled' : '' }} {!! $attributes->merge(['class' => ''.$class['fail']  ]) !!}>
        {!!  $attributes->get('value') ?? $slot !!}
    </textarea>
</div>
