<?php

namespace App\Services;

use App\Models\DataBebasHarian;
use App\Models\DataIntegrasi;
use App\Models\DataOverstaying;
use App\Models\DataPenghuni;
use App\Models\Instansi;
use App\Models\Upt;
use App\Models\User;
use App\Models\WaPimpinan;
use App\Models\WaScheduledReport;
use App\Models\WaSetting;
use Illuminate\Support\Facades\DB;

class WhatsAppService
{
    public static function send(string $target, string $message): array
    {
        $settings = WaSetting::getFirst();

        if (!$settings || empty($settings->api_key) || empty($settings->aktif)) {
            return [
                'success' => false,
                'message' => 'Notifikasi WhatsApp belum dikonfigurasi atau sedang nonaktif.'
            ];
        }

        $isGroup = preg_match('/[a-zA-Z]/', $target);
        if (!$isGroup) {
            $target = preg_replace('/^0/', '62', $target);
            $target = preg_replace('/[^0-9]/', '', $target);
        }

        $apiKey = $settings->api_key;
        $apiUrl = $settings->api_url ?: 'https://api.fonnte.com/send';
        $gateway = $settings->gateway_name ?: 'fonnte';

        $ch = curl_init();

        if ($gateway === 'fonnte') {
            curl_setopt_array($ch, [
                CURLOPT_URL => $apiUrl,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => http_build_query([
                    'target' => $target,
                    'message' => $message,
                    'countryCode' => '62',
                ]),
                CURLOPT_HTTPHEADER => [
                    'Authorization: ' . $apiKey,
                    'Content-Type: application/x-www-form-urlencoded'
                ],
                CURLOPT_TIMEOUT => 30,
            ]);
        } else {
            curl_setopt_array($ch, [
                CURLOPT_URL => $apiUrl,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode([
                    'target' => $target,
                    'message' => $message,
                ]),
                CURLOPT_HTTPHEADER => [
                    'Authorization: ' . $apiKey,
                    'Content-Type: application/json'
                ],
                CURLOPT_TIMEOUT => 30,
            ]);
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        $success = ($httpCode >= 200 && $httpCode < 300) && !$error;
        $responseData = json_decode($response, true);
        $actualMessage = 'Pesan berhasil dikirim.';

        if ($gateway === 'fonnte' && $responseData) {
            if (isset($responseData['status']) && $responseData['status'] === false) {
                $actualMessage = $responseData['reason'] ?? $responseData['message'] ?? 'Error dari Fonnte';
                $success = false;
            } elseif (isset($responseData['status']) && $responseData['status'] === true) {
                $actualMessage = 'Berhasil terkirim via Fonnte.';
            }
        }

        return [
            'success' => $success,
            'message' => $actualMessage,
            'response' => $response,
            'error' => $error,
            'http_code' => $httpCode,
            'parsed_response' => $responseData,
        ];
    }

    public static function sendMorningReminder(): array
    {
        $settings = WaSetting::getFirst();
        if (!$settings || empty($settings->api_key) || empty($settings->aktif)) {
            return [
                'success' => false,
                'message' => 'Pengaturan WA belum aktif.',
                'sent' => 0
            ];
        }

        $today = date('Y-m-d');

        $donePenghuni = DataPenghuni::where('tanggal', $today)->distinct()->pluck('upt_id')->toArray();
        $doneOverstaying = DataOverstaying::where('tanggal', $today)->distinct()->pluck('upt_id')->toArray();
        $doneIntegrasi = DataIntegrasi::where('tanggal', $today)->distinct()->pluck('upt_id')->toArray();
        $doneBebasHarian = DataBebasHarian::where('tanggal', $today)->distinct()->pluck('upt_id')->toArray();

        $timeRestriction = getTimeRestriction();
        $jamMulai = $timeRestriction['jam_mulai'];
        $jamSelesai = $timeRestriction['jam_selesai'];

        $operators = User::with('upt')
            ->where('role', 'operator')
            ->whereNotNull('no_wa')
            ->where('no_wa', '!=', '')
            ->get();

        $sent = 0;
        $failed = 0;
        $skipped = 0;
        $responses = [];

        foreach ($operators as $op) {
            $uptId = $op->upt_id;
            $missing = [];

            if (!in_array($uptId, $donePenghuni)) $missing[] = 'Data Penghuni';
            if (!in_array($uptId, $doneOverstaying)) $missing[] = 'Data Overstaying';
            if (!in_array($uptId, $doneIntegrasi)) $missing[] = 'Data Integrasi';
            if (!in_array($uptId, $doneBebasHarian)) $missing[] = 'Data Bebas Harian';

            if (empty($missing)) {
                $skipped++;
                continue;
            }

            $missingText = implode(', ', $missing);
            $template = $settings->template_pagi ?: "🌅 *PENGINGAT INPUT DATA*\n\nYth. {nama}\nUPT: {upt}\n\n⚠️ *Belum Input:* {list_missing}\n\nMohon segera lengkapi data hari ini.\n⏰ Batas waktu: Pukul {jam_mulai} - {jam_selesai} WIB\n\n_Sultan Banten Ditjenpas_";

            $message = str_replace(
                ['{nama}', '{upt}', '{jam_mulai}', '{jam_selesai}', '{list_missing}'],
                [$op->nama_lengkap, $op->upt?->nama_upt ?? '-', $jamMulai, $jamSelesai, $missingText],
                $template
            );

            $res = self::send($op->no_wa, $message);
            if ($res['success']) {
                $sent++;
            } else {
                $failed++;
            }

            $responses[] = [
                'name' => $op->nama_lengkap,
                'upt' => $op->upt?->nama_upt ?? '-',
                'status' => $res['success'] ? 'success' : 'failed'
            ];
        }

        try {
            DB::table('wa_notification_logs')->insert([
                'tipe' => 'reminder_pagi',
                'target' => 'operator belum input',
                'pesan' => "Smart reminder sent to {$sent} operators. {$skipped} operators skipped (already input).",
                'status' => $failed > 0 ? 'partial' : 'success',
                'jumlah_penerima' => $sent,
                'tanggal_kirim' => now(),
            ]);
        } catch (\Exception $e) {}

        return [
            'success' => true,
            'message' => "Reminder terkirim ke {$sent} operator, {$skipped} dilewati (sudah input).",
            'sent' => $sent,
            'failed' => $failed,
            'skipped' => $skipped,
            'details' => $responses
        ];
    }

    public static function sendAfternoonReport(): array
    {
        $settings = WaSetting::getFirst();
        if (!$settings || empty($settings->api_key) || empty($settings->aktif)) {
            return ['success' => false, 'message' => 'Pengaturan WA belum aktif.'];
        }

        if (empty($settings->group_id)) {
            return ['success' => false, 'message' => 'Group ID belum diatur.'];
        }

        $today = date('Y-m-d');
        $allUPTs = Upt::orderBy('nama_upt')->get();

        $penghuniUPTs = DataPenghuni::where('tanggal', $today)->distinct()->pluck('upt_id')->toArray();
        $overstayingUPTs = DataOverstaying::where('tanggal', $today)->distinct()->pluck('upt_id')->toArray();
        $integrasiUPTs = DataIntegrasi::where('tanggal', $today)->distinct()->pluck('upt_id')->toArray();
        $bebasHarianUPTs = DataBebasHarian::where('tanggal', $today)->distinct()->pluck('upt_id')->toArray();

        $missingPenghuni = [];
        $missingOverstaying = [];
        $missingIntegrasi = [];
        $missingBebasHarian = [];

        foreach ($allUPTs as $upt) {
            if (!in_array($upt->id, $penghuniUPTs)) $missingPenghuni[] = $upt->nama_upt;
            if (!in_array($upt->id, $overstayingUPTs)) $missingOverstaying[] = $upt->nama_upt;
            if (!in_array($upt->id, $integrasiUPTs)) $missingIntegrasi[] = $upt->nama_upt;
            if (!in_array($upt->id, $bebasHarianUPTs)) $missingBebasHarian[] = $upt->nama_upt;
        }

        $listBelumInput = "";
        if (empty($missingPenghuni) && empty($missingOverstaying) && empty($missingIntegrasi) && empty($missingBebasHarian)) {
            $listBelumInput = "✅ *SEMUA UPT SUDAH MENGINPUT DATA LENGKAP*";
        } else {
            if (!empty($missingPenghuni)) {
                $listBelumInput .= "🔴 *Belum Input Data Penghuni* (" . count($missingPenghuni) . " UPT):\n";
                foreach ($missingPenghuni as $name) $listBelumInput .= "   • $name\n";
                $listBelumInput .= "\n";
            }
            if (!empty($missingOverstaying)) {
                $listBelumInput .= "🔴 *Belum Input Data Overstaying* (" . count($missingOverstaying) . " UPT):\n";
                foreach ($missingOverstaying as $name) $listBelumInput .= "   • $name\n";
                $listBelumInput .= "\n";
            }
            if (!empty($missingIntegrasi)) {
                $listBelumInput .= "🔴 *Belum Input Data Integrasi* (" . count($missingIntegrasi) . " UPT):\n";
                foreach ($missingIntegrasi as $name) $listBelumInput .= "   • $name\n";
                $listBelumInput .= "\n";
            }
            if (!empty($missingBebasHarian)) {
                $listBelumInput .= "🔴 *Belum Input Bebas Harian* (" . count($missingBebasHarian) . " UPT):\n";
                foreach ($missingBebasHarian as $name) $listBelumInput .= "   • $name\n";
                $listBelumInput .= "\n";
            }
        }

        $totalBelum = count($missingPenghuni) + count($missingOverstaying) + count($missingIntegrasi) + count($missingBebasHarian);

        $template = $settings->template_siang ?: "📊 *LAPORAN MONITORING INPUT DATA*\nTanggal: {tanggal}\nWaktu: {waktu} WIB\n\n{list_belum_input}\n_Sultan Banten Ditjenpas_";

        $message = str_replace(
            ['{tanggal}', '{waktu}', '{list_belum_input}', '{total_belum}'],
            [formatIndonesianDate($today), date('H:i'), trim($listBelumInput), $totalBelum],
            $template
        );

        $res = self::send($settings->group_id, $message);

        try {
            DB::table('wa_notification_logs')->insert([
                'tipe' => 'laporan_siang',
                'target' => $settings->group_id,
                'pesan' => $message,
                'status' => $res['success'] ? 'success' : 'failed',
                'jumlah_penerima' => 1,
                'tanggal_kirim' => now(),
            ]);
        } catch (\Exception $e) {}

        return [
            'success' => $res['success'],
            'message' => $res['message'],
            'missing_penghuni' => count($missingPenghuni),
            'missing_overstaying' => count($missingOverstaying),
            'missing_integrasi' => count($missingIntegrasi),
            'missing_bebas_harian' => count($missingBebasHarian),
        ];
    }

    public static function sendScheduledReport(int $scheduleId): array
    {
        $schedule = WaScheduledReport::find($scheduleId);
        if (!$schedule) {
            return ['success' => false, 'message' => 'Jadwal laporan tidak ditemukan.'];
        }

        $today = date('Y-m-d');
        $comprehensiveData = self::getComprehensiveReportData($today);

        $template = $schedule->template_pesan ?: self::getDefaultTemplate();
        $message = self::formatTemplateMessage($template, $comprehensiveData);

        $res = ['success' => true];
        if (!empty($schedule->group_id) && $schedule->group_id !== '-') {
            $res = self::send($schedule->group_id, $message);
        }

        if ($res['success']) {
            $schedule->update(['last_sent' => now()]);
        }

        try {
            DB::table('wa_scheduled_report_logs')->insert([
                'report_id' => $scheduleId,
                'pesan' => $message,
                'status' => $res['success'] ? 'success' : 'failed',
                'response' => $res['message'] ?? ($res['response'] ?? null),
                'tanggal_kirim' => now(),
            ]);
        } catch (\Exception $e) {}

        if (!empty($schedule->kirim_ke_pimpinan) && $res['success']) {
            $pimpinanList = WaPimpinan::where('aktif', 1)->orderBy('urutan')->get();
            foreach ($pimpinanList as $pimpinan) {
                if (!empty($pimpinan->no_wa)) {
                    self::send($pimpinan->no_wa, $message);
                }
            }
        }

        return $res;
    }

    public static function processScheduledReports(): array
    {
        $today = date('Y-m-d');
        $currentTime = date('H:i:s');
        $currentDayNum = date('w');
        $isWeekday = ($currentDayNum >= 1 && $currentDayNum <= 5);

        $schedules = WaScheduledReport::where('aktif', 1)
            ->where('jam_kirim', '<=', $currentTime)
            ->where(function ($q) use ($today) {
                $q->whereNull('last_sent')->orWhereDate('last_sent', '<', $today);
            })
            ->get();

        $results = [];
        foreach ($schedules as $s) {
            $shouldSend = false;
            $hari = $s->hari;

            if ($hari === 'daily') {
                $shouldSend = true;
            } elseif ($hari === 'weekdays' && $isWeekday) {
                $shouldSend = true;
            } elseif ((string)$hari === (string)$currentDayNum) {
                $shouldSend = true;
            }

            if ($shouldSend) {
                $res = self::sendScheduledReport($s->id);
                $results[] = [
                    'id' => $s->id,
                    'nama' => $s->nama_laporan,
                    'status' => $res['success'] ? 'success' : 'failed',
                    'message' => $res['message'] ?? '',
                ];
            }
        }

        return $results;
    }

    public static function runCron(string $action = 'run'): array
    {
        $settings = WaSetting::getFirst();
        if (!$settings || empty($settings->aktif)) {
            return ['success' => false, 'message' => 'WhatsApp notifications are disabled'];
        }

        switch ($action) {
            case 'morning':
                return self::sendMorningReminder();
            case 'afternoon':
                return self::sendAfternoonReport();
            case 'scheduled':
                $scheduledResults = self::processScheduledReports();
                return [
                    'success' => true,
                    'message' => 'Processed ' . count($scheduledResults) . ' schedules',
                    'details' => $scheduledResults,
                ];
            case 'run':
            default:
                $morning = ['status' => 'skipped'];
                $afternoon = ['status' => 'skipped'];
                $today = date('Y-m-d');
                $currentTime = date('H:i');

                $morningTime = substr($settings->jam_reminder_pagi ?? '07:30', 0, 5);
                if ($currentTime >= $morningTime) {
                    $sentToday = DB::table('wa_notification_logs')
                        ->where('tipe', 'reminder_pagi')
                        ->whereDate('tanggal_kirim', $today)
                        ->count();
                    $morning = $sentToday === 0 ? self::sendMorningReminder() : ['status' => 'already_sent'];
                }

                $afternoonTime = substr($settings->jam_reminder_siang ?? '12:00', 0, 5);
                if ($currentTime >= $afternoonTime) {
                    $sentToday = DB::table('wa_notification_logs')
                        ->where('tipe', 'laporan_siang')
                        ->whereDate('tanggal_kirim', $today)
                        ->count();
                    $afternoon = $sentToday === 0 ? self::sendAfternoonReport() : ['status' => 'already_sent'];
                }

                $scheduledResults = self::processScheduledReports();

                return [
                    'success' => true,
                    'morning' => $morning,
                    'afternoon' => $afternoon,
                    'scheduled' => $scheduledResults,
                ];
        }
    }

    public static function getDefaultTemplate(): string
    {
        return "📊 *LAPORAN TERPADU HARIAN (SULTAN BANTEN)*\n" .
               "📅 Tanggal: {tanggal}\n" .
               "⏰ Waktu: {waktu} WIB\n\n" .
               "🏢 *RINGKASAN TOTAL:*\n" .
               "• Total UPT: {total_upt} UPT\n" .
               "• Kapasitas: {total_kapasitas} orang\n" .
               "• Isi Penghuni: {total_penghuni} orang\n" .
               "• Persentase Hunian: {persen_hunian}%\n\n" .
               "👥 *RINCIAN PENGHUNI:*\n" .
               "• Tahanan: {total_tahanan} orang\n" .
               "• Narapidana: {total_napi} orang\n" .
               "• Overstaying: {total_overstaying} orang\n" .
               "• Integrasi (PB/CB/CMB): {total_integrasi} orang\n" .
               "• Bebas Harian: {total_bebas_harian} orang\n\n" .
               "⚖️ *KLASIFIKASI KHUSUS:*\n" .
               "• Narkoba: {narkoba_total} orang\n" .
               "• Tipikor: {tipikor} orang\n" .
               "• Teroris: {teroris} orang\n\n" .
               "_Laporan otomatis dari Sistem Sultan Banten Ditjenpas_";
    }

    public static function getTemplateVariables(): array
    {
        return [
            'tanggal' => 'Tanggal laporan saat ini',
            'waktu' => 'Jam pengiriman pesan',
            'total_upt' => 'Total jumlah UPT aktif',
            'total_kapasitas' => 'Total kapasitas seluruh UPT',
            'total_penghuni' => 'Total penghuni aktual hari ini',
            'persen_hunian' => 'Persentase tingkat hunian terhadap kapasitas',
            'total_tahanan' => 'Total seluruh tahanan',
            'total_napi' => 'Total seluruh narapidana',
            'total_overstaying' => 'Total tahanan overstaying',
            'total_integrasi' => 'Total usulan dan SK integrasi',
            'total_bebas_harian' => 'Total bebas harian hari ini',
            'narkoba_total' => 'Total kasus narkoba',
            'tipikor' => 'Total kasus tipikor',
            'teroris' => 'Total kasus terorisme',
        ];
    }

    public static function formatTemplateMessage(string $template, array $data): string
    {
        foreach ($data as $var => $value) {
            $template = str_replace('{' . $var . '}', (string)$value, $template);
        }
        return $template;
    }

    public static function getComprehensiveReportData(string $tanggal): array
    {
        $excludeKlasifikasi = ['WNA', 'Sakit Berkepanjangan', 'Lansia >70 tahun', 'Pidana Mati', 'Seumur Hidup'];

        $totalUpt = Upt::count();
        $totalKapasitas = (int) Upt::sum('kapasitas');

        $penghuni = DataPenghuni::where('tanggal', $tanggal)
            ->whereNotIn('klasifikasi_pidana', $excludeKlasifikasi)
            ->selectRaw('
                COALESCE(SUM(tahanan_dewasa_laki + tahanan_dewasa_perempuan + tahanan_anak_laki + tahanan_anak_perempuan), 0) as tot_tahanan,
                COALESCE(SUM(narapidana_dewasa_laki + narapidana_dewasa_perempuan + narapidana_anak_laki + narapidana_anak_perempuan), 0) as tot_napi
            ')
            ->first();

        $totTahanan = (int) ($penghuni->tot_tahanan ?? 0);
        $totNapi = (int) ($penghuni->tot_napi ?? 0);
        $totPenghuni = $totTahanan + $totNapi;
        $persenHunian = $totalKapasitas > 0 ? round(($totPenghuni / $totalKapasitas) * 100, 1) : 0;

        $totOverstaying = (int) DataOverstaying::where('tanggal', $tanggal)
            ->sum(DB::raw('overstaying_ai + overstaying_aii + overstaying_aiii + overstaying_aiv + overstaying_av'));

        $totIntegrasi = (int) DataIntegrasi::where('tanggal', $tanggal)
            ->sum(DB::raw('usulan_pb + usulan_cb + usulan_cmb + usulan_cmk + usulan_asimilasi + sk_terbit_pb + sk_terbit_cb + sk_terbit_cmb + sk_terbit_cmk + sk_terbit_asimilasi'));

        $totBebasHarian = (int) DataBebasHarian::where('tanggal', $tanggal)
            ->sum(DB::raw('bebas_murni + pb + cb + cmb + asimilasi + bdh + lainnya'));

        $narkoba = (int) DataPenghuni::where('tanggal', $tanggal)
            ->whereIn('klasifikasi_pidana', ['Narkoba Bandar', 'Narkoba Pengguna'])
            ->sum(DB::raw('tahanan_dewasa_laki + tahanan_dewasa_perempuan + tahanan_anak_laki + tahanan_anak_perempuan + narapidana_dewasa_laki + narapidana_dewasa_perempuan + narapidana_anak_laki + narapidana_anak_perempuan'));

        $tipikor = (int) DataPenghuni::where('tanggal', $tanggal)
            ->where('klasifikasi_pidana', 'Tipikor')
            ->sum(DB::raw('tahanan_dewasa_laki + tahanan_dewasa_perempuan + tahanan_anak_laki + tahanan_anak_perempuan + narapidana_dewasa_laki + narapidana_dewasa_perempuan + narapidana_anak_laki + narapidana_anak_perempuan'));

        $teroris = (int) DataPenghuni::where('tanggal', $tanggal)
            ->where('klasifikasi_pidana', 'Teroris')
            ->sum(DB::raw('tahanan_dewasa_laki + tahanan_dewasa_perempuan + tahanan_anak_laki + tahanan_anak_perempuan + narapidana_dewasa_laki + narapidana_dewasa_perempuan + narapidana_anak_laki + narapidana_anak_perempuan'));

        return [
            'tanggal' => formatIndonesianDate($tanggal),
            'waktu' => date('H:i'),
            'total_upt' => $totalUpt,
            'total_kapasitas' => $totalKapasitas,
            'total_penghuni' => $totPenghuni,
            'persen_hunian' => $persenHunian,
            'total_tahanan' => $totTahanan,
            'total_napi' => $totNapi,
            'total_overstaying' => $totOverstaying,
            'total_integrasi' => $totIntegrasi,
            'total_bebas_harian' => $totBebasHarian,
            'narkoba_total' => $narkoba,
            'tipikor' => $tipikor,
            'teroris' => $teroris,
        ];
    }
}
