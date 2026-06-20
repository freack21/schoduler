<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Jadwal;
use App\Models\Kelas;
use App\Models\Guru;
use App\Models\Mapel;
use App\Models\JamPelajaran;
use Barryvdh\DomPDF\Facade\Pdf;

class ExportJadwalController extends Controller
{
    public function exportKelas(Request $request)
    {
        $ids = $request->input('ids', []);
        if (empty($ids)) return back()->with('error', 'Pilih minimal 1 kelas.');

        $tahunAjaran = $request->input('tahun_ajaran', \App\Models\Pengaturan::activeTahunAjaran());
        $entities = Kelas::whereIn('id', $ids)->get();
        $jadwalList = Jadwal::with(['mapel', 'guru', 'jamPelajaran'])
            ->whereIn('kelas_id', $ids)
            ->where('tahun_ajaran', $tahunAjaran)
            ->get();
        $jamPelajaran = JamPelajaran::orderBy('jam_ke')->get()->groupBy('hari');

        $jadwalGrouped = [];
        foreach ($jadwalList as $j) {
            $jadwalGrouped[$j->kelas_id][$j->hari][$j->jam_pelajaran_id][] = $j;
        }

        $pdf = Pdf::loadView('exports.jadwal-kelas', [
            'entities' => $entities,
            'jadwalGrouped' => $jadwalGrouped,
            'jamPelajaran' => $jamPelajaran,
            'tahunAjaran' => $tahunAjaran,
        ])->setPaper('a4', 'landscape');

        return $pdf->stream('Jadwal_Kelas.pdf');
    }

    public function exportGuru(Request $request)
    {
        $ids = $request->input('ids', []);
        if (empty($ids)) return back()->with('error', 'Pilih minimal 1 guru.');

        $tahunAjaran = $request->input('tahun_ajaran', \App\Models\Pengaturan::activeTahunAjaran());
        $entities = Guru::whereIn('id', $ids)->get();
        $jadwalList = Jadwal::with(['mapel', 'kelas', 'jamPelajaran'])
            ->whereIn('guru_id', $ids)
            ->where('tahun_ajaran', $tahunAjaran)
            ->get();
        $jamPelajaran = JamPelajaran::orderBy('jam_ke')->get()->groupBy('hari');

        $jadwalGrouped = [];
        foreach ($jadwalList as $j) {
            $jadwalGrouped[$j->guru_id][$j->hari][$j->jam_pelajaran_id][] = $j;
        }

        $pdf = Pdf::loadView('exports.jadwal-guru', [
            'entities' => $entities,
            'jadwalGrouped' => $jadwalGrouped,
            'jamPelajaran' => $jamPelajaran,
            'tahunAjaran' => $tahunAjaran,
        ])->setPaper('a4', 'landscape');

        return $pdf->stream('Jadwal_Guru.pdf');
    }

    public function exportMapel(Request $request)
    {
        $ids = $request->input('ids', []);
        if (empty($ids)) return back()->with('error', 'Pilih minimal 1 mata pelajaran.');

        $tahunAjaran = $request->input('tahun_ajaran', \App\Models\Pengaturan::activeTahunAjaran());
        $entities = Mapel::whereIn('id', $ids)->get();
        $jadwalList = Jadwal::with(['guru', 'kelas', 'jamPelajaran'])
            ->whereIn('mapel_id', $ids)
            ->where('tahun_ajaran', $tahunAjaran)
            ->get();
        $jamPelajaran = JamPelajaran::orderBy('jam_ke')->get()->groupBy('hari');

        $jadwalGrouped = [];
        foreach ($jadwalList as $j) {
            $jadwalGrouped[$j->mapel_id][$j->hari][$j->jam_pelajaran_id][] = $j;
        }

        $pdf = Pdf::loadView('exports.jadwal-mapel', [
            'entities' => $entities,
            'jadwalGrouped' => $jadwalGrouped,
            'jamPelajaran' => $jamPelajaran,
            'tahunAjaran' => $tahunAjaran,
        ])->setPaper('a4', 'landscape');

        return $pdf->stream('Jadwal_Mapel.pdf');
    }

    public function exportKomprehensif(Request $request)
    {
        $ids = $request->input('ids', []);
        if (empty($ids)) return back()->with('error', 'Pilih minimal 1 tingkat.');

        $tahunAjaran = $request->input('tahun_ajaran', \App\Models\Pengaturan::activeTahunAjaran());
        $tingkatList = \App\Models\Tingkat::whereIn('id', $ids)->get();
        $kelasList = Kelas::whereIn('tingkat_id', $ids)->with(['jurusan', 'tingkat'])->orderBy('nama')->get();
        $kelasIds = $kelasList->pluck('id')->toArray();

        $jadwalList = Jadwal::with(['mapel', 'guru', 'jamPelajaran', 'kelas'])
            ->whereIn('kelas_id', $kelasIds)
            ->where('tahun_ajaran', $tahunAjaran)
            ->get();
        
        $jamPelajaran = JamPelajaran::orderBy('jam_ke')->get()->groupBy('hari');

        $jadwalGrouped = [];
        foreach ($jadwalList as $j) {
            $jadwalGrouped[$j->kelas->tingkat_id][$j->hari][$j->jam_pelajaran_id][$j->kelas_id][] = $j;
        }

        $kelasByTingkat = [];
        foreach ($kelasList as $k) {
            $kelasByTingkat[$k->tingkat_id][] = $k;
        }

        $pdf = Pdf::loadView('exports.jadwal-komprehensif', [
            'tingkatList' => $tingkatList,
            'kelasByTingkat' => $kelasByTingkat,
            'jadwalGrouped' => $jadwalGrouped,
            'jamPelajaran' => $jamPelajaran,
            'tahunAjaran' => $tahunAjaran,
        ])->setPaper('folio', 'landscape');

        return $pdf->stream('Jadwal_Komprehensif.pdf');
    }
}
