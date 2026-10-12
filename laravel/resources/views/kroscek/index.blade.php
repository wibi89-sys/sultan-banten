@extends('layouts.app')

@section('title', 'Kroscek Data - Sultan Banten')
@section('page_title', 'Kroscek Data')

@push('styles')
<style>
    .status-badge {
        font-weight: 600;
        padding: 6px 14px;
        border-radius: 20px;
        font-size: 13px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
    }
    .status-sesuai {
        background-color: #d1e7dd;
        color: #0f5132;
        border: 1px solid #badbcc;
    }
    .status-tidak-sesuai {
        background-color: #f8d7da;
        color: #842029;
        border: 1px solid #f5c2c7;
    }
    .status-kosong {
        background-color: #e2e3e5;
        color: #41464b;
        border: 1px solid #d3d6d8;
    }
    .summary-card {
        border-radius: 12px;
        border: 1px solid rgba(0,0,0,0.06);
        transition: all 0.2s ease;
        background: #fff;
    }
    .summary-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 14px rgba(0,0,0,0.06);
    }
    .filter-btn {
        font-size: 13px;
        font-weight: 500;
        border-radius: 20px;
        padding: 4px 12px;
    }
    .filter-btn.active {
        background-color: #0d6efd;
        color: #fff !important;
        border-color: #0d6efd;
    }
</style>
@endpush

