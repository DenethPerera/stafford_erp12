<?php

namespace App\Http\Controllers;

use App\Models\TestReportType;
use Illuminate\Http\Request;

class TestReportTypeController extends Controller
{
    public function index()
    {
        $testReportTypes = TestReportType::orderBy('created_at', 'desc')->get();
        return view('test_report_types.index', compact('testReportTypes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:test_report_types,code',
            'category' => 'nullable|string|max:100',
            'default_validity_days' => 'required|integer|min:1',
            'standard_citation' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        TestReportType::create($validated);

        return redirect()->route('test-report-types.index')->with('success', 'Test Report Type created successfully.');
    }

    public function show(TestReportType $testReportType)
    {
        return response()->json($testReportType);
    }

    public function update(Request $request, TestReportType $testReportType)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:test_report_types,code,' . $testReportType->id,
            'category' => 'nullable|string|max:100',
            'default_validity_days' => 'required|integer|min:1',
            'standard_citation' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        $testReportType->update($validated);

        return redirect()->route('test-report-types.index')->with('success', 'Test Report Type updated successfully.');
    }

    public function destroy(TestReportType $testReportType)
    {
        $testReportType->delete();
        return redirect()->route('test-report-types.index')->with('success', 'Test Report Type deleted successfully.');
    }
}
