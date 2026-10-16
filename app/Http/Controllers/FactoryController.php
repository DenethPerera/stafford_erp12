<?php

namespace App\Http\Controllers;

use App\Models\Factory;
use App\Http\Requests\StoreFactoryRequest;
use App\Http\Requests\UpdateFactoryRequest;
use App\Models\Standard;

class FactoryController extends Controller
{
    public function index()
    {
        if (request()->wantsJson()) {
            return response()->json(Factory::where('status', 'active')->get());
        }
        $factories = Factory::all();
        return view('factories.index', compact('factories'));
    }

    public function create()
    {
        return view('factories.create');
    }

    public function store(StoreFactoryRequest $request)
    {
        Factory::create($request->validated());
        return redirect()->route('factories.index')->with('success', 'Factory created successfully.');
    }

    public function show(Factory $factory)
    {
        $factory->load('certificates.standard');
        $standards = Standard::all();
        return view('factories.show', compact('factory', 'standards'));
    }

    public function edit(Factory $factory)
    {
        return view('factories.edit', compact('factory'));
    }

    public function update(UpdateFactoryRequest $request, Factory $factory)
    {
        $factory->update($request->validated());
        return redirect()->route('factories.index')->with('success', 'Factory updated successfully.');
    }

    public function destroy(Factory $factory)
    {
        $factory->delete();
        return redirect()->route('factories.index')->with('success', 'Factory deleted successfully.');
    }
}