@section('content')
<div class="content-card">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
        <div>
            <h5 class="mb-1 fw-bold"><i class="fas fa-check-double text-primary me-2"></i>Kroscek Data Tahanan & Cek Bebas Harian</h5>
            <p class="text-muted small mb-0">Verifikasi kecocokan data tahanan dan kepatuhan input data bebas harian seluruh UPT</p>
        </div>
        <div class="mt-2 mt-md-0">
            <span class="badge bg-light text-dark border px-3 py-2">
                <i class="fas fa-calendar-alt text-primary me-1"></i> Tanggal: <strong>{{ \Carbon\Carbon::parse($filterTanggal)->translatedFormat('d F Y') }}</strong>
            </span>
        </div>
    </div>

    <!-- Ringkasan Statistik KPI -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="p-3 summary-card h-100 border-start border-4 border-primary">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="text-muted small fw-semibold">Total UPT</span>
                    <div class="bg-primary bg-opacity-10 text-primary rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                        <i class="fas fa-building small"></i>
                    </div>
                </div>
                <h4 class="mb-0 fw-bold">{{ $totalUpt }}</h4>
                <small class="text-muted">UPT Terdaftar</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="p-3 summary-card h-100 border-start border-4 border-success">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="text-muted small fw-semibold">Kroscek Sesuai</span>
                    <div class="bg-success bg-opacity-10 text-success rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                        <i class="fas fa-check small"></i>
                    </div>
                </div>
                <h4 class="mb-0 fw-bold text-success">{{ $totalSesuai }}</h4>
                <small class="text-muted">Tahanan sinkron</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="p-3 summary-card h-100 border-start border-4 border-success">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="text-muted small fw-semibold">Bebas Harian Input</span>
                    <div class="bg-success bg-opacity-10 text-success rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                        <i class="fas fa-check-circle small"></i>
                    </div>
                </div>
                <h4 class="mb-0 fw-bold text-success">{{ $totalBebasSudah }} <span class="fs-6 text-muted fw-normal">/ {{ $totalUpt }}</span></h4>
                <small class="text-muted">{{ $sumTotalBebas }} Orang Bebas</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="p-3 summary-card h-100 border-start border-4 border-danger">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="text-muted small fw-semibold">Belum Bebas Harian</span>
                    <div class="bg-danger bg-opacity-10 text-danger rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                        <i class="fas fa-times-circle small"></i>
                    </div>
                </div>
                <h4 class="mb-0 fw-bold text-danger">{{ $totalBebasBelum }}</h4>
                <small class="text-muted">Belum ada input</small>
            </div>
        </div>
    </div>
    
    <!-- Filter Toolbar -->
    <div class="card mb-4 bg-light border-0 shadow-sm">
        <div class="card-body py-3">
            <form method="GET" action="{{ route('kroscek.index') }}" class="row align-items-end g-3">
                <div class="col-md-4 col-sm-6">
                    <label class="form-label fw-bold mb-1 small text-secondary">
                        <i class="fas fa-calendar-day me-1 text-primary"></i>Pilih Tanggal Kroscek
                    </label>
                    <input type="date" name="tanggal" class="form-control" value="{{ $filterTanggal }}" required>
                </div>
                <div class="col-md-2 col-sm-6">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search me-1"></i> Tampilkan
                    </button>
                </div>
                <div class="col-md-6 col-12">
                    <label class="form-label fw-bold mb-1 small text-secondary">
                        <i class="fas fa-filter me-1 text-primary"></i>Cari Nama UPT
                    </label>
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                        <input type="text" id="searchUpt" class="form-control" placeholder="Ketik untuk mencari UPT...">
                    </div>
                </div>
            </form>

            <div class="d-flex flex-wrap gap-2 mt-3 pt-3 border-top">
                <span class="small fw-bold text-muted align-self-center me-1">Filter Tampilan:</span>
                <button type="button" class="btn btn-sm btn-outline-secondary filter-btn active" data-filter="all">
                    Semua ({{ $totalUpt }})
                </button>
                <button type="button" class="btn btn-sm btn-outline-success filter-btn" data-filter="bebas-sudah">
                    <i class="fas fa-check-circle me-1"></i>Sudah Bebas Harian ({{ $totalBebasSudah }})
                </button>
                <button type="button" class="btn btn-sm btn-outline-danger filter-btn" data-filter="bebas-belum">
                    <i class="fas fa-times-circle me-1"></i>Belum Bebas Harian ({{ $totalBebasBelum }})
                </button>
                <button type="button" class="btn btn-sm btn-outline-primary filter-btn" data-filter="kroscek-sesuai">
                    <i class="fas fa-check-double me-1"></i>Tahanan Sesuai ({{ $totalSesuai }})
                </button>
                <button type="button" class="btn btn-sm btn-outline-warning filter-btn" data-filter="kroscek-tidak">
                    <i class="fas fa-exclamation-triangle me-1"></i>Tahanan Selisih ({{ $totalTidakSesuai }})
                </button>
            </div>
        </div>
    </div>

    <!-- Tabel Kroscek Data -->
    <div class="table-responsive">
        <table class="table table-hover align-middle table-sm mb-0">
            <thead class="bg-primary bg-opacity-10">
                <tr>
                    <th class="text-center" width="5%">No</th>
                    <th class="text-start" width="25%">Nama UPT</th>
                    <th class="text-center" width="15%">Tahanan<br><small class="fw-normal text-muted">(Data Penghuni)</small></th>
                    <th class="text-center" width="15%">Tahanan<br><small class="fw-normal text-muted">(Overstaying A1-A5)</small></th>
                    <th class="text-center" width="20%">Kroscek Tahanan</th>
                    <th class="text-center" width="20%">Cek Bebas Harian</th>
                </tr>
            </thead>
            <tbody id="kroscekTableBody">
                @forelse($kroscekData as $row)
                    @php
                        $jmlPenghuni = $row->jml_penghuni !== null ? (int)$row->jml_penghuni : null;
                        $jmlOverstaying = $row->jml_overstaying !== null ? (int)$row->jml_overstaying : null;
                        
                        $statusKroscek = "";
                        $statusKroscekClass = "";
                        $statusKroscekIcon = "";
                        $isKroscekSesuai = false;
                        $selisih = 0;
                        
                        if ($jmlPenghuni === null && $jmlOverstaying === null) {
                            $statusKroscek = "Belum Input Keduanya";
                            $statusKroscekClass = "status-kosong";
                            $statusKroscekIcon = "fa-minus-circle";
                        } elseif ($jmlPenghuni === null) {
                            $statusKroscek = "Penghuni Belum Input";
                            $statusKroscekClass = "status-kosong";
                            $statusKroscekIcon = "fa-exclamation-circle";
                        } elseif ($jmlOverstaying === null) {
                            $statusKroscek = "Overstaying Belum Input";
                            $statusKroscekClass = "status-kosong";
                            $statusKroscekIcon = "fa-exclamation-circle";
                        } elseif ($jmlPenghuni === $jmlOverstaying) {
                            $statusKroscek = "Sesuai";
                            $statusKroscekClass = "status-sesuai";
                            $statusKroscekIcon = "fa-check-circle";
                            $isKroscekSesuai = true;
                        } else {
                            $selisih = abs($jmlPenghuni - $jmlOverstaying);
                            $statusKroscek = "Tidak Sesuai (Selisih: $selisih)";
                            $statusKroscekClass = "status-tidak-sesuai";
                            $statusKroscekIcon = "fa-times-circle";
                        }

                        $isInputBebas = ((int)$row->input_bebas_count > 0);
                        $totalBebas = (int)($row->total_bebas ?? 0);
                    @endphp
                    <tr data-upt="{{ strtolower($row->nama_upt) }}" 
                        data-kroscek="{{ $isKroscekSesuai ? 'sesuai' : 'tidak' }}" 
                        data-bebas="{{ $isInputBebas ? 'sudah' : 'belum' }}">
                        <td class="text-center text-muted">{{ $loop->iteration }}</td>
                        <td class="fw-bold text-dark">
                            <i class="fas fa-building text-primary me-2 opacity-50"></i>
                            {{ $row->nama_upt }}
                        </td>
                        <td class="text-center fw-bold">
                            {{ $jmlPenghuni !== null ? number_format($jmlPenghuni, 0, ',', '.') : '-' }}
                        </td>
                        <td class="text-center fw-bold">
                            {{ $jmlOverstaying !== null ? number_format($jmlOverstaying, 0, ',', '.') : '-' }}
                        </td>
                        <td class="text-center">
                            <span class="status-badge {{ $statusKroscekClass }}">
                                <i class="fas {{ $statusKroscekIcon }}"></i> {{ $statusKroscek }}
                            </span>
                        </td>
                        <td class="text-center">
                            @if($isInputBebas)
                                <span class="status-badge status-sesuai shadow-none">
                                    <i class="fas fa-check-circle text-success fs-6"></i> Sudah Input
                                </span>
                                <div class="small text-muted mt-1">
                                    <i class="fas fa-door-open me-1 text-success"></i>
                                    {!! $totalBebas > 0 ? "<strong>{$totalBebas}</strong> Orang Bebas" : "Nihil (0 Bebas)" !!}
                                </div>
                            @else
                                <span class="status-badge status-tidak-sesuai shadow-none">
                                    <i class="fas fa-times-circle text-danger fs-6"></i> Belum Input
                                </span>
                                <div class="small text-danger mt-1">
                                    <i class="fas fa-clock me-1"></i>Belum ada data
                                </div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">Belum ada data UPT terdaftar.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const searchInput = document.getElementById('searchUpt');
    const filterBtns = document.querySelectorAll('.filter-btn');
    const tableRows = document.querySelectorAll('#kroscekTableBody tr');

    function applyTableFilter() {
        const searchVal = searchInput ? searchInput.value.toLowerCase().trim() : '';
        const activeBtn = document.querySelector('.filter-btn.active');
        const activeFilter = activeBtn ? activeBtn.getAttribute('data-filter') : 'all';

        tableRows.forEach(row => {
            const uptName = row.getAttribute('data-upt') || '';
            const kroscek = row.getAttribute('data-kroscek') || '';
            const bebas = row.getAttribute('data-bebas') || '';

            const matchesSearch = uptName.includes(searchVal);
            let matchesCategory = true;

            if (activeFilter === 'bebas-sudah') {
                matchesCategory = (bebas === 'sudah');
            } else if (activeFilter === 'bebas-belum') {
                matchesCategory = (bebas === 'belum');
            } else if (activeFilter === 'kroscek-sesuai') {
                matchesCategory = (kroscek === 'sesuai');
            } else if (activeFilter === 'kroscek-tidak') {
                matchesCategory = (kroscek === 'tidak');
            }

            row.style.display = (matchesSearch && matchesCategory) ? '' : 'none';
        });
    }

    searchInput?.addEventListener('input', applyTableFilter);

    filterBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            filterBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            applyTableFilter();
        });
    });
</script>
@endpush
