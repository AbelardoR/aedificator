@props(['disabled' => false])
@php $class['fail'] = ($errors->has($attributes->get('name'))) ? ' fail' : ''; @endphp
<textarea {{ $disabled ? 'disabled' : '' }} {!! $attributes->merge(['class' => 'app-input-textarea'.$class['fail']  ]) !!}>
{!!  $attributes->get('value') ?? $slot !!}
</textarea>
