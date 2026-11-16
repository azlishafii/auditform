<?php

namespace Tests\Feature;

use App\Models\Form;
use App\Models\FormField;
use App\Support\UadBookDrafts;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateUadBookDraftsTest extends TestCase
{
    use RefreshDatabase;

    /** Borang seperti di server sebelum Draf 3: 2 draf + 2 soalan bahagian + komen. */
    private function makeLegacyForm(array $extraChoiceOptions = []): Form
    {
        $form = Form::create(['title' => 'Buku UAD', 'slug' => UadBookDrafts::FORM_SLUG, 'status' => 'open']);
        $form->fields()->create([
            'type' => 'image_choice', 'label' => '1. Pilih draf', 'position' => 0,
            'options' => [
                ['value' => 'draf1', 'label' => 'Draf 1 – Nama Disunting', 'description' => 'Penerangan admin', 'image' => '/uploads/lama1.jpg', 'gallery' => []],
                ['value' => 'draf2', 'label' => 'Draf 2 – Biru Moden', 'description' => '', 'image' => '/uploads/lama2.jpg', 'gallery' => []],
                ...$extraChoiceOptions,
            ],
        ]);
        foreach ([1, 2] as $book) {
            $form->fields()->create([
                'type' => 'part_feedback', 'label' => "Bahagian draf $book", 'position' => $book,
                'options' => [['value' => 'kulitdepan', 'label' => 'Kulit', 'image' => '/uploads/lama.jpg'], ['value' => 'tambahan', 'label' => 'Bahagian admin', 'image' => '/uploads/x.jpg']],
                'settings' => ['show_if' => "draf$book"],
            ]);
        }
        $form->fields()->create(['type' => 'long_text', 'label' => '3. Komen', 'position' => 3]);

        return $form;
    }

    public function test_it_adds_draft_three_with_gallery_and_conditional_part_question(): void
    {
        $form = $this->makeLegacyForm();

        $this->artisan('uad:kemaskini-draf')->assertSuccessful();
        $this->artisan('uad:kemaskini-draf')->assertSuccessful(); // selamat dijalankan semula

        $fields = $form->fields()->get();
        $options = collect($fields[0]->options)->keyBy('value');

        $this->assertSame(['draf1', 'draf2', 'draf3'], $options->keys()->all());
        $this->assertSame('Draf 1 – Nama Disunting', $options['draf1']['label']);
        $this->assertSame('Penerangan admin', $options['draf1']['description']);
        $this->assertSame('/images/uad/book1/overview.jpg', $options['draf1']['image']);
        $this->assertNotSame('', $options['draf2']['description']);
        $this->assertCount(12, $options['draf3']['gallery']);
        $this->assertSame('/images/uad/book3/sekapur-sirih-2.jpg', $options['draf3']['gallery'][4]['src']);

        // Soalan bahagian Draf 3 dimasukkan sebelum soalan komen, hanya sekali
        $this->assertSame(['image_choice', 'part_feedback', 'part_feedback', 'part_feedback', 'long_text'], $fields->pluck('type')->all());
        $this->assertSame('draf3', $fields[3]->setting('show_if'));
        $this->assertSame([0, 1, 2, 3, 4], $fields->pluck('position')->all());

        // Imej bahagian sedia ada dikemas kini; bahagian tambahan admin dikekalkan
        $this->assertSame('/images/uad/book1/kulit-depan.jpg', $fields[1]->options[0]['image']);
        $this->assertSame('/uploads/x.jpg', $fields[1]->options[1]['image']);
    }

    public function test_it_reuses_draft_three_added_through_pdf_import(): void
    {
        $form = $this->makeLegacyForm([['value' => 'k3j9x2ab', 'label' => 'Draf 3', 'description' => '', 'image' => '/uploads/p3.jpg', 'gallery' => []]]);

        $this->artisan('uad:kemaskini-draf')->assertSuccessful();

        $choice = $form->fields()->where('type', 'image_choice')->first();
        $this->assertCount(3, $choice->options);
        $this->assertSame('k3j9x2ab', $choice->options[2]['value']);
        $this->assertSame('Draf 3 – Teal & Ungu', $choice->options[2]['label']);
        $this->assertTrue(FormField::where('type', 'part_feedback')->get()->contains(fn ($f) => $f->setting('show_if') === 'k3j9x2ab'));
    }

    public function test_it_fails_when_form_is_missing(): void
    {
        $this->artisan('uad:kemaskini-draf')->assertFailed();
    }

    public function test_seeder_creates_form_with_three_drafts(): void
    {
        $this->seed();

        $form = Form::where('slug', UadBookDrafts::FORM_SLUG)->firstOrFail();
        $this->assertCount(3, $form->fields[0]->options);
        $this->assertSame(['draf1', 'draf2', 'draf3'], $form->fields->where('type', 'part_feedback')->map->setting('show_if')->values()->all());
    }
}
