<?php

namespace App\Http\Controllers;

use App\Models\Season;
use Illuminate\Http\Request;

class SeasonController extends Controller
{
    public function index()
    {
        // Fetch seasons with pagination, ordered by latest
        $seasons = Season::latest()->paginate(10);
        // Return the view with the seasons data
        return view('master-data.seasons.index', compact('seasons'));
    }

    public function store(Request $request)
    {
        // Validate the request data
        $request->validate([
            'name' => 'required|string|max:255|unique:seasons,name',
        ]);

        // Create a new season
        Season::create($request->only('name'));
        
        // Redirect back to the seasons index with a success message
        return redirect()->route('master-data.seasons.index')
                         ->with('success', 'Season created successfully.');
    }

    public function edit(Season $season)
    {
        return view('master-data.seasons.edit', compact('season'));
    }

    public function update(Request $request, Season $season)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:seasons,name,' . $season->id,
        ]);

        $season->update($request->only('name'));

        return redirect()->route('master-data.seasons.index')
                         ->with('success', 'Season updated successfully.');
    }

    public function destroy(Season $season)
    {
        $season->delete();

        return redirect()->route('master-data.seasons.index')
                         ->with('success', 'Season deleted successfully.');
    }
}