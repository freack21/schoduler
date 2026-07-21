<?php

namespace App\Livewire\Admin;

use App\Models\JamPelajaran;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Jam Pelajaran')]
class DataJamPelajaran extends Component
{
    public string $hariFilter = 'Senin';
    public array $hariList = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    
    // Setting global jam mulai per hari
    public string $jamMulaiHari = '07:00';

    public function mount()
    {
        $this->loadJamMulai();
    }

    public function updatedHariFilter()
    {
        $this->loadJamMulai();
    }

    public function loadJamMulai()
    {
        $firstJam = JamPelajaran::where('hari', $this->hariFilter)->orderBy('jam_mulai')->first();
        if ($firstJam) {
            $this->jamMulaiHari = substr($firstJam->jam_mulai, 0, 5);
        } else {
            $this->jamMulaiHari = '07:00';
        }
    }

    public function updateWaktu()
    {
        $this->validate(['jamMulaiHari' => 'required|date_format:H:i']);
        $this->recalculateTimes($this->hariFilter, $this->jamMulaiHari);
        $this->dispatch('toast', type: 'success', message: 'Waktu mulai diperbarui.');
    }

    public function addBlock(bool $isIstirahat)
    {
        $jams = JamPelajaran::where('hari', $this->hariFilter)->orderBy('jam_mulai')->get();
        $nextJamKe = $jams->max('jam_ke') + 1;
        
        $durasi = $isIstirahat ? 15 : 45;
        
        JamPelajaran::create([
            'hari' => $this->hariFilter,
            'jam_ke' => $nextJamKe,
            'is_istirahat' => $isIstirahat,
            'durasi_menit' => $durasi,
            'jam_mulai' => '00:00:00',
            'jam_selesai' => '00:00:00',
        ]);
        
        $this->recalculateTimes($this->hariFilter, $this->jamMulaiHari);
    }

    public function removeBlock(int $id)
    {
        $jam = JamPelajaran::find($id);
        if (!$jam) return;

        $jam->delete();
        $jams = JamPelajaran::where('hari', $this->hariFilter)->orderBy('jam_mulai')->get();
        foreach ($jams as $index => $j) {
            $j->update(['jam_ke' => $index + 1]);
        }
        $this->recalculateTimes($this->hariFilter, $this->jamMulaiHari);
    }
    
    public function updateDuration(int $id, int $durasi)
    {
        if ($durasi < 5) return;
        JamPelajaran::findOrFail($id)->update(['durasi_menit' => $durasi]);
        $this->recalculateTimes($this->hariFilter, $this->jamMulaiHari);
    }

    public function updateNamaKegiatan(int $id, string $namaKegiatan)
    {
        JamPelajaran::findOrFail($id)->update(['nama_kegiatan' => empty(trim($namaKegiatan)) ? null : trim($namaKegiatan)]);
    }

    public function moveBlock(int $id, string $direction)
    {
        $jam = JamPelajaran::findOrFail($id);
        $swapJam = null;

        if ($direction === 'up') {
            $swapJam = JamPelajaran::where('hari', $this->hariFilter)->where('jam_ke', '<', $jam->jam_ke)->orderBy('jam_mulai', 'desc')->first();
        } else {
            $swapJam = JamPelajaran::where('hari', $this->hariFilter)->where('jam_ke', '>', $jam->jam_ke)->orderBy('jam_mulai', 'asc')->first();
        }

        if ($swapJam) {
            $temp = $jam->jam_ke;
            $jam->update(['jam_ke' => $swapJam->jam_ke]);
            $swapJam->update(['jam_ke' => $temp]);
            $this->recalculateTimes($this->hariFilter, $this->jamMulaiHari);
        }
    }

    public function copyTo(string $targetHari)
    {
        if ($this->hariFilter === $targetHari) return;

        JamPelajaran::where('hari', $targetHari)->delete();

        $sourceJams = JamPelajaran::where('hari', $this->hariFilter)->orderBy('jam_mulai')->get();
        foreach ($sourceJams as $jam) {
            JamPelajaran::create([
                'hari' => $targetHari,
                'jam_ke' => $jam->jam_ke,
                'jam_mulai' => $jam->jam_mulai,
                'jam_selesai' => $jam->jam_selesai,
                'is_istirahat' => $jam->is_istirahat,
                'durasi_menit' => $jam->durasi_menit,
                'nama_kegiatan' => $jam->nama_kegiatan,
            ]);
        }

        $this->dispatch('toast', type: 'success', message: "Berhasil disalin ke $targetHari");
    }

    private function recalculateTimes(string $hari, string $startAt)
    {
        $jams = JamPelajaran::where('hari', $hari)->orderBy('jam_mulai')->get();
        if ($jams->isEmpty()) return;

        $currentTime = Carbon::createFromFormat('H:i', $startAt);

        foreach ($jams as $jam) {
            $mulai = $currentTime->format('H:i:s');
            $currentTime->addMinutes($jam->durasi_menit);
            $selesai = $currentTime->format('H:i:s');
            
            $jam->update([
                'jam_mulai' => $mulai,
                'jam_selesai' => $selesai,
            ]);
        }
    }

    public function render()
    {
        return view('livewire.admin.data-jam-pelajaran', [
            'jamList' => JamPelajaran::where('hari', $this->hariFilter)->orderBy('jam_mulai')->get(),
        ]);
    }
}
