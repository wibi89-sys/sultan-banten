@extends('layouts.app')

@section('title', 'Pengaturan WhatsApp - Sultan Banten')
@section('page_title')
    <i class="fab fa-whatsapp text-success me-2"></i>Pengaturan WhatsApp
@endsection

@push('styles')
<style>
    .wa-icon { color: #25D366; }
    .form-switch .form-check-input { width: 3em; height: 1.5em; }
</style>
@endpush

@section('content')
<div class="row g-4">
    <!-- Settings Form -->
    <div class="col-lg-6">
        <div class="content-card h-100">
            <h5 class="fw-bold fs-6 pb-2 mb-3 border-bottom"><i class="fas fa-cog text-primary me-2"></i>Konfigurasi Gateway WhatsApp</h5>
            
            <form action="{{ route('wa.settings.update') }}" method="POST">
                @csrf
                <div class="row mb-3 align-items-center">
                    <label class="col-sm-4 col-form-label small fw-semibold">Gateway</label>
                    <div class="col-sm-8">
                        <select name="gateway_name" class="form-select form-select-sm">
                            <option value="fonnte" {{ ($waSettings->gateway_name ?? 'fonnte') == 'fonnte' ? 'selected' : '' }}>Fonnte</option>
                            <option value="wablas" {{ ($waSettings->gateway_name ?? '') == 'wablas' ? 'selected' : '' }}>Wablas</option>
                            <option value="starsender" {{ ($waSettings->gateway_name ?? '') == 'starsender' ? 'selected' : '' }}>Starsender</option>
                            <option value="other" {{ ($waSettings->gateway_name ?? '') == 'other' ? 'selected' : '' }}>Lainnya</option>
                        </select>
                    </div>
                </div>
                
                <div class="row mb-3 align-items-center">
                    <label class="col-sm-4 col-form-label small fw-semibold">API Key</label>
                    <div class="col-sm-8">
                        <input type="text" name="api_key" class="form-control form-control-sm" 
                            value="{{ $waSettings->api_key ?? '' }}" 
                            placeholder="Masukkan API Key dari gateway">
                    </div>
                </div>
                
                <div class="row mb-3 align-items-center">
                    <label class="col-sm-4 col-form-label small fw-semibold">API URL</label>
                    <div class="col-sm-8">
                        <input type="text" name="api_url" class="form-control form-control-sm" 
                            value="{{ $waSettings->api_url ?? 'https://api.fonnte.com/send' }}" 
                            placeholder="URL endpoint API">
                    </div>
                </div>
                
                <div class="row mb-3 align-items-center">
                    <label class="col-sm-4 col-form-label small fw-semibold">Group ID</label>
                    <div class="col-sm-8">
                        <input type="text" name="group_id" class="form-control form-control-sm" 
                            value="{{ $waSettings->group_id ?? '' }}" 
                            placeholder="ID Grup WhatsApp untuk laporan">
                        <small class="text-muted d-block mt-1" style="font-size: 11px;">
                            <strong>Fonnte:</strong> Kode undangan grup atau Group JID (<code>120363xxx@g.us</code>)
                        </small>
                    </div>
                </div>
                
                <div class="row mb-3 align-items-center">
                    <label class="col-sm-4 col-form-label small fw-semibold">Reminder Pagi</label>
                    <div class="col-sm-8">
                        <input type="time" name="jam_reminder_pagi" class="form-control form-control-sm" 
                            value="{{ substr($waSettings->jam_reminder_pagi ?? '07:30:00', 0, 5) }}">
                        <small class="text-muted" style="font-size: 11px;">Waktu kirim reminder ke operator</small>
                    </div>
                </div>
                
                <div class="row mb-3 align-items-center">
                    <label class="col-sm-4 col-form-label small fw-semibold">Laporan Siang</label>
                    <div class="col-sm-8">
                        <input type="time" name="jam_reminder_siang" class="form-control form-control-sm" 
                            value="{{ substr($waSettings->jam_reminder_siang ?? '12:00:00', 0, 5) }}">
                        <small class="text-muted" style="font-size: 11px;">Waktu kirim laporan ke grup</small>
                    </div>
                </div>
                
                <div class="row mb-3 align-items-center">
                    <label class="col-sm-4 col-form-label small fw-semibold">Status Gateway</label>
                    <div class="col-sm-8">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="aktif" id="aktif" 
                                {{ !empty($waSettings->aktif) ? 'checked' : '' }}>
                            <label class="form-check-label ms-2" for="aktif">
                                <span class="badge {{ !empty($waSettings->aktif) ? 'bg-success' : 'bg-secondary' }}">
                                    {{ !empty($waSettings->aktif) ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </label>
                        </div>
                    </div>
                </div>
                
                <hr class="my-3">
                <h6 class="mb-3 fw-bold fs-6"><i class="fas fa-file-alt text-primary me-2"></i>Template Pesan</h6>
                
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Template Pesan Pagi (Reminder Input Data)</label>
                    <textarea name="template_pagi" class="form-control form-control-sm" rows="4" 
                        placeholder="Template pesan untuk reminder pagi...">{{ $waSettings->template_pagi ?? '' }}</textarea>
                    <small class="text-muted" style="font-size: 11px;">
                        <strong>Variabel:</strong> <code>{nama}</code>, <code>{upt}</code>, <code>{jam_mulai}</code>, <code>{jam_selesai}</code>, <code>{list_missing}</code>
                    </small>
                </div>
                
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Template Pesan Siang (Laporan Belum Input)</label>
                    <textarea name="template_siang" class="form-control form-control-sm" rows="4" 
                        placeholder="Template pesan untuk laporan siang...">{{ $waSettings->template_siang ?? '' }}</textarea>
                    <small class="text-muted" style="font-size: 11px;">
                        <strong>Variabel:</strong> <code>{tanggal}</code>, <code>{waktu}</code>, <code>{list_belum_input}</code>, <code>{total_belum}</code>
                    </small>
                </div>
                
                <div class="text-end">
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="fas fa-save me-1"></i> Simpan Pengaturan
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Test Tools & Operator List -->
    <div class="col-lg-6">
        <div class="content-card mb-4">
            <h5 class="fw-bold fs-6 pb-2 mb-3 border-bottom"><i class="fas fa-paper-plane text-success me-2"></i>Test & Kirim Manual</h5>
            
            <!-- Test Message Personal -->
            <form action="{{ route('wa.settings.test') }}" method="POST" class="mb-4">
                @csrf
                <input type="hidden" name="action" value="test_message">
                <label class="form-label small fw-semibold">Test Kirim Pesan (Nomor Pribadi)</label>
                <div class="row g-2 mb-2">
                    <div class="col-5">
                        <input type="text" name="test_number" class="form-control form-control-sm" placeholder="Nomor WA (08xxx)" required>
                    </div>
                    <div class="col-7">
                        <input type="text" name="test_message" class="form-control form-control-sm" placeholder="Pesan test" value="Test dari Sultan Banten">
                    </div>
                </div>
                <button type="submit" class="btn btn-outline-success btn-sm">
                    <i class="fab fa-whatsapp me-1"></i> Test ke Nomor
                </button>
            </form>
            
            <!-- Test Group -->
            <form action="{{ route('wa.settings.test') }}" method="POST" class="mb-4">
                @csrf
                <input type="hidden" name="action" value="test_group">
                <label class="form-label small fw-semibold">Test Kirim ke Grup</label>
                <div class="mb-2">
                    <small class="text-muted">Target Group ID: <code>{{ $waSettings->group_id ?? '-' }}</code></small>
                </div>
                <button type="submit" class="btn btn-outline-primary btn-sm">
                    <i class="fas fa-users me-1"></i> Test ke Grup
                </button>
            </form>
            
            <!-- Manual Triggers -->
            <label class="form-label small fw-semibold d-block">Kirim Notifikasi Massal Manual</label>
            <div class="d-flex gap-2 flex-wrap">
                <form action="{{ route('wa.settings.test') }}" method="POST" class="d-inline">
                    @csrf
                    <input type="hidden" name="action" value="send_morning">
                    <button type="submit" class="btn btn-warning btn-sm" onclick="return confirm('Kirim reminder pagi ke operator sekarang?');">
                        <i class="fas fa-sun me-1"></i> Kirim Reminder Pagi
                    </button>
                </form>
                <form action="{{ route('wa.settings.test') }}" method="POST" class="d-inline">
                    @csrf
                    <input type="hidden" name="action" value="send_afternoon">
                    <button type="submit" class="btn btn-info text-white btn-sm" onclick="return confirm('Kirim laporan monitoring siang ke grup sekarang?');">
                        <i class="fas fa-chart-line me-1"></i> Kirim Laporan Siang
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Operators List -->
        <div class="content-card">
            <h5 class="fw-bold fs-6 pb-2 mb-3 border-bottom"><i class="fas fa-users text-primary me-2"></i>Daftar Operator (Target Reminder)</h5>
            <div class="table-responsive" style="max-height: 280px; overflow-y: auto;">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="sticky-top bg-light">
                        <tr>
                            <th>Nama</th>
                            <th>UPT</th>
                            <th>No. WA</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($operators as $op)
                        <tr>
                            <td class="fw-semibold">{{ $op->nama_lengkap ?? $op->username }}</td>
                            <td>{{ $op->upt?->nama_upt ?? '-' }}</td>
                            <td>
                                @if(!empty($op->no_wa))
                                    <code>{{ $op->no_wa }}</code>
                                @else
                                    <span class="text-muted small">Belum diisi</span>
                                @endif
                            </td>
                            <td>
                                @if(!empty($op->no_wa))
                                    <span class="badge bg-success">Siap</span>
                                @else
                                    <span class="badge bg-secondary">Kosong</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-3">Tidak ada data operator</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Notification Logs -->
<div class="content-card mt-4">
    <h5 class="fw-bold fs-6 pb-2 mb-3 border-bottom"><i class="fas fa-history text-secondary me-2"></i>Log Pengiriman Notifikasi Terakhir</h5>
    <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Waktu</th>
                    <th>Tipe</th>
                    <th>Target</th>
                    <th>Pesan / Deskripsi</th>
                    <th>Penerima</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($logs as $log)
                <tr>
                    <td class="small text-muted">{{ date('d/m/Y H:i', strtotime($log->tanggal_kirim)) }}</td>
                    <td><span class="badge bg-light text-dark border">{{ $log->tipe }}</span></td>
                    <td><code>{{ $log->target }}</code></td>
                    <td class="small">{{ Str::limit($log->pesan, 70) }}</td>
                    <td>{{ $log->jumlah_penerima ?? 1 }}</td>
                    <td>
                        @if($log->status === 'success')
                            <span class="badge bg-success">Berhasil</span>
                        @elseif($log->status === 'partial')
                            <span class="badge bg-warning text-dark">Sebagian</span>
                        @else
                            <span class="badge bg-danger">Gagal</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center text-muted py-3">Belum ada riwayat notifikasi</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
