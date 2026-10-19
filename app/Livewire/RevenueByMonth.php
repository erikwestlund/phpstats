<?php

namespace App\Livewire;

use App\Models\Order;
use Livewire\Component;

use function Phplyr\{col, floor_date, group_by, mutate, sum, summarise};

class RevenueByMonth extends Component
{
    public string $channel = 'All';

    public function render()
    {
        $orders = Order::query()
            ->when($this->channel !== 'All', fn ($query) => $query->where('channel', $this->channel));

        $monthly = \Phibble\DataFrame::fromQuery($orders)
            |> mutate(month: floor_date(col('placed_on'), 'month'))
            |> group_by('month', 'customer')
            |> summarise(revenue: sum('total'));

        $chart = \Phiz\Plot::of($monthly)
            ->aes(x: 'month', y: 'revenue', color: 'customer')
            ->line()
            ->point()
            ->labs(x: null, y: 'Revenue ($)', color: null);

        return view('livewire.revenue-by-month', ['chart' => $chart]);
    }
}
