<?php

namespace Database\Seeders;

use App\Models\GuruMapel;
use App\Models\Jadwal;
use App\Models\JamPelajaran;
use App\Models\Pengaturan;
use Illuminate\Database\Seeder;

class JadwalRandomSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🔄 Generating random schedule...');

        // Bersihin jadwal lama
        Jadwal::truncate();

        // Load data
        $guruMapels = GuruMapel::with(['mapel', 'guru', 'kelas'])->get();
        $jamPelajaranList = JamPelajaran::orderBy('jam_ke')->get();
        $hariAktif = Pengaturan::getHariAktif();

        if ($guruMapels->isEmpty()) {
            $this->command->error('❌ Tidak ada data guru_mapel. Jalankan DatabaseSeeder dulu!');
            return;
        }

        // Build genes (satu gen = satu jam pelajaran)
        $genes = [];
        foreach ($guruMapels as $gm) {
            $jamPerMinggu = $gm->mapel->jam_per_minggu;
            for ($j = 0; $j < $jamPerMinggu; $j++) {
                $genes[] = [
                    'guru_mapel_id' => $gm->id,
                    'guru_id' => $gm->guru_id,
                    'kelas_id' => $gm->kelas_id,
                    'mapel_id' => $gm->mapel_id,
                    'mapel_nama' => $gm->mapel->nama,
                    'guru_nama' => $gm->guru->user->nama_lengkap,
                    'kelas_nama' => $gm->kelas->nama,
                ];
            }
        }

        // Only non-istirahat slots
        $jamAktif = $jamPelajaranList->where('is_istirahat', false);
        $jamIds = $jamAktif->pluck('id')->toArray();

        // Build slot map
        $slotMap = [];
        foreach ($hariAktif as $hari) {
            foreach ($jamIds as $jamId) {
                $slotMap[] = [
                    'hari' => $hari,
                    'jam_pelajaran_id' => $jamId,
                ];
            }
        }

        $totalSlots = count($slotMap);
        $entries = [];
        $usedSlots = []; // Track slot yang udah dipake per kelas & guru

        $this->command->info("📊 Total genes: " . count($genes));
        $this->command->info("📊 Total slots: " . $totalSlots);

        // Shuffle genes biar random
        shuffle($genes);

        foreach ($genes as $gene) {
            $kelasId = $gene['kelas_id'];
            $guruId = $gene['guru_id'];

            // Cari slot yang available (gak bentrok)
            $availableSlots = [];
            foreach ($slotMap as $idx => $slot) {
                $keyKelas = "kelas_{$kelasId}_{$slot['hari']}_{$slot['jam_pelajaran_id']}";
                $keyGuru = "guru_{$guruId}_{$slot['hari']}_{$slot['jam_pelajaran_id']}";

                if (!isset($usedSlots[$keyKelas]) && !isset($usedSlots[$keyGuru])) {
                    $availableSlots[] = $idx;
                }
            }

            // Kalo gak ada slot available, paksa random (bakal bentrok)
            if (empty($availableSlots)) {
                $slotIdx = array_rand($slotMap);
            } else {
                // Preferensi: pilih slot di hari yang belum penuh
                $slotIdx = $availableSlots[array_rand($availableSlots)];
            }

            $slot = $slotMap[$slotIdx];

            // Mark slot as used
            $keyKelas = "kelas_{$kelasId}_{$slot['hari']}_{$slot['jam_pelajaran_id']}";
            $keyGuru = "guru_{$guruId}_{$slot['hari']}_{$slot['jam_pelajaran_id']}";
            $usedSlots[$keyKelas] = true;
            $usedSlots[$keyGuru] = true;

            $entries[] = [
                'guru_mapel_id' => $gene['guru_mapel_id'],
                'hari' => $slot['hari'],
                'jam_pelajaran_id' => $slot['jam_pelajaran_id'],
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Insert in chunks
        foreach (array_chunk($entries, 100) as $chunk) {
            Jadwal::insert($chunk);
        }

        // Hitung statistik bentrok
        $bentrok = $this->hitungBentrok($entries);

        $this->command->info("✅ Jadwal random berhasil digenerate!");
        $this->command->info("📊 Total jadwal: " . count($entries));
        $this->command->warn("⚠️ Bentrok Guru: {$bentrok['guru']}");
        $this->command->warn("⚠️ Bentrok Kelas: {$bentrok['kelas']}");
        $this->command->info("🔑 Login sebagai:");
        $this->command->info("   - Admin: admin / password123");
        $this->command->info("   - Guru: (NIP) / password123");
        $this->command->info("   - Siswa: (NISN) / password123");
    }

    /**
     * Hitung jumlah bentrok di jadwal
     */
    private function hitungBentrok(array $entries): array
    {
        $guruBentrok = 0;
        $kelasBentrok = 0;

        $guruSlots = [];
        $kelasSlots = [];

        // Load guru_mapel untuk dapet guru_id dan kelas_id
        $guruMapels = GuruMapel::with(['guru', 'kelas'])->get()->keyBy('id');

        foreach ($entries as $entry) {
            $gm = $guruMapels[$entry['guru_mapel_id']] ?? null;
            if (!$gm)
                continue;

            $guruId = $gm->guru_id;
            $kelasId = $gm->kelas_id;
            $key = $entry['hari'] . '_' . $entry['jam_pelajaran_id'];

            // Cek bentrok guru
            if (isset($guruSlots[$guruId][$key])) {
                $guruBentrok++;
            }
            $guruSlots[$guruId][$key] = true;

            // Cek bentrok kelas
            if (isset($kelasSlots[$kelasId][$key])) {
                $kelasBentrok++;
            }
            $kelasSlots[$kelasId][$key] = true;
        }

        return [
            'guru' => $guruBentrok,
            'kelas' => $kelasBentrok,
        ];
    }
}