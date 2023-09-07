@props(['parents' => [], 'current' => false, 'hidden' => true ])
<div class="breadcrumb" @if (!$hidden) hidden @endif>
<ul {{ $attributes->merge(['class' => '' ]) }}>
    @if ($attributes->has('home'))
        <li class="set-home">
            <a href="{{ route($attributes->get('home')) }}" class="upf">{{ __('breadcrumb.'.$attributes->get('home')) }}</a>
        </li>
    @endif
    @empty(!$parents)
        @foreach ($parents as $name => $route)
            <li class="parent">
                <a href="{{ $route }}" class="upf">
                    {{ __(''.$name) }}
                </a>
            </li>
        @endforeach
    @endempty

    <li class="{{ ($current) ? 'current' : ' ' }}">
        <a href="" class="upf">{{ $slot }}</a>
    </li>
</ul>
</div>
