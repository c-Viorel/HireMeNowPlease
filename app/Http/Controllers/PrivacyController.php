<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\Privacy\DataExporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PrivacyController extends Controller
{
    public function edit(): \Illuminate\Contracts\View\View
    {
        return view('privacy.center');
    }

    public function export(Request $request, DataExporter $exporter): Response
    {
        $payload = $exporter->forUser($request->user());

        return response(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
            ->header('Content-Type', 'application/json')
            ->header('Content-Disposition', 'attachment; filename=hireme-datele-mele.json');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();

        DB::transaction(function () use ($user): void {
            $profile = $user->candidateProfile;

            if ($profile) {
                $profile->update([
                    'phone' => null,
                    'location' => null,
                    'headline' => null,
                    'summary' => null,
                    'skills' => [],
                    'cv_path' => null,
                ]);

                $profile->experiences()->delete();
                $profile->educations()->delete();
                $profile->certifications()->delete();
                $profile->links()->delete();
            }

            $user->forceFill([
                'name' => 'Utilizator anonimizat',
                'email' => 'sters+'.$user->id.'@hireme.invalid',
                'is_active' => false,
                'password' => bcrypt(bin2hex(random_bytes(16))),
            ])->save();
        });

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')
            ->with('status', 'Contul tau a fost anonimizat. Datele personale au fost sterse.');
    }
}
