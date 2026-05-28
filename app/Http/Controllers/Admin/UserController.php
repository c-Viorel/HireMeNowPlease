<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(): View
    {
        return view('admin.users.index', [
            'users' => User::query()
                ->latest()
                ->paginate(15),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        if ($request->user()->is($user) && $request->boolean('is_active') === false) {
            return back()->withErrors(['user' => 'You cannot deactivate your own admin account.']);
        }

        $validated = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $user->update([
            'is_active' => $validated['is_active'],
        ]);

        return redirect()->route('admin.users.index')
            ->with('status', 'user-updated');
    }
}
