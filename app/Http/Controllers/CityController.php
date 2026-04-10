<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\City;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

final class CityController extends Controller
{
    /** GET /api/cities — повертає популярні міста (обласні центри) */
    public function index(): JsonResponse
    {
        $cities = Cache::remember('cities_popular', now()->addMinutes(30), function (): array {
            return City::popular()
                ->limit(20)
                ->get(['id', 'name', 'region', 'is_region_center'])
                ->toArray();
        });

        return response()->json(['data' => $cities]);
    }

    /** GET /api/cities/search?q= — пошук міст (мін. 2 символи) */
    public function search(Request $request): JsonResponse
    {
        $q = trim((string) $request->input('q', ''));

        if (mb_strlen($q) < 2) {
            return response()->json(['data' => [], 'message' => 'Мінімум 2 символи'], 422);
        }

        $results = City::search($q)
            ->limit(15)
            ->get(['id', 'name', 'region', 'is_region_center'])
            ->toArray();

        return response()->json(['data' => $results, 'count' => count($results)]);
    }

    /** POST /api/cities/nearest — найближче місто за координатами (Haversine) */
    public function nearest(Request $request): JsonResponse
    {
        $request->validate([
            'latitude'  => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        $lat = (float) $request->input('latitude');
        $lng = (float) $request->input('longitude');

        $city = City::whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->selectRaw(
                'id, name, region, is_region_center,
                (6371 * acos(
                    cos(radians(?)) * cos(radians(latitude)) *
                    cos(radians(longitude) - radians(?)) +
                    sin(radians(?)) * sin(radians(latitude))
                )) AS distance',
                [$lat, $lng, $lat]
            )
            ->orderBy('distance')
            ->first();

        if (! $city) {
            return response()->json(['message' => 'Місто не знайдено'], 404);
        }

        return response()->json([
            'data' => [
                'id'               => $city->id,
                'name'             => $city->name,
                'region'           => $city->region,
                'is_region_center' => $city->is_region_center,
                'distance_km'      => round($city->distance, 1),
            ],
        ]);
    }
}
