<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Photo de profil : upload, remplacement, retrait, validation et suppression avec le compte.
 */
class PhotoProfilTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    private function envoyer(User $user, ?UploadedFile $photo)
    {
        return $this->actingAs($user)->patch('/profile', array_filter([
            'name'  => $user->name,
            'email' => $user->email,
            'photo' => $photo,
        ]));
    }

    public function test_upload_d_une_photo(): void
    {
        $user = User::factory()->create();

        $this->envoyer($user, UploadedFile::fake()->image('moi.jpg', 300, 300))
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();
        $this->assertStringStartsWith('avatars/', $user->photo_profil);
        Storage::disk('public')->assertExists($user->photo_profil);
        $this->assertSame(Storage::disk('public')->url($user->photo_profil), $user->photo_url);
    }

    public function test_la_photo_s_affiche_dans_le_profil_et_la_navigation(): void
    {
        $user = User::factory()->create(['prenom' => 'Ala', 'name' => 'Ben Daoud']);

        // Sans photo : initiales
        $this->actingAs($user)->get('/profile')->assertOk()->assertSee('AB')->assertDontSee('avatars/');

        $this->envoyer($user, UploadedFile::fake()->image('moi.png'));
        $url = $user->fresh()->photo_url;

        $this->get('/profile')->assertOk()->assertSee($url)->assertSee('Retirer la photo');
        $this->get('/dashboard')->assertOk()->assertSee($url);
    }

    public function test_remplacer_la_photo_supprime_l_ancienne(): void
    {
        $user = User::factory()->create();

        $this->envoyer($user, UploadedFile::fake()->image('premiere.jpg'));
        $ancienne = $user->fresh()->photo_profil;

        $this->envoyer($user->fresh(), UploadedFile::fake()->image('seconde.webp'))->assertSessionHasNoErrors();
        $nouvelle = $user->fresh()->photo_profil;

        $this->assertNotSame($ancienne, $nouvelle);
        Storage::disk('public')->assertMissing($ancienne);
        Storage::disk('public')->assertExists($nouvelle);
    }

    public function test_modifier_le_profil_sans_photo_garde_la_photo(): void
    {
        $user = User::factory()->create();
        $this->envoyer($user, UploadedFile::fake()->image('moi.jpg'));
        $photo = $user->fresh()->photo_profil;

        $this->envoyer($user->fresh(), null)->assertSessionHasNoErrors();

        $this->assertSame($photo, $user->fresh()->photo_profil);
        Storage::disk('public')->assertExists($photo);
    }

    public function test_validation_du_type_de_fichier(): void
    {
        $user = User::factory()->create();

        $this->envoyer($user, UploadedFile::fake()->create('cv.pdf', 100, 'application/pdf'))
            ->assertSessionHasErrors('photo');
        $this->envoyer($user, UploadedFile::fake()->image('anim.gif'))
            ->assertSessionHasErrors(['photo' => 'La photo doit être au format JPG, PNG ou WebP.']);

        $this->assertNull($user->fresh()->photo_profil);
        $this->assertEmpty(Storage::disk('public')->allFiles());
    }

    public function test_validation_de_la_taille(): void
    {
        $user = User::factory()->create();

        $this->envoyer($user, UploadedFile::fake()->image('grande.jpg')->size(2049))
            ->assertSessionHasErrors(['photo' => 'La photo ne doit pas dépasser 2 Mo.']);
        $this->envoyer($user, UploadedFile::fake()->image('limite.jpg')->size(2048))
            ->assertSessionHasNoErrors();
    }

    public function test_retirer_la_photo(): void
    {
        $user = User::factory()->create();
        $this->envoyer($user, UploadedFile::fake()->image('moi.jpg'));
        $photo = $user->fresh()->photo_profil;

        $this->delete(route('profile.photo.destroy'))
            ->assertRedirect('/profile')
            ->assertSessionHas('status', 'photo-removed');

        $this->assertNull($user->fresh()->photo_profil);
        Storage::disk('public')->assertMissing($photo);
    }

    public function test_supprimer_le_compte_supprime_la_photo(): void
    {
        $user = User::factory()->create();
        $this->envoyer($user, UploadedFile::fake()->image('moi.jpg'));
        $photo = $user->fresh()->photo_profil;

        $this->delete('/profile', ['password' => 'password'])->assertRedirect('/');

        $this->assertNull($user->fresh());
        Storage::disk('public')->assertMissing($photo);
    }

    public function test_un_admin_qui_supprime_un_compte_supprime_aussi_sa_photo(): void
    {
        $membre = User::factory()->create();
        $this->envoyer($membre, UploadedFile::fake()->image('moi.jpg'));
        $photo = $membre->fresh()->photo_profil;

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->actingAs($admin)->delete("/admin/users/{$membre->id}")->assertRedirect('/admin/users');

        Storage::disk('public')->assertMissing($photo);
    }

    public function test_changer_d_email_envoie_un_nouveau_lien_de_verification(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->actingAs($user)->patch('/profile', ['name' => $user->name, 'email' => 'nouvelle@example.com'])
            ->assertSessionHasNoErrors();

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
        Notification::assertSentTo($user->fresh(), VerifyEmail::class);
    }
}
