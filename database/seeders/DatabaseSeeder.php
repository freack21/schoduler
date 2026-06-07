<?php

namespace Database\Seeders;

use App\Models\Guru;
use App\Models\GuruMapel;
use App\Models\Jurusan;
use App\Models\Kurikulum;
use App\Models\Jadwal;
use App\Models\JamPelajaran;
use App\Models\Kelas;
use App\Models\Mapel;
use App\Models\Pengaturan;
use App\Models\Siswa;
use App\Models\Tingkat;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;

class DatabaseSeeder extends Seeder
{
    private const DEFAULT_PASSWORD = 'password123';

    public function run(): void
    {
        DB::transaction(function (): void {
            $this->sweepDatabase();

            $data = $this->loadAllJson();

            $this->seedAdmin();
            $tingkatByKode = $this->seedTingkat();
            $jurusanByKode = $this->seedJurusan();
            $kelasByJsonName = $this->seedKelas($data['siswa'] ?? [], $tingkatByKode, $jurusanByKode);
            $this->seedJamPelajaran();
            $this->seedPengaturan();

            $mapelByName = $this->seedMapelFromGuruData($data['guru'] ?? []);
            $guruCount = $this->seedGuru($data['guru'] ?? [], $mapelByName, $tingkatByKode, $jurusanByKode);
            $siswaCount = $this->seedSiswa($data['siswa'] ?? [], $kelasByJsonName);

            $this->command?->info('✅ Seeder selesai! Database sudah di-sweep dan diisi dari database/seeders/data/all.json.');
            $this->command?->info("📊 Total: 1 Admin, {$guruCount} Guru, {$siswaCount} Siswa, " . count($kelasByJsonName) . ' Kelas, ' . count($mapelByName) . ' Mapel');
        }, 3);
    }

    private function sweepDatabase(): void
    {
        Schema::disableForeignKeyConstraints();

        foreach ([
            'jadwal',
            'guru_mapel',
            'kurikulum',
            'jurusan',
            'siswa',
            'guru',
            'mapel',
            'kelas',
            'tingkat',
            'jam_pelajaran',
            'pengaturan',
            'users',
        ] as $table) {
            if (Schema::hasTable($table)) {
                // DELETE is transaction-safe; TRUNCATE causes implicit commit in MySQL.
                DB::table($table)->delete();
            }
        }

        Schema::enableForeignKeyConstraints();
    }

    private function loadAllJson(): array
    {
        $path = database_path('seeders/data/data_seeder.json');

        if (! file_exists($path)) {
            throw new RuntimeException("File data tidak ditemukan: {$path}");
        }

        $data = json_decode(file_get_contents($path), true);

        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($data)) {
            throw new RuntimeException('Format JSON tidak valid: ' . json_last_error_msg());
        }

