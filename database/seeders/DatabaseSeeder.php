<?php

namespace Database\Seeders;

use App\Models\Form;
use App\Models\User;
use App\Support\UadBookDrafts;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Akaun admin diambil daripada .env (ADMIN_NAME / ADMIN_EMAIL / ADMIN_PASSWORD)
        $email = env('ADMIN_EMAIL', 'admin@example.com');
        $user = User::updateOrCreate(['email' => $email], [
            'name' => env('ADMIN_NAME', 'Admin UAD'),
            'password' => Hash::make(env('ADMIN_PASSWORD', 'ChangeMe!123')),
        ]);

        if (! Form::where('slug', UadBookDrafts::FORM_SLUG)->exists()) {
            $this->seedBookForm($user);
        }
    }

    private function seedBookForm(User $user): void
    {
        $drafts = UadBookDrafts::drafts();

        $form = Form::create([
            'user_id' => $user->id,
            'title' => 'Pemilihan Draf Konsep Buku Laporan Tahunan Unit Audit Dalam 2025',
            'slug' => UadBookDrafts::FORM_SLUG,
            'description' => 'Pembekal telah mengemukakan tiga (3) draf konsep reka bentuk buku Laporan Tahunan Unit Audit Dalam KEJORA. '
                .'Sila teliti setiap draf (klik imej untuk zoom), pilih reka bentuk yang paling sesuai dan berikan komen atau cadangan penambahbaikan.',
            'status' => 'draft',
            'collect_identity' => true,
            'identity_required' => true,
            'respondent_options' => [
                'YB Senator Dato’ Haji Abdul Halim bin Haji Suleiman',
                'YBrs. Encik Tuan Ahmad bin Tuan Wil',
                'YBrs. Puan Murni Rahayu binti Ahmad Anuar',
                'YBhg. Tuan Haji Miswan bin Yunus',
                'Encik Mohamad Rizal bin Mohd Hidzir',
                'Puan Fatin Nabila binti Mohamed',
                'Encik Mohamad Azli Shafii bin Mohd Raub',
                'Cik Nurul Nabihatul Najwa binti Adie Norizal',
                'Puan Suhaila binti Sulaiman',
                'Puan Zulaiha binti Abd Kadir',
                'Encik Suhaimi bin Md. Yusuf',
            ],
            'allow_multiple' => false,
            'thank_you' => 'Terima kasih kerana memberikan pandangan. Maklum balas anda telah direkodkan.',
        ]);

        $fields = [
            [
                'type' => 'image_choice', 'required' => true,
                'label' => '1. Draf konsep manakah yang anda pilih?',
                'help' => 'Klik "Lihat semua bahagian" pada setiap draf untuk melihat kulit, isi kandungan, perutusan dan pembahagi bahagian.',
                'options' => array_map(fn (array $draft) => UadBookDrafts::choiceOption($draft), $drafts),
                'settings' => ['allow_comment' => false],
            ],
            // Soalan bersyarat: hanya bahagian bagi draf yang dipilih dipaparkan
            ...array_map(fn (array $draft) => [
                'type' => 'part_feedback', 'required' => false,
                'label' => UadBookDrafts::partFeedbackLabel($draft['label']),
                'help' => UadBookDrafts::PART_FEEDBACK_HELP,
                'options' => UadBookDrafts::partOptions($draft['book']), 'settings' => ['show_if' => $draft['value']],
            ], $drafts),
            [
                'type' => 'long_text', 'required' => false,
                'label' => '3. Cadangan / komen tambahan (pilihan)',
                'help' => 'Contoh: gabungan elemen daripada draf-draf ini, warna, jenis huruf, gambar yang digunakan dan sebagainya.',
                'options' => null, 'settings' => null,
            ],
        ];

        foreach ($fields as $i => $f) {
            $form->fields()->create($f + ['position' => $i]);
        }
    }
}
