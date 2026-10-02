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
        $this->lounges = LoungeProduct::active()->where('id', $id)->get();

        if ($this->lounges->isEmpty()) {
            $this->redirectRoute('air.lounge');
            session()->flash('error', 'That lounge is no longer available. Please choose another.');
        }
    }

    public function render()
    {
        return view('livewire.pages.lounge.loungeplans', ['lounges' => $this->lounges])
            ->layout('layouts.app', ['title' => 'Lounge Details - TravelWheel']);
    }
}
