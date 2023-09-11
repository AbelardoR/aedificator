@props(['disabled' => false, 'route_prefix' => '/dashboard/filemanager', 'box-class' => ''])
@php
    $class['base'] = 'app-input-image';
    $class['fail'] = ($errors->has($attributes->get('name'))) ? ' fail' : '';
    $class['box'] = $boxClass;
@endphp

<div class="{{ trim(implode(' ', $class)) }}" id="{{ $attributes->get('id').'-box' }}" >

    <span class="remove-img" role="button" >&#x2716;</span>

    <input type="hidden" {{ $disabled ? 'disabled' : '' }} {!! $attributes->merge(['class' => ''.$class['fail']]) !!} >

    <img src="{!!  $attributes->get('value') ?? $slot !!}">

    <span class="choser-file" role="button" route-prefix = "{{ $route_prefix }}">@lang('crud.choose.img')</span>

</div>


