<?php

namespace App\Livewire\Admin;

use App\Models\Jadwal;
use App\Models\Kelas;
use App\Models\JamPelajaran;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
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
                // You can't select an empty slot to move
                $this->dispatch('toast', type: 'error', message: 'Pilih blok jadwal yang ingin dipindah!');
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
        $conflictDest = Jadwal::where('guru_id', $source->guru_id)
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

    public function render()
    {
        $jadwalRaw = Jadwal::with(['mapel', 'guru.user'])
            ->where('kelas_id', $this->kelas_id)
            ->get();

        $matrix = [];
        foreach ($jadwalRaw as $j) {
            $matrix[$j->jam_pelajaran_id][$j->hari] = $j;
        }

        $hariAktif = explode(',', \App\Models\Pengaturan::where('key', 'hari_aktif')->value('value') ?? 'Senin,Selasa,Rabu,Kamis,Jumat');
        $jamList = JamPelajaran::orderBy('jam_ke')->get();

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
            'maxJam' => $maxJam
        ]);
    }
}
