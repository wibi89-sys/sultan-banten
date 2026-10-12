<?php

namespace App\Http\Controllers;

use App\Models\Upt;
use Illuminate\Http\Request;

class KroscekController extends Controller
{
    public function index(Request $request)
    {
        $filterTanggal = $request->query('tanggal', date('Y-m-d'));

        $kroscekData = Upt::select('upt.id as upt_id', 'upt.nama_upt')
            ->selectRaw("(SELECT SUM(tahanan_dewasa_laki + tahanan_dewasa_perempuan + tahanan_anak_laki + tahanan_anak_perempuan) 
                          FROM data_penghuni dp 
                          WHERE dp.upt_id = upt.id AND dp.tanggal = ? 
                          AND dp.klasifikasi_pidana NOT IN ('WNA', 'Sakit Berkepanjangan', 'Lansia >70 tahun', 'Pidana Mati', 'Seumur Hidup')) as jml_penghuni", [$filterTanggal])
            ->selectRaw("(SELECT SUM(tahanan_ai + tahanan_aii + tahanan_aiii + tahanan_aiv + tahanan_av) 
                          FROM data_overstaying do 
                          WHERE do.upt_id = upt.id AND do.tanggal = ?) as jml_overstaying", [$filterTanggal])
            ->selectRaw("(SELECT COUNT(*) 
                          FROM data_bebas_harian dbh 
                          WHERE dbh.upt_id = upt.id AND dbh.tanggal = ?) as input_bebas_count", [$filterTanggal])
            ->selectRaw("(SELECT SUM(bebas_murni + pb + cb + cmb + asimilasi + bdh + lainnya) 
                          FROM data_bebas_harian dbh 
                          WHERE dbh.upt_id = upt.id AND dbh.tanggal = ?) as total_bebas", [$filterTanggal])
            ->orderBy('upt.nama_upt', 'asc')
            ->get();

        $totalUpt = $kroscekData->count();
        $totalSesuai = 0;
        $totalTidakSesuai = 0;
        $totalBebasSudah = 0;
        $totalBebasBelum = 0;
        $sumTotalBebas = 0;

        foreach ($kroscekData as $row) {
            $jmlP = $row->jml_penghuni !== null ? (int)$row->jml_penghuni : null;
            $jmlO = $row->jml_overstaying !== null ? (int)$row->jml_overstaying : null;

            if ($jmlP !== null && $jmlO !== null && $jmlP === $jmlO) {
                $totalSesuai++;
            } else {
                $totalTidakSesuai++;
            }

            if ((int)$row->input_bebas_count > 0) {
                $totalBebasSudah++;
                $sumTotalBebas += (int)($row->total_bebas ?? 0);
            } else {
                $totalBebasBelum++;
            }
        }

        return view('kroscek.index', compact(
            'kroscekData',
            'filterTanggal',
            'totalUpt',
            'totalSesuai',
            'totalTidakSesuai',
            'totalBebasSudah',
            'totalBebasBelum',
            'sumTotalBebas'
        ));
    }
}
