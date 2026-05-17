<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        return view('users.index', [
            'users' => User::query()->latest()->paginate(15),
        ]);
    }

    public function create(): View
    {
        return view('users.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', 'in:admin,user'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ], [
            'name.required' => 'Vul een naam in.',
            'email.required' => 'Vul een e-mailadres in.',
            'email.email' => 'Vul een geldig e-mailadres in.',
            'email.unique' => 'Dit e-mailadres is al in gebruik.',
            'role.required' => 'Selecteer een rol.',
            'role.in' => 'Selecteer een geldige rol.',
            'password.required' => 'Vul een wachtwoord in.',
            'password.confirmed' => 'De wachtwoorden komen niet overeen.',
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()
            ->route('admin.users.index')
            ->with('status', 'Gebruiker is aangemaakt.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($request->user()->is($user)) {
            return redirect()
                ->route('admin.users.index')
                ->withErrors(['user' => 'Je kunt je eigen account niet verwijderen.']);
        }

        foreach ($user->reports as $report) {
            Storage::delete($report->generated_pdf_path);
        }

        foreach ($user->invoiceUploads as $invoiceUpload) {
            Storage::delete($invoiceUpload->original_pdf_path);
        }

        $user->delete();

        return redirect()
            ->route('admin.users.index')
            ->with('status', 'Gebruiker is verwijderd.');
    }
}
