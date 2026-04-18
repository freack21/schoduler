<?php

namespace Database\Seeders;

use App\Models\Guru;
use App\Models\GuruMapel;
use App\Models\JamPelajaran;
use App\Models\Kelas;
use App\Models\Mapel;
use App\Models\Pengaturan;
use App\Models\Siswa;
use App\Models\Tingkat;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ─── Admin ───
        User::create([
            'id' => 'admin',
            'nama_lengkap' => 'Administrator',
            'password' => 'password123',
            'role' => 'admin',
        ]);

        // ─── Tingkat ───
        $tingkatX = Tingkat::create(['nama' => 'X', 'kode' => '10']);
        $tingkatXI = Tingkat::create(['nama' => 'XI', 'kode' => '11']);
        $tingkatXII = Tingkat::create(['nama' => 'XII', 'kode' => '12']);

        // ─── Kelas ───
        $kelasData = [
            ['nama' => 'X-1', 'tingkat_id' => $tingkatX->id],
            ['nama' => 'X-2', 'tingkat_id' => $tingkatX->id],
            ['nama' => 'XI-1', 'tingkat_id' => $tingkatXI->id],
            ['nama' => 'XI-2', 'tingkat_id' => $tingkatXI->id],
            ['nama' => 'XII-1', 'tingkat_id' => $tingkatXII->id],
            ['nama' => 'XII-2', 'tingkat_id' => $tingkatXII->id],
        ];
        foreach ($kelasData as $k) {
            Kelas::create($k);
        }
        $allKelas = Kelas::all();

        // ─── Mapel ───
        $mapelData = [
            ['kode' => 'MTK', 'nama' => 'Matematika', 'jam_per_minggu' => 4, 'max_jam_per_hari' => 2],
            ['kode' => 'FIS', 'nama' => 'Fisika', 'jam_per_minggu' => 3, 'max_jam_per_hari' => 2],
            ['kode' => 'KIM', 'nama' => 'Kimia', 'jam_per_minggu' => 3, 'max_jam_per_hari' => 2],
            ['kode' => 'BIO', 'nama' => 'Biologi', 'jam_per_minggu' => 3, 'max_jam_per_hari' => 2],
            ['kode' => 'BIN', 'nama' => 'Bahasa Indonesia', 'jam_per_minggu' => 4, 'max_jam_per_hari' => 2],
            ['kode' => 'BIG', 'nama' => 'Bahasa Inggris', 'jam_per_minggu' => 3, 'max_jam_per_hari' => 2],
            ['kode' => 'SEJ', 'nama' => 'Sejarah', 'jam_per_minggu' => 2, 'max_jam_per_hari' => 2],
            ['kode' => 'PKN', 'nama' => 'PKN', 'jam_per_minggu' => 2, 'max_jam_per_hari' => 2],
            ['kode' => 'PJK', 'nama' => 'Penjaskes', 'jam_per_minggu' => 2, 'max_jam_per_hari' => 2],
            ['kode' => 'SBD', 'nama' => 'Seni Budaya', 'jam_per_minggu' => 2, 'max_jam_per_hari' => 2],
        ];
        foreach ($mapelData as $m) {
            Mapel::create($m);
        }
        $allMapel = Mapel::all();

        // ─── Jam Pelajaran (8 jam + 2 istirahat) ───
        $jamData = [
            ['jam_ke' => 1, 'jam_mulai' => '07:00', 'jam_selesai' => '07:45', 'is_istirahat' => false],
            ['jam_ke' => 2, 'jam_mulai' => '07:45', 'jam_selesai' => '08:30', 'is_istirahat' => false],
            ['jam_ke' => 3, 'jam_mulai' => '08:30', 'jam_selesai' => '09:15', 'is_istirahat' => false],
            ['jam_ke' => 4, 'jam_mulai' => '09:15', 'jam_selesai' => '09:30', 'is_istirahat' => true],  // Istirahat 1
            ['jam_ke' => 5, 'jam_mulai' => '09:30', 'jam_selesai' => '10:15', 'is_istirahat' => false],
            ['jam_ke' => 6, 'jam_mulai' => '10:15', 'jam_selesai' => '11:00', 'is_istirahat' => false],
            ['jam_ke' => 7, 'jam_mulai' => '11:00', 'jam_selesai' => '11:45', 'is_istirahat' => false],
            ['jam_ke' => 8, 'jam_mulai' => '11:45', 'jam_selesai' => '12:15', 'is_istirahat' => true],  // Istirahat 2
            ['jam_ke' => 9, 'jam_mulai' => '12:15', 'jam_selesai' => '13:00', 'is_istirahat' => false],
            ['jam_ke' => 10, 'jam_mulai' => '13:00', 'jam_selesai' => '13:45', 'is_istirahat' => false],
        ];
        foreach ($jamData as $j) {
            JamPelajaran::create($j);
        }

        // ─── Guru (5 guru) ───
        $guruData = [
            ['nip' => '198501012010011001', 'nama' => 'Drs. Ahmad Fauzi, M.Pd'],
            ['nip' => '198602022011012002', 'nama' => 'Sri Wahyuni, S.Pd'],
            ['nip' => '198703032012011003', 'nama' => 'Budi Santoso, S.Pd'],
            ['nip' => '198804042013012004', 'nama' => 'Rina Marlina, S.Pd'],
            ['nip' => '198905052014011005', 'nama' => 'Hendri Kurniawan, S.Pd'],
        ];

        $guruModels = [];
        foreach ($guruData as $g) {
            $user = User::create([
                'id' => $g['nip'],
                'nama_lengkap' => $g['nama'],
                'password' => 'password123',
                'role' => 'guru',
            ]);
            $guruModels[] = Guru::create(['user_id' => $user->id]);
        }

        // ─── Assign Guru → Mapel → Kelas ───
        // Guru 1 (Ahmad Fauzi): Matematika → semua kelas
        foreach ($allKelas as $kelas) {
            GuruMapel::create([
                'guru_id' => $guruModels[0]->id,
                'mapel_id' => $allMapel->where('kode', 'MTK')->first()->id,
                'kelas_id' => $kelas->id,
            ]);
        }

        // Guru 2 (Sri Wahyuni): Fisika & Kimia → X-1, X-2, XI-1
        foreach ($allKelas->whereIn('nama', ['X-1', 'X-2', 'XI-1']) as $kelas) {
            GuruMapel::create([
                'guru_id' => $guruModels[1]->id,
                'mapel_id' => $allMapel->where('kode', 'FIS')->first()->id,
                'kelas_id' => $kelas->id,
            ]);
        }
        foreach ($allKelas->whereIn('nama', ['XI-2', 'XII-1', 'XII-2']) as $kelas) {
            GuruMapel::create([
                'guru_id' => $guruModels[1]->id,
                'mapel_id' => $allMapel->where('kode', 'KIM')->first()->id,
                'kelas_id' => $kelas->id,
            ]);
        }

        // Guru 3 (Budi Santoso): B.Indonesia → semua kelas
        foreach ($allKelas as $kelas) {
            GuruMapel::create([
                'guru_id' => $guruModels[2]->id,
                'mapel_id' => $allMapel->where('kode', 'BIN')->first()->id,
                'kelas_id' => $kelas->id,
            ]);
        }

        // Guru 4 (Rina Marlina): B.Inggris & Seni Budaya
        foreach ($allKelas as $kelas) {
            GuruMapel::create([
                'guru_id' => $guruModels[3]->id,
                'mapel_id' => $allMapel->where('kode', 'BIG')->first()->id,
                'kelas_id' => $kelas->id,
            ]);
        }

        // Guru 5 (Hendri): Sejarah, PKN, Penjaskes
        foreach ($allKelas as $kelas) {
            GuruMapel::create([
                'guru_id' => $guruModels[4]->id,
                'mapel_id' => $allMapel->where('kode', 'SEJ')->first()->id,
                'kelas_id' => $kelas->id,
            ]);
            GuruMapel::create([
                'guru_id' => $guruModels[4]->id,
                'mapel_id' => $allMapel->where('kode', 'PKN')->first()->id,
                'kelas_id' => $kelas->id,
            ]);
        }

        // ─── Siswa (20 siswa) ───
        $namasSiswa = [
            'Andi Pratama', 'Budi Setiawan', 'Citra Dewi', 'Dian Safitri',
            'Eko Purnomo', 'Fitri Handayani', 'Gilang Ramadhan', 'Hana Permata',
            'Irfan Hakim', 'Jasmine Putri', 'Kevin Anggara', 'Lina Marlina',
            'Muhammad Rizki', 'Nadia Sari', 'Oscar Pratama', 'Putri Wulandari',
            'Rizky Aditya', 'Siti Nurhaliza', 'Taufik Hidayat', 'Umi Kalsum',
        ];

        $kelasIds = $allKelas->pluck('id')->toArray();

        foreach ($namasSiswa as $i => $nama) {
            $nisn = '00' . str_pad((string)($i + 1), 8, '0', STR_PAD_LEFT);
            $user = User::create([
                'id' => $nisn,
                'nama_lengkap' => $nama,
                'password' => 'password123',
                'role' => 'siswa',
            ]);
            Siswa::create([
                'user_id' => $user->id,
                'kelas_id' => $kelasIds[$i % count($kelasIds)],
            ]);
        }

        // ─── Pengaturan Default ───
        Pengaturan::setValue('hari_aktif', 'Senin,Selasa,Rabu,Kamis,Jumat', 'Hari aktif penjadwalan');
    }
}
