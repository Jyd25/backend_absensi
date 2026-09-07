<?php

namespace App\Console\Commands;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DeleteGuestData extends Command
{
    protected $signature = 'user:delete-guest {--force : Hapus permanen tanpa konfirmasi}';

    protected $description = 'Menghapus permanen user & employee uji coba (nama/email/NIK mengandung "tamu" atau "guest")';

    public function handle(): int
    {
        $like = function (string $column, string $word) {
            return ['LOWER(' . $column . ') LIKE ?', ['%' . $word . '%']];
        };

        $userWhere = function ($query) use ($like) {
            foreach (['name', 'email'] as $column) {
                foreach (['tamu', 'guest'] as $word) {
                    [$raw, $bind] = $like($column, $word);
                    $query->orWhereRaw($raw, $bind);
                }
            }
        };

        $employeeWhere = function ($query) use ($like) {
            foreach (['name', 'nik', 'email'] as $column) {
                foreach (['tamu', 'guest'] as $word) {
                    [$raw, $bind] = $like($column, $word);
                    $query->orWhereRaw($raw, $bind);
                }
            }
        };

        $guestUsers = User::withTrashed()->where(function ($q) use ($userWhere) {
            $userWhere($q);
        })->get();

        $guestEmployees = Employee::withTrashed()->where(function ($q) use ($employeeWhere) {
            $employeeWhere($q);
        })->get();

        $linkedUsers = User::withTrashed()
            ->whereIn('employee_id', $guestEmployees->pluck('id'))
            ->whereNotIn('id', $guestUsers->pluck('id'))
            ->get();

        $allUsers = $guestUsers->merge($linkedUsers)->unique('id');

        $this->info('Ditemukan ' . $allUsers->count() . ' user uji coba:');
        foreach ($allUsers as $u) {
            $this->line('  USER  #' . $u->id . '  ' . $u->name . '  <' . $u->email . '>');
        }

        $this->info('Ditemukan ' . $guestEmployees->count() . ' employee uji coba:');
        foreach ($guestEmployees as $e) {
            $this->line('  EMPL  #' . $e->id . '  NIK:' . ($e->nik ?? '-') . '  ' . $e->name);
        }

        if ($allUsers->isEmpty() && $guestEmployees->isEmpty()) {
            $this->warn('Tidak ada data cocok "tamu"/"guest". Cek penamaan data uji coba di aplikasi, lalu beri tahu saya.');
            return self::SUCCESS;
        }

        if (!$this->option('force') && !$this->confirm('Hapus permanen data uji coba di atas?')) {
            $this->warn('Dibatalkan.');
            return self::SUCCESS;
        }

        DB::transaction(function () use ($allUsers, $guestEmployees) {
            $userId = $allUsers->pluck('id');

            $fkTables = [
                'notifications' => 'user_id',
                'login_logs' => 'user_id',
                'api_logs' => 'user_id',
                'activity_logs' => 'user_id',
                'sessions' => 'user_id',
                'email_reports' => 'user_id',
            ];

            foreach ($fkTables as $table => $column) {
                DB::table($table)->whereIn($column, $userId)->update([$column => null]);
            }

            DB::table('attendance_histories')->whereIn('performed_by', $userId)->update(['performed_by' => null]);
            DB::table('leave_requests')->whereIn('approved_by', $userId)->update(['approved_by' => null]);
            DB::table('attendance_corrections')->whereIn('approved_by', $userId)->update(['approved_by' => null]);
            DB::table('face_update_requests')->whereIn('approved_by', $userId)->update(['approved_by' => null]);

            foreach ($allUsers as $user) {
                $user->forceDelete();
            }
            foreach ($guestEmployees as $employee) {
                $employee->forceDelete();
            }
        });

        $this->info('Selesai. User dihapus: ' . $allUsers->count() . '; Employee dihapus: ' . $guestEmployees->count() . '.');

        return self::SUCCESS;
    }
}