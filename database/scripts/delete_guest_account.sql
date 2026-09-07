-- ============================================================================
-- Hapus akun tamu hasil "login sebagai tamu" (guest login) yang pernah dibuat.
-- Kunci pencarian: email = 'tamu@scr.sch.id'
--
-- Cara pakai (pilih salah satu):
--   1) mysql -u root -p < database/scripts/delete_guest_account.sql
--   2) Salin-tempel seluruh isi file ini ke prompt mysql / phpMyAdmin (tab SQL)
--
-- Setelah sukses:  php artisan permission:cache-reset
-- ============================================================================

SET @guest_email = 'tamu@scr.sch.id';

-- ============================ 1) CEK (dry-run) ============================
SELECT id, nik, name, email, deleted_at
FROM employees
WHERE email = @guest_email;

SELECT id, name, email, role_id, employee_id, deleted_at
FROM users
WHERE email = @guest_email;

SELECT 'face_update_requests'   AS tabel, COUNT(*) AS jumlah
FROM face_update_requests
WHERE employee_id = (SELECT id FROM employees WHERE email = @guest_email LIMIT 1)
UNION ALL SELECT 'leave_requests',          COUNT(*) FROM leave_requests
WHERE employee_id = (SELECT id FROM employees WHERE email = @guest_email LIMIT 1)
UNION ALL SELECT 'attendance_corrections',  COUNT(*) FROM attendance_corrections
WHERE employee_id = (SELECT id FROM employees WHERE email = @guest_email LIMIT 1)
UNION ALL SELECT 'face_datasets',           COUNT(*) FROM face_datasets
WHERE employee_id = (SELECT id FROM employees WHERE email = @guest_email LIMIT 1)
UNION ALL SELECT 'attendances',             COUNT(*) FROM attendances
WHERE employee_id = (SELECT id FROM employees WHERE email = @guest_email LIMIT 1)
UNION ALL SELECT 'notifications',           COUNT(*) FROM notifications
WHERE user_id = (SELECT id FROM users WHERE email = @guest_email LIMIT 1)
UNION ALL SELECT 'login_logs',              COUNT(*) FROM login_logs
WHERE user_id = (SELECT id FROM users WHERE email = @guest_email LIMIT 1)
UNION ALL SELECT 'api_logs',                COUNT(*) FROM api_logs
WHERE user_id = (SELECT id FROM users WHERE email = @guest_email LIMIT 1)
UNION ALL SELECT 'activity_logs',           COUNT(*) FROM activity_logs
WHERE user_id = (SELECT id FROM users WHERE email = @guest_email LIMIT 1)
UNION ALL SELECT 'sessions',                COUNT(*) FROM sessions
WHERE user_id = (SELECT id FROM users WHERE email = @guest_email LIMIT 1)
UNION ALL SELECT 'attendance_histories(performer)', COUNT(*) FROM attendance_histories
WHERE performed_by = (SELECT id FROM users WHERE email = @guest_email LIMIT 1);

-- ============================ 2) HAPUS ============================
START TRANSACTION;

SET @emp_id = (SELECT id FROM employees WHERE email = @guest_email LIMIT 1);
SET @usr_id = (SELECT id FROM users     WHERE email = @guest_email LIMIT 1);

-- --- data milik employee tamu ---
DELETE FROM face_update_requests   WHERE employee_id = @emp_id;
DELETE FROM leave_requests         WHERE employee_id = @emp_id;
DELETE FROM attendance_corrections WHERE employee_id = @emp_id;
DELETE FROM face_datasets          WHERE employee_id = @emp_id;
DELETE FROM attendances            WHERE employee_id = @emp_id; -- attendance_histories ikut ter-cascade

-- --- data yang merujuk user tamu ---
DELETE FROM attendance_histories   WHERE performed_by = @usr_id;
DELETE FROM login_logs             WHERE user_id      = @usr_id;
DELETE FROM api_logs               WHERE user_id      = @usr_id;
DELETE FROM activity_logs          WHERE user_id      = @usr_id;
DELETE FROM leave_requests         WHERE approved_by   = @usr_id;
DELETE FROM attendance_corrections WHERE approved_by   = @usr_id;
DELETE FROM face_update_requests   WHERE approved_by   = @usr_id;
DELETE FROM notifications          WHERE user_id      = @usr_id;
DELETE FROM sessions               WHERE user_id      = @usr_id;

-- --- assign role (spatie) ---
DELETE FROM model_has_roles WHERE model_type = 'App\\Models\\User' AND model_id = @usr_id;

-- --- user dulu (FK users.employee_id RESTRICT), baru employee ---
DELETE FROM users     WHERE id = @usr_id;
DELETE FROM employees WHERE id = @emp_id;

COMMIT;

-- ============================ 3) VERIFIKASI ============================
SELECT id, nik, name, email, deleted_at FROM employees WHERE email = @guest_email;
SELECT id, name, email, role_id, employee_id, deleted_at FROM users WHERE email = @guest_email;
SELECT 'Data tamu tersisa: ' AS status, COUNT(*) AS sisa
FROM users
WHERE email = @guest_email
UNION ALL SELECT 'employees', COUNT(*) FROM employees WHERE email = @guest_email;