@extends('layouts.app')

@section('title', 'Dashboard - Sultan Banten')
@section('page_title', 'Dashboard')

@push('styles')
<!-- Leaflet CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
<!-- Exact Dashboard Page CSS from Native PHP -->
<link href="{{ asset('assets/css/dashboard-page.css') }}" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
@endpush

@section('content')
<div class="content-wrapper">

    <!-- Filter Card -->
    <div class="filter-card">
        <form method="GET" action="{{ route('dashboard') }}" class="row g-2 align-items-stretch">
            <div class="col-6 col-md-4">
                <label class="form-label small mb-1">Tanggal</label>
                <input type="date" name="tanggal" class="form-control" value="{{ $filterTanggal }}">
            </div>
            <div class="col-6 col-md-4">
                <label class="form-label small mb-1">UPT</label>
                <select name="upt_id" class="form-select">
                    <option value="0">-- Semua UPT --</option>
                    @foreach ($uptList as $upt)
                    <option value="{{ $upt->id }}" {{ $filterUpt == $upt->id ? 'selected' : '' }}>{{ $upt->nama_upt }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-4 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100"><i class="fas fa-filter me-1"></i> Filter</button>
            </div>
        </form>
    </div>

    <!-- Main Statistics Cards -->
    <div class="section-header"><i class="fas fa-chart-bar me-1"></i>Statistik Utama</div>
    <div class="row mb-3 g-2">
        <div class="col-6 col-md-4 col-xl">
            <div class="stat-card stat-card-sm h-100">
                <div class="d-flex align-items-center">
                    <div class="stat-icon-sm bg-1 me-2"><i class="fas fa-building"></i></div>
                    <div class="flex-grow-1">
                        <div class="stat-value-sm">{{ number_format($stats['total_upt'], 0, ',', '.') }}</div>
                        <p class="stat-label-sm">UPT dengan Data</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl">
            <div class="stat-card stat-card-sm h-100">
                <div class="d-flex align-items-center">
                    <div class="stat-icon-sm bg-2 me-2"><i class="fas fa-user-lock"></i></div>
                    <div class="flex-grow-1">
                        <div class="stat-value-sm">{{ number_format($stats['total_tahanan'], 0, ',', '.') }}</div>
                        <p class="stat-label-sm">Total Tahanan</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl">
            <div class="stat-card stat-card-sm h-100">
                <div class="d-flex align-items-center">
                    <div class="stat-icon-sm bg-3 me-2"><i class="fas fa-user-times"></i></div>
                    <div class="flex-grow-1">
                        <div class="stat-value-sm">{{ number_format($stats['total_narapidana'], 0, ',', '.') }}</div>
                        <p class="stat-label-sm">Total Narapidana</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl">
            <div class="stat-card stat-card-sm h-100">
                <div class="d-flex align-items-center">
                    <div class="stat-icon-sm bg-4 me-2"><i class="fas fa-users"></i></div>
                    <div class="flex-grow-1">
                        <div class="stat-value-sm">{{ number_format($stats['total_penghuni'], 0, ',', '.') }}</div>
                        <p class="stat-label-sm">Total Penghuni</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl">
            <div class="stat-card stat-card-sm h-100">
                <div class="d-flex align-items-center">
                    <div class="stat-icon-sm bg-5 me-2"><i class="fas fa-bed"></i></div>
                    <div class="flex-grow-1">
                        <div class="stat-value-sm">{{ number_format($stats['total_kapasitas'], 0, ',', '.') }}</div>
                        <p class="stat-label-sm">Total Kapasitas</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl">
            <div class="stat-card stat-card-sm stat-overkapasitas h-100 {{ getOverkapasitasColorClass($stats['persen_overkapasitas']) }}">
                <div class="d-flex align-items-center">
                    <div class="stat-icon-sm bg-white bg-opacity-25 me-2"><i class="fas fa-chart-pie"></i></div>
                    <div class="flex-grow-1">
                        <div class="stat-value-sm">{{ $stats['tingkat_hunian'] ?? 0 }}%</div>
                        <p class="stat-label-sm">Tingkat Hunian</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl">
            <div class="stat-card stat-card-sm stat-overkapasitas h-100 {{ getOverkapasitasColorClass($stats['persen_overkapasitas']) }}">
                <div class="d-flex align-items-center">
                    <div class="stat-icon-sm bg-white bg-opacity-25 me-2"><i class="fas fa-percentage"></i></div>
                    <div class="flex-grow-1">
                        <div class="stat-value-sm">{{ $stats['persen_overkapasitas'] }}%</div>
                        <p class="stat-label-sm">Overkapasitas</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl">
            <div class="stat-card stat-card-sm h-100" style="border-left: 4px solid var(--danger-500, #ef4444);">
                <div class="d-flex align-items-center">
                    <div class="stat-icon-sm me-2" style="background: linear-gradient(135deg, #f87171 0%, #ef4444 100%);"><i class="fas fa-clock"></i></div>
                    <div class="flex-grow-1">
                        <div class="stat-value-sm text-overstaying">{{ number_format($stats['total_overstaying'], 0, ',', '.') }}</div>
                        <p class="stat-label-sm">Overstaying</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Klasifikasi Statistics Cards -->
    <div class="section-header"><i class="fas fa-gavel me-1"></i>Pidum dan Pidsus</div>
    <div class="row mb-3 g-2">
        <!-- Pidana Umum -->
        <div class="col-6 col-md-4">
            <div class="stat-card stat-card-sm pidum h-100">
                <div class="d-flex align-items-center">
                    <div class="stat-icon-sm bg-7 me-2"><i class="fas fa-user"></i></div>
                    <div class="flex-grow-1">
                        <div class="stat-value-sm">{{ number_format($stats['total_pidum'], 0, ',', '.') }}</div>
                        <p class="stat-label-sm">Total Pidum</p>
                    </div>
                </div>
            </div>
        </div>
        <!-- Pidana Khusus -->
        <div class="col-6 col-md-4">
            <div class="stat-card stat-card-sm pidsus h-100">
                <div class="d-flex align-items-center">
                    <div class="stat-icon-sm bg-8 me-2"><i class="fas fa-gavel"></i></div>
                    <div class="flex-grow-1">
                        <div class="stat-value-sm">{{ number_format($stats['total_pidsus'], 0, ',', '.') }}</div>
                        <p class="stat-label-sm">Total Pidsus</p>
                    </div>
                </div>
            </div>
        </div>
        <!-- UU ITE -->
        <div class="col-6 col-md-4">
            <div class="stat-card stat-card-sm uu-ite h-100">
                <div class="d-flex align-items-center">
                    <div class="stat-icon-sm bg-9 me-2"><i class="fas fa-laptop"></i></div>
                    <div class="flex-grow-1">
                        <div class="stat-value-sm">{{ number_format($stats['total_uu_ite'], 0, ',', '.') }}</div>
                        <p class="stat-label-sm">Total UU ITE</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Penghuni Lainnya Cards -->
    <div class="section-header lainnya"><i class="fas fa-exclamation-triangle me-1"></i>Lainnya</div>
    <div class="row mb-3 g-2">
        <div class="col-6 col-md-4">
            <div class="stat-card stat-card-sm lainnya h-100">
                <div class="d-flex align-items-center">
                    <div class="stat-icon-sm bg-warning me-2"><i class="fas fa-globe"></i></div>
                    <div class="flex-grow-1">
                        <div class="stat-value-sm text-lainnya">{{ number_format($stats['total_wna'], 0, ',', '.') }}</div>
                        <p class="stat-label-sm">Total WNA</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4">
            <div class="stat-card stat-card-sm lainnya h-100">
                <div class="d-flex align-items-center">
                    <div class="stat-icon-sm bg-warning me-2"><i class="fas fa-hospital"></i></div>
                    <div class="flex-grow-1">
                        <div class="stat-value-sm text-lainnya">{{ number_format($stats['total_sakit'], 0, ',', '.') }}</div>
                        <p class="stat-label-sm">Total Sakit Berkepanjangan</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4">
            <div class="stat-card stat-card-sm lainnya h-100">
                <div class="d-flex align-items-center">
                    <div class="stat-icon-sm bg-warning me-2"><i class="fas fa-user-clock"></i></div>
                    <div class="flex-grow-1">
                        <div class="stat-value-sm text-lainnya">{{ number_format($stats['total_lansia'], 0, ',', '.') }}</div>
                        <p class="stat-label-sm">Total Lansia >70 Tahun</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Pidana Mati dan Seumur Hidup Cards -->
    <div class="section-header text-danger" style="border-bottom: 2px solid #dc3545;"><i class="fas fa-skull-crossbones me-1"></i>Pidana Mati dan Seumur Hidup</div>
    <div class="row mb-3 g-2">
        <div class="col-6 col-md-6">
            <div class="stat-card stat-card-sm h-100" style="border-left: 4px solid #dc3545;">
                <div class="d-flex align-items-center">
                    <div class="stat-icon-sm bg-danger text-white me-2"><i class="fas fa-skull-crossbones"></i></div>
                    <div class="flex-grow-1">
                        <div class="stat-value-sm text-danger">{{ number_format($stats['total_pidana_mati'], 0, ',', '.') }}</div>
                        <p class="stat-label-sm">Total Pidana Mati</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-6">
            <div class="stat-card stat-card-sm h-100" style="border-left: 4px solid #dc3545;">
                <div class="d-flex align-items-center">
                    <div class="stat-icon-sm bg-danger text-white me-2"><i class="fas fa-infinity"></i></div>
                    <div class="flex-grow-1">
                        <div class="stat-value-sm text-danger">{{ number_format($stats['total_seumur_hidup'], 0, ',', '.') }}</div>
                        <p class="stat-label-sm">Total Seumur Hidup</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bebas Harian Statistics Cards -->
    <div class="section-header" style="border-bottom: 2px solid #10b981; color: #10b981;">
        <i class="fas fa-door-open me-1"></i>Pengeluaran / Bebas Harian
    </div>
    <div class="row mb-3 g-2">
        <div class="col-6 col-md-3 col-xl">
            <div class="stat-card stat-card-sm h-100" style="border-left: 4px solid #6b7280;">
                <div class="d-flex align-items-center">
                    <div class="stat-icon-sm bg-secondary text-white me-2"><i class="fas fa-check-circle"></i></div>
                    <div class="flex-grow-1">
                        <div class="stat-value-sm">{{ number_format($stats['bebas_murni'], 0, ',', '.') }}</div>
                        <p class="stat-label-sm">Bebas Murni</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3 col-xl">
            <div class="stat-card stat-card-sm h-100" style="border-left: 4px solid #3b82f6;">
                <div class="d-flex align-items-center">
                    <div class="stat-icon-sm bg-primary text-white me-2"><i class="fas fa-user-check"></i></div>
                    <div class="flex-grow-1">
                        <div class="stat-value-sm text-primary">{{ number_format($stats['bebas_pb'], 0, ',', '.') }}</div>
                        <p class="stat-label-sm">PB (Bersyarat)</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3 col-xl">
            <div class="stat-card stat-card-sm h-100" style="border-left: 4px solid #06b6d4;">
                <div class="d-flex align-items-center">
                    <div class="stat-icon-sm bg-info text-white me-2"><i class="fas fa-calendar-check"></i></div>
                    <div class="flex-grow-1">
                        <div class="stat-value-sm text-info">{{ number_format($stats['bebas_cb'], 0, ',', '.') }}</div>
                        <p class="stat-label-sm">CB (Cuti Bersyarat)</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3 col-xl">
            <div class="stat-card stat-card-sm h-100" style="border-left: 4px solid #f59e0b;">
                <div class="d-flex align-items-center">
                    <div class="stat-icon-sm bg-warning text-dark me-2"><i class="fas fa-hourglass-end"></i></div>
                    <div class="flex-grow-1">
                        <div class="stat-value-sm text-warning">{{ number_format($stats['bebas_cmb'], 0, ',', '.') }}</div>
                        <p class="stat-label-sm">CMB</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3 col-xl">
            <div class="stat-card stat-card-sm h-100" style="border-left: 4px solid #10b981;">
                <div class="d-flex align-items-center">
                    <div class="stat-icon-sm bg-success text-white me-2"><i class="fas fa-home"></i></div>
                    <div class="flex-grow-1">
                        <div class="stat-value-sm text-success">{{ number_format($stats['bebas_asimilasi'], 0, ',', '.') }}</div>
                        <p class="stat-label-sm">Asimilasi</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3 col-xl">
            <div class="stat-card stat-card-sm h-100" style="border-left: 4px solid #ef4444;">
                <div class="d-flex align-items-center">
                    <div class="stat-icon-sm bg-danger text-white me-2"><i class="fas fa-balance-scale"></i></div>
                    <div class="flex-grow-1">
                        <div class="stat-value-sm text-danger">{{ number_format($stats['bebas_bdh'], 0, ',', '.') }}</div>
                        <p class="stat-label-sm">BDH (Demi Hukum)</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3 col-xl">
            <div class="stat-card stat-card-sm h-100" style="border-left: 4px solid #8b5cf6;">
                <div class="d-flex align-items-center">
                    <div class="stat-icon-sm text-white me-2" style="background: #8b5cf6;"><i class="fas fa-flag-checkered"></i></div>
                    <div class="flex-grow-1">
                        <div class="stat-value-sm fw-bold" style="color: #8b5cf6;">{{ number_format($stats['total_bebas'], 0, ',', '.') }}</div>
                        <p class="stat-label-sm">Total Bebas</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Peta Lokasi UPT -->
    <div class="chart-card">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
            <h5 class="mb-0"><i class="fas fa-map-marked-alt me-2"></i>Peta Lokasi UPT di Banten</h5>
            <div class="btn-group btn-group-sm mt-2 mt-sm-0" role="group">
                <button type="button" class="btn btn-outline-primary" id="btnStreet">Street</button>
                <button type="button" class="btn btn-outline-primary" id="btnSatellite">Satellite</button>
                <button type="button" class="btn btn-outline-primary active" id="btnHybrid">Hybrid</button>
            </div>
        </div>
        <div id="mapBanten" style="height: 400px; width: 100%; border-radius: 8px; z-index: 1;"></div>
        <div class="mt-2 text-muted small">
            <i class="fas fa-info-circle"></i> Klik marker untuk melihat detail UPT
        </div>
    </div>

    <!-- Overkapasitas Table -->
    <div class="table-card">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Data Penghuni & Overkapasitas per UPT</h5>
            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle">
                <i class="fas fa-calendar-day me-1"></i>{{ formatIndonesianDate($filterTanggal, false) }}
            </span>
        </div>
        
        <!-- Legend -->
        <div class="mb-2" style="font-size: 10px;">
            <span class="legend-item"><span class="legend-color bg-success"></span>Longgar</span>
            <span class="legend-item"><span class="legend-color bg-info"></span>Hampir Penuh</span>
            <span class="legend-item"><span class="legend-color bg-warning"></span>Sedikit Over</span>
            <span class="legend-item"><span class="legend-color bg-danger"></span>Over</span>
            <span class="legend-item"><span class="legend-color bg-dark-red"></span>Sangat Over</span>
        </div>
        
        <div class="table-responsive">
            <table class="table table-bordered table-hover table-sm mb-0" style="font-size: 11px; margin-bottom: 0;">
                <thead class="table-light">
                    <tr>
                        <th class="text-center align-middle py-1" style="width: 30px;">No</th>
                        <th class="align-middle py-1" style="min-width: 100px;">Unit Pelaksana Teknis</th>
                        <th class="text-center align-middle py-1" style="width: 60px;">Kapasitas</th>
                        <th class="text-center align-middle py-1" style="width: 60px;">Tahanan</th>
                        <th class="text-center align-middle py-1" style="width: 60px;">Narapidana</th>
                        <th class="text-center align-middle py-1" style="width: 70px;">Total</th>
                        <th class="text-center align-middle py-1" style="width: 75px;">Tingkat<br>Hunian</th>
                        <th class="text-center align-middle py-1" style="width: 70px;">% Over</th>
                    </tr>
                </thead>
                <tbody>
                    @php $no = 1; @endphp
                    @forelse ($uptDataTable as $row)
                        @php
                            $persen = $row->kapasitas > 0 ? round((($row->total_penghuni - $row->kapasitas) / $row->kapasitas) * 100, 1) : 0;
                            $tingkatHunianUpt = $row->kapasitas > 0 ? round(($row->total_penghuni / $row->kapasitas) * 100, 1) : 0;
                            $colorClass = getOverkapasitasColorClass($persen);
                        @endphp
                        <tr>
                            <td class="text-center align-middle py-1">{{ $no++ }}</td>
                            <td class="align-middle py-1">{{ $row->nama_upt }}</td>
                            <td class="text-center align-middle py-1">{{ number_format($row->kapasitas) }}</td>
                            <td class="text-center align-middle py-1">{{ number_format($row->total_tahanan) }}</td>
                            <td class="text-center align-middle py-1">{{ number_format($row->total_narapidana) }}</td>
                            <td class="text-center align-middle py-1"><strong>{{ number_format($row->total_penghuni) }}</strong></td>
                            <td class="text-center align-middle py-1 {{ $colorClass }}" style="font-size: 10px;"><strong>{{ $tingkatHunianUpt }}%</strong></td>
                            <td class="text-center align-middle py-1 {{ $colorClass }}" style="font-size: 10px;"><strong>{{ $persen > 0 ? '+' : '' }}{{ $persen }}%</strong></td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted py-2">Belum ada data</td></tr>
                    @endforelse
                </tbody>
                <tfoot class="table-light">
                    <tr class="fw-bold">
                        <td colspan="2" class="text-end align-middle py-1">TOTAL</td>
                        <td class="text-center align-middle py-1">{{ number_format($stats['total_kapasitas']) }}</td>
                        <td class="text-center align-middle py-1">{{ number_format($stats['total_tahanan']) }}</td>
                        <td class="text-center align-middle py-1">{{ number_format($stats['total_narapidana']) }}</td>
                        <td class="text-center align-middle py-1"><strong>{{ number_format($stats['total_penghuni']) }}</strong></td>
                        @php $total_tingkat_hunian = $stats['total_kapasitas'] > 0 ? round(($stats['total_penghuni'] / $stats['total_kapasitas']) * 100, 1) : 0; @endphp
                        <td class="text-center align-middle py-1 {{ getOverkapasitasColorClass($stats['persen_overkapasitas']) }}" style="font-size: 10px;"><strong>{{ $total_tingkat_hunian }}%</strong></td>
                        <td class="text-center align-middle py-1 {{ getOverkapasitasColorClass($stats['persen_overkapasitas']) }}" style="font-size: 10px;">
                            <strong>{{ $stats['persen_overkapasitas'] > 0 ? '+' : '' }}{{ $stats['persen_overkapasitas'] }}%</strong>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- Charts Row: Penghuni per Klasifikasi & Distribusi Narapidana/Tahanan -->
    <div class="row">
        <div class="col-lg-8 mb-3">
            <div class="chart-card h-100">
                <h5 class="mb-3"><i class="fas fa-chart-bar me-2"></i>Penghuni per Klasifikasi</h5>
                <div style="height: 320px;">
                    <canvas id="chartKlasifikasi"></canvas>
                </div>
                <div id="klasifikasiLegend" class="mt-3 w-100"></div>
            </div>
        </div>
        <div class="col-lg-4 mb-3">
            <div class="chart-card h-100 d-flex flex-column">
                <h5 class="mb-2" style="font-size: 14px;"><i class="fas fa-chart-pie me-2"></i>Distribusi Narapidana & Tahanan</h5>
                <div class="flex-grow-1 d-flex align-items-center justify-content-center" style="max-height: 260px;">
                    <canvas id="chartNarapidanaTahanan"></canvas>
                </div>
                <div class="text-center mt-1">
                    <span class="badge me-1" style="background: #667eea; font-size: 10px;"><i class="fas fa-user-check"></i> Napi: {{ number_format($stats['total_narapidana']) }}</span>
                    <span class="badge" style="background: #f5576c; font-size: 10px;"><i class="fas fa-user-clock"></i> Tahanan: {{ number_format($stats['total_tahanan']) }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabel Data Penghuni Berdasarkan Jenis Kejahatan -->
    <div class="table-card">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h5 class="mb-0"><i class="fas fa-table me-2"></i>Data Penghuni Berdasarkan Jenis Kejahatan per UPT</h5>
            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle">
                <i class="fas fa-calendar-day me-1"></i>{{ formatIndonesianDate($filterTanggal, false) }}
            </span>
        </div>
        <div class="table-responsive">
            <table class="table table-bordered table-hover table-sm mb-0" style="font-size: 10px;">
                <thead class="table-light">
                    <tr>
                        <th rowspan="2" class="text-center align-middle py-1" style="width: 25px;">No</th>
                        <th rowspan="2" class="align-middle py-1" style="min-width: 80px;">UPT</th>
                        <th class="text-center py-1 bg-primary bg-opacity-10" style="width: 40px;">Pidum</th>
                        <th colspan="9" class="text-center py-1 bg-info bg-opacity-25" style="font-size: 9px;">Pidsus</th>
                        <th class="text-center py-1 bg-primary bg-opacity-10" style="width: 40px;">ITE</th>
                        <th colspan="5" class="text-center py-1 bg-warning bg-opacity-25" style="font-size: 9px;">Lainnya</th>
                    </tr>
                    <tr>
                        <th class="text-center py-1 bg-primary bg-opacity-10" style="width: 40px; font-size: 9px;">Jml</th>
                        <th class="text-center py-1 bg-info bg-opacity-25" style="width: 38px; font-size: 9px;">Tipikor</th>
                        <th class="text-center py-1 bg-info bg-opacity-25" style="width: 38px; font-size: 9px;">Nrk<br>Br</th>
                        <th class="text-center py-1 bg-info bg-opacity-25" style="width: 38px; font-size: 9px;">Nrk<br>Pg</th>
                        <th class="text-center py-1 bg-info bg-opacity-25" style="width: 38px; font-size: 9px;">Teror</th>
                        <th class="text-center py-1 bg-info bg-opacity-25" style="width: 38px; font-size: 9px;">Traff</th>
                        <th class="text-center py-1 bg-info bg-opacity-25" style="width: 38px; font-size: 9px;">Ileg<br>Log</th>
                        <th class="text-center py-1 bg-info bg-opacity-25" style="width: 38px; font-size: 9px;">Money<br>Lau</th>
                        <th class="text-center py-1 bg-info bg-opacity-25" style="width: 38px; font-size: 9px;">Geno</th>
                        <th class="text-center py-1 bg-info bg-opacity-25 bg-success text-white" style="width: 42px; font-size: 9px;"><strong>Jml</strong></th>
                        <th class="text-center py-1 bg-primary bg-opacity-10" style="width: 40px; font-size: 9px;">Jml</th>
                        <th class="text-center py-1 bg-warning bg-opacity-25" style="width: 38px; font-size: 9px;">WNA</th>
                        <th class="text-center py-1 bg-warning bg-opacity-25" style="width: 38px; font-size: 9px;">Sakit</th>
                        <th class="text-center py-1 bg-warning bg-opacity-25" style="width: 38px; font-size: 9px;">Lansia</th>
                        <th class="text-center py-1 bg-warning bg-opacity-25" style="width: 38px; font-size: 9px;">MT</th>
                        <th class="text-center py-1 bg-warning bg-opacity-25" style="width: 38px; font-size: 9px;">SH</th>
                    </tr>
                </thead>
                <tbody>
                    @php 
                    $no = 1;
                    $totalPidum = 0;
                    $totalTipikor = 0; $totalNarkobaBandar = 0; $totalNarkobaPengguna = 0;
                    $totalTeroris = 0; $totalTrafficking = 0; $totalIlegalLogging = 0;
                    $totalMoneyLaundering = 0; $totalGenosida = 0; $totalPidsus = 0;
                    $totalUUITE = 0;
                    $totalWNA = 0; $totalSakit = 0; $totalLansia = 0; $totalPidanaMati = 0; $totalSeumurHidup = 0;
                    $grandTotalPenghuni = 0;
                    @endphp
                    @forelse ($jenisKejahatanData as $row)
                        @php
                        $wna = (int)($row->wna ?? 0);
                        $sakit = (int)($row->sakit ?? 0);
                        $lansia = (int)($row->lansia ?? 0);
                        $pidana_mati = (int)($row->pidana_mati ?? 0);
                        $seumur_hidup = (int)($row->seumur_hidup ?? 0);

                        $pidsusTotal = (int)$row->tipikor + (int)$row->narkoba_bandar + (int)$row->narkoba_pengguna + 
                                       (int)$row->teroris + (int)$row->trafficking + (int)$row->ilegal_logging + 
                                       (int)$row->money_laundering + (int)$row->genosida;

                        $totalPidum += (int)$row->pidum;
                        $totalTipikor += (int)$row->tipikor;
                        $totalNarkobaBandar += (int)$row->narkoba_bandar;
                        $totalNarkobaPengguna += (int)$row->narkoba_pengguna;
                        $totalTeroris += (int)$row->teroris;
                        $totalTrafficking += (int)$row->trafficking;
                        $totalIlegalLogging += (int)$row->ilegal_logging;
                        $totalMoneyLaundering += (int)$row->money_laundering;
                        $totalGenosida += (int)$row->genosida;
                        $totalPidsus += $pidsusTotal;
                        $totalUUITE += (int)$row->uu_ite;
                        $totalWNA += $wna;
                        $totalSakit += $sakit;
                        $totalLansia += $lansia;
                        $totalPidanaMati += $pidana_mati;
                        $totalSeumurHidup += $seumur_hidup;
                        $grandTotalPenghuni += (int)$row->total_penghuni;

                        $statistikTotalRow = (int)$row->pidum + $pidsusTotal + (int)$row->uu_ite;
                        $p_pidum = $statistikTotalRow > 0 ? round(($row->pidum / $statistikTotalRow) * 100, 1) : 0;
                        $p_pidsus = $statistikTotalRow > 0 ? round(($pidsusTotal / $statistikTotalRow) * 100, 1) : 0;
                        $p_ite = $statistikTotalRow > 0 ? round(($row->uu_ite / $statistikTotalRow) * 100, 1) : 0;
                        @endphp
                        <tr>
                            <td class="text-center py-1">{{ $no++ }}</td>
                            <td class="py-1">{{ $row->nama_upt }}</td>
                            <td class="text-center py-1">{{ number_format($row->pidum) }}<br><small class="text-muted" style="font-size: 8px;">{{ $p_pidum }}%</small></td>
                            <td class="text-center py-1">{{ number_format($row->tipikor) }}</td>
                            <td class="text-center py-1">{{ number_format($row->narkoba_bandar) }}</td>
                            <td class="text-center py-1">{{ number_format($row->narkoba_pengguna) }}</td>
                            <td class="text-center py-1">{{ number_format($row->teroris) }}</td>
                            <td class="text-center py-1">{{ number_format($row->trafficking) }}</td>
                            <td class="text-center py-1">{{ number_format($row->ilegal_logging) }}</td>
                            <td class="text-center py-1">{{ number_format($row->money_laundering) }}</td>
                            <td class="text-center py-1">{{ number_format($row->genosida) }}</td>
                            <td class="text-center py-1 table-success"><strong>{{ number_format($pidsusTotal) }}</strong><br><small class="text-success" style="font-size: 8px;">({{ $p_pidsus }}%)</small></td>
                            <td class="text-center py-1">{{ number_format($row->uu_ite) }}<br><small class="text-muted" style="font-size: 8px;">{{ $p_ite }}%</small></td>
                            <td class="text-center py-1 text-warning-emphasis align-middle">{{ number_format($wna) }}</td>
                            <td class="text-center py-1 text-warning-emphasis align-middle">{{ number_format($sakit) }}</td>
                            <td class="text-center py-1 text-warning-emphasis align-middle">{{ number_format($lansia) }}</td>
                            <td class="text-center py-1 text-warning-emphasis align-middle">{{ number_format($pidana_mati) }}</td>
                            <td class="text-center py-1 text-warning-emphasis align-middle">{{ number_format($seumur_hidup) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="18" class="text-center py-2 text-muted">Belum ada data</td></tr>
                    @endforelse
                </tbody>
                <tfoot class="table-light">
                    @php $statistikGrandTotal = $totalPidum + $totalPidsus + $totalUUITE; @endphp
                    <tr class="fw-bold" style="font-size: 10px;">
                        <td colspan="2" class="text-end py-1">TOTAL</td>
                        <td class="text-center py-1">
                            {{ number_format($totalPidum) }}<br>
                            <small class="text-muted" style="font-size: 8px;">{{ $statistikGrandTotal > 0 ? round(($totalPidum / $statistikGrandTotal) * 100, 1) : 0 }}%</small>
                        </td>
                        <td class="text-center py-1">{{ number_format($totalTipikor) }}</td>
                        <td class="text-center py-1">{{ number_format($totalNarkobaBandar) }}</td>
                        <td class="text-center py-1">{{ number_format($totalNarkobaPengguna) }}</td>
                        <td class="text-center py-1">{{ number_format($totalTeroris) }}</td>
                        <td class="text-center py-1">{{ number_format($totalTrafficking) }}</td>
                        <td class="text-center py-1">{{ number_format($totalIlegalLogging) }}</td>
                        <td class="text-center py-1">{{ number_format($totalMoneyLaundering) }}</td>
                        <td class="text-center py-1">{{ number_format($totalGenosida) }}</td>
                        <td class="text-center py-1 table-success">
                            {{ number_format($totalPidsus) }}<br>
                            <small class="text-success" style="font-size: 8px;">{{ $statistikGrandTotal > 0 ? round(($totalPidsus / $statistikGrandTotal) * 100, 1) : 0 }}%</small>
                        </td>
                        <td class="text-center py-1">
                            {{ number_format($totalUUITE) }}<br>
                            <small class="text-muted" style="font-size: 8px;">{{ $statistikGrandTotal > 0 ? round(($totalUUITE / $statistikGrandTotal) * 100, 1) : 0 }}%</small>
                        </td>
                        <td class="text-center py-1 text-warning-emphasis align-middle">{{ number_format($totalWNA) }}</td>
                        <td class="text-center py-1 text-warning-emphasis align-middle">{{ number_format($totalSakit) }}</td>
                        <td class="text-center py-1 text-warning-emphasis align-middle">{{ number_format($totalLansia) }}</td>
                        <td class="text-center py-1 text-warning-emphasis align-middle">{{ number_format($totalPidanaMati) }}</td>
                        <td class="text-center py-1 text-warning-emphasis align-middle">{{ number_format($totalSeumurHidup) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <div class="mt-1 text-muted" style="font-size: 9px;">
            <i class="fas fa-info-circle"></i> 
            <span class="badge bg-primary bg-opacity-10 text-primary" style="font-size: 9px;">Pidum & ITE</span> + 
            <span class="badge bg-info bg-opacity-25" style="font-size: 9px;">Pidsus</span>: Statistik | 
            <span class="badge bg-warning bg-opacity-25" style="font-size: 9px;">Lainnya</span>: Non-statistik | 
            <span class="badge bg-success bg-opacity-25" style="font-size: 9px;">Jml</span>: Total Pidsus
        </div>
    </div>

    <!-- Tabel Pidana Mati dan Seumur Hidup per UPT -->
    <div class="table-card mt-3">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h5 class="mb-0 text-danger"><i class="fas fa-skull-crossbones me-2"></i>Data Pidana Mati dan Seumur Hidup per UPT</h5>
            <span class="badge bg-danger bg-opacity-10 text-danger border border-danger-subtle">
                <i class="fas fa-calendar-day me-1"></i>{{ formatIndonesianDate($filterTanggal, false) }}
            </span>
        </div>
        <div class="table-responsive">
            <table class="table table-bordered table-hover table-sm mb-0" style="font-size: 11px;">
                <thead class="table-light">
                    <tr>
                        <th class="text-center align-middle py-2" style="width: 40px;">No</th>
                        <th class="align-middle py-2" style="min-width: 150px;">Unit Pelaksana Teknis</th>
                        <th class="text-center py-2" style="width: 120px; background-color: rgba(220, 53, 69, 0.1); color: #dc3545;">Pidana Mati</th>
                        <th class="text-center py-2" style="width: 120px; background-color: rgba(220, 53, 69, 0.1); color: #dc3545;">Seumur Hidup</th>
                    </tr>
                </thead>
                <tbody>
                    @php 
                    $no = 1;
                    $totalPM = 0;
                    $totalSH = 0;
                    @endphp
                    @forelse ($pmshData as $row)
                        @php
                        $pm = (int)$row->pidana_mati;
                        $sh = (int)$row->seumur_hidup;
                        $totalPM += $pm;
                        $totalSH += $sh;
                        @endphp
                        <tr>
                            <td class="text-center py-2">{{ $no++ }}</td>
                            <td class="py-2">{{ $row->nama_upt }}</td>
                            <td class="text-center py-2 text-danger"><strong>{{ number_format($pm) }}</strong></td>
                            <td class="text-center py-2 text-danger"><strong>{{ number_format($sh) }}</strong></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted py-3">Belum ada data</td></tr>
                    @endforelse
                </tbody>
                <tfoot class="table-light">
                    <tr class="fw-bold">
                        <td colspan="2" class="text-end py-2">TOTAL</td>
                        <td class="text-center py-2 text-danger" style="background-color: rgba(220, 53, 69, 0.1);">{{ number_format($totalPM) }}</td>
                        <td class="text-center py-2 text-danger" style="background-color: rgba(220, 53, 69, 0.1);">{{ number_format($totalSH) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- Tabel Bebas Harian per UPT -->
    <div class="table-card mt-3">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h5 class="mb-0 text-success"><i class="fas fa-door-open me-2"></i>Data Pengeluaran / Bebas Harian per UPT</h5>
            <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle">
                <i class="fas fa-calendar-day me-1"></i>{{ formatIndonesianDate($filterTanggal, false) }}
            </span>
        </div>
        <div class="table-responsive">
            <table class="table table-bordered table-hover table-sm mb-0" style="font-size: 11px;">
                <thead class="table-light">
                    <tr>
                        <th class="text-center align-middle py-2" style="width: 40px;">No</th>
                        <th class="align-middle py-2" style="min-width: 170px;">Unit Pelaksana Teknis</th>
                        <th class="text-center py-2" style="width: 95px; background-color: rgba(107, 114, 128, 0.08);">Bebas Murni</th>
                        <th class="text-center py-2" style="width: 95px; background-color: rgba(59, 130, 246, 0.08); color: #1d4ed8;">PB</th>
                        <th class="text-center py-2" style="width: 95px; background-color: rgba(6, 182, 212, 0.08); color: #0891b2;">CB</th>
                        <th class="text-center py-2" style="width: 95px; background-color: rgba(139, 92, 246, 0.08); color: #7c3aed;">CMB</th>
                        <th class="text-center py-2" style="width: 95px; background-color: rgba(245, 158, 11, 0.08); color: #b45309;">Asimilasi</th>
                        <th class="text-center py-2" style="width: 95px; background-color: rgba(239, 68, 68, 0.08); color: #b91c1c;">BDH</th>
                        <th class="text-center py-2" style="width: 95px; background-color: rgba(100, 116, 139, 0.08); color: #475569;">Lainnya</th>
                        <th class="text-center py-2" style="width: 100px; background-color: rgba(16, 185, 129, 0.15); color: #047857;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @php 
                    $noBebas = 1;
                    $totBM = 0; $totPB = 0; $totCB = 0; $totCMB = 0;
                    $totAsim = 0; $totBDH = 0; $totLny = 0; $totSemua = 0;
                    @endphp
                    @forelse ($bebasHarianUptData as $rowBebas)
                        @php
                        $bm = (int)$rowBebas->bebas_murni;
                        $pb = (int)$rowBebas->pb;
                        $cb = (int)$rowBebas->cb;
                        $cmb = (int)$rowBebas->cmb;
                        $asim = (int)$rowBebas->asimilasi;
                        $bdh = (int)$rowBebas->bdh;
                        $lny = (int)$rowBebas->lainnya;
                        $totalUpt = $bm + $pb + $cb + $cmb + $asim + $bdh + $lny;
                        
                        $totBM += $bm;
                        $totPB += $pb;
                        $totCB += $cb;
                        $totCMB += $cmb;
                        $totAsim += $asim;
                        $totBDH += $bdh;
                        $totLny += $lny;
                        $totSemua += $totalUpt;
                        @endphp
                        <tr>
                            <td class="text-center py-2">{{ $noBebas++ }}</td>
                            <td class="py-2 fw-medium">{{ $rowBebas->nama_upt }}</td>
                            <td class="text-center py-2 {{ $bm > 0 ? 'fw-bold' : 'text-muted' }}">{{ number_format($bm) }}</td>
                            <td class="text-center py-2 {{ $pb > 0 ? 'fw-bold text-primary' : 'text-muted' }}">{{ number_format($pb) }}</td>
                            <td class="text-center py-2 {{ $cb > 0 ? 'fw-bold text-info' : 'text-muted' }}">{{ number_format($cb) }}</td>
                            <td class="text-center py-2 {{ $cmb > 0 ? 'fw-bold' : 'text-muted' }}" style="{{ $cmb > 0 ? 'color: #7c3aed;' : '' }}">{{ number_format($cmb) }}</td>
                            <td class="text-center py-2 {{ $asim > 0 ? 'fw-bold text-warning-emphasis' : 'text-muted' }}">{{ number_format($asim) }}</td>
                            <td class="text-center py-2 {{ $bdh > 0 ? 'fw-bold text-danger' : 'text-muted' }}">{{ number_format($bdh) }}</td>
                            <td class="text-center py-2 {{ $lny > 0 ? 'fw-bold text-secondary' : 'text-muted' }}">{{ number_format($lny) }}</td>
                            <td class="text-center py-2 fw-bold text-success" style="background-color: rgba(16, 185, 129, 0.05);">{{ number_format($totalUpt) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="text-center text-muted py-3">Belum ada data pengeluaran / bebas harian</td></tr>
                    @endforelse
                </tbody>
                <tfoot class="table-light">
                    <tr class="fw-bold">
                        <td colspan="2" class="text-end py-2">TOTAL</td>
                        <td class="text-center py-2" style="background-color: rgba(107, 114, 128, 0.08);">{{ number_format($totBM) }}</td>
                        <td class="text-center py-2 text-primary" style="background-color: rgba(59, 130, 246, 0.08);">{{ number_format($totPB) }}</td>
                        <td class="text-center py-2 text-info" style="background-color: rgba(6, 182, 212, 0.08);">{{ number_format($totCB) }}</td>
                        <td class="text-center py-2" style="background-color: rgba(139, 92, 246, 0.08); color: #7c3aed;">{{ number_format($totCMB) }}</td>
                        <td class="text-center py-2 text-warning-emphasis" style="background-color: rgba(245, 158, 11, 0.08);">{{ number_format($totAsim) }}</td>
                        <td class="text-center py-2 text-danger" style="background-color: rgba(239, 68, 68, 0.08);">{{ number_format($totBDH) }}</td>
                        <td class="text-center py-2 text-secondary" style="background-color: rgba(100, 116, 139, 0.08);">{{ number_format($totLny) }}</td>
                        <td class="text-center py-2 text-success" style="background-color: rgba(16, 185, 129, 0.18); font-size: 12px;">{{ number_format($totSemua) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <div class="mt-2 text-muted d-flex flex-wrap gap-2 align-items-center" style="font-size: 10px;">
            <i class="fas fa-info-circle"></i>
            <span><strong>PB:</strong> Pembebasan Bersyarat</span> | 
            <span><strong>CB:</strong> Cuti Bersyarat</span> | 
            <span><strong>CMB:</strong> Cuti Menjelang Bebas</span> | 
            <span><strong>BDH:</strong> Bebas Demi Hukum</span>
        </div>
    </div>

    <!-- Chart Penghuni Berdasarkan Umur dan Jenis Kelamin -->
    <div class="row">
        <div class="col-lg-6 mb-3">
            <div class="chart-card h-100">
                <h5 class="mb-3"><i class="fas fa-venus-mars me-2"></i>Penghuni Berdasarkan Umur & Jenis Kelamin</h5>
                <!-- Legend dengan jumlah -->
                <div class="d-flex flex-wrap justify-content-center gap-2 mb-3">
                    <div class="d-flex align-items-center gap-2 px-2 py-1 rounded" style="background: rgba(54, 162, 235, 0.1); flex: 1 1 45%;">
                        <div style="width: 12px; height: 12px; background: rgba(54, 162, 235, 0.9); border-radius: 3px;"></div>
                        <span class="small fw-medium" style="font-size: 11px;">Dewasa ♂: <span id="legend-dewasa-laki">0</span></span>
                    </div>
                    <div class="d-flex align-items-center gap-2 px-2 py-1 rounded" style="background: rgba(54, 162, 235, 0.1); flex: 1 1 45%;">
                        <div style="width: 12px; height: 12px; background: rgba(54, 162, 235, 0.6); border-radius: 3px;"></div>
                        <span class="small fw-medium" style="font-size: 11px;">Anak ♂: <span id="legend-anak-laki">0</span></span>
                    </div>
                    <div class="d-flex align-items-center gap-2 px-2 py-1 rounded" style="background: rgba(255, 99, 132, 0.1); flex: 1 1 45%;">
                        <div style="width: 12px; height: 12px; background: rgba(255, 99, 132, 0.9); border-radius: 3px;"></div>
                        <span class="small fw-medium" style="font-size: 11px;">Dewasa ♀: <span id="legend-dewasa-perempuan">0</span></span>
                    </div>
                    <div class="d-flex align-items-center gap-2 px-2 py-1 rounded" style="background: rgba(255, 99, 132, 0.1); flex: 1 1 45%;">
                        <div style="width: 12px; height: 12px; background: rgba(255, 99, 132, 0.6); border-radius: 3px;"></div>
                        <span class="small fw-medium" style="font-size: 11px;">Anak ♀: <span id="legend-anak-perempuan">0</span></span>
                    </div>
                    <div class="d-flex align-items-center justify-content-center w-100 mt-1 pt-1" style="border-top: 1px dashed var(--border-color);">
                        <span class="small fw-bold text-primary">Total: <span id="legend-total-umur-gender">0</span></span>
                    </div>
                </div>
                <div style="height: 220px;">
                    <canvas id="chartUmurGender"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-6 mb-3">
            <div class="chart-card h-100">
                <h5 class="mb-3"><i class="fas fa-chart-pie me-2"></i>Distribusi Jenis Kelamin</h5>
                <div class="row align-items-center">
                    <div class="col-5">
                        <div style="height: 180px;">
                            <canvas id="chartGenderDistribution"></canvas>
                        </div>
                    </div>
                    <div class="col-7">
                        <div id="genderLegendContainer">
                            <!-- Custom legend akan diisi oleh JavaScript -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Overstaying Donut Charts -->
    <div class="chart-card">
        <h5 class="mb-3"><i class="fas fa-clock me-2 text-danger"></i>Overstaying</h5>
        <div class="mb-3">
            <span class="legend-item"><span class="legend-color" style="background:#22c55e;"></span>Tidak Overstaying (Normal)</span>
            <span class="legend-item"><span class="legend-color" style="background:#ef4444;"></span>Overstaying</span>
        </div>
        <div class="row">
            @php 
            $kategoriLabels = ['ai' => 'AI', 'aii' => 'AII', 'aiii' => 'AIII', 'aiv' => 'AIV', 'av' => 'AV'];
            @endphp
            @foreach ($kategoriLabels as $key => $label)
                @php $stat = $overstayingStats[$key]; @endphp
                <div class="col-4 col-md-2-4 col-lg mb-3">
                    <div class="text-center">
                        <div style="position: relative; width: 100%; max-width: 100px; margin: 0 auto;">
                            <canvas id="chartOverstaying{{ strtoupper($key) }}"></canvas>
                        </div>
                        <div class="mt-2">
                            <strong style="font-size: 14px;">{{ $label }}</strong>
                            <div class="small text-muted">
                                <span class="text-danger">{{ $stat['overstaying'] }}</span> / <span class="text-success">{{ $stat['tahanan'] }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

</div><!-- content-wrapper -->

@if($user && $pengumumanPopup->isNotEmpty())
<!-- Modal Pengumuman Popup - A4 Paper Size -->
<div class="modal fade pengumuman-modal" id="pengumumanPopupModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-bullhorn me-2"></i>Pengumuman & Berita
                    <span class="pengumuman-counter" id="pengumumanCounter"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="pengumumanCarousel" class="carousel slide" data-bs-ride="false" data-bs-interval="false">
                    <div class="carousel-inner">
                        @php 
                        $totalPengumuman = count($pengumumanPopup);
                        @endphp
                        @foreach ($pengumumanPopup as $index => $p)
                        <div class="carousel-item {{ $index === 0 ? 'active' : '' }}">
                            <div class="pengumuman-modal-item">
                                <div class="pengumuman-modal-item-header">
                                    <span class="pengumuman-modal-kategori kategori-{{ strtolower($p->kategori) }}">{{ $p->kategori }}</span>
                                    <span class="pengumuman-modal-judul">{{ $p->judul }}</span>
                                </div>
                                @if($p->gambar && file_exists(public_path($p->gambar)))
                                <div class="pengumuman-modal-image-container">
                                    <img src="{{ asset($p->gambar) }}" class="pengumuman-modal-image" alt="Gambar Pengumuman">
                                </div>
                                @endif
                                <div class="pengumuman-modal-isi">{!! nl2br(e($p->isi)) !!}</div>
                                <div class="pengumuman-modal-meta">
                                    <span><i class="fas fa-clock"></i>{{ $p->created_at ? $p->created_at->format('d/m/Y H:i') : '' }}</span>
                                    @if(!empty($p->upt?->nama_upt))
                                    <span><i class="fas fa-building"></i>{{ $p->upt->nama_upt }}</span>
                                    @endif
                                    @if($p->tipe == 'Global')
                                    <span><i class="fas fa-globe"></i>Global</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @if($totalPengumuman > 1)
                    <button class="carousel-control-prev" type="button" data-bs-target="#pengumumanCarousel" data-bs-slide="prev">
                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Previous</span>
                    </button>
                    <button class="carousel-control-next" type="button" data-bs-target="#pengumumanCarousel" data-bs-slide="next">
                        <span class="carousel-control-next-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Next</span>
                    </button>
                    @endif
                </div>
            </div>
            <div class="pengumuman-modal-footer">
                <i class="fas fa-info-circle me-1"></i>Klik tombol <strong>X</strong> di pojok kanan atas untuk menutup
            </div>
        </div>
    </div>
</div>
@endif

<!-- Welcome Modal -->
<div class="modal fade" id="welcomeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" style="max-width: 800px;">
        <div class="modal-content border-0 shadow-lg" style="background: var(--bg-card, #fff);">
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            <div class="modal-body text-center">
                @if(!empty($instansi->welcome_banner))
                <div class="welcome-banner-container">
                    <img src="{{ asset($instansi->welcome_banner) }}" class="img-fluid welcome-banner-img" alt="Welcome Sultan Banten">
                </div>
                @endif
                <div class="welcome-text-container">
                    <h3 class="fw-bold mb-2" style="color: var(--text-primary, #212529);">Selamat Datang di Sultan Banten</h3>
                    <p class="mb-3" style="color: var(--text-secondary, #6c757d);">Sistem Informasi Layanan Terpadu dan Notifikasi Banten</p>
                    <button type="button" class="btn btn-primary px-5 py-2 shadow-sm" data-bs-dismiss="modal" style="border-radius: 30px; font-weight: 600; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none;">
                        Mulai Jelajahi
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Register ChartDataLabels plugin
    if (typeof ChartDataLabels !== 'undefined') {
        Chart.register(ChartDataLabels);
    }

    // Charts
    @if(!empty($penghuniByKlasifikasi) && $penghuniByKlasifikasi->isNotEmpty())
    (function() {
        const labels = {!! json_encode($penghuniByKlasifikasi->pluck('klasifikasi_pidana')) !!};
        const dataCounts = {!! json_encode($penghuniByKlasifikasi->pluck('total')) !!};
        const colors = ['#11998e', '#667eea', '#764ba2', '#f093fb', '#f5576c', '#4facfe', '#00f2fe', '#43e97b', '#38f9d7', '#fa709a', '#fee140'];
        const total = dataCounts.reduce((a, b) => parseInt(a) + parseInt(b), 0);

        new Chart(document.getElementById('chartKlasifikasi'), {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Jumlah Penghuni',
                    data: dataCounts,
                    backgroundColor: colors,
                    borderRadius: 6,
                    borderWidth: 2,
                    borderColor: 'rgba(255,255,255,0.5)'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                animation: {
                    duration: 1000,
                    easing: 'easeOutQuart'
                },
                plugins: { 
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleFont: { size: 12, weight: 'bold' },
                        bodyFont: { size: 11 },
                        padding: 10,
                        cornerRadius: 6,
                        callbacks: {
                            label: function(context) {
                                const val = context.parsed.y;
                                const pct = total > 0 ? ((val / total) * 100).toFixed(1) : 0;
                                return 'Jumlah: ' + formatNumber(val) + ' (' + pct + '%)';
                            }
                        }
                    },
                    datalabels: {
                        anchor: 'end',
                        align: 'top',
                        color: function() {
                            return document.documentElement.getAttribute('data-theme') === 'dark' ? '#e2e8f0' : '#333';
                        },
                        font: {
                            weight: 'bold',
                            size: 11
                        },
                        formatter: function(value) {
                            return formatNumber(value);
                        }
                    }
                },
                scales: { 
                    y: { 
                        beginAtZero: true, 
                        grid: { color: 'rgba(0, 0, 0, 0.05)' },
                        ticks: { 
                            font: { size: 10 },
                            callback: function(value) {
                                return formatNumber(value);
                            }
                        } 
                    }, 
                    x: { 
                        grid: { display: false },
                        ticks: { maxRotation: 45, minRotation: 45, font: { size: 9 } } 
                    } 
                }
            },
            plugins: [ChartDataLabels]
        });

        // Populate Legend
        const legendContainer = document.getElementById('klasifikasiLegend');
        if (legendContainer) {
            let leftColHtml = '';
            let rightColHtml = '';
            const half = Math.ceil(labels.length / 2);
            
            labels.forEach((label, i) => {
                const count = dataCounts[i];
                const percent = total > 0 ? ((count / total) * 100).toFixed(1) : 0;
                const rowHtml = `
                    <tr>
                        <td style="width: 20px; padding: 4px;">
                            <span style="display: inline-block; width: 14px; height: 14px; background: ${colors[i % colors.length]}; border-radius: 3px;"></span>
                        </td>
                        <td style="font-weight: 500; padding: 4px;">${label}</td>
                        <td class="text-end" style="font-weight: 600; padding: 4px;">${formatNumber(count)}</td>
                        <td class="text-end text-muted" style="width: 60px; padding: 4px;">(${percent}%)</td>
                    </tr>
                `;
                if (i < half) {
                    leftColHtml += rowHtml;
                } else {
                    rightColHtml += rowHtml;
                }
            });
            
            legendContainer.innerHTML = `
                <div class="row">
                    <div class="col-md-6 pe-md-1">
                        <table class="table table-sm table-borderless table-hover mb-0" style="font-size: 11px;">
                            <tbody>
                                ${leftColHtml}
                            </tbody>
                        </table>
                    </div>
                    <div class="col-md-6 ps-md-1 mt-2 mt-md-0">
                        <table class="table table-sm table-borderless table-hover mb-0" style="font-size: 11px;">
                            <tbody>
                                ${rightColHtml}
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="border-top mt-2 pt-2">
                    <table class="table table-sm table-borderless mb-0 w-100" style="font-size: 12px;">
                        <tr>
                            <td style="width: 20px; padding: 4px;"></td>
                            <td style="font-weight: 600; padding: 4px;">Total</td>
                            <td class="text-end" style="font-weight: 700; padding: 4px;">${formatNumber(total)}</td>
                            <td class="text-end text-muted" style="width: 60px; padding: 4px;">(100.0%)</td>
                        </tr>
                    </table>
                </div>
            `;
        }
    })();
    @endif

    // Pie Chart Distribusi Narapidana & Tahanan
    new Chart(document.getElementById('chartNarapidanaTahanan'), {
        type: 'pie',
        data: {
            labels: ['Narapidana', 'Tahanan'],
            datasets: [{
                data: [{{ $stats['total_narapidana'] }}, {{ $stats['total_tahanan'] }}],
                backgroundColor: ['#667eea', '#f5576c'],
                borderWidth: 2,
                borderColor: 'transparent'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            animation: {
                duration: 1000,
                easing: 'easeOutQuart'
            },
            plugins: {
                legend: {
                    display: true,
                    position: 'bottom',
                    labels: {
                        font: { size: 11 },
                        padding: 15,
                        usePointStyle: true
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    titleFont: { size: 12, weight: 'bold' },
                    bodyFont: { size: 11 },
                    padding: 10,
                    cornerRadius: 6,
                    callbacks: {
                        label: function(context) {
                            var total = context.dataset.data.reduce((a, b) => a + b, 0);
                            var percentage = total > 0 ? ((context.parsed / total) * 100).toFixed(1) : 0;
                            return context.label + ': ' + formatNumber(context.parsed) + ' (' + percentage + '%)';
                        }
                    }
                },
                datalabels: {
                    color: '#fff',
                    font: {
                        weight: 'bold',
                        size: 12
                    },
                    formatter: function(value, context) {
                        var total = context.dataset.data.reduce((a, b) => a + b, 0);
                        var percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                        return percentage + '%';
                    }
                }
            }
        },
        plugins: [ChartDataLabels]
    });

    // Overstaying Donut Charts
    @foreach ($overstayingStats as $key => $stat)
    (function() {
        const ctx = document.getElementById('chartOverstaying{{ strtoupper($key) }}');
        if (!ctx) return;
        
        const normal = {{ $stat['normal'] }};
        const overstaying = {{ $stat['overstaying'] }};
        
        if (normal === 0 && overstaying === 0) {
            // Empty state
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Tidak Ada Data'],
                    datasets: [{
                        data: [1],
                        backgroundColor: ['#e9ecef'],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    cutout: '55%',
                    plugins: {
                        legend: { display: false },
                        tooltip: { enabled: false }
                    }
                }
            });
        } else {
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Normal', 'Overstaying'],
                    datasets: [{
                        data: [normal, overstaying],
                        backgroundColor: ['rgba(34, 197, 94, 0.9)', 'rgba(239, 68, 68, 0.9)'],
                        borderWidth: 2,
                        borderColor: '#fff',
                        hoverOffset: 8,
                        hoverBorderWidth: 3
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    cutout: '55%',
                    animation: {
                        animateRotate: true,
                        animateScale: true,
                        duration: 800
                    },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            titleFont: { size: 11, weight: 'bold' },
                            bodyFont: { size: 10 },
                            padding: 8,
                            cornerRadius: 6,
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.parsed || 0;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                    return label + ': ' + value + ' (' + percentage + '%)';
                                }
                            }
                        }
                    }
                }
            });
        }
    })();
    @endforeach

    // Chart Penghuni Berdasarkan Umur dan Jenis Kelamin (Grouped Bar Chart)
    const umurGenderData = {!! json_encode($umurGenderData) !!};
    const intDewasaLaki = parseInt(umurGenderData?.dewasa_laki) || 0;
    const intAnakLaki = parseInt(umurGenderData?.anak_laki) || 0;
    const intDewasaPerempuan = parseInt(umurGenderData?.dewasa_perempuan) || 0;
    const intAnakPerempuan = parseInt(umurGenderData?.anak_perempuan) || 0;

    // Helper function for Indonesian number format
    function formatNumber(num) {
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    }

    // Update legend values with percentages
    const totalUmurGender = intDewasaLaki + intAnakLaki + intDewasaPerempuan + intAnakPerempuan;
    
    function getLegendText(val) {
        const percent = totalUmurGender > 0 ? ((val / totalUmurGender) * 100).toFixed(1) : 0;
        return formatNumber(val) + ' (' + percent + '%)';
    }

    if (document.getElementById('legend-dewasa-laki')) {
        document.getElementById('legend-dewasa-laki').textContent = getLegendText(intDewasaLaki);
        document.getElementById('legend-anak-laki').textContent = getLegendText(intAnakLaki);
        document.getElementById('legend-dewasa-perempuan').textContent = getLegendText(intDewasaPerempuan);
        document.getElementById('legend-anak-perempuan').textContent = getLegendText(intAnakPerempuan);
        document.getElementById('legend-total-umur-gender').textContent = formatNumber(totalUmurGender);
    }

    new Chart(document.getElementById('chartUmurGender'), {
        type: 'bar',
        data: {
            labels: ['Dewasa', 'Anak'],
            datasets: [
                {
                    label: 'Laki-laki',
                    data: [intDewasaLaki, intAnakLaki],
                    backgroundColor: 'rgba(54, 162, 235, 0.9)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 2,
                    borderRadius: 6,
                    hoverBackgroundColor: 'rgba(54, 162, 235, 1)',
                    hoverBorderColor: 'rgba(54, 162, 235, 1)',
                    hoverBorderWidth: 3
                },
                {
                    label: 'Perempuan',
                    data: [intDewasaPerempuan, intAnakPerempuan],
                    backgroundColor: 'rgba(255, 99, 132, 0.9)',
                    borderColor: 'rgba(255, 99, 132, 1)',
                    borderWidth: 2,
                    borderRadius: 6,
                    hoverBackgroundColor: 'rgba(255, 99, 132, 1)',
                    hoverBorderColor: 'rgba(255, 99, 132, 1)',
                    hoverBorderWidth: 3
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: {
                duration: 1000,
                easing: 'easeOutQuart'
            },
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    titleFont: { size: 13, weight: 'bold' },
                    bodyFont: { size: 12 },
                    padding: 12,
                    cornerRadius: 8,
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': ' + formatNumber(context.parsed.y) + ' orang';
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    },
                    ticks: {
                        font: { size: 11 },
                        callback: function(value) {
                            return formatNumber(value);
                        }
                    }
                },
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        font: { size: 12, weight: '500' }
                    }
                }
            }
        }
    });

    // Chart Distribusi Jenis Kelamin (Doughnut Chart)
    const totalLaki = intDewasaLaki + intAnakLaki;
    const totalPerempuan = intDewasaPerempuan + intAnakPerempuan;
    const totalGender = totalLaki + totalPerempuan;

    new Chart(document.getElementById('chartGenderDistribution'), {
        type: 'doughnut',
        data: {
            labels: ['Laki-laki', 'Perempuan'],
            datasets: [{
                data: [totalLaki, totalPerempuan],
                backgroundColor: [
                    'rgba(54, 162, 235, 0.8)',
                    'rgba(255, 99, 132, 0.8)'
                ],
                borderColor: [
                    'rgba(54, 162, 235, 1)',
                    'rgba(255, 99, 132, 1)'
                ],
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '50%',
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    callbacks: {
                        label: function(context) {
                            const value = context.parsed;
                            const percentage = totalGender > 0 ? ((value / totalGender) * 100).toFixed(1) : 0;
                            return context.label + ': ' + value.toLocaleString('id-ID') + ' (' + percentage + '%)';
                        }
                    }
                }
            }
        }
    });

    // Custom Legend dengan jumlah dan persentase
    const persenLaki = totalGender > 0 ? ((totalLaki / totalGender) * 100).toFixed(1) : 0;
    const persenPerempuan = totalGender > 0 ? ((totalPerempuan / totalGender) * 100).toFixed(1) : 0;

    if (document.getElementById('genderLegendContainer')) {
        document.getElementById('genderLegendContainer').innerHTML = `
            <table class="table table-sm table-borderless mb-0" style="font-size: 12px;">
                <tbody>
                    <tr>
                        <td style="width: 20px;">
                            <span style="display: inline-block; width: 14px; height: 14px; background: rgba(54, 162, 235, 0.8); border-radius: 3px;"></span>
                        </td>
                        <td style="width: 80px; font-weight: 500;">Laki-laki</td>
                        <td class="text-end" style="font-weight: 600;">${totalLaki.toLocaleString('id-ID')}</td>
                        <td class="text-end" style="color: var(--text-secondary);">(${persenLaki}%)</td>
                    </tr>
                    <tr>
                        <td>
                            <span style="display: inline-block; width: 14px; height: 14px; background: rgba(255, 99, 132, 0.8); border-radius: 3px;"></span>
                        </td>
                        <td style="font-weight: 500;">Perempuan</td>
                        <td class="text-end" style="font-weight: 600;">${totalPerempuan.toLocaleString('id-ID')}</td>
                        <td class="text-end" style="color: var(--text-secondary);">(${persenPerempuan}%)</td>
                    </tr>
                    <tr style="border-top: 1px solid var(--border-color);">
                        <td colspan="2" style="font-weight: 600;">Total Penghuni</td>
                        <td class="text-end" style="font-weight: 700;">${totalGender.toLocaleString('id-ID')}</td>
                        <td class="text-end" style="color: var(--text-secondary);">(100%)</td>
                    </tr>
                </tbody>
            </table>
        `;
    }

    // Define Map Layers
    const streetLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    });

    const satelliteLayer = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
        attribution: 'Tiles &copy; Esri &mdash; Source: Esri, i-cubed, USDA, USGS, AEX, GeoEye, Getmapping, Aerogrid, IGN, IGP, UPR-EGP, and the GIS User Community'
    });

    const hybridLayer = L.layerGroup([
        satelliteLayer,
        L.tileLayer('https://services.arcgisonline.com/ArcGIS/rest/services/Reference/World_Boundaries_and_Places/MapServer/tile/{z}/{y}/{x}', {
            attribution: 'Labels &copy; Esri'
        })
    ]);

    // Initialize Map - Peta Banten with Hybrid as default
    const mapBanten = L.map('mapBanten', {
        layers: [hybridLayer]
    }).setView([-6.4409, 106.1385], 9);

    // Map Mode Switcher Logic
    const btnStreet = document.getElementById('btnStreet');
    const btnSatellite = document.getElementById('btnSatellite');
    const btnHybrid = document.getElementById('btnHybrid');

    function updateActiveMapBtn(activeBtn) {
        [btnStreet, btnSatellite, btnHybrid].forEach(btn => btn?.classList.remove('active'));
        activeBtn?.classList.add('active');
    }

    btnStreet?.addEventListener('click', function() {
        mapBanten.removeLayer(satelliteLayer);
        mapBanten.removeLayer(hybridLayer);
        streetLayer.addTo(mapBanten);
        updateActiveMapBtn(this);
    });

    btnSatellite?.addEventListener('click', function() {
        mapBanten.removeLayer(streetLayer);
        mapBanten.removeLayer(hybridLayer);
        satelliteLayer.addTo(mapBanten);
        updateActiveMapBtn(this);
    });

    btnHybrid?.addEventListener('click', function() {
        mapBanten.removeLayer(streetLayer);
        mapBanten.removeLayer(satelliteLayer);
        hybridLayer.addTo(mapBanten);
        updateActiveMapBtn(this);
    });

    // Banten Province Boundary (simplified GeoJSON)
    const bantenBoundary = {
        "type": "Feature",
        "properties": {"name": "Banten"},
        "geometry": {
            "type": "Polygon",
            "coordinates": [[
                [105.1, -5.9], [105.3, -5.85], [105.6, -5.9], [105.8, -6.0],
                [106.0, -6.1], [106.2, -6.2], [106.4, -6.3], [106.6, -6.5],
                [106.8, -6.6], [106.9, -6.7], [107.0, -6.85], [106.95, -7.0],
                [106.8, -7.1], [106.6, -7.05], [106.4, -6.95], [106.2, -6.85],
                [106.0, -6.75], [105.8, -6.6], [105.6, -6.45], [105.4, -6.3],
                [105.2, -6.1], [105.1, -5.9]
            ]]
        }
    };

    // Add boundary layer
    L.geoJSON(bantenBoundary, {
        style: {
            color: '#667eea',
            weight: 2,
            opacity: 0.6,
            fillColor: '#667eea',
            fillOpacity: 0.1
        }
    }).addTo(mapBanten);

    // Custom marker icon
    const uptIcon = L.divIcon({
        className: 'custom-marker',
        html: '<div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 14px; box-shadow: 0 2px 8px rgba(0,0,0,0.3); border: 2px solid white;"><i class="fas fa-building" style="font-size: 12px;"></i></div>',
        iconSize: [32, 32],
        iconAnchor: [16, 16],
        popupAnchor: [0, -16]
    });

    // Add markers for each UPT
    const uptLocations = {!! json_encode($uptLocations) !!};
    const markers = [];

    uptLocations.forEach(upt => {
        const totalPenghuni = parseInt(upt.total_penghuni) || 0;
        const kapasitas = parseInt(upt.kapasitas) || 0;
        const persen = kapasitas > 0 ? Math.round(((totalPenghuni - kapasitas) / kapasitas) * 100) : 0;
        const persenText = persen > 0 ? '+' + persen + '%' : persen + '%';
        
        // Determine color based on capacity percentage (new formula)
        let statusColor = '#22c55e'; // green (masih longgar)
        let statusText = 'Masih Longgar';
        if (persen > 100) { statusColor = '#b71c1c'; statusText = 'Sangat Over'; }
        else if (persen > 20) { statusColor = '#ef4444'; statusText = 'Over Kapasitas'; }
        else if (persen > 0) { statusColor = '#ff9800'; statusText = 'Sedikit Over'; }
        else if (persen === 0) { statusColor = '#eab308'; statusText = 'Pas Penuh'; }
        else if (persen > -25) { statusColor = '#06b6d4'; statusText = 'Hampir Penuh'; }

        if (upt.latitude && upt.longitude) {
            const marker = L.marker([parseFloat(upt.latitude), parseFloat(upt.longitude)], { icon: uptIcon })
                .addTo(mapBanten)
                .bindPopup(`
                    <div style="min-width: 220px;">
                        <h6 style="margin: 0 0 8px 0; color: #667eea; font-weight: bold;">
                            <i class="fas fa-building"></i> ${upt.nama_upt}
                        </h6>
                        <p style="margin: 0 0 5px 0; font-size: 12px;">
                            <i class="fas fa-map-marker-alt text-danger"></i> ${upt.alamat || 'Alamat tidak tersedia'}
                        </p>
                        <hr style="margin: 8px 0;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 3px;">
                            <span style="font-size: 12px;"><i class="fas fa-bed text-info"></i> Kapasitas:</span>
                            <strong style="font-size: 12px;">${formatNumber(kapasitas)}</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 3px;">
                            <span style="font-size: 12px;"><i class="fas fa-users text-primary"></i> Penghuni:</span>
                            <strong style="font-size: 12px;">${formatNumber(totalPenghuni)}</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span style="font-size: 12px;"><i class="fas fa-percentage" style="color:${statusColor}"></i> Status:</span>
                            <strong style="font-size: 12px; color: ${statusColor};">${persenText} (${statusText})</strong>
                        </div>
                    </div>
                `);
            markers.push(marker);
        }
    });

    // Fit bounds to show all markers if there are any
    if (markers.length > 0) {
        const group = L.featureGroup(markers);
        mapBanten.fitBounds(group.getBounds().pad(0.1));
    }

    // Fix map display issue when in hidden container
    setTimeout(() => {
        mapBanten.invalidateSize();
    }, 500);

    @if($user && $pengumumanPopup->isNotEmpty())
    // Show pengumuman popup on page load
    document.addEventListener('DOMContentLoaded', function() {
        var pengumumanModalEl = document.getElementById('pengumumanPopupModal');
        if (pengumumanModalEl) {
            var pengumumanModal = new bootstrap.Modal(pengumumanModalEl);
            pengumumanModal.show();
            
            @if(count($pengumumanPopup) > 1)
            var carousel = document.getElementById('pengumumanCarousel');
            var counter = document.getElementById('pengumumanCounter');
            var totalSlides = {{ count($pengumumanPopup) }};
            
            function updateCounter() {
                var activeItem = carousel.querySelector('.carousel-item.active');
                var items = carousel.querySelectorAll('.carousel-item');
                var currentIndex = Array.from(items).indexOf(activeItem) + 1;
                if (counter) counter.textContent = currentIndex + ' / ' + totalSlides;
            }
            
            carousel.addEventListener('slid.bs.carousel', updateCounter);
            updateCounter();
            @endif
        }
    });
    @endif

    // Welcome Modal
    document.addEventListener('DOMContentLoaded', function() {
        // Show welcome modal if it hasn't been shown in this session
        // AND if there is a banner to show
        if (!sessionStorage.getItem('welcomeShown') && {{ !empty($instansi->welcome_banner) ? 'true' : 'false' }}) {
            var welcomeModalElement = document.getElementById('welcomeModal');
            if (welcomeModalElement) {
                var welcomeModal = new bootstrap.Modal(welcomeModalElement);
                setTimeout(function() {
                    welcomeModal.show();
                }, 1000); 
                sessionStorage.setItem('welcomeShown', 'true');
            }
        }
    });
</script>
@endpush
