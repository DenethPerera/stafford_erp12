<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class ForwarderLoginController extends Controller
{
    public function create()
    {
        return view('auth.forwarder-login');
    }

    public function store(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $loggedIn = Auth::guard('web')->attemptWhen(
            $credentials,
            function ($user) {
                return $user->hasRole('forwarder');
            }
        );

        if (! $loggedIn) {
            throw ValidationException::withMessages([
                'email' => 'Invalid forwarder email or password.',
            ]);
        }

        $request->session()->regenerate();

        return redirect()->route('forwarder.dashboard');
    }

    public function destroy(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('forwarder.login');
    }
}
