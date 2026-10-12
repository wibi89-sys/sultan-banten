@extends('layouts.app')

@section('title', 'Jadwal Laporan WhatsApp - Sultan Banten')
@section('page_title')
    <i class="fas fa-clock text-primary me-2"></i>Jadwal Laporan WhatsApp
@endsection

@section('content')
<div class="content-card mb-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <div>
            <h5 class="mb-1 fw-bold"><i class="fas fa-calendar-alt text-primary me-2"></i>Daftar Jadwal Pengiriman Laporan</h5>
            <p class="text-muted small mb-0">Kelola jadwal laporan otomatis ke grup WhatsApp atau daftar pimpinan</p>
        </div>
        <button class="btn btn-primary btn-sm px-3" data-bs-toggle="modal" data-bs-target="#modalAddSchedule">
            <i class="fas fa-plus me-1"></i> Tambah Jadwal
        </button>
    </div>

    <div class="table-responsive">
        <table class="table table-hover table-sm align-middle mb-0">
            <thead>
                <tr>
                    <th width="4%">No</th>
                    <th>Nama Laporan</th>
                    <th>Target</th>
                    <th>Jam Kirim</th>
                    <th>Frekuensi</th>
                    <th>Pimpinan</th>
                    <th>Terakhir Dikirim</th>
                    <th>Status</th>
                    <th width="15%" class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($schedules as $i => $s)
                <tr>
                    <td class="text-muted">{{ $i + 1 }}</td>
                    <td class="fw-semibold">{{ $s->nama_laporan }}</td>
                    <td><code>{{ $s->group_id }}</code></td>
                    <td><span class="badge bg-light text-dark border">{{ substr($s->jam_kirim, 0, 5) }} WIB</span></td>
                    <td><span class="badge bg-info text-dark">{{ ucfirst($s->hari) }}</span></td>
                    <td>
                        @if($s->kirim_ke_pimpinan)
                            <span class="badge bg-success"><i class="fas fa-check me-1"></i>Ya</span>
                        @else
                            <span class="badge bg-secondary">Tidak</span>
                        @endif
                    </td>
                    <td class="small text-muted">{{ $s->last_sent ? $s->last_sent->format('d/m/Y H:i') : '-' }}</td>
                    <td>
                        <form action="{{ route('wa.schedule.toggle', $s->id) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-xs py-0 px-2 badge {{ $s->aktif ? 'bg-success' : 'bg-secondary' }}" style="border:none; cursor:pointer;" title="Klik untuk ubah status">
                                {{ $s->aktif ? 'Aktif' : 'Nonaktif' }}
                            </button>
                        </form>
                    </td>
                    <td class="text-center">
                        <div class="btn-group btn-group-sm">
                            <!-- Send Now -->
                            <form action="{{ route('wa.schedule.sendNow', $s->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Kirim laporan ini sekarang?');">
                                @csrf
                                <button type="submit" class="btn btn-outline-success btn-sm" title="Kirim Sekarang">
                                    <i class="fas fa-paper-plane"></i>
                                </button>
                            </form>
                            <!-- Edit -->
                            <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalEdit{{ $s->id }}" title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                            <!-- Delete -->
                            <form action="{{ route('wa.schedule.destroy', $s->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus jadwal laporan ini?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-outline-danger btn-sm" title="Hapus">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>

                        <!-- Modal Edit -->
                        <div class="modal fade" id="modalEdit{{ $s->id }}" tabindex="-1">
                            <div class="modal-dialog modal-lg modal-dialog-centered text-start">
                                <div class="modal-content">
                                    <div class="modal-header py-2 bg-primary text-white">
                                        <h5 class="modal-title fs-6"><i class="fas fa-edit me-2"></i>Edit Jadwal Laporan</h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                    </div>
                                    <form action="{{ route('wa.schedule.update', $s->id) }}" method="POST">
                                        @csrf
                                        @method('PUT')
                                        <div class="modal-body">
                                            <div class="row g-3 mb-3">
                                                <div class="col-md-6">
                                                    <label class="form-label small fw-semibold">Nama Laporan <span class="text-danger">*</span></label>
                                                    <input type="text" name="nama_laporan" class="form-control form-control-sm" value="{{ $s->nama_laporan }}" required>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label small fw-semibold">Target Penerima</label>
                                                    <select name="target_penerima" class="form-select form-select-sm">
                                                        <option value="grup" {{ !$s->kirim_ke_pimpinan && $s->group_id !== '-' ? 'selected' : '' }}>Hanya Grup</option>
                                                        <option value="pimpinan" {{ $s->kirim_ke_pimpinan && $s->group_id === '-' ? 'selected' : '' }}>Hanya Pimpinan</option>
                                                        <option value="keduanya" {{ $s->kirim_ke_pimpinan && $s->group_id !== '-' ? 'selected' : '' }}>Grup & Pimpinan</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label small fw-semibold">Group ID</label>
                                                    <input type="text" name="group_id" class="form-control form-control-sm" value="{{ $s->group_id }}">
                                                </div>
                                                <div class="col-md-3">
                                                    <label class="form-label small fw-semibold">Jam Kirim <span class="text-danger">*</span></label>
                                                    <input type="time" name="jam_kirim" class="form-control form-control-sm" value="{{ substr($s->jam_kirim, 0, 5) }}" required>
                                                </div>
                                                <div class="col-md-3">
                                                    <label class="form-label small fw-semibold">Hari / Frekuensi</label>
                                                    <select name="hari" class="form-select form-select-sm">
                                                        <option value="daily" {{ $s->hari === 'daily' ? 'selected' : '' }}>Setiap Hari (Daily)</option>
                                                        <option value="weekdays" {{ $s->hari === 'weekdays' ? 'selected' : '' }}>Hari Kerja (Senin-Jumat)</option>
                                                        <option value="monday" {{ $s->hari === 'monday' ? 'selected' : '' }}>Senin Saja</option>
                                                        <option value="friday" {{ $s->hari === 'friday' ? 'selected' : '' }}>Jumat Saja</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label small fw-semibold">Template Pesan</label>
                                                <textarea name="template_pesan" class="form-control form-control-sm" rows="8">{{ $s->template_pesan }}</textarea>
                                            </div>
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="aktif" id="aktifEdit{{ $s->id }}" {{ $s->aktif ? 'checked' : '' }}>
                                                <label class="form-check-label small" for="aktifEdit{{ $s->id }}">Jadwal Aktif</label>
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
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="text-center text-muted py-4">Belum ada jadwal laporan</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Available Variables Helper -->
<div class="content-card mb-4">
    <h6 class="fw-bold mb-3"><i class="fas fa-tags text-primary me-2"></i>Daftar Tag Variabel untuk Template Pesan</h6>
    <div class="row g-2">
        @foreach ($variables as $tag => $desc)
        <div class="col-md-3 col-sm-6">
            <div class="p-2 border rounded bg-light">
                <code class="fw-bold text-primary">&#123;{{ $tag }}&#125;</code>
                <div class="small text-muted" style="font-size: 11px;">{{ $desc }}</div>
            </div>
        </div>
        @endforeach
    </div>
