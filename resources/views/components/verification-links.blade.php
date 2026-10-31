{{--
    The About page's Validation list: every package that publishes verification
    reports links its Verification section, and a package that documents its
    checks in a docs page named verification links that page.
--}}
<ul>
    @foreach (\App\Content\Package::all() as $package)
        @php
            $url = $package->verifications() !== [] ? $package->url('verification')
                : (in_array('verification', $package->docsPaths(), true) ? $package->url('docs', ['path' => 'verification']) : null);
        @endphp
        @if ($url)
            <li><a href="{{ $url }}" class="font-mono">{{ $package->name() }}</a> verification</li>
        @endif
    @endforeach
</ul>
