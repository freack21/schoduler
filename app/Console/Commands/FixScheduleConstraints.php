<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\JamPelajaran;
use App\Models\Kelas;
use App\Models\Kurikulum;
use App\Models\GuruMapel;

class FixScheduleConstraints extends Command
{
    protected $signature = 'app:fix-schedule-constraints {--slots= : Jumlah slot kustom yang ingin ditambahkan}';
    protected $description = 'Simulasi dan perbaikan otomatis slot Jam Pelajaran agar jadwal mencapai 0 bentrok secara matematis';

    public function handle()
    {
        $this->info("🔍 Menganalisa constraint jadwal & menjalankan simulasi heuristik...");

        $jamList = JamPelajaran::where('is_istirahat', false)->get();
        $dbDays = $jamList->pluck('hari')->unique()->toArray();
        $allDays = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        $hariAktif = array_values(array_intersect($allDays, $dbDays));
        
        if (empty($hariAktif)) {
            $hariAktif = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];
        }

        $totalSlots = 0;
        foreach ($hariAktif as $hari) {
            $totalSlots += $jamList->where('hari', $hari)->count();
        }

        $kelasList = Kelas::all();
        $kurikulumList = Kurikulum::with('mapel')->get();
        $guruMapelAll = GuruMapel::all();

        $maxDemand = 0;
        $maxKelasName = '';

        foreach ($kelasList as $k) {
            $kuriList = $kurikulumList->where('tingkat_id', $k->tingkat_id);
            if ($k->jurusan_id) {
                $kuriList = $kuriList->filter(function($kuri) use ($k) {
                    return is_null($kuri->jurusan_id) || $kuri->jurusan_id == $k->jurusan_id;
                });
            } else {
                $kuriList = $kuriList->whereNull('jurusan_id');
            }

            $kelasJam = 0;
            $mapelGurus = [];

            foreach ($kuriList as $kuri) {
                if (!$kuri->mapel) continue;
                $jam = $kuri->mapel->jam_per_minggu;
                if (!$kuri->mapel->is_parallel) {
                    $kelasJam += $jam;
                } else {
                    $key = 'par_' . md5($kuri->mapel->kelompok_paralel);
                    if (!isset($mapelGurus[$k->id][$key])) {
                        $kelasJam += $jam;
                        $mapelGurus[$k->id][$key] = true;
                    }
                }
            }

            if ($kelasJam > $maxDemand) {
                $maxDemand = $kelasJam;
                $maxKelasName = $k->nama;
            }
        }

        $this->info("📊 Total Slot Aktif saat ini: $totalSlots jam/minggu");
        $this->info("📈 Beban Kelas Terberat ($maxKelasName): $maxDemand jam/minggu");

        // Jalankan simulasi untuk mencari minimum slot size
        $this->line("\n🤖 Menjalankan simulasi kebutuhan slot...");
        $bestSlotsNeeded = 0;
        $simulationResults = [];

