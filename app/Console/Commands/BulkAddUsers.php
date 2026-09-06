<?php

namespace App\Console\Commands;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\Role;
use App\Models\User;
use App\Models\WorkSchedule;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class BulkAddUsers extends Command
{
    protected $signature = 'user:bulk-add {--target=45 : Target total jumlah user}';

    protected $description = 'Menambahkan user (Employee + User) sampai target total, tanpa menghapus yang sudah terdaftar';

    private const EMPLOYEE_NAMES = [
        'Ilsan Rajib Mulqi',
        'Susanto, S.Si',
        'Albiansyah',
        'Kukuh Iman Perdana',
        'Lukmanul Hakim, Lc',
        'Moh. Rizal Faqih',
        'Archiasa',
        'Dyah Harimurti',
        'Sandra Susanto',
        'Irwansyah',
        'Kukuh Iman Perdana',
        'Steviana Amalia Ratih',
        'Arman Romadon',
        'Andi Aji Setianata',
        'Aris Setyawan, S.Si.',
        'Ahmad Zaki Haidir S.Si.',
        'Muhamad farhan ramadhan',
        'Mukhsin, B.A',
        'Ikhsanul Hakim',
        'Mohamad Fauzan',
        'Aulia Faris Humam',
        'Ema Nur Alviana',
        'Tharie Andini Mayasari',
        'Arif Rizki  Ramadhan',
        'Euis Nursifa Laila',
        'Azhar Iqbal Nugroho',
        'Mhd. Aspandi Abi',
        'Bagas Mashadi',
        'Muhamad Syamsul Gunawan',
        'Muhammad Fauzan',
        'Abdul Salam Mutahary',
        'Zakwan Itshar',
        'Mahyudin',
        'Jati Kuncoro',
        'Nurul Restiana',
        'Nadya Khairiyah',
        'Cantika Choirunisa',
        'Ulya Nabila',
        'Pindho Prakoso',
        'Mara Irpan Pane',
        'Sri Intan Lina',
        'Ashari',
        'Nur Alfisyahrin',
    ];

    private array $usedEmails = [];

    public function handle(): int
    {
        $target = (int) $this->option('target');
        $current = User::count();

        $this->info("User saat ini: {$current}. Target total: {$target}.");

        if ($current >= $target) {
            $this->warn("Jumlah user sudah mencapai/melebihi target ({$current} >= {$target}). Tidak ada yang ditambahkan.");
            return self::SUCCESS;
        }

        $toAdd = $target - $current;

        $guruRole = Role::where('name', 'Guru')->first() ?? Role::query()->first();
        $department = Department::where('name', 'Akademik')->first() ?? Department::query()->first();
        $position = Position::where('name', 'Guru')->first() ?? Position::query()->first();
        $schedule = WorkSchedule::where('name', 'Akademik (Guru & Pimpinan)')->first() ?? WorkSchedule::query()->first();

        if (!$guruRole || !$department || !$position || !$schedule) {
            $this->error('Referensi role/department/position/schedule tidak ditemukan. Jalankan php artisan db:seed terlebih dahulu.');
            return self::FAILURE;
        }

        $added = 0;
        $skipped = 0;
        $processedNames = [];

        foreach (self::EMPLOYEE_NAMES as $rawName) {
            if ($added >= $toAdd) {
                break;
            }

            $fullName = preg_replace('/\s+/', ' ', trim($rawName));
            $nameKey = mb_strtolower($fullName);

            if (in_array($nameKey, $processedNames, true)) {
                $this->line("SKIP (duplikat di daftar): {$fullName}");
                continue;
            }
            $processedNames[] = $nameKey;

            if (Employee::whereRaw('LOWER(name) = ?', [$nameKey])->exists()) {
                $this->line("SKIP (sudah terdaftar): {$fullName}");
                $skipped++;
                continue;
            }

            $email = $this->uniqueEmail($fullName);
            $nik = $this->uniqueNik();

            $employee = Employee::create([
                'nik' => $nik,
                'name' => $fullName,
                'gender' => 'male',
                'email' => $email,
                'department_id' => $department->id,
                'position_id' => $position->id,
                'schedule_id' => $schedule->id,
                'is_active' => true,
            ]);

            $user = User::create([
                'role_id' => $guruRole->id,
                'employee_id' => $employee->id,
                'name' => $fullName,
                'email' => $email,
                'password' => Hash::make('user123'),
                'status' => 'active',
            ]);

            $user->syncRoles($guruRole->name);

            $this->info("+ {$fullName} -> {$email} (pass: user123, role: {$guruRole->name})");
            $added++;
        }

        $this->newLine();
        $this->info("Selesai. Ditambahkan: {$added}. Dilewati: {$skipped}. Total user sekarang: " . User::count());

        return self::SUCCESS;
    }

    private function uniqueEmail(string $fullName): string
    {
        $firstWord = explode(' ', $fullName)[0];
        $base = mb_strtolower(preg_replace('/[^a-zA-Z]/', '', $firstWord));

        if ($base === '') {
            $base = 'user';
        }

        $domain = '@scr.sch.id';
        $email = $base . $domain;
        $suffix = 2;

        while ($this->emailTaken($email)) {
            $email = $base . $suffix . $domain;
            $suffix++;
        }

        $this->usedEmails[] = $email;

        return $email;
    }

    private function emailTaken(string $email): bool
    {
        if (in_array($email, $this->usedEmails, true)) {
            return true;
        }

        if (User::withTrashed()->where('email', $email)->exists()) {
            return true;
        }

        return Employee::withTrashed()->where('email', $email)->exists();
    }

    private function uniqueNik(): string
    {
        $sequence = 1;

        do {
            $nik = 'GRU' . str_pad((string) $sequence, 3, '0', STR_PAD_LEFT);
            $sequence++;
        } while (Employee::withTrashed()->where('nik', $nik)->exists());

        return $nik;
    }
}