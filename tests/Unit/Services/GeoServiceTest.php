<?php

use App\Services\GeoService;

it('calculates the distance between Riyadh center and the airport as roughly 30-40km', function () {
    $distance = GeoService::distanceMeters(24.7136, 46.6753, 24.9578, 46.6989);

    expect($distance)->toBeGreaterThan(30000)->toBeLessThan(40000);
});

it('returns zero distance for identical coordinates', function () {
    $distance = GeoService::distanceMeters(24.7136, 46.6753, 24.7136, 46.6753);

    expect($distance)->toBe(0);
});

it('returns a distance under 150m for a nearby point within the geofence', function () {
    // إزاحة صغيرة جدًا (~0.001 درجة تقريبًا 100 متر أو أقل)
    $distance = GeoService::distanceMeters(24.7136, 46.6753, 24.7145, 46.6753);

    expect($distance)->toBeLessThan(150);
});
