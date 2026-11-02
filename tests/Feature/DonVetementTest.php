<?php

namespace Tests\Feature;

use App\Models\DonVetement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class DonVetementTest extends TestCase
{
    use RefreshDatabase;

    public function test_donor_can_submit_a_donation_without_a_collection_point(): void
    {
        Storage::fake('public');
        $donor = User::factory()->create(['role' => User::ROLE_DONATEUR]);

        $response = $this->actingAs($donor)->post(route('collecte.dons.store'), [
            'type' => 'Chemise',
            'matiere' => 'Coton',
            'couleur' => 'Bleu',
            'taille' => 'M',
            'etat' => 'Bon',
            'photo' => UploadedFile::fake()->image('chemise.jpg'),
            'photo_choice' => 'original',
        ]);

        $don = DonVetement::firstOrFail();

        $response->assertRedirect(route('collecte.dons.show', ['don' => $don->id]));
        $this->assertDatabaseHas('don_vetements', [
            'id' => $don->id,
            'user_id' => $donor->id,
            'point_collecte_id' => null,
            'couleur' => 'Bleu',
            'statut' => 'DEPOSE',
        ]);
        $this->assertNotEmpty($don->qr_code);
        Storage::disk('public')->assertExists($don->photo_url);

        $this->actingAs($donor)
            ->get(route('collecte.dons.show', ['don' => $don->id]))
            ->assertOk()
            ->assertSee('/storage/'.$don->photo_url, false)
            ->assertSee('Photo originale')
            ->assertSee('<svg', false)
            ->assertSee(route('collecte.dons.verifier', ['qrCode' => $don->qr_code]), false);
    }

    public function test_qr_code_verification_page_shows_only_public_donation_information(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('dons/veste.jpg', 'image-content');
        $donor = User::factory()->create([
            'name' => 'Private donor name',
            'email' => 'private-donor@example.test',
            'role' => User::ROLE_DONATEUR,
        ]);
        $don = DonVetement::factory()->for($donor)->create([
            'type' => 'Veste',
            'couleur' => 'Bleu',
            'statut' => 'EN_TRI',
            'date_depot' => '2026-10-25',
            'photo_url' => 'dons/veste.jpg',
            'photo_originale_url' => 'dons/veste.jpg',
        ]);

        $this->get(route('collecte.dons.verifier', ['qrCode' => $don->qr_code]))
            ->assertOk()
            ->assertSee('Don de vêtement vérifié')
            ->assertSee('Veste')
            ->assertSee('Bleu')
            ->assertSee('En tri')
            ->assertSee('25/10/2026')
            ->assertSee('src="/storage/dons/veste.jpg"', false)
            ->assertDontSee('Private donor name')
            ->assertDontSee('private-donor@example.test');
    }

    public function test_qr_code_verification_page_returns_not_found_for_unknown_code(): void
    {
        $this->get(route('collecte.dons.verifier', ['qrCode' => 'unknown-code']))
            ->assertNotFound();
    }

    public function test_donor_must_upload_a_valid_image_with_the_donation(): void
    {
        Storage::fake('public');
        $donor = User::factory()->create(['role' => User::ROLE_DONATEUR]);

        $this->actingAs($donor)
            ->from(route('collecte.dons.create'))
            ->post(route('collecte.dons.store'), [
                'type' => 'Chemise',
                'matiere' => 'Coton',
                'couleur' => 'Bleu',
                'taille' => 'M',
                'etat' => 'Bon',
                'photo' => UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'),
                'photo_choice' => 'original',
            ])
            ->assertRedirect(route('collecte.dons.create'))
            ->assertSessionHasErrors('photo');

        $this->assertDatabaseCount('don_vetements', 0);
    }

    public function test_duplicate_check_detects_the_same_photo_from_the_same_donor(): void
    {
        Storage::fake('public');
        $donor = User::factory()->create(['role' => User::ROLE_DONATEUR]);
        $image = UploadedFile::fake()->image('veste.png', 100, 100);
        $storedPath = $image->store('dons', 'public');
        $don = DonVetement::factory()->for($donor)->create([
            'type' => 'Veste',
            'couleur' => 'Bleu',
            'photo_url' => $storedPath,
            'photo_originale_url' => $storedPath,
        ]);
        $samePhoto = UploadedFile::fake()->createWithContent(
            'veste.png',
            Storage::disk('public')->get($storedPath)
        );

        $response = $this->actingAs($donor)
            ->postJson(route('collecte.dons.detecter-doublon'), [
                'photo' => $samePhoto,
                'type' => 'Veste',
                'matiere' => 'Coton',
                'couleur' => 'Bleu',
                'taille' => 'M',
            ])
            ->assertOk()
            ->assertJsonPath('matches.0.donation_id', $don->id)
            ->assertJsonPath('matches.0.detection', 'image');

        $this->assertNotEmpty($response->json('check_token'));
    }

    public function test_duplicate_check_does_not_compare_other_donors_photos(): void
    {
        Storage::fake('public');
        $donor = User::factory()->create(['role' => User::ROLE_DONATEUR]);
        $otherDonor = User::factory()->create(['role' => User::ROLE_DONATEUR]);
        $image = UploadedFile::fake()->image('veste.png', 100, 100);
        $storedPath = $image->store('dons', 'public');
        DonVetement::factory()->for($otherDonor)->create([
            'photo_url' => $storedPath,
            'photo_originale_url' => $storedPath,
        ]);
        $samePhoto = UploadedFile::fake()->createWithContent(
            'veste.png',
            Storage::disk('public')->get($storedPath)
        );

        $this->actingAs($donor)
            ->postJson(route('collecte.dons.detecter-doublon'), [
                'photo' => $samePhoto,
                'type' => 'Veste',
                'matiere' => 'Coton',
                'couleur' => 'Bleu',
                'taille' => 'M',
            ])
            ->assertOk()
            ->assertJsonCount(0, 'matches');
    }

    public function test_duplicate_check_uses_gemini_to_compare_different_photos_of_the_same_item(): void
    {
        config(['services.gemini.key' => 'test-key']);
        Storage::fake('public');
        $donor = User::factory()->create(['role' => User::ROLE_DONATEUR]);
        $oldImage = UploadedFile::fake()->image('veste-ancienne.jpg', 100, 100);
        $storedPath = $oldImage->store('dons', 'public');
        $don = DonVetement::factory()->for($donor)->create([
            'type' => 'Veste',
            'matiere' => 'Coton',
            'couleur' => 'Bleu',
            'taille' => 'M',
            'photo_url' => $storedPath,
            'photo_originale_url' => $storedPath,
            'photo_hashes' => [str_repeat('0', 16)],
        ]);
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'content' => ['parts' => [[
                        'text' => json_encode([
                            'matches' => [[
                                'donation_id' => $don->id,
                                'confidence' => 0.91,
                            ]],
                        ]),
                    ]]],
                ]],
            ]),
        ]);

        $this->actingAs($donor)
            ->postJson(route('collecte.dons.detecter-doublon'), [
                'photo' => UploadedFile::fake()->image('veste-autre-angle.jpg', 100, 100),
                'type' => 'Veste',
                'matiere' => 'Coton',
                'couleur' => 'Bleu',
                'taille' => 'M',
            ])
            ->assertOk()
            ->assertJsonPath('matches.0.donation_id', $don->id)
            ->assertJsonPath('matches.0.detection', 'vetement')
            ->assertJsonPath('matches.0.confidence', 0.91)
            ->assertJsonPath('ai_status', 'checked');

        Http::assertSent(fn ($request) => str_contains($request->body(), 'inline_data'));
    }

    public function test_donor_must_confirm_a_detected_duplicate_before_saving(): void
    {
        Storage::fake('public');
        $donor = User::factory()->create(['role' => User::ROLE_DONATEUR]);
        $image = UploadedFile::fake()->image('veste.png', 100, 100);
        $storedPath = $image->store('dons', 'public');
        $existing = DonVetement::factory()->for($donor)->create([
            'type' => 'Veste',
            'couleur' => 'Bleu',
            'photo_url' => $storedPath,
            'photo_originale_url' => $storedPath,
        ]);
        $samePhoto = UploadedFile::fake()->createWithContent(
            'veste.png',
            Storage::disk('public')->get($storedPath)
        );
        $check = $this->actingAs($donor)->postJson(route('collecte.dons.detecter-doublon'), [
            'photo' => $samePhoto,
            'type' => 'Veste',
            'matiere' => 'Coton',
            'couleur' => 'Bleu',
            'taille' => 'M',
        ])->assertOk();

        $response = $this->actingAs($donor)->from(route('collecte.dons.create'))
            ->post(route('collecte.dons.store'), [
                'type' => 'Veste',
                'matiere' => 'Coton',
                'couleur' => 'Bleu',
                'taille' => 'M',
                'etat' => 'Bon',
                'photo' => $samePhoto,
                'photo_choice' => 'original',
                'duplicate_check_token' => $check->json('check_token'),
            ]);

        $response->assertRedirect(route('collecte.dons.create'))
            ->assertSessionHasErrors('duplicate_confirmation');
        $this->assertDatabaseCount('don_vetements', 1);
        $this->assertDatabaseHas('don_vetements', ['id' => $existing->id]);
    }

    public function test_donor_can_confirm_and_submit_a_possible_duplicate(): void
    {
        Storage::fake('public');
        $donor = User::factory()->create(['role' => User::ROLE_DONATEUR]);
        $image = UploadedFile::fake()->image('veste.png', 100, 100);
        $storedPath = $image->store('dons', 'public');
        DonVetement::factory()->for($donor)->create([
            'type' => 'Veste',
            'couleur' => 'Bleu',
            'photo_url' => $storedPath,
            'photo_originale_url' => $storedPath,
        ]);
        $samePhoto = UploadedFile::fake()->createWithContent(
            'veste.png',
            Storage::disk('public')->get($storedPath)
        );
        $check = $this->actingAs($donor)->postJson(route('collecte.dons.detecter-doublon'), [
            'photo' => $samePhoto,
            'type' => 'Veste',
            'matiere' => 'Coton',
            'couleur' => 'Bleu',
            'taille' => 'M',
        ])->assertOk();

        $response = $this->actingAs($donor)->post(route('collecte.dons.store'), [
            'type' => 'Veste',
            'matiere' => 'Coton',
            'couleur' => 'Bleu',
            'taille' => 'M',
            'etat' => 'Bon',
            'photo' => $samePhoto,
            'photo_choice' => 'original',
            'duplicate_check_token' => $check->json('check_token'),
            'duplicate_confirmed' => '1',
        ]);

        $response->assertRedirect(route('collecte.dons.show', ['don' => DonVetement::latest('id')->firstOrFail()->id]));
        $this->assertDatabaseCount('don_vetements', 2);
    }

    public function test_donor_can_get_ai_suggestions_from_a_photo(): void
    {
        config(['services.gemini.key' => 'test-key']);
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'content' => ['parts' => [[
                        'text' => json_encode([
                            'est_vetement' => true,
                            'type' => 'blazer',
                            'matiere' => 'laine',
                            'couleur' => 'bleu marine',
                            'taille' => 'M (estimée)',
                            'etat' => 'Très bon',
                        ]),
                    ]]],
                ]],
            ]),
        ]);
        $donor = User::factory()->create(['role' => User::ROLE_DONATEUR]);

        $this->actingAs($donor)
            ->postJson(route('collecte.dons.analyse-photo'), [
                'photo' => UploadedFile::fake()->image('blazer.jpg'),
            ])
            ->assertOk()
            ->assertJsonPath('suggestion.type', 'Blazer')
            ->assertJsonPath('suggestion.matiere', 'Laine')
            ->assertJsonPath('suggestion.couleur', 'Bleu marine')
            ->assertJsonPath('suggestion.taille', 'M (estimée)')
            ->assertJsonPath('suggestion.etat', 'Très bon');

        Http::assertSent(fn ($request) => str_contains($request->body(), 'inline_data'));
    }

    public function test_ai_suggestions_are_not_returned_for_a_photo_without_clothing(): void
    {
        config(['services.gemini.key' => 'test-key']);
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'content' => ['parts' => [[
                        'text' => json_encode([
                            'est_vetement' => false,
                            'type' => '',
                            'matiere' => '',
                            'couleur' => '',
                            'taille' => '',
                            'etat' => 'Bon',
                        ]),
                    ]]],
                ]],
            ]),
        ]);
        $donor = User::factory()->create(['role' => User::ROLE_DONATEUR]);

        $this->actingAs($donor)
            ->postJson(route('collecte.dons.analyse-photo'), [
                'photo' => UploadedFile::fake()->image('animal.jpg'),
            ])
            ->assertStatus(422)
            ->assertJsonPath('message', "L'IA ne reconnaît pas de vêtement sur cette photo.");
    }

    public function test_ai_suggestions_require_a_configured_provider(): void
    {
        config(['services.gemini.key' => null]);
        $donor = User::factory()->create(['role' => User::ROLE_DONATEUR]);

        $this->actingAs($donor)
            ->postJson(route('collecte.dons.analyse-photo'), [
                'photo' => UploadedFile::fake()->image('blazer.jpg'),
            ])
            ->assertStatus(503)
            ->assertJsonPath('message', "L'analyse IA est indisponible pour le moment.");
    }

    public function test_only_donors_can_request_ai_suggestions(): void
    {
        $collector = User::factory()->create(['role' => User::ROLE_COLLECTEUR]);

        $this->actingAs($collector)
            ->postJson(route('collecte.dons.analyse-photo'), [
                'photo' => UploadedFile::fake()->image('blazer.jpg'),
            ])
            ->assertForbidden();
    }

    public function test_donor_can_generate_and_preview_an_enhanced_image(): void
    {
        config([
            'services.donation_images.provider' => 'gemini',
            'services.gemini.key' => 'test-key',
            'services.gemini.image_model' => 'gemini-test-image',
        ]);
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'content' => ['parts' => [
                        ['text' => 'Generated product photo'],
                        ['inlineData' => [
                            'mimeType' => 'image/png',
                            'data' => 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+j0eUAAAAASUVORK5CYII=',
                        ]],
                    ]],
                ]],
            ]),
        ]);
        Storage::fake('local');
        $donor = User::factory()->create(['role' => User::ROLE_DONATEUR]);

        $response = $this->actingAs($donor)
            ->postJson(route('collecte.dons.ameliorer-photo'), [
                'photo' => UploadedFile::fake()->image('blazer.webp'),
            ])
            ->assertOk()
            ->assertJsonPath('preview', 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+j0eUAAAAASUVORK5CYII=');

        Storage::disk('local')->assertExists("don-enhancements/{$donor->id}/{$response->json('token')}.png");
        Http::assertSent(fn ($request) => str_contains($request->url(), 'gemini-test-image')
            && ($request->data()['generationConfig']['responseModalities'] ?? null) === ['TEXT', 'IMAGE']);
    }

    public function test_donor_can_generate_an_enhanced_image_with_local_comfyui(): void
    {
        config([
            'services.donation_images.provider' => 'comfyui',
            'services.donation_images.comfyui.url' => 'http://127.0.0.1:8188',
            'services.donation_images.comfyui.checkpoint' => 'textilecycle-test.safetensors',
            'services.donation_images.comfyui.timeout' => 5,
            'services.donation_images.comfyui.denoise' => 0.5,
        ]);
        $imageBytes = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+j0eUAAAAASUVORK5CYII=');
        Http::fake(function ($request) use ($imageBytes) {
            $path = parse_url($request->url(), PHP_URL_PATH);
            if ($path === '/upload/image') {
                return Http::response(['name' => 'textilecycle-test-input.png', 'subfolder' => '', 'type' => 'input']);
            }
            if ($path === '/prompt') {
                return Http::response(['prompt_id' => 'test-prompt-id']);
            }
            if ($path === '/history/test-prompt-id') {
                return Http::response([
                    'test-prompt-id' => [
                        'status' => ['status_str' => 'success', 'completed' => true],
                        'outputs' => [
                            '8' => ['images' => [[
                                'filename' => 'textilecycle-output.png',
                                'subfolder' => '',
                                'type' => 'output',
                            ]]],
                        ],
                    ],
                ]);
            }
            if ($path === '/view') {
                return Http::response($imageBytes, 200, ['Content-Type' => 'image/png']);
            }

            return Http::response([], 404);
        });
        Storage::fake('local');
        $donor = User::factory()->create(['role' => User::ROLE_DONATEUR]);

        $response = $this->actingAs($donor)
            ->postJson(route('collecte.dons.ameliorer-photo'), [
                'photo' => UploadedFile::fake()->image('blazer.webp'),
            ])
            ->assertOk()
            ->assertJsonPath('preview', 'data:image/png;base64,'.base64_encode($imageBytes));

        Storage::disk('local')->assertExists("don-enhancements/{$donor->id}/{$response->json('token')}.png");
        Http::assertSent(fn ($request) => parse_url($request->url(), PHP_URL_PATH) === '/upload/image');
        Http::assertSent(fn ($request) => parse_url($request->url(), PHP_URL_PATH) === '/prompt'
            && ($request->data()['prompt']['6']['inputs']['denoise'] ?? null) === 0.5
            && ($request->data()['prompt']['3']['inputs']['text'] ?? null)
                === 'Extract the garment from this photo. Keep the exact same garment: same color, shape, fabric, '
                    .'print, text, logo, seams, and signs of wear. Remove the person and the background. Show only '
                    .'the garment, centered on a plain light background. Do not redesign it or add anything.'
            && ($request->data()['prompt']['4']['inputs']['text'] ?? null)
                === 'person, human, face, hands, mannequin, model, background clutter, extra garments, '
                    .'accessories, watermark, blurry, deformed');
        Http::assertSent(fn ($request) => parse_url($request->url(), PHP_URL_PATH) === '/view'
            && ($request['filename'] ?? null) === 'textilecycle-output.png');
    }

    public function test_donor_can_create_a_faithful_cutout_with_local_comfyui(): void
    {
        config([
            'services.donation_images.provider' => 'comfyui',
            'services.donation_images.comfyui.url' => 'http://127.0.0.1:8188',
            'services.donation_images.comfyui.checkpoint' => null,
            'services.donation_images.comfyui.timeout' => 5,
        ]);
        $imageBytes = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+j0eUAAAAASUVORK5CYII=');
        $mask = imagecreatetruecolor(8, 8);
        imagefill($mask, 0, 0, imagecolorallocate($mask, 255, 255, 255));
        ob_start();
        imagepng($mask);
        $maskBytes = ob_get_clean();
        imagedestroy($mask);

        Http::fake(function ($request) use ($imageBytes, $maskBytes) {
            $path = parse_url($request->url(), PHP_URL_PATH);
            if ($path === '/object_info') {
                return Http::response(array_fill_keys([
                    'SAMModelLoader',
                    'GroundingDinoModelLoader',
                    'GroundingDinoSAMSegment',
                    'MaskToImage',
                    'EmptyImage',
                    'ImageCompositeMasked',
                    'SaveImage',
                    'LoadImage',
                ], []));
            }
            if ($path === '/upload/image') {
                return Http::response(['name' => 'textilecycle-source.png', 'subfolder' => '', 'type' => 'input']);
            }
            if ($path === '/prompt') {
                return Http::response(['prompt_id' => 'faithful-prompt-id']);
            }
            if ($path === '/history/faithful-prompt-id') {
                return Http::response([
                    'faithful-prompt-id' => [
                        'status' => ['status_str' => 'success', 'completed' => true],
                        'outputs' => [
                            '6' => ['images' => [['filename' => 'textilecycle-mask.png', 'subfolder' => '', 'type' => 'output']]],
                            '9' => ['images' => [['filename' => 'textilecycle-cutout.png', 'subfolder' => '', 'type' => 'output']]],
                        ],
                    ],
                ]);
            }
            if ($path === '/view') {
                return Http::response(
                    ($request['filename'] ?? '') === 'textilecycle-mask.png' ? $maskBytes : $imageBytes,
                    200,
                    ['Content-Type' => 'image/png']
                );
            }

            return Http::response([], 404);
        });
        Storage::fake('local');
        $donor = User::factory()->create(['role' => User::ROLE_DONATEUR]);

        $response = $this->actingAs($donor)
            ->postJson(route('collecte.dons.ameliorer-photo'), [
                'photo' => UploadedFile::fake()->image('chemise.png', 32, 32),
                'mode' => 'faithful',
                'type' => 'Chemise',
            ])
            ->assertOk()
            ->assertJsonPath('preview', 'data:image/png;base64,'.base64_encode($imageBytes));

        Storage::disk('local')->assertExists("don-enhancements/{$donor->id}/{$response->json('token')}.png");
        Http::assertSent(fn ($request) => parse_url($request->url(), PHP_URL_PATH) === '/prompt'
            && ($request->data()['prompt']['2']['class_type'] ?? null) === 'GroundingDinoModelLoader'
            && ($request->data()['prompt']['3']['inputs']['model_name'] ?? null) === 'sam_vit_b (375MB)'
            && ($request->data()['prompt']['4']['inputs']['prompt'] ?? null) === 'shirt'
            && ($request->data()['prompt']['8']['class_type'] ?? null) === 'ImageCompositeMasked'
            && ($request->data()['prompt']['8']['inputs']['source'] ?? null) === ['1', 0]
            && ($request->data()['prompt']['8']['inputs']['mask'] ?? null) === ['4', 1]);
        Http::assertSent(fn ($request) => parse_url($request->url(), PHP_URL_PATH) === '/upload/image');
    }

    public function test_faithful_cutout_explains_when_segmentation_nodes_are_missing(): void
    {
        config([
            'services.donation_images.provider' => 'comfyui',
            'services.donation_images.comfyui.url' => 'http://127.0.0.1:8188',
            'services.donation_images.comfyui.checkpoint' => null,
        ]);
        Http::fake(['*/object_info' => Http::response([])]);
        Storage::fake('local');
        $donor = User::factory()->create(['role' => User::ROLE_DONATEUR]);

        $this->actingAs($donor)
            ->postJson(route('collecte.dons.ameliorer-photo'), [
                'photo' => UploadedFile::fake()->image('chemise.png'),
                'mode' => 'faithful',
                'type' => 'Chemise',
            ])
            ->assertStatus(502)
            ->assertJsonPath(
                'message',
                'Le détourage fidèle nécessite le node pack ComfyUI Segment Anything (storyicon/comfyui_segment_anything). '
                    .'Installez-le, redémarrez ComfyUI et vérifiez que les modèles SAM et GroundingDINO sont disponibles.'
            );
    }

    public function test_local_image_enhancement_does_not_require_a_gemini_key(): void
    {
        config([
            'services.gemini.key' => null,
            'services.donation_images.provider' => 'comfyui',
            'services.donation_images.comfyui.url' => 'http://127.0.0.1:8188',
            'services.donation_images.comfyui.checkpoint' => '',
        ]);
        $donor = User::factory()->create(['role' => User::ROLE_DONATEUR]);

        $this->actingAs($donor)
            ->postJson(route('collecte.dons.ameliorer-photo'), [
                'photo' => UploadedFile::fake()->image('blazer.jpg'),
            ])
            ->assertStatus(503)
            ->assertJsonPath('message', "L'amélioration d'image IA est indisponible pour le moment.");
    }

    public function test_gemini_image_generation_is_available_when_selected_explicitly(): void
    {
        config([
            'services.donation_images.provider' => 'gemini',
            'services.gemini.key' => 'test-key',
            'services.gemini.image_model' => 'gemini-test-image',
        ]);
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'content' => ['parts' => [[
                        'inlineData' => [
                            'mimeType' => 'image/png',
                            'data' => 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+j0eUAAAAASUVORK5CYII=',
                        ],
                    ]]],
                ]],
            ]),
        ]);
        Storage::fake('local');
        $donor = User::factory()->create(['role' => User::ROLE_DONATEUR]);

        $this->actingAs($donor)
            ->postJson(route('collecte.dons.ameliorer-photo'), [
                'photo' => UploadedFile::fake()->image('blazer.jpg'),
            ])
            ->assertOk();

        Http::assertSent(fn ($request) => str_contains($request->url(), 'gemini-test-image'));
    }

    public function test_image_enhancement_requires_a_configured_provider(): void
    {
        config([
            'services.donation_images.provider' => 'comfyui',
            'services.donation_images.comfyui.url' => null,
            'services.donation_images.comfyui.checkpoint' => null,
            'services.gemini.key' => null,
        ]);
        $donor = User::factory()->create(['role' => User::ROLE_DONATEUR]);

        $this->actingAs($donor)
            ->postJson(route('collecte.dons.ameliorer-photo'), [
                'photo' => UploadedFile::fake()->image('blazer.jpg'),
            ])
            ->assertStatus(503)
            ->assertJsonPath('message', "L'amélioration d'image IA est indisponible pour le moment.");
    }

    public function test_only_donors_can_request_image_enhancement(): void
    {
        $collector = User::factory()->create(['role' => User::ROLE_COLLECTEUR]);

        $this->actingAs($collector)
            ->postJson(route('collecte.dons.ameliorer-photo'), [
                'photo' => UploadedFile::fake()->image('blazer.jpg'),
            ])
            ->assertForbidden();
    }

    public function test_donor_can_choose_the_enhanced_image_while_preserving_both_versions(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        $donor = User::factory()->create(['role' => User::ROLE_DONATEUR]);
        $token = (string) Str::uuid();
        $temporaryPath = "don-enhancements/{$donor->id}/{$token}.png";
        $imageBytes = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+j0eUAAAAASUVORK5CYII=');
        Storage::disk('local')->put($temporaryPath, $imageBytes);

        $this->actingAs($donor)->post(route('collecte.dons.store'), [
            'type' => 'Blazer',
            'matiere' => 'Laine',
            'couleur' => 'Bleu marine',
            'taille' => 'M',
            'etat' => 'Très bon',
            'photo' => UploadedFile::fake()->image('blazer.jpg'),
            'photo_choice' => 'enhanced',
            'enhanced_token' => $token,
        ])->assertRedirect();

        $don = DonVetement::firstOrFail();
        $this->assertSame($don->photo_amelioree_url, $don->photo_url);
        $this->assertNotSame($don->photo_originale_url, $don->photo_amelioree_url);
        Storage::disk('public')->assertExists($don->photo_originale_url);
        Storage::disk('public')->assertExists($don->photo_amelioree_url);
        Storage::disk('local')->assertMissing($temporaryPath);

        $this->actingAs($donor)
            ->get(route('collecte.dons.show', ['don' => $don->id]))
            ->assertOk()
            ->assertSee('Photo originale')
            ->assertSee('Photo améliorée par l’IA')
            ->assertSee('utilisée pour le don');
    }

    public function test_donor_can_keep_the_original_as_selected_and_still_preserve_the_enhancement(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        $donor = User::factory()->create(['role' => User::ROLE_DONATEUR]);
        $token = (string) Str::uuid();
        Storage::disk('local')->put(
            "don-enhancements/{$donor->id}/{$token}.png",
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+j0eUAAAAASUVORK5CYII=')
        );

        $this->actingAs($donor)->post(route('collecte.dons.store'), [
            'type' => 'Blazer',
            'matiere' => 'Laine',
            'couleur' => 'Bleu marine',
            'taille' => 'M',
            'etat' => 'Très bon',
            'photo' => UploadedFile::fake()->image('blazer.jpg'),
            'photo_choice' => 'original',
            'enhanced_token' => $token,
        ])->assertRedirect();

        $don = DonVetement::firstOrFail();
        $this->assertSame($don->photo_originale_url, $don->photo_url);
        $this->assertNotNull($don->photo_amelioree_url);
        Storage::disk('public')->assertExists($don->photo_originale_url);
        Storage::disk('public')->assertExists($don->photo_amelioree_url);
    }

    public function test_donor_cannot_use_another_donors_temporary_enhancement(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $owner = User::factory()->create(['role' => User::ROLE_DONATEUR]);
        $otherDonor = User::factory()->create(['role' => User::ROLE_DONATEUR]);
        $token = (string) Str::uuid();
        Storage::disk('local')->put("don-enhancements/{$owner->id}/{$token}.png", 'private');

        $this->actingAs($otherDonor)
            ->from(route('collecte.dons.create'))
            ->post(route('collecte.dons.store'), [
                'type' => 'Blazer',
                'matiere' => 'Laine',
                'couleur' => 'Bleu marine',
                'taille' => 'M',
                'etat' => 'Très bon',
                'photo' => UploadedFile::fake()->image('blazer.jpg'),
                'photo_choice' => 'enhanced',
                'enhanced_token' => $token,
            ])
            ->assertRedirect(route('collecte.dons.create'))
            ->assertSessionHasErrors('enhanced_token');

        $this->assertDatabaseCount('don_vetements', 0);
    }

    public function test_donor_cannot_view_another_donors_donation(): void
    {
        $donor = User::factory()->create(['role' => User::ROLE_DONATEUR]);
        $otherDonor = User::factory()->create(['role' => User::ROLE_DONATEUR]);
        $don = DonVetement::create([
            'user_id' => $otherDonor->id,
            'type' => 'Manteau privé',
            'matiere' => 'Laine',
            'taille' => 'L',
            'etat' => 'Bon',
            'statut' => 'DEPOSE',
            'date_depot' => now()->toDateString(),
        ]);

        $this->actingAs($donor)
            ->get(route('collecte.dons.show', ['don' => $don->id]))
            ->assertForbidden();
    }

    public function test_donor_only_sees_their_own_donations_in_the_list(): void
    {
        $donor = User::factory()->create(['role' => User::ROLE_DONATEUR]);
        $otherDonor = User::factory()->create(['role' => User::ROLE_DONATEUR]);

        foreach ([[$donor, 'Mon don privé'], [$otherDonor, 'Don d’un autre']] as [$owner, $type]) {
            DonVetement::create([
                'user_id' => $owner->id,
                'type' => $type,
                'matiere' => 'Coton',
                'taille' => 'M',
                'etat' => 'Bon',
                'statut' => 'DEPOSE',
                'date_depot' => now()->toDateString(),
            ]);
        }

        $this->actingAs($donor)
            ->get(route('collecte.dons.index'))
            ->assertOk()
            ->assertSee('Mon don privé')
            ->assertSee('taille M')
            ->assertDontSee('Don d’un autre');
    }

    public function test_donation_creation_form_does_not_require_a_collection_point(): void
    {
        $donor = User::factory()->create(['role' => User::ROLE_DONATEUR]);

        $this->actingAs($donor)
            ->get(route('collecte.dons.create'))
            ->assertOk()
            ->assertSee('name="photo"', false)
            ->assertSee('id="couleur"', false)
            ->assertSee('id="taille"', false)
            ->assertSee('duplicate-check-token', false)
            ->assertSee('Continuer malgré tout')
            ->assertSee('detecter-doublon', false)
            ->assertSee('serveur ComfyUI local')
            ->assertSee('Détourage fidèle')
            ->assertSee('Retrait IA expérimental')
            ->assertSee('comfyui_segment_anything')
            ->assertSee('enctype="multipart/form-data"', false)
            ->assertDontSee('Point de dépôt');
    }

    public function test_collector_can_update_a_donation_status(): void
    {
        $collector = User::factory()->create(['role' => User::ROLE_COLLECTEUR]);
        $donor = User::factory()->create(['role' => User::ROLE_DONATEUR]);
        $don = DonVetement::create([
            'user_id' => $donor->id,
            'type' => 'Pantalon',
            'matiere' => 'Denim',
            'taille' => '40',
            'etat' => 'Très bon',
            'statut' => 'DEPOSE',
            'date_depot' => now()->toDateString(),
        ]);

        $this->actingAs($collector)
            ->patch(route('collecte.dons.statut', ['don' => $don->id]), ['statut' => 'EN_TRI'])
            ->assertRedirect(route('collecte.dons.show', ['don' => $don->id]));

        $this->assertDatabaseHas('don_vetements', [
            'id' => $don->id,
            'statut' => 'EN_TRI',
        ]);
    }
}
