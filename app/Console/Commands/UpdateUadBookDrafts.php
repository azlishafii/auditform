<?php

namespace App\Console\Commands;

use App\Models\Form;
use App\Models\FormField;
use App\Support\UadBookDrafts;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('uad:kemaskini-draf')]
#[Description('Kemas kini borang draf Buku UAD: imej & galeri setiap draf, tambah draf baharu dan soalan maklum balas bahagiannya')]
class UpdateUadBookDrafts extends Command
{
    public function handle(): int
    {
        $form = Form::where('slug', UadBookDrafts::FORM_SLUG)->first();
        if (! $form) {
            $this->error('Borang "'.UadBookDrafts::FORM_SLUG.'" tidak dijumpai.');

            return self::FAILURE;
        }

        $choice = $form->fields()->where('type', 'image_choice')->first();
        if (! $choice) {
            $this->error('Soalan pilih imej tidak dijumpai dalam borang ini.');

            return self::FAILURE;
        }

        DB::transaction(function () use ($form, $choice) {
            $draftLabels = $this->syncChoiceOptions($choice);
            $this->syncPartFeedbackFields($form, $choice, $draftLabels);
        });

        $this->info('Borang "'.$form->title.'" dikemas kini.');

        return self::SUCCESS;
    }

    /**
     * Kemas kini imej & galeri setiap draf; tambah draf yang belum wujud.
     * Nama/penerangan yang telah disunting admin dikekalkan.
     *
     * @return array<string, array{book: int, label: string}> nilai pilihan => buku & label draf
     */
    private function syncChoiceOptions(FormField $choice): array
    {
        $options = $choice->options ?? [];
        $labels = [];

        foreach (UadBookDrafts::drafts() as $draft) {
            $fresh = UadBookDrafts::choiceOption($draft);
            $index = $this->findDraftOption($options, $draft);

            if ($index === null) {
                $options[] = $fresh;
                $labels[$fresh['value']] = ['book' => $draft['book'], 'label' => $fresh['label']];
                $this->line("  + {$fresh['label']} ditambah");

                continue;
            }

            $option = $options[$index];
            $option['image'] = $fresh['image'];
            $option['gallery'] = $fresh['gallery'];
            if (preg_match('/^\s*(Draf\s*\d+)?\s*$/iu', $option['label'] ?? '')) {
                $option['label'] = $fresh['label'];
            }
            if (trim($option['description'] ?? '') === '') {
                $option['description'] = $fresh['description'];
            }
            $options[$index] = $option;
            $labels[$option['value']] = ['book' => $draft['book'], 'label' => $option['label']];
            $this->line("  ✓ {$option['label']}: imej & galeri dikemas kini");
        }

        $choice->update(['options' => $options]);

        return $labels;
    }

    /**
     * Cari pilihan draf mengikut nilai (cth: draf3) atau label "Draf N" (pilihan yang ditambah melalui Import PDF).
     *
     * @param  array<int, array<string, mixed>>  $options
     * @param  array{book: int, value: string}  $draft
     */
    private function findDraftOption(array $options, array $draft): ?int
    {
        foreach ($options as $i => $option) {
            if (($option['value'] ?? null) === $draft['value']) {
                return $i;
            }
        }
        foreach ($options as $i => $option) {
            if (preg_match('/^\s*Draf\s*'.$draft['book'].'\b/iu', $option['label'] ?? '')) {
                return $i;
            }
        }

        return null;
    }

    /**
     * Pastikan setiap draf ada soalan "Maklum balas setiap bahagian" (bersyarat) dengan imej terkini.
     *
     * @param  array<string, array{book: int, label: string}>  $draftLabels
     */
    private function syncPartFeedbackFields(Form $form, FormField $choice, array $draftLabels): void
    {
        foreach ($draftLabels as $value => ['book' => $book, 'label' => $label]) {
            $fields = $form->fields()->get();
            $existing = $fields->first(fn (FormField $f) => $f->type === 'part_feedback' && $f->setting('show_if') === $value);

            if ($existing) {
                $fresh = collect(UadBookDrafts::partOptions($book))->keyBy('value');
                $existing->update(['options' => array_map(
                    fn (array $o) => isset($fresh[$o['value'] ?? '']) ? ['image' => $fresh[$o['value']]['image']] + $o : $o,
                    $existing->options ?? []
                )]);

                continue;
            }

            // Letak selepas soalan maklum balas bahagian yang terakhir (atau selepas soalan pilihan draf)
            $after = $fields->where('type', 'part_feedback')->max('position') ?? $choice->position;
            FormField::where('form_id', $form->id)->where('position', '>', $after)->increment('position');

            $form->fields()->create([
                'type' => 'part_feedback',
                'required' => false,
                'label' => UadBookDrafts::partFeedbackLabel($label),
                'help' => UadBookDrafts::PART_FEEDBACK_HELP,
                'options' => UadBookDrafts::partOptions($book),
                'settings' => ['show_if' => $value],
                'position' => $after + 1,
            ]);
            $this->line('  + Soalan "'.UadBookDrafts::partFeedbackLabel($label).'" ditambah');
        }
    }
}
