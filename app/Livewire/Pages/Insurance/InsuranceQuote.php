<?php

namespace App\Livewire\Pages\Insurance;

use App\Models\InsuranceQuote as InsuranceQuoteModel;
use Livewire\Component;

class InsuranceQuote extends Component
{
    public InsuranceQuoteModel $quote;

    public function mount(int $qid): void
    {
        $this->quote = InsuranceQuoteModel::findOrFail($qid);
    }

    public function render()
    {
        return view('livewire.pages.insurance.insurance-quote', ['quote' => $this->quote])
            ->layout('layouts.app', ['title' => 'Your Insurance Quote - TravelWheel']);
    }
}
