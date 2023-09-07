@props(['disabled' => false])
@php $class['fail'] = ($errors->has($attributes->get('name'))) ? ' fail' : ''; @endphp
<input {{ $disabled ? 'disabled' : '' }} {!! $attributes->merge(['class' => 'app-input-date'.$class['fail'], 'type' => 'date']) !!}>
