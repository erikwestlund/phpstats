<x-layout title="Rent dashboard">
    <article class="max-w-3xl">
        <h1 class="text-3xl font-semibold tracking-tight">Rent dashboard</h1>
        <p class="mt-4 text-zinc-700 dark:text-zinc-300">
            The rentals are simulated; the listings, prices, and neighborhoods are generated for this example.
        </p>
        <p class="mt-4 text-zinc-700 dark:text-zinc-300">
            The dashboard is small and reactive, in the style of a Shiny app.
            Each input is a property of a Livewire component. When an input changes, the server filters the data with phplyr, summarizes it, refits a logistic model with phit, and returns new tables and phiz charts.
            The page has no JavaScript written for it.
        </p>
        <p class="mt-4 text-zinc-700 dark:text-zinc-300">
            The model estimates the probability that a rental costs more than the budget from its floor area and number of bedrooms.
        </p>

        <h2 class="mt-8 text-sm font-medium uppercase tracking-wide text-zinc-500">Code</h2>
        <x-code-tabs class="mt-3">
            <x-slot:chain>class RentDashboard extends Component
{
    public array $neighborhoods = [...];
    public int $minBedrooms = 1;
    public int $maxBedrooms = 4;
    public int $budget = 2500;

    public function render()
    {
        $rentals = phplyr(\Phibble\DataFrame::fromQuery(SimulatedRental::query()))
            ->filter(col('neighborhood')->isIn($this->neighborhoods),
                     col('bedrooms')->between($this->minBedrooms, $this->maxBedrooms))
            ->mutate(over_budget: col('monthly_rent')->gt($this->budget))
            ->get();

        $summary = phplyr($rentals)
            ->groupBy('neighborhood')
            ->summarize(rentals: n(), median_rent: median('monthly_rent'),
                        share_over_budget: mean('over_budget'))
            ->get();

        $model = phplyr($rentals)->glm('over_budget ~ area_m2 + bedrooms', family: 'binomial');

        return view('livewire.rent-dashboard', compact('rentals', 'summary', 'model'));
    }
}</x-slot:chain>
            <x-slot:pipe>class RentDashboard extends Component
{
    public array $neighborhoods = [...];
    public int $minBedrooms = 1;
    public int $maxBedrooms = 4;
    public int $budget = 2500;

    public function render()
    {
        $rentals = \Phibble\DataFrame::fromQuery(SimulatedRental::query()) |>
            filter(col('neighborhood')->isIn($this->neighborhoods),
                      col('bedrooms')->between($this->minBedrooms, $this->maxBedrooms)) |>
            mutate(over_budget: col('monthly_rent')->gt($this->budget));

        $summary = $rentals |>
            group_by('neighborhood') |>
            summarize(rentals: n(), median_rent: median('monthly_rent'),
                         share_over_budget: mean('over_budget'));

        $model = $rentals |> glm('over_budget ~ area_m2 + bedrooms', family: 'binomial');

        return view('livewire.rent-dashboard', compact('rentals', 'summary', 'model'));
    }
}</x-slot:pipe>
        </x-code-tabs>
        <pre class="mt-3 overflow-x-auto rounded-lg bg-zinc-100 p-4 text-sm dark:bg-zinc-900"><code>&lt;input type="checkbox" value="Harbor" wire:model.live="neighborhoods"&gt;
&lt;x-phiz :plot="$scatter" /&gt;</code></pre>
    </article>

    <div class="mt-10">
        <livewire:rent-dashboard />
    </div>
</x-layout>
