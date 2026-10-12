@extends('layouts.app')

@section('title', 'Activity Log - Sultan Banten')
@section('page_title')
    <i class="fas fa-history me-2"></i>Activity Log
@endsection

@push('styles')
<style>
    .badge-aksi {
        font-size: 11px;
        padding: 5px 9px;
        border-radius: 6px;
    }
    .log-time {
        font-size: 12px;
        color: var(--text-secondary);
    }
    .detail-json {
        background: var(--bg-card, #f8f9fa);
        border: 1px solid var(--border-color, #e9ecef);
        border-radius: 6px;
        padding: 10px;
        font-family: monospace;
        font-size: 11px;
        max-height: 200px;
        overflow-y: auto;
        white-space: pre-wrap;
    }
</style>
@endpush

@section('content')
<!-- Statistics Cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon bg-1">
                <i class="fas fa-history"></i>
            </div>
            <div class="stat-value">{{ number_format($stats['total']) }}</div>
            <div class="stat-label">Total Aktivitas</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon bg-7">
                <i class="fas fa-calendar-day"></i>
            </div>
            <div class="stat-value">{{ number_format($stats['today']) }}</div>
            <div class="stat-label">Aktivitas Hari Ini</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon bg-2">
                <i class="fas fa-calendar-week"></i>
            </div>
            <div class="stat-value">{{ number_format($stats['this_week']) }}</div>
            <div class="stat-label">Aktivitas Minggu Ini</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon bg-3">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-value">{{ count($stats['top_users']) }}</div>
            <div class="stat-label">User Teraktif</div>
        </div>
    </div>
</div>

<!-- Top Users & Actions -->
<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="content-card h-100">
            <h6 class="mb-3 fw-bold"><i class="fas fa-chart-pie text-primary me-2"></i>Aktivitas per Aksi</h6>
            <div class="d-flex flex-wrap gap-2">
                @foreach ($stats['by_action'] as $item)
                    @php $label = $aksiLabels[$item['aksi']] ?? ['label' => $item['aksi'], 'icon' => 'fa-circle', 'color' => 'secondary']; @endphp
                    <span class="badge bg-{{ $label['color'] }} badge-aksi">
                        <i class="fas {{ $label['icon'] }} me-1"></i>
                        {{ $label['label'] }}: {{ number_format($item['total']) }}
                    </span>
                @endforeach
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="content-card h-100">
            <h6 class="mb-3 fw-bold"><i class="fas fa-users text-primary me-2"></i>Top User Aktif</h6>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <tbody>
                        @forelse ($stats['top_users'] as $i => $u)
                        <tr>
                            <td style="width: 30px;" class="fw-bold text-muted">{{ $i + 1 }}.</td>
                            <td>{{ $u['user_name'] }}</td>
                            <td class="text-end"><strong>{{ number_format($u['total']) }}</strong> aksi</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" class="text-muted text-center py-2">Belum ada data aktivitas</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Filter Toolbar -->
<div class="content-card mb-4">
    <h6 class="mb-3 fw-bold"><i class="fas fa-filter text-primary me-2"></i>Filter Log</h6>
    <form method="GET" action="{{ route('activity-log.index') }}" class="row g-2 align-items-end">
        <div class="col-md-2">
            <label class="form-label small fw-semibold">Aksi</label>
            <select name="aksi" class="form-select form-select-sm">
                <option value="">Semua Aksi</option>
                @foreach ($aksiLabels as $key => $label)
                <option value="{{ $key }}" {{ $filterAksi == $key ? 'selected' : '' }}>{{ $label['label'] }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small fw-semibold">Modul</label>
            <select name="modul" class="form-select form-select-sm">
                <option value="">Semua Modul</option>
                @foreach ($modulLabels as $key => $label)
                <option value="{{ $key }}" {{ $filterModul == $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small fw-semibold">User</label>
            <select name="user_id" class="form-select form-select-sm">
                <option value="">Semua User</option>
                @foreach ($allUsers as $u)
                <option value="{{ $u->id }}" {{ $filterUserId == $u->id ? 'selected' : '' }}>{{ $u->nama_lengkap ?? $u->username }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small fw-semibold">Tanggal Mulai</label>
            <input type="date" name="tanggal_mulai" class="form-control form-control-sm" value="{{ $filterTanggalMulai }}">
        </div>
        <div class="col-md-2">
            <label class="form-label small fw-semibold">Tanggal Akhir</label>
            <input type="date" name="tanggal_akhir" class="form-control form-control-sm" value="{{ $filterTanggalAkhir }}">
        </div>
        <div class="col-md-2 d-flex gap-1">
            <button type="submit" class="btn btn-primary btn-sm flex-fill" title="Cari">
                <i class="fas fa-search me-1"></i>Filter
            </button>
            <a href="{{ route('activity-log.index') }}" class="btn btn-secondary btn-sm" title="Reset">
                <i class="fas fa-redo"></i>
            </a>
        </div>
    </form>
</div>

<!-- Logs Table -->
<div class="content-card">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="mb-0 fw-bold"><i class="fas fa-list text-primary me-2"></i>Daftar Riwayat Aktivitas</h6>
        <span class="badge bg-secondary">{{ number_format($logs->total()) }} log</span>
    </div>
    
    <div class="table-responsive">
        <table class="table table-hover table-sm align-middle mb-0">
            <thead>
                <tr class="align-middle">
                    <th width="4%">No</th>
                    <th width="14%">Waktu</th>
                    <th width="15%">User</th>
                    <th width="10%">Aksi</th>
                    <th width="12%">Modul</th>
                    <th>Deskripsi</th>
                    <th width="11%">IP Address</th>
                    <th width="7%" class="text-center">Detail</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($logs as $i => $log)
                @php
                    $label = $aksiLabels[$log->aksi] ?? ['label' => $log->aksi, 'icon' => 'fa-circle', 'color' => 'secondary'];
                    $modulName = $modulLabels[$log->modul] ?? ucfirst($log->modul);
                @endphp
                <tr>
                    <td class="text-muted">{{ $logs->firstItem() + $i }}</td>
                    <td class="log-time">{{ $log->created_at ? $log->created_at->format('d/m/Y H:i:s') : '-' }}</td>
                    <td class="fw-semibold">{{ $log->user_name ?? ($log->user?->nama_lengkap ?? 'Sistem') }}</td>
                    <td>
                        <span class="badge bg-{{ $label['color'] }} badge-aksi">
                            <i class="fas {{ $label['icon'] }} me-1"></i>{{ $label['label'] }}
                        </span>
                    </td>
                    <td><span class="badge bg-light text-dark border">{{ $modulName }}</span></td>
                    <td>{{ $log->deskripsi }}</td>
                    <td class="small text-muted">{{ $log->ip_address ?? '-' }}</td>
                    <td class="text-center">
                        @if(!empty($log->detail))
                        <button type="button" class="btn btn-outline-info btn-xs py-0 px-2" data-bs-toggle="modal" data-bs-target="#modalDetail{{ $log->id }}" title="Lihat detail payload">
                            <i class="fas fa-eye"></i>
                        </button>
                        
                        <!-- Modal Detail -->
                        <div class="modal fade" id="modalDetail{{ $log->id }}" tabindex="-1">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content">
                                    <div class="modal-header py-2 bg-light">
                                        <h6 class="modal-title fs-6"><i class="fas fa-info-circle me-1"></i>Detail Payload Log #{{ $log->id }}</h6>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body text-start">
                                        <p class="small mb-1"><strong>Deskripsi:</strong> {{ $log->deskripsi }}</p>
                                        <p class="small mb-2"><strong>User Agent:</strong> <span class="text-muted">{{ $log->user_agent }}</span></p>
                                        <div class="detail-json">{{ is_array($log->detail) ? json_encode($log->detail, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : $log->detail }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @else
                        <span class="text-muted small">-</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center text-muted py-4">
                        <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                        Tidak ada catatan log aktivitas yang sesuai.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    <div class="mt-3">
        {{ $logs->links('pagination::bootstrap-5') }}
    </div>
</div>
@endsection