</div>

<!-- Modal Add Schedule -->
<div class="modal fade" id="modalAddSchedule" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header py-2 bg-primary text-white">
                <h5 class="modal-title fs-6"><i class="fas fa-plus-circle me-2"></i>Tambah Jadwal Laporan WhatsApp</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('wa.schedule.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Nama Laporan <span class="text-danger">*</span></label>
                            <input type="text" name="nama_laporan" class="form-control form-control-sm" placeholder="Contoh: Laporan Sore Harian" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Target Penerima</label>
                            <select name="target_penerima" class="form-select form-select-sm">
                                <option value="grup">Hanya Grup WhatsApp</option>
                                <option value="pimpinan">Hanya Daftar Pimpinan</option>
                                <option value="keduanya">Keduanya (Grup & Pimpinan)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Group ID</label>
                            <input type="text" name="group_id" class="form-control form-control-sm" placeholder="ID grup WhatsApp (jika kirim ke grup)">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Jam Kirim <span class="text-danger">*</span></label>
                            <input type="time" name="jam_kirim" class="form-control form-control-sm" value="16:00" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Hari / Frekuensi</label>
                            <select name="hari" class="form-select form-select-sm">
                                <option value="daily">Setiap Hari (Daily)</option>
                                <option value="weekdays">Hari Kerja (Senin-Jumat)</option>
                                <option value="monday">Senin Saja</option>
                                <option value="friday">Jumat Saja</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Template Pesan</label>
                        <textarea name="template_pesan" class="form-control form-control-sm" rows="8">{{ $defaultTemplate }}</textarea>
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary btn-sm px-3">Tambah Jadwal</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Execution Logs -->
<div class="content-card">
    <h5 class="fw-bold fs-6 pb-2 mb-3 border-bottom"><i class="fas fa-history text-secondary me-2"></i>Log Pengiriman Jadwal Terakhir</h5>
    <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Waktu</th>
                    <th>Jadwal Laporan</th>
                    <th>Status</th>
                    <th>Response</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($logs as $log)
                <tr>
                    <td class="small text-muted">{{ date('d/m/Y H:i', strtotime($log->tanggal_kirim ?? 'now')) }}</td>
                    <td class="fw-semibold">{{ $log->nama_laporan }}</td>
                    <td>
                        <span class="badge {{ $log->status === 'success' ? 'bg-success' : 'bg-danger' }}">
                            {{ ucfirst($log->status) }}
                        </span>
                    </td>
                    <td class="small text-muted">{{ Str::limit($log->response, 80) }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="text-center text-muted py-3">Belum ada riwayat pengiriman jadwal</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