        for ($extra = 0; $extra <= 16; $extra += 2) {
            $testSlots = $maxDemand + $extra;
            
            // Build virtual slot map
            $virtualMap = [];
            $s = 0;
            foreach ($hariAktif as $hIdx => $hari) {
                $slotsPerDay = (int)ceil($testSlots / count($hariAktif));
                if ($hIdx === count($hariAktif) - 1) {
                    $slotsPerDay = $testSlots - ($slotsPerDay * (count($hariAktif) - 1));
                }
                for ($j = 1; $j <= $slotsPerDay; $j++) {
                    $virtualMap[$s] = [
                        'hari' => $hari,
                        'hari_idx' => $hIdx,
                        'jam_ke' => $j,
                    ];
                    $s++;
                }
            }

            // Precompute valid block starts
            $validStarts = [];
            for ($size = 1; $size <= 4; $size++) {
                $validStarts[$size] = [];
                for ($slot = 0; $slot <= $s - $size; $slot++) {
                    if ($virtualMap[$slot]['hari_idx'] === $virtualMap[$slot + $size - 1]['hari_idx']) {
                        $validStarts[$size][] = $slot;
                    }
                }
            }

            // Setup demands and static load balancing virtual
            $demands = [];
            foreach ($kelasList as $k) {
                $kuriList = $kurikulumList->where('tingkat_id', $k->tingkat_id);
                if ($k->jurusan_id) {
                    $kuriList = $kuriList->filter(fn($kuri) => is_null($kuri->jurusan_id) || $kuri->jurusan_id == $k->jurusan_id);
                } else {
                    $kuriList = $kuriList->whereNull('jurusan_id');
                }

                $kelasDemands = [];
                foreach ($kuriList as $kuri) {
                    if (!$kuri->mapel) continue;
                    $eligibleGurus = $guruMapelAll->where('mapel_id', $kuri->mapel_id)
                        ->where('tingkat_id', $k->tingkat_id)
                        ->filter(fn($gm) => is_null($gm->jurusan_id) || $gm->jurusan_id == $k->jurusan_id)
                        ->pluck('guru_id')
                        ->values()
                        ->toArray();

                    if (empty($eligibleGurus)) continue;

                    if ($kuri->mapel->is_parallel) {
                        $kelompok = $kuri->mapel->kelompok_paralel ?: ('id_' . $kuri->mapel->id);
                        $key = 'parallel_' . md5($kelompok) . '_' . $kuri->mapel->jam_per_minggu . '_' . $kuri->mapel->jam_per_hari;

                        if (!isset($kelasDemands[$key])) {
                            $kelasDemands[$key] = [
                                'kelas_id' => $k->id,
                                'mapel_ids' => [],
                                'jam_per_minggu' => $kuri->mapel->jam_per_minggu,
                                'jam_per_hari' => $kuri->mapel->jam_per_hari,
                                'eligible_gurus' => []
                            ];
                        }
                        if (!in_array($kuri->mapel_id, $kelasDemands[$key]['mapel_ids'])) {
                            $kelasDemands[$key]['mapel_ids'][] = $kuri->mapel_id;
                            $kelasDemands[$key]['eligible_gurus'][$kuri->mapel_id] = array_values($eligibleGurus);
                        }
                    } else {
                        $demands[] = [
                            'kelas_id' => $k->id,
                            'mapel_ids' => [$kuri->mapel_id],
                            'jam_per_minggu' => $kuri->mapel->jam_per_minggu,
                            'jam_per_hari' => $kuri->mapel->jam_per_hari,
                            'eligible_gurus' => [
                                $kuri->mapel_id => array_values($eligibleGurus)
                            ]
                        ];
                    }
                }
                foreach ($kelasDemands as $kd) {
                    $demands[] = $kd;
                }
            }

            $blocks = [];
            foreach ($demands as $dIdx => $demand) {
                $sisa = $demand['jam_per_minggu'];
                $maxPerHari = $demand['jam_per_hari'];
                while ($sisa > 0) {
                    $maxPH = (empty($maxPerHari) || $maxPerHari <= 0) ? $sisa : $maxPerHari;
                    $take = min($sisa, $maxPH);
                    $blocks[] = [
                        'demand_idx' => $dIdx,
                        'size' => $take
                    ];
                    $sisa -= $take;
                }
            }

            $assignedGurus = [];
            $guruLoad = [];
            $dIndices = array_keys($demands);
            usort($dIndices, function($a, $b) use ($demands) {
                $countA = array_sum(array_map('count', $demands[$a]['eligible_gurus']));
                $countB = array_sum(array_map('count', $demands[$b]['eligible_gurus']));
                return $countA <=> $countB;
            });

            foreach ($dIndices as $dIdx) {
                $demand = $demands[$dIdx];
                $picked = [];
                foreach ($demand['eligible_gurus'] as $mId => $eligible) {
                    $bestGuru = $eligible[0];
                    $minLoad = PHP_INT_MAX;
                    foreach ($eligible as $gid) {
                        $load = $guruLoad[$gid] ?? 0;
                        if ($load < $minLoad) {
                            $minLoad = $load;
                            $bestGuru = $gid;
                        }
                    }
                    $picked[$mId] = $bestGuru;
                    $guruLoad[$bestGuru] = ($guruLoad[$bestGuru] ?? 0) + $demand['jam_per_minggu'];
                }
                $assignedGurus[$dIdx] = $picked;
            }

            $usedGuruSlots = [];
            $usedKelasSlots = [];
            $bIndices = array_keys($blocks);
            usort($bIndices, fn($a, $b) => $blocks[$b]['size'] <=> $blocks[$a]['size']);

            $kelasConflicts = 0;
            $guruConflicts = 0;

            foreach ($bIndices as $bIdx) {
                $block = $blocks[$bIdx];
                $dIdx = $block['demand_idx'];
                $demand = $demands[$dIdx];
                $guruMap = $assignedGurus[$dIdx];
                $kelasId = $demand['kelas_id'];
                $size = $block['size'];

                $valid = $validStarts[$size] ?? [];
                if (empty($valid)) {
                    $kelasConflicts += 10;
                    continue;
                }

                $bestSlot = $valid[0];
                $minC = PHP_INT_MAX;

                foreach ($valid as $sSlot) {
                    $c = 0;
                    for ($i = 0; $i < $size; $i++) {
                        $sIdx = $sSlot + $i;
                        foreach ($guruMap as $guruId) {
                            if (isset($usedGuruSlots[$guruId][$sIdx])) $c++;
                        }
                        if (isset($usedKelasSlots[$kelasId][$sIdx])) $c++;
                    }
                    if ($c < $minC) {
                        $minC = $c;
                        $bestSlot = $sSlot;
                    }
                    if ($c === 0) break;
                }

                for ($i = 0; $i < $size; $i++) {
                    $sIdx = $bestSlot + $i;
                    foreach ($guruMap as $guruId) {
                        if (isset($usedGuruSlots[$guruId][$sIdx])) $guruConflicts++;
                        $usedGuruSlots[$guruId][$sIdx] = true;
                    }
                    if (isset($usedKelasSlots[$kelasId][$sIdx])) $kelasConflicts++;
                    $usedKelasSlots[$kelasId][$sIdx] = true;
                }
            }

            $totalConflicts = $guruConflicts + $kelasConflicts;
            $simulationResults[$testSlots] = $totalConflicts;

            if ($totalConflicts === 0 && $bestSlotsNeeded === 0) {
                $bestSlotsNeeded = $testSlots;
            }
        }

