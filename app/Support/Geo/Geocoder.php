<?php

namespace App\Support\Geo;

use Illuminate\Support\Str;

class Geocoder
{
    /**
     * Static dictionary of major Romanian cities -> coordinates.
     * Avoids an external API dependency for the common cases.
     *
     * @var array<string, array{lat: float, lng: float}>
     */
    private const CITIES = [
        'bucuresti' => ['lat' => 44.4268, 'lng' => 26.1025],
        'cluj-napoca' => ['lat' => 46.7712, 'lng' => 23.6236],
        'cluj' => ['lat' => 46.7712, 'lng' => 23.6236],
        'timisoara' => ['lat' => 45.7489, 'lng' => 21.2087],
        'iasi' => ['lat' => 47.1585, 'lng' => 27.6014],
        'constanta' => ['lat' => 44.1598, 'lng' => 28.6348],
        'craiova' => ['lat' => 44.3302, 'lng' => 23.7949],
        'brasov' => ['lat' => 45.6580, 'lng' => 25.6012],
        'galati' => ['lat' => 45.4353, 'lng' => 28.0080],
        'ploiesti' => ['lat' => 44.9469, 'lng' => 26.0215],
        'oradea' => ['lat' => 47.0465, 'lng' => 21.9189],
        'braila' => ['lat' => 45.2692, 'lng' => 27.9575],
        'arad' => ['lat' => 46.1866, 'lng' => 21.3123],
        'pitesti' => ['lat' => 44.8565, 'lng' => 24.8692],
        'sibiu' => ['lat' => 45.7983, 'lng' => 24.1256],
        'bacau' => ['lat' => 46.5670, 'lng' => 26.9146],
        'targu mures' => ['lat' => 46.5425, 'lng' => 24.5579],
        'baia mare' => ['lat' => 47.6572, 'lng' => 23.5680],
        'buzau' => ['lat' => 45.1500, 'lng' => 26.8333],
        'suceava' => ['lat' => 47.6514, 'lng' => 26.2556],
    ];

    /**
     * @return array{lat: float, lng: float}|null
     */
    public function coordinates(?string $city): ?array
    {
        if ($city === null || trim($city) === '') {
            return null;
        }

        $key = $this->normalize($city);

        if (isset(self::CITIES[$key])) {
            return self::CITIES[$key];
        }

        foreach (self::CITIES as $name => $coords) {
            if (str_contains($key, $name)) {
                return $coords;
            }
        }

        return null;
    }

    private function normalize(string $city): string
    {
        return Str::of($city)
            ->ascii()
            ->lower()
            ->squish()
            ->value();
    }
}
