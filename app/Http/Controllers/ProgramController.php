<?php

namespace App\Http\Controllers;

use App\Models\Program;
use Illuminate\Http\Request;

class ProgramController extends Controller
{
    public function index()
    {
        // Fetch programs with pagination, ordered by latest
        $programs = Program::latest()->paginate(10);
        // Return the view with the programs data
        return view('master-data.programs.index', compact('programs'));
    }

    public function store(Request $request)
    {
        // Validate the request data
        $request->validate([
            'name' => 'required|string|max:255|unique:programs,name',
        ]);

        // Create a new program
        Program::create($request->only('name'));
        
        // Redirect back to the programs index with a success message
        return redirect()->route('master-data.programs.index')
                         ->with('success', 'Program created successfully.');
    }

    public function edit(Program $program)
    {
        return view('master-data.programs.edit', compact('program'));
    }

    public function update(Request $request, Program $program)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:programs,name,' . $program->id,
        ]);

        $program->update($request->only('name'));

        return redirect()->route('master-data.programs.index')
                         ->with('success', 'Program updated successfully.');
    }

    public function destroy(Program $program)
    {
        $program->delete();

        return redirect()->route('master-data.programs.index')
                         ->with('success', 'Program deleted successfully.');
    }
}