<?php

namespace App\Livewire;

use Livewire\Component;

class FlightSearch extends Component
{
    // Trip type
    public string $trip = 'OneWay';

    // One-way / Return fields
    public string $from = '';
    public string $to = '';
    public string $depart = '';
    public string $returning = '';

    // Passengers
    public int $adults = 1;
    public int $childs = 0;
    public int $kids = 0;

    // Cabin
    public string $flight_type = 'Y';

    // Language
    public string $language = 'en';

    // Multi-city legs
    public array $multiLegs = [
        ['from' => '', 'to' => '', 'depart' => ''],
        ['from' => '', 'to' => '', 'depart' => ''],
    ];

    // Loading state
    public bool $loading = false;

    // Validation rules
    protected function rules(): array
    {
        $rules = [
            'trip'        => 'required|in:OneWay,Return,multi',
            'adults'      => 'required|integer|min:1|max:9',
            'childs'      => 'required|integer|min:0|max:9',
            'kids'        => 'required|integer|min:0|max:9',
            'flight_type' => 'required|in:Y,S,C,F',
        ];

        if ($this->trip !== 'multi') {
            $rules['from']   = 'required|string|min:3';
            $rules['to']     = 'required|string|min:3';
            $rules['depart'] = 'required|date';

            if ($this->trip === 'Return') {
                $rules['returning'] = 'required|date|after_or_equal:depart';
            }
        } else {
            foreach ($this->multiLegs as $i => $_) {
                $rules["multiLegs.{$i}.from"]   = 'required|string|min:3';
                $rules["multiLegs.{$i}.to"]     = 'required|string|min:3';
                $rules["multiLegs.{$i}.depart"] = 'required|date';
            }
        }

        return $rules;
    }

    public function swapAirports(): void
    {
        [$this->from, $this->to] = [$this->to, $this->from];
    }

    public function addLeg(): void
    {
        if (count($this->multiLegs) < 6) {
            $this->multiLegs[] = ['from' => '', 'to' => '', 'depart' => ''];
        }
    }

    public function removeLeg(int $index): void
    {
        if (count($this->multiLegs) > 2) {
            array_splice($this->multiLegs, $index, 1);
            $this->multiLegs = array_values($this->multiLegs);
        }
    }

    public function incrementPassenger(string $field): void
    {
        if ($this->$field < 9) {
            $this->$field++;
        }
    }

    public function decrementPassenger(string $field): void
    {
        $min = $field === 'adults' ? 1 : 0;
        if ($this->$field > $min) {
            $this->$field--;
        }
    }

    public function search(): void
    {
        $this->validate();

        $this->loading = true;

        if ($this->trip !== 'multi') {
            $params = http_build_query([
                'trip'        => $this->trip,
                'from'        => [$this->from],
                'to'          => [$this->to],
                'depart'      => [$this->depart],
                'returning'   => $this->returning,
                'adults'      => $this->adults,
                'childs'      => $this->childs,
                'kids'        => $this->kids,
                'flight_type' => $this->flight_type,
                'language'    => $this->language,
            ]);
        } else {
            $params = http_build_query([
                'trip'        => $this->trip,
                'from'        => array_column($this->multiLegs, 'from'),
                'to'          => array_column($this->multiLegs, 'to'),
                'depart'      => array_column($this->multiLegs, 'depart'),
                'adults'      => $this->adults,
                'childs'      => $this->childs,
                'kids'        => $this->kids,
                'flight_type' => $this->flight_type,
                'language'    => $this->language,
            ]);
        }

        $this->redirect(route('air.flightpost') . '?' . $params);
    }

    public function render()
    {
        return view('livewire.pages.flight.flight-search');
    }
    
}