<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AbandonedObject;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AbandonedObjectController extends Controller
{
    public function index(): JsonResponse
    {
        $objects = AbandonedObject::all();
        return response()->json($objects);
    }

    public function byType(?int $type = null): JsonResponse
    {
        $objects = AbandonedObject::where('type', $type)->whereNotNull('borders')->get();
        return response()->json($objects);
    }

    public function create(Request $request)
    {

    }
}
