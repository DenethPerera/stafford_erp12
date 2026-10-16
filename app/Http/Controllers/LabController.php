<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Lab;

class LabController extends Controller
{
    public function index()
    {
        $labs = Lab::orderBy('name')->paginate(10);
        return view('labs.index', compact('labs'));
    }

    public function simulatePo(Lab $lab, \App\Services\ApiIntegrationService $service)
    {
        // Generate a random PO number to simulate a real one
        $poNumber = 'PO-' . rand(100000, 999999);
        $service->fetchPoAndAssignToLab($poNumber, $lab->id);
        
        return redirect()->route('labs.index')->with('success', "Mock Data ($poNumber) has been generated and assigned to {$lab->name}!");
    }

    public function create()
    {
        return view('labs.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:labs,name',
            'lab_code' => 'nullable|string|max:100',
            'email' => 'nullable|email|max:255',
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'address_line1' => 'nullable|string|max:255',
            'address_line2' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->has('is_active');

        Lab::create($validated);

        return redirect()->route('labs.index')->with('success', 'Lab created successfully.');
    }

    public function show(Lab $lab)
    {
        return view('labs.show', compact('lab'));
    }

    public function edit(Lab $lab)
    {
        return view('labs.edit', compact('lab'));
    }

    public function update(Request $request, Lab $lab)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:labs,name,' . $lab->id,
            'lab_code' => 'nullable|string|max:100',
            'email' => 'nullable|email|max:255',
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'address_line1' => 'nullable|string|max:255',
            'address_line2' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->has('is_active');

        $lab->update($validated);

        return redirect()->route('labs.index')->with('success', 'Lab updated successfully.');
    }

    public function destroy(Lab $lab)
    {
        $lab->delete();
        return redirect()->route('labs.index')->with('success', 'Lab deleted successfully.');
    }
}
