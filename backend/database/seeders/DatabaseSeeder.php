<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        // ============================================
        // 1. USERS (Roles: admin, dosen, mahasiswa)
        // ============================================
        $users = [
            [
                'name' => 'Admin Utama',
                'email' => 'admin@wdu.ac.id',
                'role' => 'admin',
                'password' => Hash::make('password123'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Dr. Budi Santoso, M.Kom',
                'email' => 'budi@wdu.ac.id',
                'role' => 'dosen',
                'password' => Hash::make('password123'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Dr. Siti Rahayu, M.T.',
                'email' => 'siti@wdu.ac.id',
                'role' => 'dosen',
                'password' => Hash::make('password123'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Dr. Ahmad Fauzi, S.Pd., M.Pd.',
                'email' => 'ahmad@wdu.ac.id',
                'role' => 'dosen',
                'password' => Hash::make('password123'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'I Gusti Ketut Arya, S.T., M.T.',
                'email' => 'arya@wdu.ac.id',
                'role' => 'dosen',
                'password' => Hash::make('password123'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Andi Prasetya',
                'email' => 'mahasiswa1@wdu.ac.id',
                'role' => 'mahasiswa',
                'password' => Hash::make('password123'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Bella Aprilia',
                'email' => 'mahasiswa2@wdu.ac.id',
                'role' => 'mahasiswa',
                'password' => Hash::make('password123'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Cakra Negara',
                'email' => 'mahasiswa3@wdu.ac.id',
                'role' => 'mahasiswa',
                'password' => Hash::make('password123'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Orang Tua Mahasiswa',
                'email' => 'orangtua@wdu.ac.id',
                'role' => 'admin',
                'password' => Hash::make('password123'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('users')->insert($users);

        // ============================================
        // 2. FAKULTAS (Faculties)
        // ============================================
        $fakultas = [
            ['name' => 'Fakultas Ilmu Komputer dan Teknologi Informasi', 'kode' => 'FIT', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Fakultas Ekonomika dan Bisnis', 'kode' => 'FEB', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Fakultas Sastra dan Seni', 'kode' => 'FSS', 'created_at' => now(), 'updated_at' => now()],
        ];
        DB::table('fakultas')->insert($fakultas);

        // ============================================
        // 3. PROGRAM STUDI (Study Programs)
        // ============================================
        $programStudi = [
            ['name' => 'Teknik Informatika', 'kode' => 'TI', 'faculty_id' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Sistem Informasi', 'kode' => 'SI', 'faculty_id' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Ekonomika', 'kode' => 'EKO', 'faculty_id' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Manajemen', 'kode' => 'MAN', 'faculty_id' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Sastra Inggris', 'kode' => 'SI-ING', 'faculty_id' => 3, 'created_at' => now(), 'updated_at' => now()],
        ];
        DB::table('program_studis')->insert($programStudi);

        // ============================================
        // 4. DOSEN (Lecturers)
        // Migration columns: id, user_id, nip, bidang_keahlian, foto, email_kontak, no_hp, gelar_depan, gelar_belakang
        // + final migration adds: full_name, birth_date, birth_place, gender, address, phone, email, education, expertise, faculty_id, study_program_id, position, status
        // ============================================
        $dosens = [
            [
                'user_id' => 2,
                'nip' => '198501012010010001',
                'bidang_keahlian' => 'Machine Learning, Data Mining',
                'full_name' => 'Dr. Budi Santoso, M.Kom',
                'birth_date' => '1985-01-01',
                'birth_place' => 'Jakarta',
                'gender' => 'male',
                'address' => 'Jl. Sudirman No. 10, Jakarta',
                'phone' => '0813-1111-2222',
                'email' => 'budi@wdu.ac.id',
                'education' => 's2',
                'expertise' => 'Machine Learning, Data Mining',
                'faculty_id' => 1,
                'study_program_id' => 1,
                'position' => 'lecturer',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => 3,
                'nip' => '198602022011020002',
                'bidang_keahlian' => 'Database Systems, Cloud Computing',
                'full_name' => 'Dr. Siti Rahayu, M.T.',
                'birth_date' => '1986-02-02',
                'birth_place' => 'Bandung',
                'gender' => 'female',
                'address' => 'Jl. Gatot Subroto No. 20, Bandung',
                'phone' => '0813-3333-4444',
                'email' => 'siti@wdu.ac.id',
                'education' => 's2',
                'expertise' => 'Database Systems, Cloud Computing',
                'faculty_id' => 1,
                'study_program_id' => 1,
                'position' => 'lecturer',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => 4,
                'nip' => '198703032012030003',
                'bidang_keahlian' => 'Software Engineering, Agile Methodology',
                'full_name' => 'Dr. Ahmad Fauzi, S.Pd., M.Pd.',
                'birth_date' => '1987-03-03',
                'birth_place' => 'Yogyakarta',
                'gender' => 'male',
                'address' => 'Jl. Malioboro No. 30, Yogyakarta',
                'phone' => '0813-5555-6666',
                'email' => 'ahmad@wdu.ac.id',
                'education' => 's2',
                'expertise' => 'Software Engineering, Agile Methodology',
                'faculty_id' => 1,
                'study_program_id' => 2,
                'position' => 'lecturer',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => 5,
                'nip' => '198804042013040004',
                'bidang_keahlian' => 'Computer Networks, Cybersecurity',
                'full_name' => 'I Gusti Ketut Arya, S.T., M.T.',
                'birth_date' => '1988-04-04',
                'birth_place' => 'Denpasar',
                'gender' => 'male',
                'address' => 'Jl. Pantai Indah No. 40, Denpasar',
                'phone' => '0813-7777-8888',
                'email' => 'arya@wdu.ac.id',
                'education' => 's2',
                'expertise' => 'Computer Networks, Cybersecurity',
                'faculty_id' => 1,
                'study_program_id' => 1,
                'position' => 'lecturer',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];
        DB::table('dosens')->insert($dosens);

        // ============================================
        // 5. MAHASISWA (Students)
        // Migration columns: id, user_id, nim, jurusan, program_studi, angkatan, semester_aktif, foto, ipk, dosen_wali_id, status
        // + final migration adds: full_name, birth_date, birth_place, gender, address, emergency_contact, emergency_phone, blood_type, religion, ukt_amount, ukt_status, father_name, mother_name, parent_occupation, parent_address, parent_phone, faculty_id, study_program_id
        // ============================================
        $mahasiswas = [
            [
                'user_id' => 6,
                'nim' => '2023010101',
                'jurusan' => 'Teknik Informatika',
                'program_studi' => 'Teknik Informatika',
                'angkatan' => 2023,
                'semester_aktif' => 3,
                'ipk' => 3.75,
                'dosen_wali_id' => 1, // dosen id 1 (Budi)
                'status' => 'aktif',
                'full_name' => 'Andi Prasetya',
                'birth_date' => '2003-05-15',
                'birth_place' => 'Surabaya',
                'gender' => 'male',
                'address' => 'Jl. Alamno. 12, Surabaya',
                'emergency_contact' => 'Ibu Siti',
                'emergency_phone' => '0812-1234-5678',
                'blood_type' => 'A',
                'religion' => 'Islam',
                'ukt_amount' => 3500000,
                'ukt_status' => 'paid',
                'father_name' => 'Bapak Prasetya',
                'mother_name' => 'Ibu Siti',
                'parent_occupation' => 'Pegawai Negeri',
                'parent_address' => 'Jl. Alamno. 12, Surabaya',
                'parent_phone' => '0812-1234-5678',
                'faculty_id' => 1,
                'study_program_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => 7,
                'nim' => '2023010102',
                'jurusan' => 'Sistem Informasi',
                'program_studi' => 'Sistem Informasi',
                'angkatan' => 2023,
                'semester_aktif' => 3,
                'ipk' => 3.85,
                'dosen_wali_id' => 2, // dosen id 2 (Siti)
                'status' => 'aktif',
                'full_name' => 'Bella Aprilia',
                'birth_date' => '2004-03-20',
                'birth_place' => 'Medan',
                'gender' => 'female',
                'address' => 'Jl. Sudirman No. 56, Medan',
                'emergency_contact' => 'Ayah Budi',
                'emergency_phone' => '0813-9876-5432',
                'blood_type' => 'B',
                'religion' => 'Islam',
                'ukt_amount' => 3500000,
                'ukt_status' => 'paid',
                'father_name' => 'Bapak Budi',
                'mother_name' => 'Ibu Sari',
                'parent_occupation' => 'Wira Usaha',
                'parent_address' => 'Jl. Sudirman No. 56, Medan',
                'parent_phone' => '0813-9876-5432',
                'faculty_id' => 1,
                'study_program_id' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => 8,
                'nim' => '2023010103',
                'jurusan' => 'Teknik Informatika',
                'program_studi' => 'Teknik Informatika',
                'angkatan' => 2023,
                'semester_aktif' => 3,
                'ipk' => 3.60,
                'dosen_wali_id' => 3, // dosen id 3 (Ahmad)
                'status' => 'aktif',
                'full_name' => 'Cakra Negara',
                'birth_date' => '2003-08-10',
                'birth_place' => 'Semarang',
                'gender' => 'male',
                'address' => 'Jl. Pahlawan No. 8, Semarang',
                'emergency_contact' => 'Ibu Luhur',
                'emergency_phone' => '0812-5555-6666',
                'blood_type' => 'O',
                'religion' => 'Islam',
                'ukt_amount' => 3500000,
                'ukt_status' => 'partial',
                'father_name' => 'Bapak Luhur',
                'mother_name' => 'Ibu Sari',
                'parent_occupation' => 'Petani',
                'parent_address' => 'Jl. Pahlawan No. 8, Semarang',
                'parent_phone' => '0812-5555-6666',
                'faculty_id' => 1,
                'study_program_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];
        DB::table('mahasiswas')->insert($mahasiswas);

        // ============================================
        // 6. MATA KULIAH (Courses)
        // Migration columns: id, kode, nama, sks, kuota, terisi, semester, dosen_id, deskripsi
        // + final migration adds: description, type, total_hours, prerequisites, study_program_id
        // ============================================
        $mataKuliah = [
            [
                'kode' => 'IF201',
                'nama' => 'Pemrograman Web',
                'sks' => 3,
                'semester' => 3,
                'dosen_id' => 1, // dosen table id 1 (Budi)
                'deskripsi' => 'Belajar dasar-dasar pemrograman web menggunakan HTML, CSS, dan JavaScript',
                'description' => 'Belajar dasar-dasar pemrograman web menggunakan HTML, CSS, dan JavaScript',
                'type' => 'mandatory',
                'total_hours' => 36,
                'prerequisites' => 'IF101',
                'study_program_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'kode' => 'IF202',
                'nama' => 'Basis Data',
                'sks' => 3,
                'semester' => 3,
                'dosen_id' => 2, // dosen table id 2 (Siti)
                'deskripsi' => 'Pengenalan sistem database, normalisasi, dan SQL',
                'description' => 'Pengenalan sistem database, normalisasi, dan SQL',
                'type' => 'mandatory',
                'total_hours' => 36,
                'prerequisites' => 'IF101',
                'study_program_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'kode' => 'IF203',
                'nama' => 'Struktur Data dan Algoritma',
                'sks' => 3,
                'semester' => 2,
                'dosen_id' => 3, // dosen table id 3 (Ahmad)
                'deskripsi' => 'Implementasi berbagai struktur data dan algoritma',
                'description' => 'Implementasi berbagai struktur data dan algoritma',
                'type' => 'mandatory',
                'total_hours' => 36,
                'prerequisites' => 'IF101',
                'study_program_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'kode' => 'IF204',
                'nama' => 'Jaringan Komputer',
                'sks' => 3,
                'semester' => 4,
                'dosen_id' => 4, // dosen table id 4 (Arya)
                'deskripsi' => 'Konsep jaringan komputer, TCP/IP, dan keamanan jaringan',
                'description' => 'Konsep jaringan komputer, TCP/IP, dan keamanan jaringan',
                'type' => 'mandatory',
                'total_hours' => 36,
                'prerequisites' => 'IF102',
                'study_program_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'kode' => 'SI301',
                'nama' => 'Analisis dan Perancangan Sistem Informasi',
                'sks' => 3,
                'semester' => 5,
                'dosen_id' => 1, // dosen table id 1 (Budi)
                'deskripsi' => 'Metodologi analisis dan perancangan sistem informasi',
                'description' => 'Metodologi analisis dan perancangan sistem informasi',
                'type' => 'mandatory',
                'total_hours' => 36,
                'prerequisites' => 'IF201',
                'study_program_id' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'kode' => 'SI302',
                'nama' => 'Manajemen Proyek Teknologi Informasi',
                'sks' => 3,
                'semester' => 5,
                'dosen_id' => 2, // dosen table id 2 (Siti)
                'deskripsi' => 'Prinsip manajemen proyek dengan pendekatan agile',
                'description' => 'Prinsip manajemen proyek dengan pendekatan agile',
                'type' => 'mandatory',
                'total_hours' => 36,
                'prerequisites' => null,
                'study_program_id' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];
        DB::table('mata_kuliahs')->insert($mataKuliah);

        // ============================================
        // 7. JADWAL (Schedules)
        // Migration columns: id, mata_kuliah_id, hari, jam_mulai, jam_selesai, ruangan
        // + final migration adds: lecturer_id, class_type, start_date, end_date
        // ============================================
        $jadwals = [
            [
                'mata_kuliah_id' => 1,
                'hari' => 'Senin',
                'jam_mulai' => '08:00:00',
                'jam_selesai' => '09:30:00',
                'ruangan' => 'R.101',
                'lecturer_id' => 1, // dosen table id 1
                'class_type' => 'regular',
                'start_date' => '2025-01-27',
                'end_date' => '2025-05-30',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'mata_kuliah_id' => 2,
                'hari' => 'Selasa',
                'jam_mulai' => '10:00:00',
                'jam_selesai' => '11:30:00',
                'ruangan' => 'R.102',
                'lecturer_id' => 2,
                'class_type' => 'regular',
                'start_date' => '2025-01-27',
                'end_date' => '2025-05-30',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'mata_kuliah_id' => 3,
                'hari' => 'Rabu',
                'jam_mulai' => '08:00:00',
                'jam_selesai' => '09:30:00',
                'ruangan' => 'R.201',
                'lecturer_id' => 3,
                'class_type' => 'regular',
                'start_date' => '2025-01-27',
                'end_date' => '2025-05-30',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'mata_kuliah_id' => 4,
                'hari' => 'Kamis',
                'jam_mulai' => '13:00:00',
                'jam_selesai' => '14:30:00',
                'ruangan' => 'R.101',
                'lecturer_id' => 4,
                'class_type' => 'regular',
                'start_date' => '2025-01-27',
                'end_date' => '2025-05-30',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'mata_kuliah_id' => 5,
                'hari' => 'Senin',
                'jam_mulai' => '14:00:00',
                'jam_selesai' => '15:30:00',
                'ruangan' => 'R.301',
                'lecturer_id' => 1,
                'class_type' => 'regular',
                'start_date' => '2025-01-27',
                'end_date' => '2025-05-30',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'mata_kuliah_id' => 6,
                'hari' => 'Selasa',
                'jam_mulai' => '11:00:00',
                'jam_selesai' => '12:30:00',
                'ruangan' => 'R.302',
                'lecturer_id' => 2,
                'class_type' => 'regular',
                'start_date' => '2025-01-27',
                'end_date' => '2025-05-30',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];
        DB::table('jadwals')->insert($jadwals);

        // ============================================
        // 8. KRS (Enrollments)
        // Migration columns: id, mahasiswa_id, semester, total_sks, status(draft/menunggu/disetujui/ditolak), catatan_dosen
        // + final migration adds: mata_kuliah_id, status(pending/approved/rejected/completed), academic_year, semester_type, approved_at, approved_by, notes
        // Note: The final migration adds a NEW status column with different enum values.
        //       We use the ORIGINAL migration's enum values here.
        // ============================================
        $krs = [
            [
                'mahasiswa_id' => 1,
                'semester' => 'Genap 2024/2025',
                'total_sks' => 6,
                'status' => 'disetujui',
                'mata_kuliah_id' => 1,
                'academic_year' => '2024/2025',
                'semester_type' => 'even',
                'approved_at' => now()->subDays(10),
                'approved_by' => 1,
                'created_at' => now()->subDays(15),
                'updated_at' => now()->subDays(10),
            ],
            [
                'mahasiswa_id' => 1,
                'semester' => 'Genap 2024/2025',
                'total_sks' => 6,
                'status' => 'disetujui',
                'mata_kuliah_id' => 2,
                'academic_year' => '2024/2025',
                'semester_type' => 'even',
                'approved_at' => now()->subDays(10),
                'approved_by' => 1,
                'created_at' => now()->subDays(15),
                'updated_at' => now()->subDays(10),
            ],
            [
                'mahasiswa_id' => 2,
                'semester' => 'Genap 2024/2025',
                'total_sks' => 6,
                'status' => 'disetujui',
                'mata_kuliah_id' => 1,
                'academic_year' => '2024/2025',
                'semester_type' => 'even',
                'approved_at' => now()->subDays(10),
                'approved_by' => 1,
                'created_at' => now()->subDays(15),
                'updated_at' => now()->subDays(10),
            ],
            [
                'mahasiswa_id' => 2,
                'semester' => 'Genap 2024/2025',
                'total_sks' => 6,
                'status' => 'disetujui',
                'mata_kuliah_id' => 5,
                'academic_year' => '2024/2025',
                'semester_type' => 'even',
                'approved_at' => now()->subDays(10),
                'approved_by' => 1,
                'created_at' => now()->subDays(15),
                'updated_at' => now()->subDays(10),
            ],
            [
                'mahasiswa_id' => 3,
                'semester' => 'Genap 2024/2025',
                'total_sks' => 6,
                'status' => 'disetujui',
                'mata_kuliah_id' => 3,
                'academic_year' => '2024/2025',
                'semester_type' => 'even',
                'approved_at' => now()->subDays(10),
                'approved_by' => 1,
                'created_at' => now()->subDays(15),
                'updated_at' => now()->subDays(10),
            ],
            [
                'mahasiswa_id' => 3,
                'semester' => 'Genap 2024/2025',
                'total_sks' => 6,
                'status' => 'disetujui',
                'mata_kuliah_id' => 4,
                'academic_year' => '2024/2025',
                'semester_type' => 'even',
                'approved_at' => now()->subDays(10),
                'approved_by' => 1,
                'created_at' => now()->subDays(15),
                'updated_at' => now()->subDays(10),
            ],
        ];
        DB::table('krs')->insert($krs);

        // ============================================
        // 9. PEMBAYARAN (Payments)
        // Migration columns: id, mahasiswa_id, semester, jumlah, status(pending/menunggu_verifikasi/lunas), metode, kode_transaksi, tanggal_bayar, keterangan
        // ============================================
        $pembayarans = [
            [
                'mahasiswa_id' => 1,
                'semester' => 'Genap 2024/2025',
                'jumlah' => 3500000,
                'status' => 'lunas',
                'metode' => 'virtual_account',
                'kode_transaksi' => 'TRX-20250127-001',
                'tanggal_bayar' => now()->subDays(30),
                'keterangan' => 'Pembayaran UKT lunas',
                'created_at' => now()->subDays(30),
                'updated_at' => now()->subDays(30),
            ],
            [
                'mahasiswa_id' => 2,
                'semester' => 'Genap 2024/2025',
                'jumlah' => 3500000,
                'status' => 'lunas',
                'metode' => 'qris',
                'kode_transaksi' => 'TRX-20250127-002',
                'tanggal_bayar' => now()->subDays(25),
                'keterangan' => 'Pembayaran UKT lunas',
                'created_at' => now()->subDays(25),
                'updated_at' => now()->subDays(25),
            ],
            [
                'mahasiswa_id' => 3,
                'semester' => 'Genap 2024/2025',
                'jumlah' => 1750000,
                'status' => 'pending',
                'metode' => 'ewallet',
                'kode_transaksi' => 'TRX-20250127-003',
                'tanggal_bayar' => now()->subDays(20),
                'keterangan' => 'Pembayaran sebagian, sisa Rp 1.750.000',
                'created_at' => now()->subDays(20),
                'updated_at' => now()->subDays(20),
            ],
        ];
        DB::table('pembayarans')->insert($pembayarans);

        // ============================================
        // 10. ANNOUNCEMENTS (Pengumuman) - tabel 'announcements' bukan 'pengumuman'
        // Migration columns: id, title, content, priority, target_audience, target_ids, start_date, end_date, is_published, created_by, views
        // ============================================
        $announcements = [
            [
                'title' => 'Jadwal Ujian Akhir Semester Genap 2024/2025',
                'content' => 'Jadwal ujian akhir semester genap akan dimulai pada tanggal 15 Maret 2025. Mohon untuk dijadikan referensi.',
                'target_audience' => 'all',
                'start_date' => '2025-03-15',
                'end_date' => '2025-03-20',
                'is_published' => true,
                'priority' => 'high',
                'created_by' => 1,
                'views' => 45,
                'created_at' => now()->subDays(5),
                'updated_at' => now()->subDays(5),
            ],
            [
                'title' => 'Libur Cuti Bersama Natal dan Tahun Baru',
                'content' => 'Kampus libur untuk cuti bersama Natal dan Tahun Baru. Mohon untuk diingat-ingatkan.',
                'target_audience' => 'all',
                'start_date' => '2024-12-25',
                'end_date' => '2025-01-05',
                'is_published' => true,
                'priority' => 'urgent',
                'created_by' => 1,
                'views' => 120,
                'created_at' => now()->subMonths(6),
                'updated_at' => now()->subMonths(6),
            ],
            [
                'title' => 'Pemberitahuan Sidang Tugas Akhir',
                'content' => 'Sidang Tugas Akhir akan dimulai pada minggu ini. Silakan cek jadwal sidang masing-masing.',
                'target_audience' => 'students',
                'start_date' => '2025-02-10',
                'end_date' => '2025-02-14',
                'is_published' => true,
                'priority' => 'medium',
                'created_by' => 1,
                'views' => 78,
                'created_at' => now()->subDays(10),
                'updated_at' => now()->subDays(10),
            ],
        ];
        DB::table('announcements')->insert($announcements);

        // ============================================
        // 11. PRESENSI (Attendances)
        // Migration columns: id, enrollment_id, student_id, schedule_id, attendance_date, status, check_in_time, check_out_time, attendance_method, recorded_by, notes
        // ============================================
        $attendances = [
            [
                'student_id' => 1,
                'schedule_id' => 1,
                'attendance_date' => '2025-02-10',
                'status' => 'present',
                'check_in_time' => '08:05:00',
                'check_out_time' => '09:30:00',
                'attendance_method' => 'qr_code',
                'recorded_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'student_id' => 1,
                'schedule_id' => 2,
                'attendance_date' => '2025-02-11',
                'status' => 'present',
                'check_in_time' => '10:05:00',
                'check_out_time' => '11:30:00',
                'attendance_method' => 'qr_code',
                'recorded_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'student_id' => 2,
                'schedule_id' => 1,
                'attendance_date' => '2025-02-10',
                'status' => 'present',
                'check_in_time' => '08:02:00',
                'check_out_time' => '09:30:00',
                'attendance_method' => 'qr_code',
                'recorded_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'student_id' => 2,
                'schedule_id' => 5,
                'attendance_date' => '2025-02-10',
                'status' => 'absent',
                'check_in_time' => null,
                'check_out_time' => null,
                'attendance_method' => 'manual',
                'recorded_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'student_id' => 3,
                'schedule_id' => 3,
                'attendance_date' => '2025-02-12',
                'status' => 'late',
                'check_in_time' => '08:35:00',
                'check_out_time' => '09:30:00',
                'attendance_method' => 'qr_code',
                'recorded_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];
        DB::table('attendances')->insert($attendances);

        // ============================================
        // 12. NILAI (Grades)
        // Migration columns: id, enrollment_id, student_id, course_id, assignment_score, quiz_score, mid_exam_score, final_exam_score, practicum_score, attendance_score, total_score, letter_grade, grade_point, graded_by, graded_at, status, notes
        // ============================================
        $grades = [
            [
                'student_id' => 1,
                'course_id' => 1,
                'assignment_score' => 85.50,
                'quiz_score' => 78.00,
                'mid_exam_score' => 82.00,
                'final_exam_score' => 88.00,
                'practicum_score' => 0,
                'attendance_score' => 100.00,
                'total_score' => 84.67,
                'letter_grade' => 'B+',
                'grade_point' => 3.33,
                'graded_by' => 2,
                'graded_at' => now()->subDays(5),
                'status' => 'published',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'student_id' => 1,
                'course_id' => 2,
                'assignment_score' => 90.00,
                'quiz_score' => 85.00,
                'mid_exam_score' => 88.00,
                'final_exam_score' => 92.00,
                'practicum_score' => 0,
                'attendance_score' => 100.00,
                'total_score' => 89.50,
                'letter_grade' => 'A-',
                'grade_point' => 3.67,
                'graded_by' => 3,
                'graded_at' => now()->subDays(5),
                'status' => 'published',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'student_id' => 2,
                'course_id' => 1,
                'assignment_score' => 88.00,
                'quiz_score' => 82.00,
                'mid_exam_score' => 85.00,
                'final_exam_score' => 90.00,
                'practicum_score' => 0,
                'attendance_score' => 100.00,
                'total_score' => 86.50,
                'letter_grade' => 'B+',
                'grade_point' => 3.33,
                'graded_by' => 2,
                'graded_at' => now()->subDays(5),
                'status' => 'published',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'student_id' => 3,
                'course_id' => 3,
                'assignment_score' => 75.00,
                'quiz_score' => 70.00,
                'mid_exam_score' => 72.00,
                'final_exam_score' => 78.00,
                'practicum_score' => 0,
                'attendance_score' => 95.00,
                'total_score' => 74.20,
                'letter_grade' => 'C+',
                'grade_point' => 2.67,
                'graded_by' => 4,
                'graded_at' => now()->subDays(5),
                'status' => 'published',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];
        DB::table('grades')->insert($grades);

        // ============================================
        // 13. BERITA/NEWS (News)
        // Migration columns: id, title, slug, excerpt, content, featured_image, category, tags, author_id, is_published, published_at, views
        // ============================================
        $news = [
            [
                'title' => 'Universitas WDU Gelar Workshop Digital Transformation',
                'slug' => 'universitas-wdu-gelar-workshop-digital-transformation',
                'excerpt' => 'Workshop tentang transformasi digital dalam pendidikan tinggi',
                'content' => 'Universitas WDU kembali mengadakan workshop tentang transformasi digital dalam pendidikan tinggi. Workshop ini dihadiri oleh dosen dan mahasiswa dari seluruh fakultas.',
                'category' => 'event',
                'tags' => json_encode(['workshop', 'digital', 'inovasi']),
                'author_id' => 1,
                'is_published' => true,
                'published_at' => now()->subDays(3),
                'views' => 150,
                'created_at' => now()->subDays(5),
                'updated_at' => now()->subDays(3),
            ],
            [
                'title' => 'Mahasiswa Teknik Informatika Juara 1 Lomba Hackathon Nasional',
                'slug' => 'mahasiswa-teknik-informatika-juara-1-lomba-hackathon-nasional',
                'excerpt' => 'Tim mahasiswa berhasil menyusun solusi inovatif dalam kompetisi nasional',
                'content' => 'Tim mahasiswa Teknik Informatika Universitas WDU berhasil menjadi juara 1 dalam Lomba Hackathon Nasional di Jakarta. Mereka mengembangkan aplikasi untuk optimalisasi transportasi publik.',
                'category' => 'achievement',
                'tags' => json_encode(['prestasi', 'hackathon', 'inovasi']),
                'author_id' => 1,
                'is_published' => true,
                'published_at' => now()->subDays(10),
                'views' => 320,
                'created_at' => now()->subDays(12),
                'updated_at' => now()->subDays(10),
            ],
        ];
        DB::table('news')->insert($news);

        // ============================================
        // 14. FAQ (Frequently Asked Questions)
        // Migration columns: id, question, answer, category, order, is_active
        // ============================================
        $faqs = [
            [
                'question' => 'Bagaimana cara mendaftar Kartu Studi?',
                'answer' => 'Anda dapat mendaftar Kartu Studi melalui portal mahasiswa dengan menggunakan akun Anda.',
                'category' => 'academic',
                'order' => 1,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'question' => 'Apa saja persyaratan untuk mengambil cuti akademik?',
                'answer' => 'Persyaratan cuti akademik adalah surat permohonan cuti dari mahasiswa dan persetujuan dosen wali.',
                'category' => 'academic',
                'order' => 2,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'question' => 'Bagaimana cara membayar UKT?',
                'answer' => 'Pembayaran UKT dapat dilakukan melalui portal pembayaran resmi universitas atau melalui rekening bank yang telah ditentukan.',
                'category' => 'finance',
                'order' => 1,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'question' => 'Apa yang harus dilakukan jika lupa password?',
                'answer' => 'Anda dapat menggunakan fitur "Lupa Password" pada halaman login atau menghubungi bagian akademik.',
                'category' => 'general',
                'order' => 1,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];
        DB::table('faqs')->insert($faqs);

        // ============================================
        // 15. CONTACT_MESSAGES (Contact Messages)
        // Migration columns: id, name, email, phone, subject, message, status, admin_response, responded_by, responded_at
        // ============================================
        $contactMessages = [
            [
                'name' => 'Andi Prasetya',
                'email' => 'andi.prasetya@wdu.ac.id',
                'phone' => '0812-1234-5678',
                'subject' => 'Pertanyaan tentang Jadwal Ujian',
                'message' => 'Assalamu alaikum, saya ingin bertanya tentang jadwal ujian mata kuliah Pemrograman Web.',
                'status' => 'unread',
                'created_at' => now()->subDays(2),
                'updated_at' => now()->subDays(2),
            ],
            [
                'name' => 'Bella Aprilia',
                'email' => 'bella.aprilia@wdu.ac.id',
                'phone' => '0813-9876-5432',
                'subject' => 'Permintaan Perubahan Jadwal',
                'message' => 'Saya ingin meminta perubahan jadwal mata kuliah karena bentrok dengan jadwal lain.',
                'status' => 'read',
                'created_at' => now()->subDays(5),
                'updated_at' => now()->subDays(4),
            ],
        ];
        DB::table('contact_messages')->insert($contactMessages);

        // ============================================
        // 16. CALENDAR_EVENTS (Academic Calendar)
        // Migration columns: id, title, description, event_date, start_time, end_time, event_type, priority, created_by, is_public
        // ============================================
        $calendarEvents = [
            [
                'title' => 'Awal Semester Genap 2024/2025',
                'description' => 'Awal semester genap tahun akademik 2024/2025',
                'event_date' => '2025-01-27',
                'start_time' => '08:00:00',
                'end_time' => '17:00:00',
                'event_type' => 'academic',
                'priority' => 'high',
                'created_by' => 1,
                'is_public' => true,
                'created_at' => now()->subMonths(6),
                'updated_at' => now()->subMonths(6),
            ],
            [
                'title' => 'Ujian Tengah Semester',
                'description' => 'Masa ujian tengah semester',
                'event_date' => '2025-02-24',
                'start_time' => '08:00:00',
                'end_time' => '17:00:00',
                'event_type' => 'exam',
                'priority' => 'high',
                'created_by' => 1,
                'is_public' => true,
                'created_at' => now()->subMonths(6),
                'updated_at' => now()->subMonths(6),
            ],
            [
                'title' => 'Cuti Bersama Natal',
                'description' => 'Tanggal merah nasional - Cuti bersama Natal dan Tahun Baru',
                'event_date' => '2024-12-25',
                'start_time' => null,
                'end_time' => null,
                'event_type' => 'holiday',
                'priority' => 'medium',
                'created_by' => 1,
                'is_public' => true,
                'created_at' => now()->subMonths(6),
                'updated_at' => now()->subMonths(6),
            ],
            [
                'title' => 'Penyerahan Hasil Akhir Semester',
                'description' => 'Batas akhir penyerahan nilai semester',
                'event_date' => '2025-05-25',
                'start_time' => '00:00:00',
                'end_time' => '23:59:59',
                'event_type' => 'deadline',
                'priority' => 'high',
                'created_by' => 1,
                'is_public' => true,
                'created_at' => now()->subMonths(6),
                'updated_at' => now()->subMonths(6),
            ],
        ];
        DB::table('calendar_events')->insert($calendarEvents);

        // ============================================
        // 17. NOTIFIKASI (Notifications)
        // Migration columns: id, judul, isi, target_role(mahasiswa/dosen/semua), tipe(info/warning/success/danger), is_active
        // ============================================
        $notifikasis = [
            [
                'judul' => 'Pendaftaran KRS Berhasil',
                'isi' => 'Pendaftaran mata kuliah untuk semester genap berhasil. Jumlah mata kuliah yang terdaftar: 2',
                'target_role' => 'mahasiswa',
                'tipe' => 'success',
                'is_active' => true,
                'created_at' => now()->subDays(2),
                'updated_at' => now()->subDays(2),
            ],
            [
                'judul' => 'Pembayaran UKT Lunas',
                'isi' => 'Pembayaran UKT bulan ini telah lunas. Terima kasih atas pembayarannya.',
                'target_role' => 'mahasiswa',
                'tipe' => 'info',
                'is_active' => true,
                'created_at' => now()->subDays(5),
                'updated_at' => now()->subDays(5),
            ],
            [
                'judul' => 'Jadwal Mengajar Diperbarui',
                'isi' => 'Jadwal mengajar Anda telah diperbarui untuk semester genap 2024/2025',
                'target_role' => 'dosen',
                'tipe' => 'info',
                'is_active' => true,
                'created_at' => now()->subDays(1),
                'updated_at' => now()->subDays(1),
            ],
        ];
        DB::table('notifikasis')->insert($notifikasis);

        $this->command->info('Database seeded successfully with comprehensive data!');
    }
}
