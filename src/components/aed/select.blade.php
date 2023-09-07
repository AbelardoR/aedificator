@props(['disabled' => false])
@php $class['fail'] = ($errors->has($attributes->get('name'))) ? ' fail' : ''; @endphp
<select {{ $disabled ? 'disabled' : '' }} {!! $attributes->merge(['class' => 'app-input-select'.$class['fail'], 'type' => 'text']) !!}>
    {{ $slot }}
</select>