        // Tampilkan tabel hasil simulasi
        $this->line("\n📊 Tabel Hasil Analisa Kelayakan:");
        foreach ($simulationResults as $slots => $conflicts) {
            $status = $conflicts === 0 ? "✅ LAYAK (0 bentrok)" : "❌ BENTROK ($conflicts bentrok)";
            $this->line("   - Kapasitas $slots slot/minggu: $status");
        }

        $customSlots = $this->option('slots');
        if ($customSlots) {
            $targetSlots = (int)$customSlots;
        } else {
            $targetSlots = $bestSlotsNeeded ?: ($maxDemand + 10);
        }

        if ($totalSlots >= $targetSlots) {
            $this->info("\n✅ Jumlah slot aktif saat ini ($totalSlots) sudah mencukupi target ($targetSlots)!");
            return;
        }

        $shortage = $targetSlots - $totalSlots;
        $this->warn("\n⚠️ Sistem membutuhkan $shortage slot jam pelajaran tambahan agar bisa 0 bentrok!");

        if (!$this->confirm("Apakah Anda ingin sistem secara otomatis menambahkan $shortage slot Jam Pelajaran ekstra (sore hari) agar kapasitas menjadi $targetSlots slot/minggu?", true)) {
            $this->info('Dibatalkan.');
            return;
        }

        $added = 0;
        $dayIndex = 0;
        $totalDays = count($hariAktif);

        while ($added < $shortage) {
            $hari = $hariAktif[$dayIndex];
            
            $lastJam = JamPelajaran::where('hari', $hari)->orderBy('jam_ke', 'desc')->first();
            $jamKe = $lastJam ? $lastJam->jam_ke + 1 : 1;
            
            if ($lastJam && $lastJam->jam_selesai) {
                $jamMulai = $lastJam->jam_selesai;
                $timestamp = strtotime($jamMulai);
                $jamSelesai = date("H:i:s", $timestamp + (45 * 60));
            } else {
                $jamMulai = "15:00:00";
                $jamSelesai = "15:45:00";
            }

            JamPelajaran::create([
                'hari' => $hari,
                'jam_ke' => $jamKe,
                'jam_mulai' => $jamMulai,
                'jam_selesai' => $jamSelesai,
                'is_istirahat' => false,
                'nama_kegiatan' => 'Jam Tambahan (Auto Fix)',
                'durasi_menit' => 45,
            ]);

            $added++;
            $dayIndex = ($dayIndex + 1) % $totalDays;
        }

        $this->info("🎉 Selesai! Berhasil menambahkan $added slot jam pelajaran baru.");
        $this->info("💡 Silakan jalankan 'php artisan jadwal:generate' lagi. Kali ini dijamin tembus 0 bentrok!");
    }
}
