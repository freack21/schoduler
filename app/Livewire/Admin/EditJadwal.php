<?php

namespace App\Livewire\Admin;

use App\Models\Jadwal;
use App\Models\Kelas;
use App\Models\JamPelajaran;
use App\Models\Kurikulum;
use App\Models\GuruMapel;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\On;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Reassemble Jadwal')]
class EditJadwal extends Component
{
    public $kelas_id;
    public $kelasList = [];
    
    public $selectedJadwalId = null;
    public $selectedHari = null;
    public $selectedJamPelajaranId = null;

    // Modal Insert Manual State
    public bool $showInsertModal = false;
    public $insertHari = null;
    public $insertJamId = null;
    public $insertMapelId = null;
    public $insertGuruId = null;
    
    public array $availableMapel = [];
    public array $availableGurus = [];

    public function mount()
    {
        $this->kelasList = Kelas::orderBy('tingkat_id')->orderBy('nama')->get();
        if ($this->kelasList->isNotEmpty()) {
            $this->kelas_id = $this->kelasList->first()->id;
        }
    }

    public function updatedKelasId()
    {
        $this->resetSelection();
    }

    public function resetSelection()
    {
        $this->selectedJadwalId = null;
        $this->selectedHari = null;
        $this->selectedJamPelajaranId = null;
    }

    public function selectSlot($hari, $jamPelajaranId, $jadwalId = null)
    {
        if ($this->selectedHari === null && $this->selectedJamPelajaranId === null) {
            // First click: select the slot
            if ($jadwalId) {
                // Select a block to move
                $this->selectedJadwalId = $jadwalId;
                $this->selectedHari = $hari;
                $this->selectedJamPelajaranId = $jamPelajaranId;
            } else {
                // If clicking an empty slot without selecting anything first, open Insert Manual modal
                $this->openInsertModal($hari, $jamPelajaranId);
            }
        } else {
            // Second click: determine destination
            $sourceJadwalId = $this->selectedJadwalId;
            $destJadwalId = $jadwalId;
            $destHari = $hari;
            $destJamId = $jamPelajaranId;
            
            if ($sourceJadwalId === $destJadwalId) {
                // Cancel selection
                $this->resetSelection();
                return;
            }

            // Attempt to move or swap
            $this->moveOrSwap($sourceJadwalId, $destHari, $destJamId, $destJadwalId);
            $this->resetSelection();
        }
    }

    private function moveOrSwap($sourceJadwalId, $destHari, $destJamId, $destJadwalId = null)
    {
        $source = Jadwal::find($sourceJadwalId);
        if (!$source) return;

        // Check Guru Availability for Destination Slot
        $activeTahunAjaran = \App\Models\Pengaturan::activeTahunAjaran();
        $conflictDest = Jadwal::where('guru_id', $source->guru_id)
            ->where('tahun_ajaran', $activeTahunAjaran)
            ->where('hari', $destHari)
            ->where('jam_pelajaran_id', $destJamId)
            ->where('id', '!=', $source->id)
            ->first();

        if ($conflictDest) {
            $this->dispatch('toast', type: 'error', message: 'Gagal! Guru '.$source->guru->user->nama_lengkap.' sedang mengajar di kelas '.$conflictDest->kelas->nama.' pada jam tersebut.');
            return;
        }

        if ($destJadwalId) {
            // It's a SWAP
            $dest = Jadwal::find($destJadwalId);
            if (!$dest) return;

            // Check Guru Availability for Source Slot
            $conflictSource = Jadwal::where('guru_id', $dest->guru_id)
                ->where('tahun_ajaran', $activeTahunAjaran)
                ->where('hari', $source->hari)
                ->where('jam_pelajaran_id', $source->jam_pelajaran_id)
                ->where('id', '!=', $dest->id)
                ->first();

            if ($conflictSource) {
                $this->dispatch('toast', type: 'error', message: 'Gagal Swap! Guru '.$dest->guru->user->nama_lengkap.' sedang mengajar di kelas '.$conflictSource->kelas->nama.' pada jam asal.');
                return;
            }

            // Perform Swap
            $tempHari = $source->hari;
            $tempJamId = $source->jam_pelajaran_id;

            $source->hari = $dest->hari;
            $source->jam_pelajaran_id = $dest->jam_pelajaran_id;
            $source->save();

            $dest->hari = $tempHari;
            $dest->jam_pelajaran_id = $tempJamId;
            $dest->save();

            $this->dispatch('toast', type: 'success', message: 'Jadwal berhasil ditukar!');
        } else {
            // It's a MOVE
            $source->hari = $destHari;
            $source->jam_pelajaran_id = $destJamId;
            $source->save();

            $this->dispatch('toast', type: 'success', message: 'Jadwal berhasil dipindah!');
        }
    }

