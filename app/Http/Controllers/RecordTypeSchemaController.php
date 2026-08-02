<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ExperimentRecord;
use Illuminate\Http\JsonResponse;

class RecordTypeSchemaController extends Controller
{
    /**
     * Expose ExperimentRecord::fieldSchema() over the API, so clients can
     * flatten `details` JSON without hardcoding a duplicate of the schema.
     */
    public function show(string $recordType): JsonResponse
    {
        return response()->json([
            'record_type' => $recordType,
            'label' => ExperimentRecord::RECORD_TYPES[$recordType] ?? null,
            'family' => ExperimentRecord::familyOf($recordType),
            'fields' => ExperimentRecord::fieldSchema($recordType),
        ]);
    }
}
