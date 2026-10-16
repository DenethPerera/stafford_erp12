<?php

namespace App\Http\Controllers;

use App\Models\CpscToken;
use App\Models\User;
use Illuminate\Http\Request;

class CpscTokenController extends Controller
{
    /**
     * Display a listing of CPSC tokens and the creation form/modal.
     */
    public function index()
    {
        $tokens = CpscToken::with('user')->latest()->paginate(15);
        $users = User::orderBy('name')->get();

        return view('cpsc_tokens.index', compact('tokens', 'users'));
    }

    /**
     * Store a newly created CPSC token in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id'    => 'required|exists:users,id',
            'api_secret' => 'required|string|max:500',
            'token'      => 'required|string',
            'expires_at' => 'required|date',
        ]);

        CpscToken::create($validated);

        return redirect()->route('cpsc-tokens.index')
            ->with('success', 'CPSC API Token saved successfully.');
    }

    /**
     * Remove the specified CPSC token from storage.
     */
    public function destroy(CpscToken $cpscToken)
    {
        $cpscToken->delete();

        return redirect()->route('cpsc-tokens.index')
            ->with('success', 'CPSC API Token deleted successfully.');
    }
}
