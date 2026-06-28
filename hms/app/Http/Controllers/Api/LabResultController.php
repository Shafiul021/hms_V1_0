<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateLabResultRequest;
use App\Http\Resources\LabResultResource;
use App\Models\LabRequest;
use App\Models\LabResult;
use App\Services\OpdService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LabResultController extends Controller
{
    /**
     * Upload or update a lab result for a given lab request.
     *
     * PATCH /api/lab-results/{id}
     * Here {id} refers to the LabRequest id (the lab request that needs a result).
     * Role: admin|nurse
     */
    public function update(UpdateLabResultRequest $request, int $id, OpdService $service): LabResultResource
    {
        $labRequest = LabRequest::findOrFail($id);
        $result     = $service->uploadLabResult($labRequest, $request->validated(), auth()->id());

        return new LabResultResource($result);
    }

    /**
     * Display the specified lab result.
     *
     * GET /api/lab-results/{id}
     * Role: admin|doctor|patient
     */
    public function show(int $id): LabResultResource
    {
        $result = LabResult::with(['labRequest.test', 'labRequest.doctor.user', 'technician'])->findOrFail($id);

        return new LabResultResource($result);
    }

    /**
     * Stream the result file via a signed temporary URL.
     *
     * GET /api/lab-results/{id}/download
     * Middleware: signed
     */
    public function download(int $id, OpdService $service): StreamedResponse|JsonResponse
    {
        $result   = LabResult::findOrFail($id);
        $filePath = $service->getResultFilePath($result);

        if (! $filePath || ! file_exists($filePath)) {
            return response()->json(['message' => 'Result file not found.'], 404);
        }

        $filename  = basename($result->result_file);
        $mimeType  = mime_content_type($filePath) ?: 'application/octet-stream';

        return response()->streamDownload(function () use ($filePath) {
            readfile($filePath);
        }, $filename, [
            'Content-Type'        => $mimeType,
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
