<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * Vérification de l'adresse e-mail (MustVerifyEmail + middleware verified).
 */
class VerificationEmailTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_compte_non_verifie_est_renvoye_vers_la_page_de_verification(): void
    {
        $user = User::factory()->unverified()->create();

        foreach (['/dashboard', '/profile', '/adresses', '/collecte/dons'] as $url) {
            $this->actingAs($user)->get($url)->assertRedirect(route('verification.notice'));
        }
    }

    public function test_les_espaces_metier_exigent_aussi_un_compte_verifie(): void
    {
        $recycleur = User::factory()->unverified()->create(['role' => User::ROLE_RECYCLEUR]);
        $admin = User::factory()->unverified()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($recycleur)->get(route('recyclage.dashboard'))->assertRedirect(route('verification.notice'));
        $this->actingAs($admin)->get(route('admin.users.index'))->assertRedirect(route('verification.notice'));
    }

    public function test_un_compte_verifie_accede_au_tableau_de_bord(): void
    {
        $this->actingAs(User::factory()->create())->get('/dashboard')->assertOk();
    }

    public function test_la_page_de_verification_est_en_francais(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->get(route('verification.notice'))
            ->assertOk()
            ->assertSee('Vérifiez votre adresse e-mail')
            ->assertSee($user->email);
    }

    public function test_le_lien_signe_verifie_le_compte_et_ouvre_l_acces(): void
    {
        $user = User::factory()->unverified()->create();

        $lien = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $this->actingAs($user)->get($lien)->assertRedirect(route('dashboard', absolute: false).'?verified=1');

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $this->actingAs($user->fresh())->get('/dashboard')->assertOk();
    }

    public function test_un_lien_expire_est_refuse(): void
    {
        $user = User::factory()->unverified()->create();

        $lien = URL::temporarySignedRoute(
            'verification.verify',
            now()->subMinute(),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $this->actingAs($user)->get($lien)->assertForbidden();
        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_le_renvoi_du_lien_fonctionne(): void
    {
        Notification::fake();
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->from(route('verification.notice'))
            ->post(route('verification.send'))
            ->assertRedirect(route('verification.notice'))
            ->assertSessionHas('status', 'verification-link-sent');

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_l_inscription_envoie_l_email_de_verification(): void
    {
        Notification::fake();

        $this->post('/register', [
            'name'                  => 'Dupont',
            'prenom'                => 'Jean',
            'email'                 => 'jean.dupont@example.com',
            'role'                  => 'CLIENT',
            'password'              => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect(route('dashboard'));

        $user = User::where('email', 'jean.dupont@example.com')->firstOrFail();

        $this->assertFalse($user->hasVerifiedEmail());
        Notification::assertSentTo($user, VerifyEmail::class);

        // Le nouveau compte doit d'abord vérifier son e-mail
        $this->get('/dashboard')->assertRedirect(route('verification.notice'));
    }

    public function test_l_email_de_verification_est_en_francais(): void
    {
        $user = User::factory()->unverified()->create();

        $mail = (new VerifyEmail)->toMail($user);

        $this->assertSame('Vérifier mon adresse e-mail', $mail->subject);
        $this->assertSame('Vérifier mon adresse e-mail', $mail->actionText);
    }

    public function test_un_compte_cree_par_l_admin_est_deja_verifie_sans_email(): void
    {
        Notification::fake();
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)->post('/admin/users', [
            'name'                  => 'Mansouri',
            'prenom'                => 'Karim',
            'email'                 => 'karim@example.com',
            'telephone'             => '21655000001',
            'role'                  => User::ROLE_COLLECTEUR,
            'actif'                 => '1',
            'password'              => 'Password123!',
            'password_confirmation' => 'Password123!',
        ])->assertSessionHasNoErrors();

        $cree = User::where('email', 'karim@example.com')->firstOrFail();

        $this->assertTrue($cree->hasVerifiedEmail());
        Notification::assertNotSentTo($cree, VerifyEmail::class);

        // Le compte peut se connecter et accéder directement à son espace
        $this->post('/logout');
        $this->post('/login', ['email' => 'karim@example.com', 'password' => 'Password123!'])
            ->assertRedirect(route('logistique.tournees.index', absolute: false));
        $this->get(route('logistique.tournees.index'))->assertOk();
    }

    public function test_les_routes_publiques_techniques_ne_demandent_pas_de_verification(): void
    {
        foreach (['stripe.webhook', 'verification.notice', 'verification.send', 'logout'] as $nom) {
            $this->assertNotContains('verified', Route::getRoutes()->getByName($nom)->gatherMiddleware(), $nom);
        }

        $this->assertNotContains('verified', Route::getRoutes()->match(
            \Illuminate\Http\Request::create('/api/metrics')
        )->gatherMiddleware());
    }

    public function test_les_comptes_de_demo_sont_verifies(): void
    {
        $this->seed(\Database\Seeders\DatabaseSeeder::class);

        foreach (['admin', 'client', 'donateur', 'atelier', 'recycleur', 'collecteur'] as $compte) {
            $user = User::where('email', "{$compte}@retiss.tn")->firstOrFail();
            $this->assertTrue($user->hasVerifiedEmail(), "{$compte}@retiss.tn n'est pas vérifié");
            $this->assertTrue(Hash::check('password', $user->password), "{$compte}@retiss.tn : mot de passe");
        }

        $this->assertSame(0, User::whereNull('email_verified_at')->count());
    }
}
