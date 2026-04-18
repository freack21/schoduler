<?php

namespace Database\Seeders;

use App\Models\Guru;
use App\Models\GuruMapel;
use App\Models\JamPelajaran;
use App\Models\Jadwal;
use App\Models\Kelas;
use App\Models\Mapel;
use App\Models\Pengaturan;
use App\Models\Siswa;
use App\Models\Tingkat;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ─────────────────────────────────────────────────────────────────
        // ADMIN
        // ─────────────────────────────────────────────────────────────────
        User::create([
            'id' => 'admin',
            'nama_lengkap' => 'Administrator',
            'password' => bcrypt('password123'),
            'role' => 'admin',
        ]);

        // ─────────────────────────────────────────────────────────────────
        // TINGKAT
        // ─────────────────────────────────────────────────────────────────
        $tingkatX = Tingkat::create(['nama' => 'X', 'kode' => '10']);
        $tingkatXI = Tingkat::create(['nama' => 'XI', 'kode' => '11']);
        $tingkatXII = Tingkat::create(['nama' => 'XII', 'kode' => '12']);

        // ─────────────────────────────────────────────────────────────────
        // KELAS (21 Kelas - Masing-masing tingkat 7 kelas)
        // ─────────────────────────────────────────────────────────────────
        $kelasList = [];

        // Tingkat X: X-1 sampai X-7
        for ($i = 1; $i <= 7; $i++) {
            $kelasList[] = Kelas::create([
                'nama' => "X-{$i}",
                'tingkat_id' => $tingkatX->id,
            ]);
        }

        // Tingkat XI: XI-IPA-1, XI-IPA-2, XI-IPA-3, XI-IPS-1, XI-IPS-2, XI-IPS-3, XI-BAHASA
        $kelasList[] = Kelas::create(['nama' => 'XI-IPA-1', 'tingkat_id' => $tingkatXI->id]);
        $kelasList[] = Kelas::create(['nama' => 'XI-IPA-2', 'tingkat_id' => $tingkatXI->id]);
        $kelasList[] = Kelas::create(['nama' => 'XI-IPA-3', 'tingkat_id' => $tingkatXI->id]);
        $kelasList[] = Kelas::create(['nama' => 'XI-IPS-1', 'tingkat_id' => $tingkatXI->id]);
        $kelasList[] = Kelas::create(['nama' => 'XI-IPS-2', 'tingkat_id' => $tingkatXI->id]);
        $kelasList[] = Kelas::create(['nama' => 'XI-IPS-3', 'tingkat_id' => $tingkatXI->id]);
        $kelasList[] = Kelas::create(['nama' => 'XI-BAHASA', 'tingkat_id' => $tingkatXI->id]);

        // Tingkat XII: XII-IPA-1, XII-IPA-2, XII-IPA-3, XII-IPS-1, XII-IPS-2, XII-IPS-3, XII-BAHASA
        $kelasList[] = Kelas::create(['nama' => 'XII-IPA-1', 'tingkat_id' => $tingkatXII->id]);
        $kelasList[] = Kelas::create(['nama' => 'XII-IPA-2', 'tingkat_id' => $tingkatXII->id]);
        $kelasList[] = Kelas::create(['nama' => 'XII-IPA-3', 'tingkat_id' => $tingkatXII->id]);
        $kelasList[] = Kelas::create(['nama' => 'XII-IPS-1', 'tingkat_id' => $tingkatXII->id]);
        $kelasList[] = Kelas::create(['nama' => 'XII-IPS-2', 'tingkat_id' => $tingkatXII->id]);
        $kelasList[] = Kelas::create(['nama' => 'XII-IPS-3', 'tingkat_id' => $tingkatXII->id]);
        $kelasList[] = Kelas::create(['nama' => 'XII-BAHASA', 'tingkat_id' => $tingkatXII->id]);

        $allKelas = collect($kelasList);

        // ─────────────────────────────────────────────────────────────────
        // MATA PELAJARAN (Lengkap Kurikulum Merdeka)
        // ─────────────────────────────────────────────────────────────────
        $mapelData = [
            // Kelompok Umum (Semua Jurusan)
            ['kode' => 'PAI', 'nama' => 'Pendidikan Agama Islam', 'jam_per_minggu' => 2, 'max_jam_per_hari' => 1],
            ['kode' => 'PKN', 'nama' => 'Pendidikan Kewarganegaraan', 'jam_per_minggu' => 2, 'max_jam_per_hari' => 1],
            ['kode' => 'BIN', 'nama' => 'Bahasa Indonesia', 'jam_per_minggu' => 4, 'max_jam_per_hari' => 2],
            ['kode' => 'MTK', 'nama' => 'Matematika', 'jam_per_minggu' => 4, 'max_jam_per_hari' => 2],
            ['kode' => 'SEJ', 'nama' => 'Sejarah Indonesia', 'jam_per_minggu' => 2, 'max_jam_per_hari' => 1],
            ['kode' => 'BIG', 'nama' => 'Bahasa Inggris', 'jam_per_minggu' => 3, 'max_jam_per_hari' => 2],
            ['kode' => 'PJK', 'nama' => 'PJOK', 'jam_per_minggu' => 2, 'max_jam_per_hari' => 2],
            ['kode' => 'SBD', 'nama' => 'Seni Budaya', 'jam_per_minggu' => 2, 'max_jam_per_hari' => 2],
            ['kode' => 'PRA', 'nama' => 'Prakarya dan Kewirausahaan', 'jam_per_minggu' => 2, 'max_jam_per_hari' => 2],

            // Kelompok IPA (Peminatan)
            ['kode' => 'FIS', 'nama' => 'Fisika', 'jam_per_minggu' => 4, 'max_jam_per_hari' => 2],
            ['kode' => 'KIM', 'nama' => 'Kimia', 'jam_per_minggu' => 4, 'max_jam_per_hari' => 2],
            ['kode' => 'BIO', 'nama' => 'Biologi', 'jam_per_minggu' => 4, 'max_jam_per_hari' => 2],
            ['kode' => 'MTM', 'nama' => 'Matematika Peminatan', 'jam_per_minggu' => 3, 'max_jam_per_hari' => 2],

            // Kelompok IPS (Peminatan)
            ['kode' => 'EKO', 'nama' => 'Ekonomi', 'jam_per_minggu' => 4, 'max_jam_per_hari' => 2],
            ['kode' => 'SOS', 'nama' => 'Sosiologi', 'jam_per_minggu' => 4, 'max_jam_per_hari' => 2],
            ['kode' => 'GEO', 'nama' => 'Geografi', 'jam_per_minggu' => 4, 'max_jam_per_hari' => 2],
            ['kode' => 'SEP', 'nama' => 'Sejarah Peminatan', 'jam_per_minggu' => 3, 'max_jam_per_hari' => 2],

            // Kelompok Bahasa (Peminatan)
            ['kode' => 'BJP', 'nama' => 'Bahasa Jepang', 'jam_per_minggu' => 4, 'max_jam_per_hari' => 2],
            ['kode' => 'ANT', 'nama' => 'Antropologi', 'jam_per_minggu' => 3, 'max_jam_per_hari' => 2],
            ['kode' => 'SAS', 'nama' => 'Sastra Indonesia', 'jam_per_minggu' => 3, 'max_jam_per_hari' => 2],
            ['kode' => 'BSI', 'nama' => 'Bahasa Asing Lainnya', 'jam_per_minggu' => 2, 'max_jam_per_hari' => 2],

            // Muatan Lokal
            ['kode' => 'TIK', 'nama' => 'Informatika', 'jam_per_minggu' => 2, 'max_jam_per_hari' => 2],
            ['kode' => 'BMR', 'nama' => 'Budaya Melayu Riau', 'jam_per_minggu' => 2, 'max_jam_per_hari' => 1],
            ['kode' => 'BPK', 'nama' => 'Bimbingan Konseling', 'jam_per_minggu' => 1, 'max_jam_per_hari' => 1],
        ];

        foreach ($mapelData as $m) {
            Mapel::create($m);
        }
        $allMapel = Mapel::all();

        // ─────────────────────────────────────────────────────────────────
        // JAM PELAJARAN (SMA Negeri 1 Tapung Hulu - 5 Hari Sekolah)
        // ─────────────────────────────────────────────────────────────────
        $jamData = [
            // Senin - Kamis
            ['jam_ke' => 1, 'jam_mulai' => '07:15', 'jam_selesai' => '08:00', 'is_istirahat' => false],
            ['jam_ke' => 2, 'jam_mulai' => '08:00', 'jam_selesai' => '08:45', 'is_istirahat' => false],
            ['jam_ke' => 3, 'jam_mulai' => '08:45', 'jam_selesai' => '09:30', 'is_istirahat' => false],
            ['jam_ke' => 4, 'jam_mulai' => '09:30', 'jam_selesai' => '10:00', 'is_istirahat' => true],  // Istirahat 1
            ['jam_ke' => 5, 'jam_mulai' => '10:00', 'jam_selesai' => '10:45', 'is_istirahat' => false],
            ['jam_ke' => 6, 'jam_mulai' => '10:45', 'jam_selesai' => '11:30', 'is_istirahat' => false],
            ['jam_ke' => 7, 'jam_mulai' => '11:30', 'jam_selesai' => '12:15', 'is_istirahat' => false],
            ['jam_ke' => 8, 'jam_mulai' => '12:15', 'jam_selesai' => '12:45', 'is_istirahat' => true],  // Istirahat 2 (Sholat)
            ['jam_ke' => 9, 'jam_mulai' => '12:45', 'jam_selesai' => '13:30', 'is_istirahat' => false],
            ['jam_ke' => 10, 'jam_mulai' => '13:30', 'jam_selesai' => '14:15', 'is_istirahat' => false],
            ['jam_ke' => 11, 'jam_mulai' => '14:15', 'jam_selesai' => '15:00', 'is_istirahat' => false],
        ];

        foreach ($jamData as $j) {
            JamPelajaran::create($j);
        }

        // ─────────────────────────────────────────────────────────────────
        // GURU (56 Guru - Sesuai dengan kondisi SMA Negeri 1 Tapung Hulu)
        // ─────────────────────────────────────────────────────────────────
        $guruList = [
            // Kepala Sekolah & Wakil
            ['nip' => '196504151989031008', 'nama' => 'Drs. H. Syamsul Bahri, M.Pd', 'mapel' => ['BIN']],
            ['nip' => '197003211995121001', 'nama' => 'Dra. Hj. Rosmawati, M.Si', 'mapel' => ['MTK']],
            ['nip' => '197208121998021003', 'nama' => 'Drs. Ahmad Yani, M.Pd', 'mapel' => ['SEJ', 'SEP']],
            ['nip' => '197511032000121004', 'nama' => 'Hj. Nurhayati, S.Pd, M.Pd', 'mapel' => ['BIN', 'SAS']],

            // Guru Agama
            ['nip' => '197803142002121001', 'nama' => 'H. Abdul Rahman, S.Ag, M.Pd.I', 'mapel' => ['PAI']],
            ['nip' => '198105122006042008', 'nama' => 'Hj. Siti Aminah, S.Pd.I', 'mapel' => ['PAI']],
            ['nip' => '198407152008011007', 'nama' => 'Muhammad Ridwan, S.Pd.I', 'mapel' => ['PAI']],

            // Guru PKN
            ['nip' => '197906182003121005', 'nama' => 'Drs. Bambang Sutrisno', 'mapel' => ['PKN']],
            ['nip' => '198209232007021004', 'nama' => 'Yuliana Sari, S.Pd', 'mapel' => ['PKN']],
            ['nip' => '198512112010012008', 'nama' => 'Hendra Gunawan, S.Pd', 'mapel' => ['PKN']],

            // Guru Bahasa Indonesia
            ['nip' => '197605192001122003', 'nama' => 'Dra. Sri Wahyuni, M.Pd', 'mapel' => ['BIN']],
            ['nip' => '198008152005012006', 'nama' => 'Rina Marlina, S.Pd', 'mapel' => ['BIN']],
            ['nip' => '198303212008012009', 'nama' => 'Dewi Sartika, S.Pd', 'mapel' => ['BIN', 'SAS']],
            ['nip' => '198606112011012010', 'nama' => 'Ahmad Fauzi, S.Pd', 'mapel' => ['BIN']],

            // Guru Matematika
            ['nip' => '197702182002121004', 'nama' => 'Drs. Budi Santoso, M.Pd', 'mapel' => ['MTK']],
            ['nip' => '198103252006041005', 'nama' => 'Sri Handayani, S.Pd', 'mapel' => ['MTK']],
            ['nip' => '198408182009021006', 'nama' => 'Rudi Hartono, S.Pd', 'mapel' => ['MTK', 'MTM']],
            ['nip' => '198710152012031007', 'nama' => 'Lina Wati, S.Pd', 'mapel' => ['MTK']],
            ['nip' => '199001202015041008', 'nama' => 'Muhammad Rizki, S.Pd', 'mapel' => ['MTK', 'MTM']],

            // Guru Bahasa Inggris
            ['nip' => '197811202003122004', 'nama' => 'Hj. Fatimah Azzahra, S.Pd, M.Pd', 'mapel' => ['BIG']],
            ['nip' => '198204182007012005', 'nama' => 'John Marpaung, S.Pd', 'mapel' => ['BIG']],
            ['nip' => '198506152010022006', 'nama' => 'Rita Susanti, S.Pd', 'mapel' => ['BIG']],
            ['nip' => '198808192013032007', 'nama' => 'Dimas Prasetyo, S.Pd', 'mapel' => ['BIG']],

            // Guru IPA (Fisika, Kimia, Biologi)
            ['nip' => '197912152004121003', 'nama' => 'Drs. Hendri Kurniawan, M.Pd', 'mapel' => ['FIS']],
            ['nip' => '198205212007011004', 'nama' => 'Ratna Dewi, S.Pd', 'mapel' => ['FIS']],
            ['nip' => '198510202010021005', 'nama' => 'Agus Salim, S.Pd', 'mapel' => ['FIS']],
            ['nip' => '197803112002121002', 'nama' => 'Dra. Hj. Kartini, M.Si', 'mapel' => ['KIM']],
            ['nip' => '198106162006041003', 'nama' => 'Surya Dharma, S.Pd', 'mapel' => ['KIM']],
            ['nip' => '198409142009021004', 'nama' => 'Maya Sari, S.Pd', 'mapel' => ['KIM']],
            ['nip' => '197701232001122001', 'nama' => 'Dra. Rosita, M.Pd', 'mapel' => ['BIO']],
            ['nip' => '198002282005012002', 'nama' => 'Hendra Wijaya, S.Pd', 'mapel' => ['BIO']],
            ['nip' => '198303172008012003', 'nama' => 'Nurul Hidayah, S.Pd', 'mapel' => ['BIO']],

            // Guru IPS (Ekonomi, Sosiologi, Geografi)
            ['nip' => '197911182003121004', 'nama' => 'Drs. Syafruddin, M.Pd', 'mapel' => ['EKO']],
            ['nip' => '198207232007011005', 'nama' => 'Lisa Anggraini, S.Pd', 'mapel' => ['EKO']],
            ['nip' => '198512172010021006', 'nama' => 'Taufik Hidayat, S.Pd', 'mapel' => ['EKO']],
            ['nip' => '197802162002121003', 'nama' => 'Dra. Hj. Yusnidar, M.Si', 'mapel' => ['SOS']],
            ['nip' => '198104212006041004', 'nama' => 'Rahmat Hidayat, S.Pd', 'mapel' => ['SOS']],
            ['nip' => '198407162009021005', 'nama' => 'Desi Ratnasari, S.Pd', 'mapel' => ['SOS']],
            ['nip' => '197901202003122001', 'nama' => 'Drs. H. Marzuki, M.Pd', 'mapel' => ['GEO']],
            ['nip' => '198203252007011002', 'nama' => 'Yanti Susilawati, S.Pd', 'mapel' => ['GEO']],
            ['nip' => '198506192010021003', 'nama' => 'Andi Wijaya, S.Pd', 'mapel' => ['GEO']],

            // Guru Sejarah
            ['nip' => '197812112002121001', 'nama' => 'Drs. Hasan Basri, M.Pd', 'mapel' => ['SEJ', 'SEP']],
            ['nip' => '198105182006041002', 'nama' => 'Mardiana, S.Pd', 'mapel' => ['SEJ']],
            ['nip' => '198408222009021003', 'nama' => 'Zulkifli, S.Pd', 'mapel' => ['SEJ', 'SEP']],

            // Guru Seni Budaya & Prakarya
            ['nip' => '197905152004121003', 'nama' => 'Eka Putri, S.Sn', 'mapel' => ['SBD']],
            ['nip' => '198210202007011004', 'nama' => 'Robby Chandra, S.Pd', 'mapel' => ['SBD', 'PRA']],
            ['nip' => '198512252010021005', 'nama' => 'Fitriani, S.Pd', 'mapel' => ['PRA']],

            // Guru PJOK
            ['nip' => '197807182003121002', 'nama' => 'H. Musliadi, S.Pd, M.Or', 'mapel' => ['PJK']],
            ['nip' => '198109232006041003', 'nama' => 'Yuni Astuti, S.Pd', 'mapel' => ['PJK']],
            ['nip' => '198403282009021004', 'nama' => 'Fadli Rahman, S.Pd', 'mapel' => ['PJK']],

            // Guru Bahasa Asing & Muatan Lokal
            ['nip' => '198006142005012003', 'nama' => 'Yuki Tanaka, S.Pd', 'mapel' => ['BJP', 'BSI']],
            ['nip' => '198311182008012004', 'nama' => 'Mira Suryani, S.Pd', 'mapel' => ['ANT']],
            ['nip' => '198602222011012005', 'nama' => 'Hendra Saputra, S.Kom', 'mapel' => ['TIK']],
            ['nip' => '197911172004121001', 'nama' => 'Drs. M. Nasir, M.Pd', 'mapel' => ['BMR']],
            ['nip' => '198204212007011002', 'nama' => 'Rahmawati, S.Pd', 'mapel' => ['BPK']],
        ];

        $guruModels = [];
        foreach ($guruList as $g) {
            $user = User::create([
                'id' => $g['nip'],
                'nama_lengkap' => $g['nama'],
                'password' => bcrypt('password123'),
                'role' => 'guru',
            ]);
            $guruModels[] = [
                'model' => Guru::create(['user_id' => $user->id]),
                'mapel' => $g['mapel'],
            ];
        }

        // ─────────────────────────────────────────────────────────────────
        // ASSIGN GURU KE MAPEL & KELAS (Realistis)
        // ─────────────────────────────────────────────────────────────────
        foreach ($guruModels as $guruData) {
            $guru = $guruData['model'];
            $mapelKodes = $guruData['mapel'];

            foreach ($mapelKodes as $kode) {
                $mapel = $allMapel->where('kode', $kode)->first();
                if (!$mapel)
                    continue;

                // Tentukan kelas mana yang diajar berdasarkan mapel
                $targetKelas = $this->getTargetKelasForMapel($kode, $allKelas);

                foreach ($targetKelas as $kelas) {
                    // Cek duplikat
                    $exists = GuruMapel::where('guru_id', $guru->id)
                        ->where('mapel_id', $mapel->id)
                        ->where('kelas_id', $kelas->id)
                        ->exists();

                    if (!$exists) {
                        GuruMapel::create([
                            'guru_id' => $guru->id,
                            'mapel_id' => $mapel->id,
                            'kelas_id' => $kelas->id,
                        ]);
                    }
                }
            }
        }

        // ─────────────────────────────────────────────────────────────────
        // SISWA (210 Siswa - 10 per kelas x 21 kelas)
        // ─────────────────────────────────────────────────────────────────
        $siswaCount = 25;
        $this->seedSiswa($allKelas, $siswaCount);
        $allSiswaCount = $allKelas->count() * $siswaCount;

        // ─────────────────────────────────────────────────────────────────
        // PENGATURAN DEFAULT
        // ─────────────────────────────────────────────────────────────────
        Pengaturan::setValue('hari_aktif', 'Senin,Selasa,Rabu,Kamis,Jumat', 'Hari aktif penjadwalan');
        Pengaturan::setValue('nama_sekolah', 'SMA Negeri 1 Tapung Hulu', 'Nama sekolah');
        Pengaturan::setValue('alamat_sekolah', 'Jl. Kampung Lama No. 10, Kasikan, Kec. Tapung Hulu, Kab. Kampar, Riau', 'Alamat sekolah');
        Pengaturan::setValue('tahun_ajaran', '2025/2026', 'Tahun ajaran aktif');
        Pengaturan::setValue('semester', 'Genap', 'Semester aktif');

        // Bersihkan jadwal lama
        Jadwal::truncate();

        $this->command->info('✅ Seeder selesai! Data SMA Negeri 1 Tapung Hulu siap digunakan.');
        $this->command->info('📊 Total: 1 Admin, ' . count($guruModels) . ' Guru, ' . $allSiswaCount . ' Siswa, ' . $allKelas->count() . ' Kelas, ' . $allMapel->count() . ' Mapel');
    }

    /**
     * Tentukan kelas target berdasarkan mapel (realistis sesuai jurusan)
     */
    private function getTargetKelasForMapel(string $kodeMapel, $allKelas): array
    {
        $result = [];

        // Mapel Umum (Semua Kelas)
        $umum = ['PAI', 'PKN', 'BIN', 'MTK', 'SEJ', 'BIG', 'PJK', 'SBD', 'PRA', 'TIK', 'BMR', 'BPK'];
        if (in_array($kodeMapel, $umum)) {
            return $allKelas->all();
        }

        // Mapel IPA (Hanya kelas IPA dan X)
        $ipa = ['FIS', 'KIM', 'BIO', 'MTM'];
        if (in_array($kodeMapel, $ipa)) {
            foreach ($allKelas as $kelas) {
                $nama = $kelas->nama;
                if (str_starts_with($nama, 'X-') || str_contains($nama, 'IPA')) {
                    $result[] = $kelas;
                }
            }
            return $result;
        }

        // Mapel IPS (Hanya kelas IPS)
        $ips = ['EKO', 'SOS', 'GEO', 'SEP'];
        if (in_array($kodeMapel, $ips)) {
            foreach ($allKelas as $kelas) {
                if (str_contains($kelas->nama, 'IPS')) {
                    $result[] = $kelas;
                }
            }
            return $result;
        }

        // Mapel Bahasa (Hanya kelas Bahasa)
        $bahasa = ['BJP', 'ANT', 'SAS', 'BSI'];
        if (in_array($kodeMapel, $bahasa)) {
            foreach ($allKelas as $kelas) {
                if (str_contains($kelas->nama, 'BAHASA')) {
                    $result[] = $kelas;
                }
            }
            return $result;
        }

        return $result;
    }

    /**
     * Generate siswa
     */
    private function seedSiswa($allKelas, $siswaCount): void
    {
        $namaDepan = [
            'Ahmad',
            'Budi',
            'Citra',
            'Dewi',
            'Eko',
            'Fitri',
            'Gilang',
            'Hana',
            'Irfan',
            'Jasmine',
            'Kevin',
            'Lina',
            'Muhammad',
            'Nadia',
            'Oscar',
            'Putri',
            'Rizky',
            'Siti',
            'Taufik',
            'Umi',
            'Vina',
            'Wahyu',
            'Yoga',
            'Zahra',
            'Aditya',
            'Bayu',
            'Cindy',
            'Dimas',
            'Elsa',
            'Fajar'
        ];

        $namaBelakang = [
            'Pratama',
            'Setiawan',
            'Dewi',
            'Safitri',
            'Purnomo',
            'Handayani',
            'Ramadhan',
            'Permata',
            'Hakim',
            'Putri',
            'Anggara',
            'Marlina',
            'Rizki',
            'Sari',
            'Pratama',
            'Wulandari',
            'Aditya',
            'Nurhaliza',
            'Hidayat',
            'Kalsum',
            'Melati',
            'Santoso',
            'Pratama',
            'Azzahra'
        ];

        $counter = 1;

        foreach ($allKelas as $kelas) {
            for ($i = 0; $i < $siswaCount; $i++) {
                $nama = $namaDepan[array_rand($namaDepan)] . ' ' . $namaBelakang[array_rand($namaBelakang)];
                $nisn = '00' . str_pad((string) $counter, 8, '0', STR_PAD_LEFT);

                // Cek duplikat NISN
                while (User::where('id', $nisn)->exists()) {
                    $counter++;
                    $nisn = '00' . str_pad((string) $counter, 8, '0', STR_PAD_LEFT);
                }

                $user = User::create([
                    'id' => $nisn,
                    'nama_lengkap' => $nama,
                    'password' => bcrypt('password123'),
                    'role' => 'siswa',
                ]);

                Siswa::create([
                    'user_id' => $user->id,
                    'kelas_id' => $kelas->id,
                ]);

                $counter++;
            }
        }
    }
}