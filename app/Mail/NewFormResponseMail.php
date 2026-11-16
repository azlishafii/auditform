<?php

namespace App\Mail;

use App\Models\FormResponse;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewFormResponseMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public FormResponse $response) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Maklum balas baharu: '.$this->response->form->title,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.new-form-response',
            with: [
                'form' => $this->response->form,
                'choices' => $this->choiceSummary(),
                'url' => route('admin.forms.response', [$this->response->form_id, $this->response->id]),
            ],
        );
    }

    /**
     * Ringkasan jawapan bagi soalan pilihan (cth: draf yang dipilih).
     *
     * @return array<int, array{question: string, answer: string}>
     */
    private function choiceSummary(): array
    {
        $summary = [];

        foreach ($this->response->answers()->with('field')->get() as $answer) {
            $field = $answer->field;
            if (! $field || ! in_array($field->type, ['single_choice', 'dropdown', 'image_choice', 'multi_choice'], true)) {
                continue;
            }

            $labels = array_map(fn ($key) => $field->optionLabel((string) $key), (array) $answer->value);
            $summary[] = ['question' => $field->label, 'answer' => implode(', ', $labels)];
        }

        return $summary;
    }
}
