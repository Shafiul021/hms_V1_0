<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreNursingNoteRequest;
use App\Http\Resources\NursingNoteResource;
use App\Models\Admission;
use App\Services\IpdService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class NursingNoteController extends Controller
{
    /**
     * Add a nursing note to an admission.
     *
     * POST /api/admissions/{id}/notes
     * Role: admin|nurse
     */
    public function store(StoreNursingNoteRequest $request, int $id, IpdService $service): NursingNoteResource
    {
        $admission = Admission::findOrFail($id);
        $note      = $service->addNote($admission, $request->validated(), auth()->id());

        return new NursingNoteResource($note);
    }

    /**
     * List all nursing notes for an admission (chronological).
     *
     * GET /api/admissions/{id}/notes
     * Role: admin|doctor|nurse
     */
    public function index(int $id): AnonymousResourceCollection
    {
        $admission = Admission::findOrFail($id);

        $notes = $admission->nursingNotes()
            ->with('nurse')
            ->orderBy('recorded_at', 'asc')
            ->get();

        return NursingNoteResource::collection($notes);
    }
}
