<?php

namespace App\Support;

/**
 * Definisi draf konsep Buku Laporan Tahunan UAD (imej dalam public/images/uad/book{N}).
 * Digunakan oleh DatabaseSeeder (pemasangan baharu) dan arahan uad:kemaskini-draf (borang sedia ada).
 */
class UadBookDrafts
{
    public const FORM_SLUG = 'draf-buku-uad-2025';

    /**
     * Bahagian buku yang dinilai: [kunci pilihan, label, nama fail imej].
     *
     * @var array<int, array{0: string, 1: string, 2: string}>
     */
    public const PARTS = [
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

    /**
     * Halaman tambahan dalam galeri sesuatu buku, diletakkan selepas bahagian yang dinyatakan.
     *
     * @var array<int, array<int, array{after: string, caption: string, file: string}>>
     */
    private const EXTRA_GALLERY = [
        3 => [['after' => 'sekapur-sirih', 'caption' => 'Sekapur Sirih (sambungan)', 'file' => 'sekapur-sirih-2']],
    ];

    /**
     * @return array<int, array{book: int, value: string, label: string, description: string}>
     */
    public static function drafts(): array
    {
        return [
            ['book' => 1, 'value' => 'draf1', 'label' => 'Draf 1 – Krim & Emas', 'description' => 'Tema elegan krim dengan aksen emas, bingkai bulat dan gambar bangunan KEJORA.'],
            ['book' => 2, 'value' => 'draf2', 'label' => 'Draf 2 – Biru Moden', 'description' => 'Tema biru korporat dengan corak geometri dan pembahagi bahagian berwarna.'],
            ['book' => 3, 'value' => 'draf3', 'label' => 'Draf 3 – Teal & Ungu', 'description' => 'Tema moden teal dan ungu dengan bingkai pentagon emas, corak geometri Islamik dan gambar bangunan KEJORA.'],
        ];
    }

    public static function image(int $book, string $file): string
    {
        return "/images/uad/book{$book}/{$file}.jpg";
    }

    /**
     * @return array<int, array{src: string, caption: string}>
     */
    public static function gallery(int $book): array
    {
        $gallery = [];
        foreach (self::PARTS as [, $label, $file]) {
            $gallery[] = ['src' => self::image($book, $file), 'caption' => $label];
            foreach (self::EXTRA_GALLERY[$book] ?? [] as $extra) {
                if ($extra['after'] === $file) {
                    $gallery[] = ['src' => self::image($book, $extra['file']), 'caption' => $extra['caption']];
                }
            }
        }

        return $gallery;
    }

    /**
     * Pilihan untuk soalan "Maklum balas setiap bahagian".
     *
     * @return array<int, array{value: string, label: string, description: string, image: string, gallery: array<int, mixed>}>
     */
    public static function partOptions(int $book): array
    {
        return array_map(
            fn (array $part) => ['value' => $part[0], 'label' => $part[1], 'description' => '', 'image' => self::image($book, $part[2]), 'gallery' => []],
            self::PARTS
        );
    }

    /**
     * @param  array{book: int, value: string, label: string, description: string}  $draft
     * @return array{value: string, label: string, description: string, image: string, gallery: array<int, array{src: string, caption: string}>}
     */
    public static function choiceOption(array $draft): array
    {
        return [
            'value' => $draft['value'],
            'label' => $draft['label'],
            'description' => $draft['description'],
            'image' => self::image($draft['book'], 'overview'),
            'gallery' => self::gallery($draft['book']),
        ];
    }

    public static function partFeedbackLabel(string $draftLabel): string
    {
        return '2. Maklum balas setiap bahagian – '.preg_replace('/^(Draf \d+)\s*–\s*(.+)$/u', '$1 ($2)', $draftLabel);
    }

    public const PART_FEEDBACK_HELP = 'Nyatakan pandangan anda bagi setiap bahagian. Pilih "Perlu penambahbaikan" jika ada yang perlu diubah dan terangkan di ruang komen.';
}