    public function openInsertModal($hari, $jamId)
    {
        $this->insertHari = $hari;
        $this->insertJamId = $jamId;
        $this->insertMapelId = null;
        $this->insertGuruId = null;
        $this->availableMapel = [];
        $this->availableGurus = [];

        // Fetch curriculum stats to only show missing mapels
        $stats = $this->getCurriculumStats();
        $missing = [];
        foreach ($stats as $stat) {
            if ($stat['missing'] > 0) {
                $missing[] = [
                    'id' => $stat['mapel_id'],
                    'nama' => $stat['nama'] . ' (Sisa: ' . $stat['missing'] . ' jam)',
                ];
            }
        }
        $this->availableMapel = $missing;

        if (count($missing) > 0) {
            $this->showInsertModal = true;
        } else {
            $this->dispatch('toast', type: 'warning', message: 'Seluruh mapel kurikulum kelas ini sudah terpenuhi.');
        }
    }

    public function updatedInsertMapelId($mapelId)
    {
        $this->insertGuruId = null;
        if (!$mapelId) {
            $this->availableGurus = [];
            return;
        }

        $kelas = Kelas::find($this->kelas_id);
        if (!$kelas) return;

        // Get eligible teachers based on GuruMapel
        $gurus = GuruMapel::with('guru.user')
            ->where('mapel_id', $mapelId)
            ->where('tingkat_id', $kelas->tingkat_id)
            ->where(function($query) use ($kelas) {
                $query->whereNull('jurusan_id')
                      ->orWhere('jurusan_id', $kelas->jurusan_id);
            })
            ->get()
            ->map(function ($gm) {
                return [
                    'id' => $gm->guru_id,
                    'nama' => $gm->guru->user->nama_lengkap
                ];
            })->toArray();

        $this->availableGurus = $gurus;
    }

    public function insertManual()
    {
        $this->validate([
            'insertMapelId' => 'required|integer',
            'insertGuruId' => 'required|string',
        ], [
            'insertMapelId.required' => 'Pilih mapel terlebih dahulu.',
            'insertGuruId.required' => 'Pilih guru pengampu terlebih dahulu.',
        ]);

        // Check Guru Availability
        $activeTahunAjaran = \App\Models\Pengaturan::activeTahunAjaran();
        $conflict = Jadwal::where('guru_id', $this->insertGuruId)
            ->where('tahun_ajaran', $activeTahunAjaran)
            ->where('hari', $this->insertHari)
            ->where('jam_pelajaran_id', $this->insertJamId)
            ->first();

        if ($conflict) {
            $this->dispatch('toast', type: 'error', message: 'Gagal! Guru tersebut sedang mengajar di kelas '.$conflict->kelas->nama.' pada jam ini.');
            return;
        }

        Jadwal::create([
            'guru_id' => $this->insertGuruId,
            'mapel_id' => $this->insertMapelId,
            'kelas_id' => $this->kelas_id,
            'hari' => $this->insertHari,
            'jam_pelajaran_id' => $this->insertJamId,
            'tahun_ajaran' => $activeTahunAjaran,
        ]);

        $this->showInsertModal = false;
        $this->dispatch('toast', type: 'success', message: 'Mapel berhasil ditambahkan ke jadwal!');
    }

