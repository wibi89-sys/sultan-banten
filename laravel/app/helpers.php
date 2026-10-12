<?php

if (!function_exists('getOverkapasitasColorClass')) {
    function getOverkapasitasColorClass($persen) {
        if ($persen < -50) return 'bg-success text-white';           // Sangat longgar
        if ($persen < -25) return 'bg-success text-white';           // Longgar
        if ($persen < 0) return 'bg-info text-white';                // Masih ada ruang
        if ($persen == 0) return 'bg-warning text-dark';             // Pas penuh
        if ($persen <= 20) return 'bg-orange text-white';            // Sedikit over
        if ($persen <= 50) return 'bg-warning text-dark';            // Over sedang
        if ($persen <= 100) return 'bg-danger text-white';           // Over tinggi
        return 'bg-dark-red text-white';                             // Sangat over
    }
}

if (!function_exists('formatIndonesianDate')) {
    function formatIndonesianDate($date, $includeDay = true) {
        if (!$date) return '-';
        $days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        $months = [
            1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
        ];
        $ts = strtotime($date);
        $dayName = $days[(int)date('w', $ts)];
        $day = date('d', $ts);
        $month = $months[(int)date('m', $ts)];
        $year = date('Y', $ts);
        return $includeDay ? "$dayName, $day $month $year" : "$day $month $year";
    }
}

if (!function_exists('indonesianDate')) {
    function indonesianDate($date) {
        return formatIndonesianDate($date, false);
    }
}

if (!function_exists('formatDate')) {
    function formatDate($date) {
        if (empty($date)) return '-';
        $timestamp = strtotime($date);
        return date('d/m/Y', $timestamp);
    }
}

if (!function_exists('getUserUptFilter')) {
    function getUserUptFilter() {
        $user = auth()->user();
        return $user ? $user->getUserUptFilter() : null;
    }
}

if (!function_exists('getTimeRestriction')) {
    function getTimeRestriction() {
        $instansi = \App\Models\Instansi::getFirst();
        if (!$instansi) {
            return [
                'active' => false,
                'jam_mulai' => '08:00',
                'jam_selesai' => '11:00'
            ];
        }
        return [
            'active' => (bool)($instansi->batas_jam_aktif ?? false),
            'jam_mulai' => substr($instansi->jam_mulai ?? '08:00:00', 0, 5),
            'jam_selesai' => substr($instansi->jam_selesai ?? '11:00:00', 0, 5)
        ];
    }
}

if (!function_exists('isInputTimeAllowed')) {
    function isInputTimeAllowed() {
        $user = auth()->user();
        if ($user && $user->role === 'admin') {
            return ['allowed' => true, 'message' => ''];
        }

        $instansi = \App\Models\Instansi::getFirst();
        if (!$instansi || empty($instansi->batas_jam_aktif)) {
            return ['allowed' => true, 'message' => ''];
        }

        $jamMulai = $instansi->jam_mulai ?? '08:00:00';
        $jamSelesai = $instansi->jam_selesai ?? '11:00:00';
        $currentTime = date('H:i:s');

        if ($currentTime >= $jamMulai && $currentTime <= $jamSelesai) {
            return ['allowed' => true, 'message' => ''];
        }

        $jamMulaiDisplay = date('H:i', strtotime($jamMulai));
        $jamSelesaiDisplay = date('H:i', strtotime($jamSelesai));

        return [
            'allowed' => false,
            'message' => "Input data hanya dapat dilakukan pada pukul {$jamMulaiDisplay} - {$jamSelesaiDisplay}. Saat ini pukul " . date('H:i')
        ];
    }
}
