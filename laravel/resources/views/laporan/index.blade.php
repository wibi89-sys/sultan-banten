@extends('layouts.app')

@section('title', $reportTitles[$reportType] . ' - Sultan Banten')
@section('page_title')
    <i class="fas fa-print me-2"></i>{{ $reportTitles[$reportType] }}
@endsection

@push('styles')
<style>
    .report-type-card {
        border: 2px solid var(--border-color, #e2e8f0);
        border-radius: 12px;
        padding: 18px 12px;
        text-align: center;
        cursor: pointer;
        transition: all 0.25s ease;
        background: var(--card-bg, #fff);
        text-decoration: none;
        display: block;
        color: var(--text-primary, #1e293b);
    }
    .report-type-card:hover {
        transform: translateY(-3px);
        border-color: #3b82f6;
        box-shadow: 0 6px 15px rgba(59, 130, 246, 0.15);
        color: #2563eb;
    }
    .report-type-card.active {
        border-color: #2563eb;
        background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
        color: white !important;
        box-shadow: 0 6px 18px rgba(37, 99, 235, 0.3);
    }
    .report-type-card.active i, .report-type-card.active h6 {
        color: white !important;
    }
    .report-type-card i {
        font-size: 32px;
        color: #3b82f6;
        margin-bottom: 8px;
    }
</style>
@endpush

@section('content')
<div class="content-card">
    <h6 class="mb-3 fw-bold"><i class="fas fa-layer-group text-primary me-2"></i>Pilih Jenis Laporan</h6>
    
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-2">
            <a href="{{ route('laporan.index', ['type' => 'penghuni']) }}" class="report-type-card {{ $reportType == 'penghuni' ? 'active' : '' }}">
                <i class="fas fa-users"></i>
                <h6 class="mb-0 fs-6">Penghuni</h6>
            </a>
        </div>
        <div class="col-6 col-md-2">
            <a href="{{ route('laporan.index', ['type' => 'overstaying']) }}" class="report-type-card {{ $reportType == 'overstaying' ? 'active' : '' }}">
                <i class="fas fa-clock"></i>
                <h6 class="mb-0 fs-6">Overstaying</h6>
            </a>
        </div>
        <div class="col-6 col-md-2">
            <a href="{{ route('laporan.index', ['type' => 'integrasi']) }}" class="report-type-card {{ $reportType == 'integrasi' ? 'active' : '' }}">
                <i class="fas fa-exchange-alt"></i>
                <h6 class="mb-0 fs-6">Integrasi</h6>
            </a>
        </div>
        <div class="col-6 col-md-3">
            <a href="{{ route('laporan.index', ['type' => 'bebas_harian']) }}" class="report-type-card {{ $reportType == 'bebas_harian' ? 'active' : '' }}">
                <i class="fas fa-door-open"></i>
                <h6 class="mb-0 fs-6">Bebas Harian</h6>
            </a>
        </div>
        <div class="col-6 col-md-3">
            <a href="{{ route('laporan.index', ['type' => 'rekapitulasi']) }}" class="report-type-card {{ $reportType == 'rekapitulasi' ? 'active' : '' }}">
                <i class="fas fa-chart-bar"></i>
                <h6 class="mb-0 fs-6">Rekapitulasi</h6>
            </a>
        </div>
    </div>
    
    <hr class="my-4">
    
    <!-- Filter Form -->
    <form id="formLaporan" method="GET" action="{{ route('laporan.excel') }}" target="_blank">
        <input type="hidden" name="type" value="{{ $reportType }}">
        
        <div class="row g-3">
            @if(!$userUptFilter)
            <div class="col-md-4">
                <label class="form-label fw-semibold small">Pilih UPT</label>
                <select name="upt_id" id="upt_id" class="form-select">
                    <option value="">Semua UPT</option>
                    @foreach ($uptList as $upt)
                    <option value="{{ $upt->id }}">{{ $upt->nama_upt }}</option>
                    @endforeach
                </select>
            </div>
            @else
            <input type="hidden" name="upt_id" value="{{ $userUptFilter }}">
            <div class="col-md-4">
                <label class="form-label fw-semibold small">UPT</label>
                <input type="text" class="form-control" value="{{ auth()->user()->upt?->nama_upt ?? '-' }}" readonly>
            </div>
            @endif
            
            <div class="col-md-4">
                <label class="form-label fw-semibold small">Tanggal Mulai</label>
                <input type="date" name="tanggal_mulai" id="tanggal_mulai" class="form-control" value="{{ $defaultStartDate }}" required>
            </div>
            
            <div class="col-md-4">
                <label class="form-label fw-semibold small">Tanggal Akhir</label>
                <input type="date" name="tanggal_akhir" id="tanggal_akhir" class="form-control" value="{{ $defaultEndDate }}" required>
            </div>
        </div>
        
        @if(in_array($reportType, ['overstaying', 'penghuni', 'bebas_harian']))
        <div class="row g-3 mt-1">
            <div class="col-md-4">
                <label class="form-label fw-semibold small">Tempat TTD</label>
                <input type="text" name="tempat_ttd" id="tempat_ttd" class="form-control" placeholder="Contoh: Serang" value="Serang">
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold small">Jabatan Penanda Tangan</label>
                <input type="text" name="jabatan_ttd" id="jabatan_ttd" class="form-control" placeholder="Contoh: Kepala Bidang Pelayanan dan Pembinaan">
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold small">Nama Penanda Tangan</label>
                <input type="text" name="nama_ttd" id="nama_ttd" class="form-control" placeholder="Contoh: Surya Dharma">
            </div>
        </div>
        @endif
        
        <hr class="my-4">
        
        <div class="d-flex justify-content-end gap-2 flex-wrap">
            <button type="button" class="btn btn-outline-secondary" onclick="resetForm()">
                <i class="fas fa-redo me-1"></i> Reset Filter
            </button>
            @if(in_array($reportType, ['overstaying', 'penghuni', 'bebas_harian']))
            <button type="button" class="btn btn-danger px-4" onclick="submitPdf()">
                <i class="fas fa-file-pdf me-1"></i> Cetak PDF
            </button>
            @endif
            <button type="submit" class="btn btn-success px-4" onclick="document.getElementById('formLaporan').action='{{ route('laporan.excel') }}';">
                <i class="fas fa-file-excel me-1"></i> Download Excel
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    function submitPdf() {
        const form = document.getElementById('formLaporan');
        form.action = "{{ route('laporan.pdf') }}";
        form.submit();
    }

    function resetForm() {
        const today = new Date();
        const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
        const lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0);
        
        document.getElementById('tanggal_mulai').value = firstDay.toISOString().split('T')[0];
        document.getElementById('tanggal_akhir').value = lastDay.toISOString().split('T')[0];
        const uptSelect = document.getElementById('upt_id');
        if (uptSelect) uptSelect.value = '';
        
        if (document.getElementById('jabatan_ttd')) {
            document.getElementById('tempat_ttd').value = 'Serang';
            document.getElementById('jabatan_ttd').value = '';
            document.getElementById('nama_ttd').value = '';
        }
    }
</script>
@endpush