    public function confirmDeleteBlock(int $jadwalId)
    {
        $this->dispatch('swal-confirm', 
            title: 'Hapus Blok Jadwal?',
            text: 'Apakah Anda yakin ingin menghapus mapel ini dari jadwal?',
            confirmText: 'Ya, Hapus!',
            method: 'doDeleteBlock',
            payload: ['id' => $jadwalId]
        );
    }

    #[On('doDeleteBlock')]
    public function doDeleteBlock(int $id)
    {
        Jadwal::where('id', $id)->delete();
        $this->dispatch('toast', type: 'success', message: 'Blok jadwal berhasil dihapus!');
    }

    public function confirmClearClass()
    {
        $this->dispatch('swal-confirm', 
            title: 'Kosongkan Jadwal Kelas?',
            text: 'Semua jadwal di kelas ini akan dihapus permanen. Lanjutkan?',
            confirmText: 'Ya, Kosongkan!',
            method: 'doClearClass',
            payload: []
        );
    }

    #[On('doClearClass')]
    public function doClearClass()
    {
        $activeTahunAjaran = \App\Models\Pengaturan::activeTahunAjaran();
        Jadwal::where('kelas_id', $this->kelas_id)
            ->where('tahun_ajaran', $activeTahunAjaran)
            ->delete();
        $this->dispatch('toast', type: 'success', message: 'Semua jadwal kelas berhasil dikosongkan!');
    }

    private function getCurriculumStats(): array
    {
        $kelas = Kelas::find($this->kelas_id);
        if (!$kelas) return [];

        $kurikulumList = Kurikulum::with('mapel')
            ->where('tingkat_id', $kelas->tingkat_id)
            ->where(function($query) use ($kelas) {
                $query->whereNull('jurusan_id')
                      ->orWhere('jurusan_id', $kelas->jurusan_id);
            })
            ->get();

        $activeTahunAjaran = \App\Models\Pengaturan::activeTahunAjaran();
        $jadwalGroup = Jadwal::where('kelas_id', $this->kelas_id)
            ->where('tahun_ajaran', $activeTahunAjaran)
            ->selectRaw('mapel_id, COUNT(*) as total_jam')
            ->groupBy('mapel_id')
            ->pluck('total_jam', 'mapel_id')
            ->toArray();

        $stats = [];
        foreach ($kurikulumList as $kuri) {
            $required = $kuri->mapel->jam_per_minggu;
            $filled = $jadwalGroup[$kuri->mapel_id] ?? 0;
            $missing = max(0, $required - $filled);
            
            $stats[] = [
                'mapel_id' => $kuri->mapel_id,
                'kode' => $kuri->mapel->kode,
                'nama' => $kuri->mapel->nama,
                'required' => $required,
                'filled' => $filled,
                'missing' => $missing,
                'is_complete' => $filled >= $required,
            ];
        }

        // Sort by incomplete first
        usort($stats, function($a, $b) {
            return $a['is_complete'] <=> $b['is_complete'];
        });

        return $stats;
    }

    public function render()
    {
        $activeTahunAjaran = \App\Models\Pengaturan::activeTahunAjaran();
        $jadwalRaw = Jadwal::with(['mapel', 'guru.user'])
            ->where('kelas_id', $this->kelas_id)
            ->where('tahun_ajaran', $activeTahunAjaran)
            ->get();

        $matrix = [];
        foreach ($jadwalRaw as $j) {
            $matrix[$j->jam_pelajaran_id][$j->hari] = $j;
        }

        $hariAktif = explode(',', \App\Models\Pengaturan::where('key', 'hari_aktif')->value('value') ?? 'Senin,Selasa,Rabu,Kamis,Jumat');
        $jamList = JamPelajaran::orderBy('jam_mulai')->get();

        $rowMap = [];
        $maxJam = 0;
        foreach ($jamList as $j) {
            if ($j->jam_ke > $maxJam) $maxJam = $j->jam_ke;
            $rowMap[$j->jam_ke][$j->hari] = $j;
        }

        return view('livewire.admin.edit-jadwal', [
            'matrix' => $matrix,
            'hariAktif' => $hariAktif,
            'rowMap' => $rowMap,
            'maxJam' => $maxJam,
            'curriculumStats' => $this->getCurriculumStats(),
        ]);
    }
}
