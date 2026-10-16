<?php

namespace App\Http\Controllers;

use App\Models\Standard;
use App\Http\Requests\StoreStandardRequest;
use App\Http\Requests\UpdateStandardRequest;

class StandardController extends Controller
{
    public function index()
    {
        $standards = Standard::all();
        return view('standards.index', compact('standards'));
    }

    public function create()
    {
        return view('standards.create');
    }

    public function store(StoreStandardRequest $request)
    {
        Standard::create($request->validated());
        return redirect()->route('standards.index')->with('success', 'Standard created successfully.');
    }

    public function show(Standard $standard)
    {
        return view('standards.show', compact('standard'));
    }

    public function edit(Standard $standard)
    {
        return view('standards.edit', compact('standard'));
    }

    public function update(UpdateStandardRequest $request, Standard $standard)
    {
        $standard->update($request->validated());
        return redirect()->route('standards.index')->with('success', 'Standard updated successfully.');
    }

    public function destroy(Standard $standard)
    {
        $standard->delete();
        return redirect()->route('standards.index')->with('success', 'Standard deleted successfully.');
    }
}
