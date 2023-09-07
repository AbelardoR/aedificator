@props(['disabled' => false])
@php $class['fail'] = ($errors->has($attributes->get('name'))) ? ' fail' : ''; @endphp
<input value="" type='hidden' name="{{ $attributes->get('name') }}" class='app-input-checkbox'>
<input {{ $disabled ? 'disabled' : '' }} {{ $checked ? 'checked' : '' }} {!! $attributes->merge(['type' => 'checkbox', 'class' => 'app-checkbox'.$class['fail']]) !!}>
