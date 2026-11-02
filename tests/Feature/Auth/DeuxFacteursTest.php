<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

/**
 * Authentification à deux facteurs (Fortify) : activation depuis le profil
 * et deuxième étape de connexion.
 */
class DeuxFacteursTest extends TestCase
{
    use RefreshDatabase;

    private const CODES_RECUPERATION = ['code-recup-0001', 'code-recup-0002', 'code-recup-0003'];

    /** Utilisateur avec une 2FA déjà confirmée ; renvoie le secret TOTP. */
    private function avecDeuxFacteurs(User $user): string
    {
        $secret = (new Google2FA)->generateSecretKey();

        $user->forceFill([
            'two_factor_secret'         => encrypt($secret),
            'two_factor_recovery_codes' => encrypt(json_encode(self::CODES_RECUPERATION)),
            'two_factor_confirmed_at'   => now(),
        ])->save();

        return $secret;
    }

    private function codeValide(string $secret): string
    {
        return (new Google2FA)->getCurrentOtp($secret);
    }

    private function motDePasseConfirme(): array
    {
        return ['auth.password_confirmed_at' => time()];
    }

    // ---------------------------------------------------------------
    // Gestion depuis le profil
    // ---------------------------------------------------------------

    public function test_la_page_2fa_exige_la_confirmation_du_mot_de_passe(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('deux-facteurs.show'))->assertRedirect(route('password.confirm'));
        $this->actingAs($user)->post(route('deux-facteurs.store'))->assertRedirect(route('password.confirm'));
        $this->assertNull($user->fresh()->two_factor_secret);
    }

    public function test_le_profil_affiche_la_section_2fa(): void
    {
        $this->actingAs(User::factory()->create())->get('/profile')
            ->assertOk()
            ->assertSee('Authentification à deux facteurs')
            ->assertSee(route('deux-facteurs.show'));
    }

    public function test_activation_puis_confirmation_avec_un_code_valide(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->withSession($this->motDePasseConfirme())
            ->post(route('deux-facteurs.store'))
            ->assertRedirect(route('deux-facteurs.show'));

        $user->refresh();
        $this->assertNotNull($user->two_factor_secret);
        $this->assertNull($user->two_factor_confirmed_at);
        $this->assertFalse($user->hasEnabledTwoFactorAuthentication(), 'Non confirmée = pas encore active');
        $this->assertCount(8, $user->recoveryCodes());

        // QR code et clé affichés
        $secret = decrypt($user->two_factor_secret);
        $this->get(route('deux-facteurs.show'))
            ->assertOk()
            ->assertSee('<svg', false)
            ->assertSee($secret)
            ->assertSee('Confirmer l\'activation', false);

        $this->post(route('deux-facteurs.confirmer'), ['code' => $this->codeValide($secret)])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('deux-facteurs.show'));

        $this->assertTrue($user->fresh()->hasEnabledTwoFactorAuthentication());
        $this->get(route('deux-facteurs.show'))->assertSee('Codes de récupération')->assertSee($user->fresh()->recoveryCodes()[0]);
    }

    public function test_la_confirmation_refuse_un_code_faux(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->withSession($this->motDePasseConfirme())->post(route('deux-facteurs.store'));

        $this->post(route('deux-facteurs.confirmer'), ['code' => '000000'])
            ->assertSessionHasErrorsIn('confirmTwoFactorAuthentication', ['code']);

        $this->assertNull($user->fresh()->two_factor_confirmed_at);
        $this->assertSame(
            'Le code de vérification est incorrect ou expiré.',
            session('errors')->getBag('confirmTwoFactorAuthentication')->first('code')
        );
    }

    public function test_regenerer_les_codes_de_recuperation(): void
    {
        $user = User::factory()->create();
        $this->avecDeuxFacteurs($user);

        $this->actingAs($user)->withSession($this->motDePasseConfirme())
            ->post(route('deux-facteurs.codes'))
            ->assertRedirect(route('deux-facteurs.show'));

        $nouveaux = $user->fresh()->recoveryCodes();
        $this->assertCount(8, $nouveaux);
        $this->assertEmpty(array_intersect($nouveaux, self::CODES_RECUPERATION));
    }

    public function test_desactivation(): void
    {
        $user = User::factory()->create();
        $this->avecDeuxFacteurs($user);

        $this->actingAs($user)->withSession($this->motDePasseConfirme())
            ->delete(route('deux-facteurs.destroy'))
            ->assertRedirect(route('deux-facteurs.show'));

        $user->refresh();
        $this->assertNull($user->two_factor_secret);
        $this->assertNull($user->two_factor_recovery_codes);
        $this->assertFalse($user->hasEnabledTwoFactorAuthentication());

        // La connexion redevient directe
        $this->post('/logout');
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticatedAs($user);
    }

    // ---------------------------------------------------------------
    // Connexion avec 2FA
    // ---------------------------------------------------------------

    public function test_la_connexion_passe_par_le_code_puis_redirige_selon_le_role(): void
    {
        $roles = [
            User::ROLE_RECYCLEUR  => 'recyclage.dashboard',
            User::ROLE_ADMIN      => 'admin.users.index',
            User::ROLE_COLLECTEUR => 'logistique.tournees.index',
            User::ROLE_ATELIER    => 'upcycling.dashboard',
            User::ROLE_CLIENT     => 'dashboard',
        ];

        foreach ($roles as $role => $route) {
            $user = User::factory()->create(['role' => $role]);
            $secret = $this->avecDeuxFacteurs($user);

            $this->post('/login', ['email' => $user->email, 'password' => 'password'])
                ->assertRedirect(route('deux-facteurs.connexion'));
            $this->assertGuest();

            $this->get(route('deux-facteurs.connexion'))->assertOk()->assertSee('Code de vérification');

            $this->post(route('deux-facteurs.verifier'), ['code' => $this->codeValide($secret)])
                ->assertRedirect(route($route, absolute: false));
            $this->assertAuthenticatedAs($user);

            $this->post('/logout');
            // Un nouveau code par compte : Fortify refuse de réutiliser un code déjà accepté
            $this->app['cache']->flush();
        }
    }

    public function test_un_code_faux_est_refuse(): void
    {
        $user = User::factory()->create();
        $this->avecDeuxFacteurs($user);

        $this->post('/login', ['email' => $user->email, 'password' => 'password']);

        $this->post(route('deux-facteurs.verifier'), ['code' => '000000'])
            ->assertSessionHasErrors(['code' => 'Le code de vérification est incorrect ou expiré.']);
        $this->assertGuest();
    }

    public function test_un_code_de_recuperation_ne_sert_qu_une_fois(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ATELIER]);
        $this->avecDeuxFacteurs($user);

        $this->post('/login', ['email' => $user->email, 'password' => 'password']);
        $this->post(route('deux-facteurs.verifier'), ['recovery_code' => 'code-recup-0002'])
            ->assertRedirect(route('upcycling.dashboard', absolute: false));
        $this->assertAuthenticatedAs($user);

        // Code consommé : remplacé par un nouveau, les autres restent valides
        $codes = $user->fresh()->recoveryCodes();
        $this->assertNotContains('code-recup-0002', $codes);
        $this->assertContains('code-recup-0001', $codes);
        $this->assertCount(3, $codes);

        $this->post('/logout');
        $this->post('/login', ['email' => $user->email, 'password' => 'password']);
        $this->post(route('deux-facteurs.verifier'), ['recovery_code' => 'code-recup-0002'])
            ->assertSessionHasErrors(['recovery_code' => 'Ce code de récupération est invalide ou a déjà été utilisé.']);
        $this->assertGuest();
    }

    public function test_les_tentatives_sont_limitees(): void
    {
        $user = User::factory()->create();
        $secret = $this->avecDeuxFacteurs($user);

        $this->post('/login', ['email' => $user->email, 'password' => 'password']);

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('deux-facteurs.verifier'), ['code' => '000000']);
        }

        // Même un bon code est bloqué tant que la limite n'est pas levée
        $this->post(route('deux-facteurs.verifier'), ['code' => $this->codeValide($secret)])
            ->assertSessionHasErrors('code');
        $this->assertStringContainsString('Trop de tentatives', session('errors')->first('code'));
        $this->assertGuest();
    }

    public function test_la_page_du_code_sans_connexion_en_attente_renvoie_au_login(): void
    {
        $this->get(route('deux-facteurs.connexion'))->assertRedirect(route('login'));
        $this->post(route('deux-facteurs.verifier'), ['code' => '123456'])->assertRedirect(route('login'));
    }

    public function test_une_2fa_non_confirmee_ne_bloque_pas_la_connexion(): void
    {
        $user = User::factory()->create();
        $user->forceFill(['two_factor_secret' => encrypt((new Google2FA)->generateSecretKey())])->save();

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticatedAs($user);
    }

    public function test_les_secrets_2fa_ne_sont_pas_serialises(): void
    {
        $user = User::factory()->create();
        $this->avecDeuxFacteurs($user);

        $this->assertArrayNotHasKey('two_factor_secret', $user->fresh()->toArray());
        $this->assertArrayNotHasKey('two_factor_recovery_codes', $user->fresh()->toArray());
    }
}
