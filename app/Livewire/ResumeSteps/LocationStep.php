<?php

declare(strict_types=1);

namespace App\Livewire\ResumeSteps;

use App\Models\City;
use App\Models\Resume;
use Livewire\Component;

class LocationStep extends Component
{
    public Resume $resume;
    public array  $formData = [];

    public string  $city               = '';
    public ?int    $cityId             = null;
    public string  $street             = '';
    public string  $building           = '';
    public ?float  $latitude           = null;
    public ?float  $longitude          = null;
    public bool    $noLocationBinding  = false;
    public array   $citySuggestions    = [];

    public function mount(Resume $resume, array $formData = []): void
    {
        $this->resume   = $resume;
        $this->formData = $formData;

        $location              = $formData['location'] ?? [];
        $this->city            = $location['city']               ?? '';
        $this->cityId          = $location['city_id']            ?? null;
        $this->street          = $location['street']             ?? '';
        $this->building        = $location['building']           ?? '';
        $this->latitude        = $location['latitude']           ?? null;
        $this->longitude       = $location['longitude']          ?? null;
        $this->noLocationBinding = $location['no_location_binding'] ?? false;
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.resume-steps.location-step');
    }

    public function updatedCity(string $value): void
    {
        $this->searchCities($value);
    }

    public function searchCities(string $query): void
    {
        if (strlen($query) < 2) {
            $this->citySuggestions = [];
            return;
        }

        $this->citySuggestions = City::where('name', 'like', "%{$query}%")
            ->orderByDesc('population')
            ->limit(6)
            ->get(['id', 'name', 'region', 'latitude', 'longitude'])
            ->map(fn (City $c) => [
                'id'        => $c->id,
                'name'      => $c->name,
                'region'    => $c->region,
                'latitude'  => $c->latitude,
                'longitude' => $c->longitude,
            ])
            ->toArray();
    }

    public function selectCity(int $id, string $name, ?float $lat, ?float $lon): void
    {
        $this->cityId          = $id;
        $this->city            = $name;
        $this->latitude        = $lat;
        $this->longitude       = $lon;
        $this->citySuggestions = [];
        $this->saveLocation();
    }

    public function toggleNoLocationBinding(): void
    {
        $this->noLocationBinding = !$this->noLocationBinding;

        if ($this->noLocationBinding) {
            $this->city      = '';
            $this->cityId    = null;
            $this->street    = '';
            $this->building  = '';
            $this->latitude  = null;
            $this->longitude = null;
        }

        $this->saveLocation();
    }

    public function saveLocation(): void
    {
        $this->dispatch('updateFormData',
            section: 'location',
            key: [
                'city'                => $this->city,
                'city_id'             => $this->cityId,
                'street'              => $this->street,
                'building'            => $this->building,
                'latitude'            => $this->latitude,
                'longitude'           => $this->longitude,
                'no_location_binding' => $this->noLocationBinding,
            ],
            value: null,
        );
    }
}
