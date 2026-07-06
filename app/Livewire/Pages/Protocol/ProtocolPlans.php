<?php

namespace App\Livewire\Pages\Protocol;

use App\Models\Protocol as ProtocolProduct;
use Livewire\Component;

class ProtocolPlans extends Component
{
    public ProtocolProduct $protocol;

    public array $protocolData = [];

    public function mount(int $id): void
    {
        $this->protocol = ProtocolProduct::findOrFail($id);
        $this->protocolData = session('protocol_data', []);
    }

    public function render()
    {
        return view('livewire.pages.protocol.protocol-plans', [
            'price1' => $this->protocol->price1,
            'price2' => $this->protocol->price2,
            'service' => $this->protocolData['service'] ?? $this->protocol->service,
        ])->layout('layouts.app', ['title' => 'Protocol Plans - TravelWheel']);
    }
}
