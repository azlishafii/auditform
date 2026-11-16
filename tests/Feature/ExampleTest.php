<?php

namespace Tests\Feature;

use App\Mail\NewFormResponseMail;
use App\Models\Form;
use App\Models\FormResponse;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    private function makeForm(): Form
    {
        $form = Form::create([
            'title' => 'Ujian', 'slug' => 'ujian-abc12', 'status' => 'open',
            'collect_identity' => true, 'identity_required' => true, 'allow_multiple' => true,
        ]);
        $form->fields()->create([
            'type' => 'image_choice', 'label' => 'Pilih', 'required' => true, 'position' => 0,
            'options' => [['value' => 'draf1', 'label' => 'A'], ['value' => 'draf2', 'label' => 'B']],
            'settings' => ['allow_comment' => true],
        ]);
        $form->fields()->create([
            'type' => 'part_feedback', 'label' => 'Bahagian', 'required' => false, 'position' => 1,
            'options' => [['value' => 'kulitdepan', 'label' => 'Kulit']],
        ]);

        return $form;
    }

    public function test_root_redirects_guests_to_login(): void
    {
        $this->get('/')->assertRedirect('/login');
    }

    public function test_admin_pages_require_login(): void
    {
        $this->get('/admin')->assertRedirect('/login');
    }

    public function test_public_form_rejects_invalid_and_stores_valid_submission(): void
    {
        $form = $this->makeForm();
        $f = $form->fields;

        $this->post("/f/{$form->slug}", ['respondent_name' => ''])->assertSessionHasErrors(['respondent_name', 'answers.'.$f[0]->id]);

        // "Perlu penambahbaikan" mesti disertakan komen
        $this->post("/f/{$form->slug}", [
            'respondent_name' => 'Ali',
            'answers' => [$f[0]->id => 'draf1', $f[1]->id => ['kulitdepan' => ['status' => 'ubah', 'comment' => '']]],
        ])->assertSessionHasErrors('answers.'.$f[1]->id);

        $this->post("/f/{$form->slug}", [
            'respondent_name' => 'Ali',
            'answers' => [$f[0]->id => 'draf1', $f[1]->id => ['kulitdepan' => ['status' => 'ubah', 'comment' => 'Besarkan logo']]],
            'comments' => [$f[0]->id => 'Lebih elegan'],
        ])->assertRedirect(route('form.thanks', $form->slug));

        $this->assertSame(1, FormResponse::count());
        $this->assertDatabaseHas('response_answers', ['form_field_id' => $f[0]->id, 'comment' => 'Lebih elegan']);
    }

    public function test_invalid_option_is_rejected(): void
    {
        $form = $this->makeForm();
        $this->post("/f/{$form->slug}", ['respondent_name' => 'Ali', 'answers' => [$form->fields[0]->id => 'hack']])
            ->assertSessionHasErrors('answers.'.$form->fields[0]->id);
    }

    public function test_name_dropdown_and_conditional_question(): void
    {
        $form = $this->makeForm();
        $form->update(['respondent_options' => ['Tuan A', 'Puan B']]);
        $f = $form->fields;
        $f[1]->update(['settings' => ['show_if' => 'draf2']]);

        // Nama di luar senarai ditolak
        $this->post("/f/{$form->slug}", ['respondent_name' => 'Orang Lain', 'answers' => [$f[0]->id => 'draf1']])
            ->assertSessionHasErrors('respondent_name');

        // Pilih draf1: soalan bersyarat (draf2) diabaikan walaupun dihantar
        $this->post("/f/{$form->slug}", [
            'respondent_name' => 'Tuan A',
            'answers' => [$f[0]->id => 'draf1', $f[1]->id => ['kulitdepan' => ['status' => 'ubah', 'comment' => '']]],
        ])->assertRedirect(route('form.thanks', $form->slug));
        $this->assertDatabaseMissing('response_answers', ['form_field_id' => $f[1]->id]);

        // Pilih draf2: soalan bersyarat direkodkan
        $this->post("/f/{$form->slug}", [
            'respondent_name' => 'Puan B',
            'answers' => [$f[0]->id => 'draf2', $f[1]->id => ['kulitdepan' => ['status' => 'suka', 'comment' => '']]],
        ])->assertRedirect(route('form.thanks', $form->slug));
        $this->assertDatabaseHas('response_answers', ['form_field_id' => $f[1]->id]);
    }

    public function test_admin_can_submit_test_responses_on_draft_and_delete_them(): void
    {
        $form = $this->makeForm();
        $form->update(['status' => 'draft', 'allow_multiple' => false]);
        $user = User::factory()->create();
        $payload = ['respondent_name' => 'Ali', 'answers' => [$form->fields[0]->id => 'draf1']];

        // Orang awam tidak boleh menghantar pada borang draf
        $this->post("/f/{$form->slug}", $payload);
        $this->assertSame(0, FormResponse::count());

        // Admin boleh menghantar berulang kali; ditanda sebagai ujian
        $this->actingAs($user)->post("/f/{$form->slug}", $payload)->assertRedirect(route('form.thanks', $form->slug));
        $this->actingAs($user)->post("/f/{$form->slug}", $payload)->assertRedirect(route('form.thanks', $form->slug));
        $this->assertSame(2, FormResponse::where('is_test', true)->count());

        // Padam data ujian tidak menjejaskan maklum balas sebenar
        $real = FormResponse::create(['form_id' => $form->id, 'respondent_name' => 'Sebenar']);
        $this->actingAs($user)->delete(route('admin.forms.tests.destroy', $form))->assertRedirect();
        $this->assertSame([$real->id], FormResponse::pluck('id')->all());
    }

    public function test_admin_is_emailed_for_real_responses_but_not_test_responses(): void
    {
        Mail::fake();
        $this->withoutDefer();
        config(['mail.notify_to' => ['uad@example.com']]);
        $form = $this->makeForm();
        $payload = ['respondent_name' => 'Ali', 'answers' => [$form->fields[0]->id => 'draf2']];

        $this->post("/f/{$form->slug}", $payload)->assertRedirect(route('form.thanks', $form->slug));

        Mail::assertSent(NewFormResponseMail::class, function (NewFormResponseMail $mail) {
            $mail->assertSeeInHtml('Ali');
            $mail->assertSeeInHtml('B');

            return $mail->hasTo('uad@example.com') && $mail->hasSubject('Maklum balas baharu: Ujian');
        });

        // Hantaran ujian admin pada borang draf tidak menghantar e-mel
        Mail::fake();
        $form->update(['status' => 'draft']);
        $this->actingAs(User::factory()->create())->post("/f/{$form->slug}", $payload);
        Mail::assertNothingSent();
    }

    public function test_response_is_saved_even_when_email_fails(): void
    {
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('SMTP tidak tersedia'));
        $this->withoutDefer();
        config(['mail.notify_to' => ['uad@example.com']]);
        $form = $this->makeForm();

        $this->post("/f/{$form->slug}", ['respondent_name' => 'Ali', 'answers' => [$form->fields[0]->id => 'draf1']])
            ->assertRedirect(route('form.thanks', $form->slug));

        $this->assertSame(1, FormResponse::count());
    }

    public function test_local_image_urls_are_versioned_so_replaced_images_bypass_browser_cache(): void
    {
        $file = public_path('images/uad/book1/kulit-depan.jpg');

        $this->assertSame(asset('images/uad/book1/kulit-depan.jpg').'?v='.filemtime($file), Form::img('/images/uad/book1/kulit-depan.jpg'));
        $this->assertSame(asset('images/tiada.jpg'), Form::img('/images/tiada.jpg'));
        $this->assertSame('https://cdn.example.com/a.jpg', Form::img('https://cdn.example.com/a.jpg'));
        $this->assertSame('', Form::img(null));
    }

    public function test_draft_form_is_hidden_from_public(): void
    {
        $form = $this->makeForm();
        $form->update(['status' => 'draft']);
        $this->get("/f/{$form->slug}")->assertNotFound();
    }

    public function test_admin_can_view_results_and_export(): void
    {
        $form = $this->makeForm();
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('admin.forms.results', $form))->assertOk();
        $this->actingAs($user)->get(route('admin.forms.export', $form))->assertOk();
    }
}
