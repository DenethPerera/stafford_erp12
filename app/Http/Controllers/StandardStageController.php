<?php

namespace App\Http\Controllers;

use App\Models\StandardStage;
use App\Models\Standard;
use App\Http\Requests\StoreStandardStageRequest;
use App\Http\Requests\UpdateStandardStageRequest;
use Illuminate\Http\Request;

class StandardStageController extends Controller
{
    public function index(Request $request)
    {
        $standards = Standard::all();
        $selectedStandardId = $request->input('standard_id');
        
        $stages = collect();
        $selectedStandard = null;
        
        if ($selectedStandardId) {
            $stages = StandardStage::with('standard')
                ->where('standard_id', $selectedStandardId)
                ->orderBy('order_sequence')
                ->get();
            $selectedStandard = Standard::find($selectedStandardId);
        }

        return view('standard_stages.index', compact('stages', 'standards', 'selectedStandardId', 'selectedStandard'));
    }

    public function create(Request $request)
    {
        $standards = Standard::all();
        $preSelectedStandardId = $request->input('standard_id');
        return view('standard_stages.create', compact('standards', 'preSelectedStandardId'));
    }

    public function store(StoreStandardStageRequest $request)
    {
        StandardStage::create($request->validated());
        return redirect()->route('standard-stages.index', ['standard_id' => $request->standard_id])->with('success', 'Renewal stage created successfully.');
    }

    public function show($id)
    {
        $standardStage = StandardStage::findOrFail($id);
        return view('standard_stages.show', compact('standardStage'));
    }

    public function edit($id)
    {
        $standardStage = StandardStage::findOrFail($id);
        $standards = Standard::all();
        return view('standard_stages.edit', compact('standardStage', 'standards'));
    }

    public function update(UpdateStandardStageRequest $request, $id)
    {
        $standardStage = StandardStage::findOrFail($id);
        $standardStage->update($request->validated());
        return redirect()->route('standard-stages.index', ['standard_id' => $request->standard_id])->with('success', 'Renewal stage updated successfully.');
    }

    public function reorder(Request $request)
{
    $request->validate([
        'order'   => 'required|array',
        'order.*' => 'exists:standard_stages,id',
    ]);

    \DB::transaction(function () use ($request) {
        // Pass 1: move everything to temporary negative values to avoid unique clashes
        foreach ($request->order as $index => $stageId) {
            StandardStage::where('id', $stageId)->update(['order_sequence' => -($index + 1)]);
        }

        // Pass 2: set the real final order
        foreach ($request->order as $index => $stageId) {
            StandardStage::where('id', $stageId)->update(['order_sequence' => $index + 1]);
        }
    });

    return response()->json(['status' => 'ok']);
}
    public function destroy($id)
    {
        $standardStage = StandardStage::findOrFail($id);
        $standardId = $standardStage->standard_id;
        $standardStage->delete();
        return redirect()->route('standard-stages.index', ['standard_id' => $standardId])->with('success', 'Renewal stage deleted successfully.');
    }
}
