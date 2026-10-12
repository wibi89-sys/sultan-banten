@extends('layouts.app')

@section('title', 'Data Penghuni - Sultan Banten')
@section('page_title')
    <i class="fas fa-users me-2"></i>Data Penghuni
@endsection

@push('styles')
<style>
    /* Compact Modal */
    .modal-xl { max-width: 900px; }
    .modal-body { padding: 16px; }
    .modal-header { padding: 10px 15px; }
    .modal-footer { padding: 10px 15px; }
    
    /* Input Table Styles */
    .input-container {
        max-height: 450px;
        overflow: auto;
        position: relative;
        background: var(--card-bg, #fff);
    }
    
    .input-table {
        border-collapse: separate;
        border-spacing: 0;
        width: 100%;
    }
    
    .input-table th, .input-table td {
        padding: 2px 3px !important;
        text-align: center;
    }
    
    .input-table .group-header {
        padding: 4px 8px !important;
        font-weight: 700;
        text-align: left;
    }
    .input-table .group-header.umum { background: #e0e7ff; color: #3730a3; }
    .input-table .group-header.khusus { background: #fee2e2; color: #991b1b; }
    .input-table .group-header.uu-ite { background: #e0f2fe; color: #075985; }
    .input-table .group-header.lainnya { background: #fef3c7; color: #92400e; }
    
    .input-table thead th {
        position: sticky;
        z-index: 10;
        background: var(--table-header-bg, #f8fafc);
        border-bottom: 2px solid var(--border-color, #e2e8f0) !important;
    }
    
    .input-table thead th.tahanan-header {
        background: linear-gradient(135deg, #ec4899 0%, #f43f5e 100%) !important;
        color: white !important;
    }
    
    .input-table thead th.narapidana-header {
        background: linear-gradient(135deg, #06b6d4 0%, #0ea5e9 100%) !important;
        color: white !important;
    }
    
    .input-table thead tr:nth-child(1) th {
        top: 0;
        z-index: 12;
    }
    
    .input-table thead tr:nth-child(2) th {
        top: 26px;
        z-index: 11;
        background: var(--gray-50, #f8fafc);
    }
    
    .input-table input {
        width: 45px !important;
        padding: 2px 2px !important;
        border: 1px solid var(--input-border, #cbd5e1);
        border-radius: 4px;
        text-align: center;
        font-size: 12px;
        transition: all 0.2s;
        background: var(--input-bg, #fff);
        color: var(--text-primary, #1e293b);
    }
    
    .input-table input:focus {
        outline: none;
        border-color: #6366f1;
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2);
    }
    
    .badge-klasifikasi {
        font-size: 11px;
        padding: 4px 8px;
    }
    
    .badge-lainnya {
        background: linear-gradient(135deg, #fb923c 0%, #f97316 100%);
        color: white !important;
    }
    
    .row-lainnya {
        background-color: #fff8e1 !important;
    }
    
    .row-lainnya td {
        color: #856404 !important;
    }
    
    [data-theme="dark"] .row-lainnya {
        background-color: rgba(251, 146, 60, 0.15) !important;
    }
    
    [data-theme="dark"] .row-lainnya td {
        color: #fb923c !important;
    }

    .data-filter-tabs {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        margin-bottom: 1rem;
        padding-bottom: 0.5rem;
        border-bottom: 1px solid var(--border-color, #e2e8f0);
    }

    .filter-tab {
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 500;
        text-decoration: none;
        color: var(--text-secondary, #64748b);
        background: var(--card-bg, #fff);
        border: 1px solid var(--border-color, #e2e8f0);
        transition: all 0.2s;
    }

    .filter-tab:hover {
        color: #2563eb;
        border-color: #2563eb;
    }

    .filter-tab.active {
        background: #2563eb;
        color: white !important;
        border-color: #2563eb;
    }

    .filter-tab.group-tab.active {
        background: #4f46e5;
        border-color: #4f46e5;
    }

    .filter-tab.lainnya-tab.active {
        background: #ea580c;
        border-color: #ea580c;
    }
</style>
@endpush

@section('content')
<div class="content-card">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
        <div>
            <h5 class="mb-1 fw-bold"><i class="fas fa-users text-primary me-2"></i>Data Penghuni Seluruh UPT</h5>
            <p class="text-muted small mb-0">Manajemen data penghuni harian berdasarkan klasifikasi pidana, tahanan, dan narapidana</p>
        </div>
        <div class="d-flex align-items-center gap-2 mt-2 mt-md-0">
            @if($timeRestriction['active'] && !$canInput)
            <span class="badge bg-warning text-dark px-3 py-2">
                <i class="fas fa-clock me-1"></i>
                Input: {{ $timeRestriction['jam_mulai'] }} - {{ $timeRestriction['jam_selesai'] }}
            </span>
            @endif
            <button class="btn btn-primary btn-sm px-3" data-bs-toggle="modal" data-bs-target="#modalAdd" {{ !$canInput ? 'disabled' : '' }}>
                <i class="fas fa-plus me-1"></i> Input Data
            </button>
        </div>
    </div>
    
    @if($timeRestriction['active'] && !$canInput && $timeRestrictionMessage)
    <div class="alert alert-warning py-2 mb-3">
        <i class="fas fa-exclamation-triangle me-1"></i>
        {{ $timeRestrictionMessage }}
    </div>
    @endif

    <!-- Data Filter Tabs -->
    <div class="data-filter-tabs">
        <a href="{{ request()->fullUrlWithQuery(['tab' => 'all', 'page' => 1]) }}" class="filter-tab {{ $selectedTab === 'all' ? 'active' : '' }}">Semua</a>
        <a href="{{ request()->fullUrlWithQuery(['tab' => 'utama', 'page' => 1]) }}" class="filter-tab group-tab {{ $selectedTab === 'utama' ? 'active' : '' }}">
            <i class="fas fa-gavel me-1"></i>Data Penghuni (Utama)
        </a>
        @foreach ($klasifikasiUmum as $k)
        <a href="{{ request()->fullUrlWithQuery(['tab' => $k, 'page' => 1]) }}" class="filter-tab {{ $selectedTab === $k ? 'active' : '' }}">{{ $k }}</a>
        @endforeach
        @foreach ($klasifikasiKhusus as $k)
        <a href="{{ request()->fullUrlWithQuery(['tab' => $k, 'page' => 1]) }}" class="filter-tab {{ $selectedTab === $k ? 'active' : '' }}">{{ $k }}</a>
        @endforeach
        <a href="{{ request()->fullUrlWithQuery(['tab' => 'UU ITE', 'page' => 1]) }}" class="filter-tab {{ $selectedTab === 'UU ITE' ? 'active' : '' }}">UU ITE</a>
        <a href="{{ request()->fullUrlWithQuery(['tab' => 'Pidana Mati', 'page' => 1]) }}" class="filter-tab {{ $selectedTab === 'Pidana Mati' ? 'active' : '' }}">Pidana Mati</a>
        <a href="{{ request()->fullUrlWithQuery(['tab' => 'Seumur Hidup', 'page' => 1]) }}" class="filter-tab {{ $selectedTab === 'Seumur Hidup' ? 'active' : '' }}">Seumur Hidup</a>
        <a href="{{ request()->fullUrlWithQuery(['tab' => 'Lansia >70 tahun', 'page' => 1]) }}" class="filter-tab {{ $selectedTab === 'Lansia >70 tahun' ? 'active' : '' }}">Lansia</a>
        <a href="{{ request()->fullUrlWithQuery(['tab' => 'Sakit Berkepanjangan', 'page' => 1]) }}" class="filter-tab {{ $selectedTab === 'Sakit Berkepanjangan' ? 'active' : '' }}">Sakit</a>
        <a href="{{ request()->fullUrlWithQuery(['tab' => 'WNA', 'page' => 1]) }}" class="filter-tab {{ $selectedTab === 'WNA' ? 'active' : '' }}">WNA</a>
        <a href="{{ request()->fullUrlWithQuery(['tab' => 'lainnya', 'page' => 1]) }}" class="filter-tab lainnya-tab {{ $selectedTab === 'lainnya' ? 'active' : '' }}">
            <i class="fas fa-ellipsis-h me-1"></i>Lainnya
        </a>
    </div>

    <!-- Filter Tanggal & UPT Toolbar -->
    <div class="card mb-3 bg-light border-0">
        <div class="card-body py-2">
            <form method="GET" action="{{ route('penghuni.index') }}" class="row g-2 align-items-end">
                <input type="hidden" name="tab" value="{{ $selectedTab }}">
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Tanggal Mulai</label>
                    <input type="date" name="tanggal_mulai" class="form-control form-control-sm" value="{{ $tanggalMulai }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Tanggal Sampai</label>
                    <input type="date" name="tanggal_sampai" class="form-control form-control-sm" value="{{ $tanggalSampai }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">UPT</label>
                    <select name="upt_id" class="form-select form-select-sm" {{ $userUptFilter ? 'disabled' : '' }}>
                        <option value="0">-- Semua UPT --</option>
                        @foreach ($uptList as $upt)
                        <option value="{{ $upt->id }}" {{ $filterUpt == $upt->id ? 'selected' : '' }}>{{ $upt->nama_upt }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-5 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm flex-fill">
                        <i class="fas fa-search me-1"></i>Filter
                    </button>
                    <a href="{{ route('penghuni.index', ['tab' => $selectedTab]) }}" class="btn btn-secondary btn-sm flex-fill">Reset</a>
                    <a href="{{ route('laporan.excel', array_filter(['type' => 'penghuni', 'tanggal_mulai' => $tanggalMulai, 'tanggal_akhir' => $tanggalSampai, 'upt_id' => $filterUpt])) }}" target="_blank" class="btn btn-success btn-sm flex-fill">
                        <i class="fas fa-file-excel me-1"></i>Excel
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover table-sm align-middle mb-0">
            <thead>
                <tr class="text-center align-middle">
                    <th rowspan="2" width="4%">No</th>
                    <th rowspan="2" width="9%">Tanggal</th>
                    <th rowspan="2" class="text-start" width="16%">UPT</th>
                    <th rowspan="2" width="14%">Klasifikasi</th>
                    <th colspan="4" style="background:#fce4ec; color:#880e4f;">Tahanan</th>
                    <th colspan="4" style="background:#e3f2fd; color:#0d47a1;">Narapidana</th>
                    <th rowspan="2" width="7%">Total</th>
                    <th rowspan="2" width="8%">Aksi</th>
                </tr>
                <tr class="text-center small">
                    <th style="background:#fce4ec; color:#880e4f;" width="4%">DL</th>
                    <th style="background:#fce4ec; color:#880e4f;" width="4%">DP</th>
                    <th style="background:#fce4ec; color:#880e4f;" width="4%">AL</th>
                    <th style="background:#fce4ec; color:#880e4f;" width="4%">AP</th>
                    <th style="background:#e3f2fd; color:#0d47a1;" width="4%">DL</th>
                    <th style="background:#e3f2fd; color:#0d47a1;" width="4%">DP</th>
                    <th style="background:#e3f2fd; color:#0d47a1;" width="4%">AL</th>
                    <th style="background:#e3f2fd; color:#0d47a1;" width="4%">AP</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $grandTotal = 0;
                    $totalTahananDL = 0; $totalTahananDP = 0; $totalTahananAL = 0; $totalTahananAP = 0;
                    $totalNarapidanaDL = 0; $totalNarapidanaDP = 0; $totalNarapidanaAL = 0; $totalNarapidanaAP = 0;
                @endphp
                @forelse ($penghuniList as $data)
                    @php
                        $total = $data->tahanan_dewasa_laki + $data->tahanan_dewasa_perempuan + 
                                 $data->tahanan_anak_laki + $data->tahanan_anak_perempuan +
                                 $data->narapidana_dewasa_laki + $data->narapidana_dewasa_perempuan +
                                 $data->narapidana_anak_laki + $data->narapidana_anak_perempuan;
                        
                        $grandTotal += $total;
                        $totalTahananDL += $data->tahanan_dewasa_laki;
                        $totalTahananDP += $data->tahanan_dewasa_perempuan;
                        $totalTahananAL += $data->tahanan_anak_laki;
                        $totalTahananAP += $data->tahanan_anak_perempuan;
                        $totalNarapidanaDL += $data->narapidana_dewasa_laki;
                        $totalNarapidanaDP += $data->narapidana_dewasa_perempuan;
                        $totalNarapidanaAL += $data->narapidana_anak_laki;
                        $totalNarapidanaAP += $data->narapidana_anak_perempuan;
                        
                        $isLainnya = in_array($data->klasifikasi_pidana, $klasifikasiLainnya);
                    @endphp
                    <tr class="{{ $isLainnya ? 'row-lainnya' : '' }}">
                        <td class="text-center text-muted">{{ $loop->iteration + ($penghuniList->currentPage() - 1) * $penghuniList->perPage() }}</td>
                        <td class="text-center">{{ \Carbon\Carbon::parse($data->tanggal)->format('d/m/y') }}</td>
                        <td class="fw-semibold text-dark">{{ $data->upt->nama_upt ?? '-' }}</td>
                        <td class="text-center">
                            <span class="badge {{ $isLainnya ? 'badge-lainnya' : 'bg-secondary' }} badge-klasifikasi">
                                {{ $data->klasifikasi_pidana }}
                            </span>
                        </td>
                        <td class="text-center">{{ $data->tahanan_dewasa_laki }}</td>
                        <td class="text-center">{{ $data->tahanan_dewasa_perempuan }}</td>
                        <td class="text-center">{{ $data->tahanan_anak_laki }}</td>
                        <td class="text-center">{{ $data->tahanan_anak_perempuan }}</td>
                        <td class="text-center">{{ $data->narapidana_dewasa_laki }}</td>
                        <td class="text-center">{{ $data->narapidana_dewasa_perempuan }}</td>
                        <td class="text-center">{{ $data->narapidana_anak_laki }}</td>
                        <td class="text-center">{{ $data->narapidana_anak_perempuan }}</td>
                        <td class="text-center fw-bold">{{ number_format($total, 0, ',', '.') }}</td>
                        <td class="text-center">
                            <button class="btn btn-warning btn-sm py-0 px-1.5" onclick="editData({{ json_encode($data) }})" title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                            <form action="{{ route('penghuni.destroy', $data->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus data penghuni ini?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm py-0 px-1.5" title="Hapus">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="14" class="text-center text-muted py-4">Belum ada data penghuni.</td>
                    </tr>
                @endforelse
            </tbody>
            @if($penghuniList->count() > 0)
            <tfoot>
                <tr style="background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%); color: white; font-weight: bold;">
                    <td colspan="4" class="text-center">TOTAL (HALAMAN INI)</td>
                    <td class="text-center">{{ number_format($totalTahananDL, 0, ',', '.') }}</td>
                    <td class="text-center">{{ number_format($totalTahananDP, 0, ',', '.') }}</td>
                    <td class="text-center">{{ number_format($totalTahananAL, 0, ',', '.') }}</td>
                    <td class="text-center">{{ number_format($totalTahananAP, 0, ',', '.') }}</td>
                    <td class="text-center">{{ number_format($totalNarapidanaDL, 0, ',', '.') }}</td>
                    <td class="text-center">{{ number_format($totalNarapidanaDP, 0, ',', '.') }}</td>
                    <td class="text-center">{{ number_format($totalNarapidanaAL, 0, ',', '.') }}</td>
                    <td class="text-center">{{ number_format($totalNarapidanaAP, 0, ',', '.') }}</td>
                    <td class="text-center">{{ number_format($grandTotal, 0, ',', '.') }}</td>
                    <td></td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>

    <!-- Pagination -->
    <div class="mt-3">
        {{ $penghuniList->links('pagination::bootstrap-5') }}
    </div>
</div>

<!-- Modal Add - Input Matrix Massal -->
<div class="modal fade" id="modalAdd" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header py-2 bg-primary text-white">
                <h5 class="modal-title fs-6"><i class="fas fa-plus-circle me-2"></i>Input Data Penghuni Seluruh Klasifikasi</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formInputMassal" method="POST" action="{{ route('penghuni.store') }}">
                @csrf
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6 mb-2 mb-md-0">
                            <label class="form-label small fw-semibold">UPT <span class="text-danger">*</span></label>
                            <select name="upt_id" class="form-select form-select-sm" required>
                                <option value="">-- Pilih UPT --</option>
                                @foreach ($uptList as $upt)
                                <option value="{{ $upt->id }}" {{ $userUptFilter == $upt->id ? 'selected' : '' }}>{{ $upt->nama_upt }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Tanggal <span class="text-danger">*</span></label>
                            <input type="date" name="tanggal" class="form-control form-control-sm" value="{{ date('Y-m-d') }}" required>
                        </div>
                    </div>
                    
                    <!-- Banner Informasi Auto-Copy Data Kemarin -->
                    <div id="previousDataNotice" class="alert alert-info py-2 px-3 mb-2 small d-none d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <div>
                            <i class="fas fa-magic text-primary me-2"></i>
                            <span id="previousDataText">Memeriksa data hari kemarin...</span>
                        </div>
                        <div class="d-flex gap-1 ms-auto">
                            <button type="button" class="btn btn-outline-danger btn-sm py-0 px-2" id="btnResetZero" title="Kosongkan semua input ke 0">
                                <i class="fas fa-eraser me-1"></i>Reset ke 0
                            </button>
                            <button type="button" class="btn btn-outline-primary btn-sm py-0 px-2" id="btnReloadPrevious" title="Muat ulang data hari kemarin">
                                <i class="fas fa-sync-alt me-1"></i>Muat Ulang
                            </button>
                        </div>
                    </div>
                    
                    <div class="input-container border rounded">
                        <table class="input-table mb-0">
                            <thead class="sticky-top">
                                <tr>
                                    <th rowspan="2" style="width: 150px; vertical-align: middle;">Klasifikasi</th>
                                    <th colspan="4" class="tahanan-header">TAHANAN</th>
                                    <th colspan="4" class="narapidana-header">NARAPIDANA</th>
                                    <th rowspan="2" style="width: 55px; vertical-align: middle;">Total</th>
                                </tr>
                                <tr>
                                    <th>DL</th><th>DP</th><th>AL</th><th>AP</th>
                                    <th>DL</th><th>DP</th><th>AL</th><th>AP</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Pidana Umum -->
                                <tr>
                                    <td colspan="10" class="group-header umum text-uppercase py-1"><i class="fas fa-user me-1"></i>Pidana Umum</td>
                                </tr>
                                @foreach ($klasifikasiUmum as $k)
                                @php $key = $klasifikasiKeys[$k]; @endphp
                                <tr>
                                    <td class="text-start px-2 fw-semibold">{{ $k }}</td>
                                    <td><input type="number" name="tahanan_dl_{{ $key }}" value="0" min="0" data-row="{{ $key }}" oninput="updateRowTotal('{{ $key }}')"></td>
                                    <td><input type="number" name="tahanan_dp_{{ $key }}" value="0" min="0" data-row="{{ $key }}" oninput="updateRowTotal('{{ $key }}')"></td>
                                    <td><input type="number" name="tahanan_al_{{ $key }}" value="0" min="0" data-row="{{ $key }}" oninput="updateRowTotal('{{ $key }}')"></td>
                                    <td><input type="number" name="tahanan_ap_{{ $key }}" value="0" min="0" data-row="{{ $key }}" oninput="updateRowTotal('{{ $key }}')"></td>
                                    <td><input type="number" name="narapidana_dl_{{ $key }}" value="0" min="0" data-row="{{ $key }}" oninput="updateRowTotal('{{ $key }}')"></td>
                                    <td><input type="number" name="narapidana_dp_{{ $key }}" value="0" min="0" data-row="{{ $key }}" oninput="updateRowTotal('{{ $key }}')"></td>
                                    <td><input type="number" name="narapidana_al_{{ $key }}" value="0" min="0" data-row="{{ $key }}" oninput="updateRowTotal('{{ $key }}')"></td>
                                    <td><input type="number" name="narapidana_ap_{{ $key }}" value="0" min="0" data-row="{{ $key }}" oninput="updateRowTotal('{{ $key }}')"></td>
                                    <td class="row-total fw-bold" id="total_{{ $key }}">0</td>
                                </tr>
                                @endforeach
                                
                                <!-- Pidana Khusus -->
                                <tr>
                                    <td colspan="10" class="group-header khusus text-uppercase py-1"><i class="fas fa-gavel me-1"></i>Pidana Khusus</td>
                                </tr>
                                @foreach ($klasifikasiKhusus as $k)
                                @php $key = $klasifikasiKeys[$k]; @endphp
                                <tr>
                                    <td class="text-start px-2 fw-semibold">{{ $k }}</td>
                                    <td><input type="number" name="tahanan_dl_{{ $key }}" value="0" min="0" data-row="{{ $key }}" oninput="updateRowTotal('{{ $key }}')"></td>
                                    <td><input type="number" name="tahanan_dp_{{ $key }}" value="0" min="0" data-row="{{ $key }}" oninput="updateRowTotal('{{ $key }}')"></td>
                                    <td><input type="number" name="tahanan_al_{{ $key }}" value="0" min="0" data-row="{{ $key }}" oninput="updateRowTotal('{{ $key }}')"></td>
                                    <td><input type="number" name="tahanan_ap_{{ $key }}" value="0" min="0" data-row="{{ $key }}" oninput="updateRowTotal('{{ $key }}')"></td>
                                    <td><input type="number" name="narapidana_dl_{{ $key }}" value="0" min="0" data-row="{{ $key }}" oninput="updateRowTotal('{{ $key }}')"></td>
                                    <td><input type="number" name="narapidana_dp_{{ $key }}" value="0" min="0" data-row="{{ $key }}" oninput="updateRowTotal('{{ $key }}')"></td>
                                    <td><input type="number" name="narapidana_al_{{ $key }}" value="0" min="0" data-row="{{ $key }}" oninput="updateRowTotal('{{ $key }}')"></td>
                                    <td><input type="number" name="narapidana_ap_{{ $key }}" value="0" min="0" data-row="{{ $key }}" oninput="updateRowTotal('{{ $key }}')"></td>
                                    <td class="row-total fw-bold" id="total_{{ $key }}">0</td>
                                </tr>
                                @endforeach
                                
                                <!-- UU ITE -->
                                <tr>
                                    <td colspan="10" class="group-header uu-ite text-uppercase py-1"><i class="fas fa-laptop me-1"></i>UU ITE</td>
                                </tr>
                                @foreach ($klasifikasiUUITE as $k)
                                @php $key = $klasifikasiKeys[$k]; @endphp
                                <tr>
                                    <td class="text-start px-2 fw-semibold">{{ $k }}</td>
                                    <td><input type="number" name="tahanan_dl_{{ $key }}" value="0" min="0" data-row="{{ $key }}" oninput="updateRowTotal('{{ $key }}')"></td>
                                    <td><input type="number" name="tahanan_dp_{{ $key }}" value="0" min="0" data-row="{{ $key }}" oninput="updateRowTotal('{{ $key }}')"></td>
                                    <td><input type="number" name="tahanan_al_{{ $key }}" value="0" min="0" data-row="{{ $key }}" oninput="updateRowTotal('{{ $key }}')"></td>
                                    <td><input type="number" name="tahanan_ap_{{ $key }}" value="0" min="0" data-row="{{ $key }}" oninput="updateRowTotal('{{ $key }}')"></td>
                                    <td><input type="number" name="narapidana_dl_{{ $key }}" value="0" min="0" data-row="{{ $key }}" oninput="updateRowTotal('{{ $key }}')"></td>
                                    <td><input type="number" name="narapidana_dp_{{ $key }}" value="0" min="0" data-row="{{ $key }}" oninput="updateRowTotal('{{ $key }}')"></td>
                                    <td><input type="number" name="narapidana_al_{{ $key }}" value="0" min="0" data-row="{{ $key }}" oninput="updateRowTotal('{{ $key }}')"></td>
                                    <td><input type="number" name="narapidana_ap_{{ $key }}" value="0" min="0" data-row="{{ $key }}" oninput="updateRowTotal('{{ $key }}')"></td>
                                    <td class="row-total fw-bold" id="total_{{ $key }}">0</td>
                                </tr>
                                @endforeach
                                
                                <!-- Lainnya -->
                                <tr>
                                    <td colspan="10" class="group-header lainnya text-uppercase py-1"><i class="fas fa-exclamation-triangle me-1"></i>Lainnya</td>
                                </tr>
                                @foreach ($klasifikasiLainnya as $k)
                                @php $key = $klasifikasiKeys[$k]; @endphp
                                <tr class="row-lainnya">
                                    <td class="text-start px-2 fw-semibold">{{ $k }}</td>
                                    <td><input type="number" name="tahanan_dl_{{ $key }}" value="0" min="0" data-row="{{ $key }}" oninput="updateRowTotal('{{ $key }}')"></td>
                                    <td><input type="number" name="tahanan_dp_{{ $key }}" value="0" min="0" data-row="{{ $key }}" oninput="updateRowTotal('{{ $key }}')"></td>
                                    <td><input type="number" name="tahanan_al_{{ $key }}" value="0" min="0" data-row="{{ $key }}" oninput="updateRowTotal('{{ $key }}')"></td>
                                    <td><input type="number" name="tahanan_ap_{{ $key }}" value="0" min="0" data-row="{{ $key }}" oninput="updateRowTotal('{{ $key }}')"></td>
                                    <td><input type="number" name="narapidana_dl_{{ $key }}" value="0" min="0" data-row="{{ $key }}" oninput="updateRowTotal('{{ $key }}')"></td>
                                    <td><input type="number" name="narapidana_dp_{{ $key }}" value="0" min="0" data-row="{{ $key }}" oninput="updateRowTotal('{{ $key }}')"></td>
                                    <td><input type="number" name="narapidana_al_{{ $key }}" value="0" min="0" data-row="{{ $key }}" oninput="updateRowTotal('{{ $key }}')"></td>
                                    <td><input type="number" name="narapidana_ap_{{ $key }}" value="0" min="0" data-row="{{ $key }}" oninput="updateRowTotal('{{ $key }}')"></td>
                                    <td class="row-total fw-bold" id="total_{{ $key }}">0</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Grand Total Summary -->
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <div class="p-2 rounded border bg-primary bg-opacity-10">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="small fw-bold text-primary">TOTAL DATA PENGHUNI UTAMA:</span>
                                    <span class="badge bg-primary fs-6 px-3 py-1" id="grandTotalUtama">0</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mt-2 mt-md-0">
                            <div class="p-2 rounded border bg-warning bg-opacity-10">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="small fw-bold text-warning">TOTAL LAINNYA:</span>
                                    <span class="badge bg-warning text-dark fs-6 px-3 py-1" id="grandTotalLainnya">0</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary btn-sm px-4">
                        <i class="fas fa-save me-1"></i> Simpan Semua Data
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit -->
<div class="modal fade" id="modalEdit" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header py-2 bg-warning text-dark">
                <h5 class="modal-title fs-6"><i class="fas fa-edit me-2"></i>Edit Data Penghuni</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="formEdit">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <input type="hidden" name="id" id="edit_id">
                    <input type="hidden" name="klasifikasi_pidana" id="edit_klasifikasi" value="">
                    
                    <div class="row mb-3">
                        <div class="col-md-6 mb-2 mb-md-0">
                            <label class="form-label small fw-semibold">UPT <span class="text-danger">*</span></label>
                            <select name="upt_id" id="edit_upt_id" class="form-select form-select-sm" required>
                                @foreach ($uptList as $upt)
                                <option value="{{ $upt->id }}">{{ $upt->nama_upt }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Tanggal <span class="text-danger">*</span></label>
                            <input type="date" name="tanggal" id="edit_tanggal" class="form-control form-control-sm" required>
                        </div>
                    </div>
                    
                    <div class="alert alert-info py-2 mb-3" id="editKlasifikasiAlert">
                        <i class="fas fa-info-circle me-1"></i>
                        Klasifikasi: <strong id="edit_klasifikasi_display"></strong>
                    </div>
                    
                    <!-- Tahanan Input -->
                    <div class="card mb-3 border-danger border-opacity-25">
                        <div class="card-header bg-danger bg-opacity-10 py-1.5 fw-semibold small text-danger">
                            <i class="fas fa-user-lock me-1"></i> TAHANAN
                        </div>
                        <div class="card-body py-2">
                            <div class="row g-2">
                                <div class="col-6 col-md-3">
                                    <label class="form-label small text-muted mb-1">Dewasa Laki-laki</label>
                                    <input type="number" name="tahanan_dewasa_laki" id="edit_tahanan_dl" class="form-control form-control-sm" value="0" min="0">
                                </div>
                                <div class="col-6 col-md-3">
                                    <label class="form-label small text-muted mb-1">Dewasa Perempuan</label>
                                    <input type="number" name="tahanan_dewasa_perempuan" id="edit_tahanan_dp" class="form-control form-control-sm" value="0" min="0">
                                </div>
                                <div class="col-6 col-md-3">
                                    <label class="form-label small text-muted mb-1">Anak Laki-laki</label>
                                    <input type="number" name="tahanan_anak_laki" id="edit_tahanan_al" class="form-control form-control-sm" value="0" min="0">
                                </div>
                                <div class="col-6 col-md-3">
                                    <label class="form-label small text-muted mb-1">Anak Perempuan</label>
                                    <input type="number" name="tahanan_anak_perempuan" id="edit_tahanan_ap" class="form-control form-control-sm" value="0" min="0">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Narapidana Input -->
                    <div class="card mb-3 border-primary border-opacity-25">
                        <div class="card-header bg-primary bg-opacity-10 py-1.5 fw-semibold small text-primary">
                            <i class="fas fa-user-times me-1"></i> NARAPIDANA
                        </div>
                        <div class="card-body py-2">
                            <div class="row g-2">
                                <div class="col-6 col-md-3">
                                    <label class="form-label small text-muted mb-1">Dewasa Laki-laki</label>
                                    <input type="number" name="narapidana_dewasa_laki" id="edit_narapidana_dl" class="form-control form-control-sm" value="0" min="0">
                                </div>
                                <div class="col-6 col-md-3">
                                    <label class="form-label small text-muted mb-1">Dewasa Perempuan</label>
                                    <input type="number" name="narapidana_dewasa_perempuan" id="edit_narapidana_dp" class="form-control form-control-sm" value="0" min="0">
                                </div>
                                <div class="col-6 col-md-3">
                                    <label class="form-label small text-muted mb-1">Anak Laki-laki</label>
                                    <input type="number" name="narapidana_anak_laki" id="edit_narapidana_al" class="form-control form-control-sm" value="0" min="0">
                                </div>
                                <div class="col-6 col-md-3">
                                    <label class="form-label small text-muted mb-1">Anak Perempuan</label>
                                    <input type="number" name="narapidana_anak_perempuan" id="edit_narapidana_ap" class="form-control form-control-sm" value="0" min="0">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div>
                        <label class="form-label small">Keterangan</label>
                        <textarea name="keterangan" id="edit_keterangan" class="form-control form-control-sm" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary btn-sm px-3">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const klasifikasiUtama = @json(array_map(function($k) use ($klasifikasiKeys) { return $klasifikasiKeys[$k]; }, $klasifikasiUtama));
    const klasifikasiLainnyaKeys = @json(array_map(function($k) use ($klasifikasiKeys) { return $klasifikasiKeys[$k]; }, $klasifikasiLainnya));
    const lainnyaList = @json($klasifikasiLainnya);

    function updateRowTotal(key) {
        const inputs = document.querySelectorAll('[data-row="' + key + '"]');
        let total = 0;
        inputs.forEach(input => {
            total += parseInt(input.value) || 0;
        });
        const el = document.getElementById('total_' + key);
        if (el) el.textContent = total;
        updateGrandTotals();
    }

    function updateGrandTotals() {
        let grandTotalUtama = 0;
        klasifikasiUtama.forEach(key => {
            const el = document.getElementById('total_' + key);
            if (el) grandTotalUtama += parseInt(el.textContent) || 0;
        });
        document.getElementById('grandTotalUtama').textContent = grandTotalUtama;

        let grandTotalLainnya = 0;
        klasifikasiLainnyaKeys.forEach(key => {
            const el = document.getElementById('total_' + key);
            if (el) grandTotalLainnya += parseInt(el.textContent) || 0;
        });
        document.getElementById('grandTotalLainnya').textContent = grandTotalLainnya;
    }

    function editData(data) {
        document.getElementById('formEdit').action = '/penghuni/' + data.id;
        document.getElementById('edit_id').value = data.id;
        document.getElementById('edit_upt_id').value = data.upt_id;
        document.getElementById('edit_tanggal').value = data.tanggal ? data.tanggal.substring(0, 10) : '';
        document.getElementById('edit_klasifikasi').value = data.klasifikasi_pidana;
        document.getElementById('edit_klasifikasi_display').textContent = data.klasifikasi_pidana;

        const alertBox = document.getElementById('editKlasifikasiAlert');
        if (lainnyaList.includes(data.klasifikasi_pidana)) {
            alertBox.className = 'alert alert-warning py-2 mb-3';
            alertBox.innerHTML = '<i class="fas fa-exclamation-triangle me-1"></i> Klasifikasi: <strong>' + data.klasifikasi_pidana + '</strong> <small class="text-muted">(Tidak termasuk perhitungan statistik utama)</small>';
        } else {
            alertBox.className = 'alert alert-info py-2 mb-3';
            alertBox.innerHTML = '<i class="fas fa-info-circle me-1"></i> Klasifikasi: <strong>' + data.klasifikasi_pidana + '</strong>';
        }

        document.getElementById('edit_tahanan_dl').value = data.tahanan_dewasa_laki;
        document.getElementById('edit_tahanan_dp').value = data.tahanan_dewasa_perempuan;
        document.getElementById('edit_tahanan_al').value = data.tahanan_anak_laki;
        document.getElementById('edit_tahanan_ap').value = data.tahanan_anak_perempuan;

        document.getElementById('edit_narapidana_dl').value = data.narapidana_dewasa_laki;
        document.getElementById('edit_narapidana_dp').value = data.narapidana_dewasa_perempuan;
        document.getElementById('edit_narapidana_al').value = data.narapidana_anak_laki;
        document.getElementById('edit_narapidana_ap').value = data.narapidana_anak_perempuan;

        document.getElementById('edit_keterangan').value = data.keterangan || '';

        new bootstrap.Modal(document.getElementById('modalEdit')).show();
    }

    // Auto-Fetch & Pre-Fill Data Hari Kemarin
    const modalAddEl = document.getElementById('modalAdd');
    const selectUpt = modalAddEl ? modalAddEl.querySelector('select[name="upt_id"]') : null;
    const inputTanggal = modalAddEl ? modalAddEl.querySelector('input[name="tanggal"]') : null;
    const noticeBox = document.getElementById('previousDataNotice');
    const noticeText = document.getElementById('previousDataText');
    const btnResetZero = document.getElementById('btnResetZero');
    const btnReloadPrevious = document.getElementById('btnReloadPrevious');

    function loadPreviousData() {
        if (!selectUpt || !inputTanggal) return;
        const uptId = selectUpt.value;
        const tanggal = inputTanggal.value;
        if (!uptId || !tanggal) {
            if (noticeBox) noticeBox.classList.add('d-none');
            return;
        }

        if (noticeBox) {
            noticeBox.classList.remove('d-none', 'alert-warning', 'alert-success');
            noticeBox.classList.add('alert-info');
            noticeText.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Memeriksa data hari kemarin untuk UPT ini...';
        }

        fetch(`{{ route('penghuni.previous-data') }}?upt_id=${uptId}&tanggal=${tanggal}`)
            .then(res => res.json())
            .then(res => {
                if (res.success && res.has_data && res.data) {
                    // Populate inputs
                    Object.keys(res.data).forEach(key => {
                        const row = res.data[key];
                        ['tahanan_dl', 'tahanan_dp', 'tahanan_al', 'tahanan_ap', 'narapidana_dl', 'narapidana_dp', 'narapidana_al', 'narapidana_ap'].forEach(col => {
                            const input = modalAddEl.querySelector(`input[name="${col}_${key}"]`);
                            if (input) {
                                input.value = row[col] ?? 0;
                            }
                        });
                        updateRowTotal(key);
                    });
                    updateGrandTotals();

                    if (noticeBox) {
                        noticeBox.classList.remove('d-none', 'alert-info', 'alert-warning');
                        noticeBox.classList.add('alert-success');
                        noticeText.innerHTML = `<i class="fas fa-check-circle text-success me-1"></i> Data berhasil <strong>disalin otomatis dari hari kemarin (${res.formatted_date})</strong>. Silakan sesuaikan angka yang berubah saja.`;
                    }
                } else {
                    // Jika data hari kemarin belum diinput, kosongkan input ke 0
                    const inputs = modalAddEl.querySelectorAll('.input-table input[type="number"]');
                    inputs.forEach(input => { input.value = 0; });
                    klasifikasiUtama.concat(klasifikasiLainnyaKeys).forEach(key => { updateRowTotal(key); });
                    updateGrandTotals();

                    if (noticeBox) {
                        noticeBox.classList.remove('d-none', 'alert-info', 'alert-success');
                        noticeBox.classList.add('alert-warning');
                        noticeText.innerHTML = `<i class="fas fa-exclamation-triangle text-warning me-1"></i> ${res.message || 'Data hari kemarin belum diinput untuk UPT ini. Silakan input dari 0.'}`;
                    }
                }
            })
            .catch(err => {
                console.error('Error loading previous data:', err);
                if (noticeBox) noticeBox.classList.add('d-none');
            });
    }

    function resetFormToZero() {
        if (!modalAddEl) return;
        const inputs = modalAddEl.querySelectorAll('.input-table input[type="number"]');
        inputs.forEach(input => {
            input.value = 0;
        });
        klasifikasiUtama.concat(klasifikasiLainnyaKeys).forEach(key => {
            updateRowTotal(key);
        });
        updateGrandTotals();

        if (noticeBox) {
            noticeBox.classList.remove('d-none', 'alert-info', 'alert-success');
            noticeBox.classList.add('alert-warning');
            noticeText.innerHTML = '<i class="fas fa-eraser me-1"></i> Semua input telah di-reset ke nilai 0.';
        }
    }

    if (selectUpt) {
        selectUpt.addEventListener('change', loadPreviousData);
    }
    if (inputTanggal) {
        inputTanggal.addEventListener('change', loadPreviousData);
    }
    if (btnResetZero) {
        btnResetZero.addEventListener('click', resetFormToZero);
    }
    if (btnReloadPrevious) {
        btnReloadPrevious.addEventListener('click', loadPreviousData);
    }

    if (modalAddEl) {
        modalAddEl.addEventListener('show.bs.modal', function() {
            if (selectUpt && selectUpt.value) {
                loadPreviousData();
            }
        });
    }
</script>
@endpush
