<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BedResource;
use App\Http\Resources\WardResource;
use App\Models\Ward;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class WardController extends Controller
{
    /**
     * List all wards (optionally with bed counts).
     *
     * GET /api/wards
     * Role: admin|doctor|nurse
     */
    public function index(): AnonymousResourceCollection
    {
        $wards = Ward::withCount('beds')->get();

        return WardResource::collection($wards);
    }

    /**
     * List all beds in a specific ward with their current status.
     *
     * GET /api/wards/{id}/beds
     * Role: admin|nurse
     */
    public function beds(int $id): AnonymousResourceCollection
    {
        $ward = Ward::findOrFail($id);
        $beds = $ward->beds()->orderBy('bed_number')->get();

        return BedResource::collection($beds);
    }
}
