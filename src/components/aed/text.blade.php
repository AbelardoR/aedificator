@props(['disabled' => false, 'errors' => null])
@php $class['fail'] = ($errors->has($attributes->get('name'))) ? ' fail' : ''; @endphp
<input {{ $disabled ? 'disabled' : '' }} {!! $attributes->merge(['class' => 'app-input-text'.$class['fail'], 'type' => 'text']) !!}>
