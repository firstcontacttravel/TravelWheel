<?php

namespace App\Livewire\Pages\Lounge;

use App\Models\Lounge as LoungeProduct;
use Illuminate\Support\Collection;
use Livewire\Component;

class LoungePlan extends Component
{
    public Collection $lounges;

    public function mount(int $id): void
    {
        $this->lounges = LoungeProduct::where('id', $id)->get();
    }

    public function render()
    {
        return view('livewire.pages.lounge.loungeplans', ['lounges' => $this->lounges])
            ->layout('layouts.app', ['title' => 'Lounge Details - TravelWheel']);
    }
}
