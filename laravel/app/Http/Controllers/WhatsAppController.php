<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use App\Models\WaPimpinan;
use App\Models\WaScheduledReport;
use App\Models\WaSetting;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WhatsAppController extends Controller
{
    // ==================== WA SETTINGS ====================
    public function settingsIndex()
    {
        $user = auth()->user();
        if (!$user->hasAccess('wa_settings')) {
            abort(403, 'Akses ditolak.');
        }

        $waSettings = WaSetting::getFirst();
        if (!$waSettings) {
            $waSettings = WaSetting::create([
                'gateway_name' => 'fonnte',
                'api_url' => 'https://api.fonnte.com/send',
                'jam_reminder_pagi' => '07:30',
                'jam_reminder_siang' => '12:00',
                'aktif' => false,
            ]);
        }

        $logs = DB::table('wa_notification_logs')->orderByDesc('tanggal_kirim')->limit(20)->get();

        $operators = User::with('upt')
            ->where('role', 'operator')
            ->orderBy('nama_lengkap')
            ->get();

        return view('wa.settings', compact('waSettings', 'logs', 'operators'));
    }

    public function settingsUpdate(Request $request)
    {
        $user = auth()->user();
        if (!$user->hasAccess('wa_settings')) {
            abort(403, 'Akses ditolak.');
        }

        $waSettings = WaSetting::getFirst();
        if (!$waSettings) {
            $waSettings = new WaSetting();
        }

        $waSettings->gateway_name = $request->input('gateway_name', 'fonnte');
        $waSettings->api_key = trim($request->input('api_key', ''));
        $waSettings->api_url = trim($request->input('api_url', 'https://api.fonnte.com/send'));
        $waSettings->group_id = trim($request->input('group_id', ''));
        $waSettings->jam_reminder_pagi = $request->input('jam_reminder_pagi', '07:30');
        $waSettings->jam_reminder_siang = $request->input('jam_reminder_siang', '12:00');
        $waSettings->aktif = $request->has('aktif');
        $waSettings->template_pagi = $request->input('template_pagi', '');
        $waSettings->template_siang = $request->input('template_siang', '');
        $waSettings->save();

        ActivityLog::log('setting', 'wa', 'Memperbarui konfigurasi WhatsApp Gateway');

        return back()->with('success', 'Pengaturan WhatsApp berhasil disimpan!');
    }

    public function settingsTest(Request $request)
    {
        $user = auth()->user();
        if (!$user->hasAccess('wa_settings')) {
            abort(403, 'Akses ditolak.');
        }

        $action = $request->input('action');

        if ($action === 'test_message') {
            $testNumber = trim($request->input('test_number', ''));
            $testMessage = trim($request->input('test_message', 'Test notification dari Sultan Banten'));

            if (!$testNumber) {
                return back()->with('error', 'Nomor tujuan harus diisi.');
            }

            $res = WhatsAppService::send($testNumber, $testMessage);
            return $res['success']
                ? back()->with('success', "✅ Pesan test berhasil dikirim ke {$testNumber}")
                : back()->with('error', '❌ Gagal mengirim pesan: ' . ($res['message'] ?? 'Unknown error'));
        }

        if ($action === 'test_group') {
            $settings = WaSetting::getFirst();
            $groupId = trim($settings->group_id ?? '');
            if (!$groupId) {
                return back()->with('error', 'Group ID belum dikonfigurasi di pengaturan.');
            }

            $testMessage = "📢 *TEST PESAN GRUP*\n\nIni adalah pesan test dari Sultan Banten.\n\n_Terkirim: " . date('d/m/Y H:i:s') . '_';
            $res = WhatsAppService::send($groupId, $testMessage);
            return $res['success']
                ? back()->with('success', '✅ Pesan test berhasil dikirim ke grup WhatsApp!')
                : back()->with('error', '❌ Gagal mengirim ke grup: ' . ($res['message'] ?? 'Unknown error'));
        }

        if ($action === 'send_morning') {
            $res = WhatsAppService::sendMorningReminder();
            return $res['success']
                ? back()->with('success', "✅ Reminder pagi berhasil dikirim ke {$res['sent']} operator.")
                : back()->with('error', '❌ Gagal mengirim reminder: ' . $res['message']);
        }

        if ($action === 'send_afternoon') {
            $res = WhatsAppService::sendAfternoonReport();
            return $res['success']
                ? back()->with('success', "✅ Laporan siang berhasil dikirim ke grup WhatsApp! Belum input: Penghuni ({$res['missing_penghuni']}), Overstaying ({$res['missing_overstaying']}), Integrasi ({$res['missing_integrasi']}), Bebas Harian ({$res['missing_bebas_harian']})")
                : back()->with('error', '❌ Gagal mengirim laporan: ' . $res['message']);
        }

        return back();
    }

    // ==================== WA SCHEDULE ====================
    public function scheduleIndex()
    {
        $user = auth()->user();
        if (!$user->hasAccess('wa_schedule')) {
            abort(403, 'Akses ditolak.');
        }

        $schedules = WaScheduledReport::orderByDesc('id')->get();
        $variables = WhatsAppService::getTemplateVariables();
        $defaultTemplate = WhatsAppService::getDefaultTemplate();

        $logs = DB::table('wa_scheduled_report_logs as l')
            ->join('wa_scheduled_reports as r', 'l.report_id', '=', 'r.id')
            ->select('l.*', 'r.nama_laporan')
            ->orderByDesc('l.tanggal_kirim')
            ->limit(20)
            ->get();

        return view('wa.schedule', compact('schedules', 'variables', 'defaultTemplate', 'logs'));
    }

    public function scheduleStore(Request $request)
    {
        $user = auth()->user();
        if (!$user->hasAccess('wa_schedule')) {
            abort(403, 'Akses ditolak.');
        }

        $request->validate([
            'nama_laporan' => 'required|string|max:100',
            'jam_kirim' => 'required',
        ]);

        $target = $request->input('target_penerima', 'grup');
        $groupId = $target === 'pimpinan' ? '-' : trim($request->input('group_id', ''));

        $schedule = WaScheduledReport::create([
            'nama_laporan' => $request->input('nama_laporan'),
            'group_id' => $groupId ?: '-',
            'jam_kirim' => $request->input('jam_kirim'),
            'hari' => $request->input('hari', 'daily'),
            'template_pesan' => $request->input('template_pesan') ?: WhatsAppService::getDefaultTemplate(),
            'kirim_ke_pimpinan' => in_array($target, ['pimpinan', 'keduanya']),
            'aktif' => true,
        ]);

        ActivityLog::log('tambah', 'wa', "Menambahkan jadwal laporan WA: {$schedule->nama_laporan}");

        return back()->with('success', 'Jadwal laporan berhasil ditambahkan!');
    }

    public function scheduleUpdate(Request $request, $id)
    {
        $user = auth()->user();
        if (!$user->hasAccess('wa_schedule')) {
            abort(403, 'Akses ditolak.');
        }

        $schedule = WaScheduledReport::findOrFail($id);

        $request->validate([
            'nama_laporan' => 'required|string|max:100',
            'jam_kirim' => 'required',
        ]);

        $target = $request->input('target_penerima', 'grup');
        $groupId = $target === 'pimpinan' ? '-' : trim($request->input('group_id', ''));

        $schedule->update([
            'nama_laporan' => $request->input('nama_laporan'),
            'group_id' => $groupId ?: '-',
            'jam_kirim' => $request->input('jam_kirim'),
            'hari' => $request->input('hari', 'daily'),
            'template_pesan' => $request->input('template_pesan') ?: WhatsAppService::getDefaultTemplate(),
            'kirim_ke_pimpinan' => in_array($target, ['pimpinan', 'keduanya']),
            'aktif' => $request->has('aktif'),
        ]);

        ActivityLog::log('edit', 'wa', "Mengubah jadwal laporan WA: {$schedule->nama_laporan}");

        return back()->with('success', 'Jadwal laporan berhasil diperbarui!');
    }

    public function scheduleDestroy($id)
    {
        $user = auth()->user();
        if (!$user->hasAccess('wa_schedule')) {
            abort(403, 'Akses ditolak.');
        }

        $schedule = WaScheduledReport::findOrFail($id);
        $name = $schedule->nama_laporan;
        $schedule->delete();

        ActivityLog::log('hapus', 'wa', "Menghapus jadwal laporan WA: {$name}");

        return back()->with('success', 'Jadwal laporan berhasil dihapus!');
    }

    public function scheduleToggle($id)
    {
        $user = auth()->user();
        if (!$user->hasAccess('wa_schedule')) {
            abort(403, 'Akses ditolak.');
        }

        $schedule = WaScheduledReport::findOrFail($id);
        $schedule->aktif = !$schedule->aktif;
        $schedule->save();

        return back()->with('success', 'Status jadwal berhasil diubah!');
    }

    public function scheduleSendNow($id)
    {
        $user = auth()->user();
        if (!$user->hasAccess('wa_schedule')) {
            abort(403, 'Akses ditolak.');
        }

        $res = WhatsAppService::sendScheduledReport($id);

        return $res['success']
            ? back()->with('success', '✅ Laporan berhasil dikirim sekarang!')
            : back()->with('error', '❌ Gagal mengirim laporan: ' . ($res['message'] ?? 'Error'));
    }

    // ==================== WA PIMPINAN ====================
    public function pimpinanIndex()
    {
        $user = auth()->user();
        if (!$user->hasAccess('wa_schedule')) {
            abort(403, 'Akses ditolak.');
        }

        $pimpinanList = WaPimpinan::orderBy('urutan')->get();

        return view('wa.pimpinan', compact('pimpinanList'));
    }

    public function pimpinanStore(Request $request)
    {
        $user = auth()->user();
        if (!$user->hasAccess('wa_schedule')) {
            abort(403, 'Akses ditolak.');
        }

        $request->validate([
            'nama' => 'required|string|max:100',
            'no_wa' => 'required|string|max:20',
        ]);

        $pimpinan = WaPimpinan::create([
            'nama' => $request->input('nama'),
            'jabatan' => $request->input('jabatan'),
            'no_wa' => $request->input('no_wa'),
            'urutan' => (int) $request->input('urutan', 0),
            'aktif' => $request->has('aktif'),
        ]);

        ActivityLog::log('tambah', 'wa_pimpinan', "Menambahkan pimpinan: {$pimpinan->nama}");

        return back()->with('success', 'Data pimpinan berhasil ditambahkan.');
    }

    public function pimpinanUpdate(Request $request, $id)
    {
        $user = auth()->user();
        if (!$user->hasAccess('wa_schedule')) {
            abort(403, 'Akses ditolak.');
        }

        $pimpinan = WaPimpinan::findOrFail($id);

        $request->validate([
            'nama' => 'required|string|max:100',
            'no_wa' => 'required|string|max:20',
        ]);

        $pimpinan->update([
            'nama' => $request->input('nama'),
            'jabatan' => $request->input('jabatan'),
            'no_wa' => $request->input('no_wa'),
            'urutan' => (int) $request->input('urutan', 0),
            'aktif' => $request->has('aktif'),
        ]);

        ActivityLog::log('edit', 'wa_pimpinan', "Mengubah data pimpinan: {$pimpinan->nama}");

        return back()->with('success', 'Data pimpinan berhasil diperbarui.');
    }

    public function pimpinanDestroy($id)
    {
        $user = auth()->user();
        if (!$user->hasAccess('wa_schedule')) {
            abort(403, 'Akses ditolak.');
        }

        $pimpinan = WaPimpinan::findOrFail($id);
        $name = $pimpinan->nama;
        $pimpinan->delete();

        ActivityLog::log('hapus', 'wa_pimpinan', "Menghapus pimpinan: {$name}");

        return back()->with('success', 'Data pimpinan berhasil dihapus.');
    }
}