        return $data;
    }

    private function seedAdmin(): void
    {
        User::create([
            'id' => 'admin',
            'nama_lengkap' => 'Administrator',
            'password' => Hash::make(self::DEFAULT_PASSWORD),
            'role' => 'admin',
        ]);
    }

    private function seedJurusan(): array
    {
        $items = [
            'MIPA' => ['kode' => 'MIPA', 'nama' => 'Matematika dan Ilmu Pengetahuan Alam'],
            'IPS' => ['kode' => 'IPS', 'nama' => 'Ilmu Pengetahuan Sosial'],
        ];

        $result = [];
        foreach ($items as $kode => $item) {
            $result[$kode] = Jurusan::create($item);
        }

        return $result;
    }

    private function seedTingkat(): array
    {
        $items = [
            '10' => ['nama' => 'X', 'kode' => '10'],
            '11' => ['nama' => 'XI', 'kode' => '11'],
            '12' => ['nama' => 'XII', 'kode' => '12'],
        ];

        $result = [];
        foreach ($items as $kode => $item) {
            $result[$kode] = Tingkat::create($item);
        }

        return $result;
    }

    private function seedKelas(array $siswaRows, array $tingkatByKode, array $jurusanByKode): array
    {
        $kelasNames = collect($siswaRows)
            ->pluck('kelas')
            ->filter()
            ->map(fn ($kelas) => trim((string) $kelas))
            ->unique()
            ->sortBy(fn ($kelas) => $this->kelasSortKey($kelas))
            ->values();

        $result = [];

        foreach ($kelasNames as $jsonName) {
            $parts = explode('-', $jsonName);
            $tingkatKode = $parts[0];
            $jurusanKode = $parts[1] ?? null;

            if (! isset($tingkatByKode[$tingkatKode])) {
                $this->command?->warn("⚠️ Kelas {$jsonName} dilewati karena tingkat {$tingkatKode} tidak dikenal.");
                continue;
            }

            $jurusanId = null;
            if ($jurusanKode && isset($jurusanByKode[$jurusanKode])) {
                $jurusanId = $jurusanByKode[$jurusanKode]->id;
            }

            $result[$jsonName] = Kelas::create([
                'nama' => $this->formatKelasName($jsonName),
                'tingkat_id' => $tingkatByKode[$tingkatKode]->id,
                'jurusan_id' => $jurusanId,
            ]);
        }

        return $result;
    }

    private function seedJamPelajaran(): void
    {
        $jamData = [
            ['jam_ke' => 1, 'jam_mulai' => '07:15', 'jam_selesai' => '08:00', 'is_istirahat' => false],
            ['jam_ke' => 2, 'jam_mulai' => '08:00', 'jam_selesai' => '08:45', 'is_istirahat' => false],
            ['jam_ke' => 3, 'jam_mulai' => '08:45', 'jam_selesai' => '09:30', 'is_istirahat' => false],
            ['jam_ke' => 4, 'jam_mulai' => '09:30', 'jam_selesai' => '10:00', 'is_istirahat' => true],
            ['jam_ke' => 5, 'jam_mulai' => '10:00', 'jam_selesai' => '10:45', 'is_istirahat' => false],
            ['jam_ke' => 6, 'jam_mulai' => '10:45', 'jam_selesai' => '11:30', 'is_istirahat' => false],
            ['jam_ke' => 7, 'jam_mulai' => '11:30', 'jam_selesai' => '12:15', 'is_istirahat' => false],
            ['jam_ke' => 8, 'jam_mulai' => '12:15', 'jam_selesai' => '12:45', 'is_istirahat' => true],
            ['jam_ke' => 9, 'jam_mulai' => '12:45', 'jam_selesai' => '13:30', 'is_istirahat' => false],
            ['jam_ke' => 10, 'jam_mulai' => '13:30', 'jam_selesai' => '14:15', 'is_istirahat' => false],
            ['jam_ke' => 11, 'jam_mulai' => '14:15', 'jam_selesai' => '15:00', 'is_istirahat' => false],
        ];

        foreach ($jamData as $item) {
            JamPelajaran::create($item);
        }
    }

    private function seedPengaturan(): void
    {
        $settings = [
            ['key' => 'hari_aktif', 'value' => 'Senin,Selasa,Rabu,Kamis,Jumat', 'label' => 'Hari aktif penjadwalan'],
            ['key' => 'nama_sekolah', 'value' => 'SMA Negeri 1 Tapung Hulu', 'label' => 'Nama sekolah'],
            ['key' => 'alamat_sekolah', 'value' => 'Jl. Kampung Lama No. 10, Kasikan, Kec. Tapung Hulu, Kab. Kampar, Riau', 'label' => 'Alamat sekolah'],
            ['key' => 'tahun_ajaran', 'value' => '2025/2026', 'label' => 'Tahun ajaran aktif'],
            ['key' => 'semester', 'value' => 'Genap', 'label' => 'Semester aktif'],
        ];

        foreach ($settings as $setting) {
            Pengaturan::create($setting);
        }
    }

    private function seedMapelFromGuruData(array $guruRows): array
    {
        $mapelNames = [];

        foreach ($guruRows as $row) {
            foreach ($this->parseMapelAssignments((string) ($row['mapel'] ?? '')) as $assignment) {
                $mapelNames[$assignment['mapel']] = true;
            }
        }

        $generatedCodes = [];
        $result = [];
        foreach (array_keys($mapelNames) as $nama) {
            $kode = $this->generateMapelCode($nama, $generatedCodes);
            $generatedCodes[] = $kode;
            $result[$nama] = Mapel::create([
                'kode' => $kode,
                'nama' => $nama,
                'jam_per_minggu' => $this->defaultJamPerMinggu($nama),
                'jam_per_hari' => $this->defaultJamPerHari($nama),
            ]);
        }

        return $result;
    }

        private function seedGuru(array $guruRows, array $mapelByName, array $tingkatByKode, array $jurusanByKode): int
    {
        $count = 0;
        $hashedPassword = Hash::make(self::DEFAULT_PASSWORD);

        foreach ($guruRows as $index => $row) {
            $nama = trim((string) ($row['nama'] ?? ''));
            if ($nama === '') {
                continue;
            }

            $user = User::create([
                'id' => $this->uniqueUserId($this->normalizeId((string) ($row['nip'] ?? '')), 'guru-' . ($index + 1)),
                'nama_lengkap' => $nama,
                'password' => $hashedPassword,
                'role' => 'guru',
            ]);

            $guru = Guru::create(['user_id' => $user->id]);
            $count++;

            foreach ($this->parseMapelAssignments((string) ($row['mapel'] ?? '')) as $assignment) {
                $mapel = $mapelByName[$assignment['mapel']] ?? null;
                if (! $mapel) {
                    continue;
                }

                // Assign guru to mapel generally
                GuruMapel::firstOrCreate([
                    'guru_id' => $guru->id,
                    'mapel_id' => $mapel->id,
                ]);

                // Register mapel into kurikulum based on intents
                foreach ($assignment['tingkat'] as $tingkatString) {
                    $parts = explode('-', $tingkatString);
                    $tKode = $parts[0];
                    $jKode = $parts[1] ?? null;

                    $tingkatId = $tingkatByKode[$tKode]->id ?? null;
                    if (! $tingkatId) continue;

                    $jurusanId = null;
                    if ($jKode && isset($jurusanByKode[$jKode])) {
                        $jurusanId = $jurusanByKode[$jKode]->id;
                    }

                    Kurikulum::firstOrCreate([
                        'tingkat_id' => $tingkatId,
                        'jurusan_id' => $jurusanId,
                        'mapel_id' => $mapel->id,
                    ]);
                }
            }
        }

        return $count;
    }

    private function seedSiswa(array $siswaRows, array $kelasByJsonName): int
    {
        $count = 0;
        $hashedPassword = Hash::make(self::DEFAULT_PASSWORD);

        foreach ($siswaRows as $index => $row) {
            $nama = trim((string) ($row['nama'] ?? ''));
            $kelasKey = trim((string) ($row['kelas'] ?? ''));

            if ($nama === '' || ctype_digit($nama) || ! isset($kelasByJsonName[$kelasKey])) {
                continue;
            }

            $rawNisn = $this->normalizeId((string) ($row['nisn'] ?? ''));
            $fallback = 'siswa-' . ($index + 1);

            $user = User::create([
                'id' => $this->uniqueUserId($rawNisn, $fallback),
                'nama_lengkap' => $nama,
                'password' => $hashedPassword,
                'role' => 'siswa',
            ]);

            Siswa::create([
                'user_id' => $user->id,
                'kelas_id' => $kelasByJsonName[$kelasKey]->id,
            ]);

            $count++;
        }

        return $count;
    }

    private function parseMapelAssignments(string $raw): array
    {
        $raw = trim(preg_replace('/\s+/', ' ', $raw));
        if ($raw === '' || strcasecmp($raw, 'Kepala Sekolah') === 0) {
            return [];
        }

        $assignments = [];
        preg_match_all('/([^,()]+?)(?:\s*\(([^)]*)\))?(?=\s*,\s*[^,()]+(?:\s*\([^)]*\))?|$)/u', $raw, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $mapel = $this->normalizeMapelName($match[1] ?? '');
            if ($mapel === '') {
                continue;
            }

            $tingkat = $this->parseTingkatList($match[2] ?? '');
            $assignments[] = [
                'mapel' => $mapel,
                'tingkat' => $tingkat ?: ['10', '11', '12'],
            ];
        }

        return $assignments;
    }

    private function parseTingkatList(string $raw): array
    {
        preg_match_all('/(10|11|12)(?:-(MIPA|IPS))?/i', $raw, $matches, PREG_SET_ORDER);
        $result = [];
        foreach ($matches as $m) {
            $base = $m[1];
            $major = isset($m[2]) && $m[2] ? '-' . strtoupper($m[2]) : '';
            $result[] = $base . $major;
        }
        return array_values(array_unique($result));
    }

    private function normalizeMapelName(string $name): string
    {
        $name = trim(preg_replace('/\s+/', ' ', $name));

        return match (mb_strtolower($name)) {
            'ppkn' => 'PPKN',
            'p. agama islam' => 'Pendidikan Agama Islam',
            'p. agama kristen' => 'Pendidikan Agama Kristen',
            'pkwu' => 'PKWU',
            'bk' => 'BK',
            default => $name,
        };
    }

    private function kelasForTingkat(array $tingkatList, array $kelasByJsonName): array
    {
        $tingkatListStr = array_map('strval', $tingkatList);
        return collect($kelasByJsonName)
            ->filter(function ($kelas, $jsonName) use ($tingkatListStr) {
                // If seeder says "11", it applies to "11-MIPA-1", "11-IPS-1", "11-1".
                // If seeder says "11-MIPA", it only applies to "11-MIPA-1", "11-MIPA-2".
                foreach ($tingkatListStr as $t) {
                    if (str_starts_with($jsonName, $t)) return true;
                }
                return false;
            })
            ->values()
            ->all();
    }

    private function formatKelasName(string $jsonName): string
    {
        $parts = explode('-', $jsonName);
        $tingkat = $parts[0] ?? '';
        $roman = match ($tingkat) {
            '10' => 'X',
            '11' => 'XI',
            '12' => 'XII',
            default => $tingkat,
        };
        
        $parts[0] = $roman;
        return implode('-', $parts);
    }

    private function kelasSortKey(string $kelas): string
    {
        [$tingkat, $nomor] = array_pad(explode('-', $kelas, 2), 2, '0');
        return str_pad($tingkat, 2, '0', STR_PAD_LEFT) . '-' . str_pad($nomor, 3, '0', STR_PAD_LEFT);
    }

    private function normalizeId(string $value): string
    {
        return trim(str_replace(["'", ' '], '', $value));
    }

    private function uniqueUserId(string $preferred, string $fallback): string
    {
        $base = $preferred !== '' ? $preferred : $fallback;
        $id = $base;
        $counter = 2;

        while (User::whereKey($id)->exists()) {
            $id = $base . '-' . $counter;
            $counter++;
        }

        return $id;
    }

    private function generateMapelCode(string $name, array $existingCodes): string
    {
        $words = explode(' ', trim(preg_replace('/[^A-Za-z0-9 ]/', ' ', $name)));
        $words = array_filter($words);
        $words = array_values($words);

        if (count($words) === 0) {
            $prefix = 'MPL';
        } elseif (count($words) === 1) {
            $prefix = Str::upper(Str::substr($words[0], 0, 3));
        } else {
            $prefix = Str::upper(Str::substr($words[0], 0, 1) . Str::substr($words[1], 0, 3));
        }

        $counter = 1;
        while (true) {
            $code = $prefix . str_pad((string)$counter, 2, '0', STR_PAD_LEFT);
            if (!in_array($code, $existingCodes, true)) {
                return $code;
            }
            $counter++;
        }
    }

    private function defaultJamPerMinggu(string $mapel): int
    {
        $lower = mb_strtolower($mapel);

        if (str_contains($lower, 'bk')) {
            return 1;
        }

        if (str_contains($lower, 'lanjut') || str_contains($lower, 'lanjutan')) {
            return 4;
        }

        return match ($mapel) {
            'Bahasa Indonesia', 'Matematika Wajib' => 4,
            'Bahasa Inggris' => 3,
            default => 2,
        };
    }

    private function defaultJamPerHari(string $mapel): int
    {
        return str_contains(mb_strtolower($mapel), 'bk') ? 1 : 2;
    }
}