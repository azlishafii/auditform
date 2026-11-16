<?php

namespace Database\Seeders;

use App\Models\Form;
use App\Models\User;
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

        if (! Form::where('slug', 'draf-buku-uad-2025')->exists()) {
            $this->seedBookForm($user);
        }
    }

    private function seedBookForm(User $user): void
    {
        $parts = [
            ['kulitdepan', 'Kulit Depan', 'kulit-depan'],
            ['isikandung', 'Isi Kandungan', 'isi-kandungan'],
            ['perutusan', 'Perutusan', 'perutusan'],
            ['sekapursirih', 'Sekapur Sirih', 'sekapur-sirih'],
            ['tadbirurus', 'Tadbir Urus Korporat', 'tadbir-urus'],
            ['bahagian1', 'Pembahagi Bahagian 1 – Maklumat UAD', 'bahagian-1'],
            ['bahagian2', 'Pembahagi Bahagian 2 – JKA KEJORA', 'bahagian-2'],
            ['bahagian3', 'Pembahagi Bahagian 3 – Pencapaian Aktiviti Pengauditan', 'bahagian-3'],
            ['bahagian4', 'Pembahagi Bahagian 4 – Laporan Pengauditan', 'bahagian-4'],
            ['kulitbelakang', 'Kulit Belakang', 'kulit-belakang'],
            ['tulang', 'Tulang (Spine)', 'tulang'],
        ];

        $img = fn (int $book, string $file) => "/images/uad/book{$book}/{$file}.jpg";

        $gallery = fn (int $book) => array_map(
            fn ($p) => ['src' => $img($book, $p[2]), 'caption' => $p[1]],
            $parts
        );
        $partOptions = fn (int $book) => array_map(
            fn ($p) => ['value' => $p[0], 'label' => $p[1], 'description' => '', 'image' => $img($book, $p[2]), 'gallery' => []],
            $parts
        );

        $form = Form::create([
            'user_id' => $user->id,
            'title' => 'Pemilihan Draf Konsep Buku Laporan Tahunan Unit Audit Dalam 2025',
            'slug' => 'draf-buku-uad-2025',
            'description' => 'Pembekal telah mengemukakan dua (2) draf konsep reka bentuk buku Laporan Tahunan Unit Audit Dalam KEJORA. '
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
                'options' => [
                    ['value' => 'draf1', 'label' => 'Draf 1 – Krim & Emas', 'description' => 'Tema elegan krim dengan aksen emas, bingkai bulat dan gambar bangunan KEJORA.', 'image' => $img(1, 'overview'), 'gallery' => $gallery(1)],
                    ['value' => 'draf2', 'label' => 'Draf 2 – Biru Moden', 'description' => 'Tema biru korporat dengan corak geometri dan pembahagi bahagian berwarna.', 'image' => $img(2, 'overview'), 'gallery' => $gallery(2)],
                ],
                'settings' => ['allow_comment' => false],
            ],
            // Dua soalan berikut bersyarat: hanya draf yang dipilih dipaparkan
            [
                'type' => 'part_feedback', 'required' => false,
                'label' => '2. Maklum balas setiap bahagian – Draf 1 (Krim & Emas)',
                'help' => 'Nyatakan pandangan anda bagi setiap bahagian. Pilih "Perlu penambahbaikan" jika ada yang perlu diubah dan terangkan di ruang komen.',
                'options' => $partOptions(1), 'settings' => ['show_if' => 'draf1'],
            ],
            [
                'type' => 'part_feedback', 'required' => false,
                'label' => '2. Maklum balas setiap bahagian – Draf 2 (Biru Moden)',
                'help' => 'Nyatakan pandangan anda bagi setiap bahagian. Pilih "Perlu penambahbaikan" jika ada yang perlu diubah dan terangkan di ruang komen.',
                'options' => $partOptions(2), 'settings' => ['show_if' => 'draf2'],
            ],
            [
                'type' => 'long_text', 'required' => false,
                'label' => '3. Cadangan / komen tambahan (pilihan)',
                'help' => 'Contoh: gabungan elemen daripada kedua-dua draf, warna, jenis huruf, gambar yang digunakan dan sebagainya.',
                'options' => null, 'settings' => null,
            ],
        ];

        foreach ($fields as $i => $f) {
            $form->fields()->create($f + ['position' => $i]);
        }
    }
}
