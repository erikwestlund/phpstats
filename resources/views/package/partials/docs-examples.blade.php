{{--
    The example pages that use the functions a docs page documents or shows,
    from Package::relatedExamples(): each title linked to its page, the
    closest match first.
--}}
<ul class="space-y-2">
    @foreach ($examples as $example)
        <li><a href="{{ $example['url'] }}" class="block text-zinc-600 transition hover:text-(--l700) dark:text-zinc-400 dark:hover:text-(--l300)">{!! \App\Content\Libraries::mono(e($example['title'])) !!}</a></li>
    @endforeach
</ul>
