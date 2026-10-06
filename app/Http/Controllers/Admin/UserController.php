<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class UserController extends Controller
{
    /*
    |------------------------------------------------------------------
    | GET /admin/users — Liste des utilisateurs (recherche + filtres)
    |------------------------------------------------------------------
    */
    public function index(Request $request): View
    {
        $users = User::query()
            ->when($request->filled('q'), function ($query) use ($request) {
                $q = '%' . $request->q . '%';
                $query->where(fn ($sub) => $sub
                    ->where('name', 'like', $q)
                    ->orWhere('prenom', 'like', $q)
                    ->orWhere('email', 'like', $q));
            })
            ->when(in_array($request->role, User::ROLES, true), fn ($query) => $query->where('role', $request->role))
            ->when(in_array($request->actif, ['0', '1'], true), fn ($query) => $query->where('actif', $request->actif === '1'))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        // Stats globales
        $stats = [
            'total'    => User::count(),
            'actifs'   => User::where('actif', true)->count(),
            'inactifs' => User::where('actif', false)->count(),
            'admins'   => User::where('role', User::ROLE_ADMIN)->count(),
        ];

        return view('admin.users.index', compact('users', 'stats'));
    }

    /*
    |------------------------------------------------------------------
    | GET /admin/users/create — Formulaire création
    |------------------------------------------------------------------
    */
    public function create(): View
    {
        return view('admin.users.create', ['user' => new User(['role' => User::ROLE_DONATEUR, 'actif' => true])]);
    }

    /*
    |------------------------------------------------------------------
    | POST /admin/users — Enregistrer un utilisateur
    |------------------------------------------------------------------
    */
    public function store(Request $request): RedirectResponse
    {
        $data = $this->valider($request);

        $user = User::create([
            ...$data,
            'actif'    => $request->boolean('actif'),
            'password' => Hash::make($data['password']),
        ]);

        // Compte créé par un admin : e-mail considéré comme vérifié
        $user->forceFill(['email_verified_at' => now()])->save();

        return redirect()
            ->route('admin.users.show', $user)
            ->with('success', "Utilisateur {$user->full_name} créé avec succès.");
    }

    /*
    |------------------------------------------------------------------
    | GET /admin/users/{user} — Détail d'un utilisateur
    |------------------------------------------------------------------
    */
    public function show(User $user): View
    {
        return view('admin.users.show', compact('user'));
    }

    /*
    |------------------------------------------------------------------
    | GET /admin/users/{user}/edit — Formulaire édition
    |------------------------------------------------------------------
    */
    public function edit(User $user): View
    {
        return view('admin.users.edit', compact('user'));
    }

    /*
    |------------------------------------------------------------------
    | PUT /admin/users/{user} — Mettre à jour un utilisateur
    |------------------------------------------------------------------
    */
    public function update(Request $request, User $user): RedirectResponse
    {
        $data  = $this->valider($request, $user);
        $actif = $request->boolean('actif');

        // Un admin ne peut ni se rétrograder ni se désactiver lui-même
        if ($user->is(Auth::user()) && ($data['role'] !== User::ROLE_ADMIN || !$actif)) {
            return back()
                ->withInput()
                ->with('error', 'Vous ne pouvez pas modifier votre propre rôle ni désactiver votre compte.');
        }

        if (empty($data['password'])) {
            unset($data['password']);
        } else {
            $data['password'] = Hash::make($data['password']);
        }

        $user->update([...$data, 'actif' => $actif]);

        return redirect()
            ->route('admin.users.show', $user)
            ->with('success', 'Utilisateur mis à jour avec succès.');
    }

    /*
    |------------------------------------------------------------------
    | DELETE /admin/users/{user} — Supprimer un utilisateur
    |------------------------------------------------------------------
    */
    public function destroy(User $user): RedirectResponse
    {
        if ($user->is(Auth::user())) {
            return back()->with('error', 'Vous ne pouvez pas supprimer votre propre compte.');
        }

        $nom = $user->full_name;
        $user->delete();

        return redirect()
            ->route('admin.users.index')
            ->with('success', "Utilisateur {$nom} supprimé.");
    }

    /*
    |------------------------------------------------------------------
    | Validation commune création / édition
    |------------------------------------------------------------------
    */
    private function valider(Request $request, ?User $user = null): array
    {
        return $request->validate(self::regles($user), self::MESSAGES);
    }

    /** Règles de validation, partagées avec l'assistant vocal */
    public static function regles(?User $user = null): array
    {
        return [
            'name'      => ['required', 'string', 'max:255'],
            'prenom'    => ['required', 'string', 'max:255'],
            'email'     => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user)],
            'telephone' => ['nullable', 'string', 'max:20'],
            'role'      => ['required', Rule::in(User::ROLES)],
            // Mot de passe obligatoire à la création, optionnel à l'édition
            'password'  => [$user ? 'nullable' : 'required', 'confirmed', Rules\Password::defaults()],
        ];
    }

    /** Messages de validation, partagés avec l'assistant vocal */
    public const MESSAGES = [
        'name.required'      => 'Le nom est obligatoire.',
        'prenom.required'    => 'Le prénom est obligatoire.',
        'email.required'     => "L'adresse e-mail est obligatoire.",
        'email.email'        => "L'adresse e-mail n'est pas valide.",
        'email.lowercase'    => "L'adresse e-mail doit être en minuscules.",
        'email.unique'       => 'Cette adresse e-mail est déjà utilisée.',
        'telephone.max'      => 'Le téléphone ne doit pas dépasser 20 caractères.',
        'role.required'      => 'Le rôle est obligatoire.',
        'role.in'            => 'Le rôle sélectionné est invalide.',
        'password.required'  => 'Le mot de passe est obligatoire.',
        'password.confirmed' => 'La confirmation du mot de passe ne correspond pas.',
        'password.min'       => 'Le mot de passe doit contenir au moins :min caractères.',
    ];
}
