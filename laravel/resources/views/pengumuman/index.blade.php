@extends('layouts.app')

@section('title', 'Pengumuman - Sultan Banten')
@section('page_title')
    <i class="fas fa-bullhorn me-2"></i>Pengumuman
@endsection

@section('content')
<div class="content-card">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
        <div>
            <h5 class="mb-1 fw-bold"><i class="fas fa-bullhorn text-primary me-2"></i>Daftar Pengumuman & Berita</h5>
            <p class="text-muted small mb-0">Kelola pengumuman untuk marquee bar, banner pop-up, dan notifikasi wilayah</p>
        </div>
        <button class="btn btn-primary btn-sm px-3 mt-2 mt-md-0" data-bs-toggle="modal" data-bs-target="#modalAdd">
            <i class="fas fa-plus me-1"></i> Tambah Pengumuman
        </button>
    </div>

    <!-- Filter Form -->
    <div class="card mb-3 bg-light border-0">
        <div class="card-body py-2">
            <form method="GET" action="{{ route('pengumuman.index') }}" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Kategori</label>
                    <select name="kategori" class="form-select form-select-sm">
                        <option value="">-- Semua Kategori --</option>
                        <option value="Penting" {{ request('kategori') == 'Penting' ? 'selected' : '' }}>Penting</option>
                        <option value="Biasa" {{ request('kategori') == 'Biasa' ? 'selected' : '' }}>Biasa</option>
                        <option value="Agenda" {{ request('kategori') == 'Agenda' ? 'selected' : '' }}>Agenda</option>
                        <option value="Peringatan" {{ request('kategori') == 'Peringatan' ? 'selected' : '' }}>Peringatan</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">-- Semua Status --</option>
                        <option value="aktif" {{ request('status') == 'aktif' ? 'selected' : '' }}>Aktif</option>
                        <option value="nonaktif" {{ request('status') == 'nonaktif' ? 'selected' : '' }}>Nonaktif</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm flex-fill"><i class="fas fa-search me-1"></i>Filter</button>
                    <a href="{{ route('pengumuman.index') }}" class="btn btn-secondary btn-sm flex-fill">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle table-sm mb-0">
            <thead class="bg-primary bg-opacity-10">
                <tr>
                    <th class="text-center" width="5%">No</th>
                    <th width="30%">Judul & Isi</th>
                    <th class="text-center" width="10%">Kategori</th>
                    <th class="text-center" width="10%">Tipe / Sasaran</th>
                    <th class="text-center" width="10%">Marquee</th>
                    <th class="text-center" width="10%">Status</th>
                    <th class="text-center" width="13%">Periode</th>
                    <th class="text-center" width="12%">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($pengumumanList as $p)
                <tr>
                    <td class="text-center text-muted">{{ $loop->iteration + ($pengumumanList->currentPage() - 1) * $pengumumanList->perPage() }}</td>
                    <td>
                        <div class="fw-bold text-dark">{{ $p->judul }}</div>
                        <div class="small text-muted text-truncate" style="max-width: 280px;">{{ strip_tags($p->isi) }}</div>
                        @if($p->gambar)
                        <small class="text-primary"><i class="fas fa-paperclip me-1"></i>Ada lampiran gambar</small>
                        @endif
                    </td>
                    <td class="text-center">
                        <span class="badge {{ $p->kategori == 'Penting' ? 'bg-danger' : ($p->kategori == 'Agenda' ? 'bg-info text-dark' : 'bg-primary') }}">
                            {{ $p->kategori }}
                        </span>
                    </td>
                    <td class="text-center small">
                        {{ $p->tipe === 'UPT' ? ($p->upt?->nama_upt ?? 'UPT Spesifik') : 'Semua UPT' }}
                    </td>
                    <td class="text-center">
                        @if($p->tampilkan_marquee)
                        <span class="badge bg-success bg-opacity-25 text-success"><i class="fas fa-check"></i> Muncul</span>
                        @else
                        <span class="badge bg-secondary bg-opacity-25 text-secondary">-</span>
                        @endif
                    </td>
                    <td class="text-center">
                        @if($p->aktif)
                        <span class="badge bg-success">Aktif</span>
                        @else
                        <span class="badge bg-danger">Nonaktif</span>
                        @endif
                    </td>
                    <td class="text-center small text-muted">
                        @if($p->tanggal_mulai && $p->tanggal_selesai)
                        {{ \Carbon\Carbon::parse($p->tanggal_mulai)->format('d/m/y') }} - {{ \Carbon\Carbon::parse($p->tanggal_selesai)->format('d/m/y') }}
                        @else
                        Selalu Aktif
                        @endif
                    </td>
                    <td class="text-center">
                        <button class="btn btn-warning btn-sm py-0 px-2" onclick="editData({{ json_encode($p) }})" title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>
                        <form action="{{ route('pengumuman.destroy', $p->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus pengumuman ini?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm py-0 px-2" title="Hapus">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center py-4 text-muted">Belum ada pengumuman.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-3">
        {{ $pengumumanList->links('pagination::bootstrap-5') }}
    </div>
</div>

<!-- Modal Add -->
<div class="modal fade" id="modalAdd" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header py-2 bg-primary text-white">
                <h5 class="modal-title fs-6"><i class="fas fa-plus me-2"></i>Tambah Pengumuman Baru</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('pengumuman.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Judul Pengumuman <span class="text-danger">*</span></label>
                        <input type="text" name="judul" class="form-control form-control-sm" required placeholder="Judul ringkas">
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Kategori <span class="text-danger">*</span></label>
                            <select name="kategori" class="form-select form-select-sm" required>
                                <option value="Penting">Penting</option>
                                <option value="Biasa">Biasa</option>
                                <option value="Agenda">Agenda</option>
                                <option value="Peringatan">Peringatan</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Tipe Sasaran</label>
                            <select name="tipe" class="form-select form-select-sm" id="add_tipe" onchange="toggleUptSelect('add')">
                                <option value="Global">Global (Semua UPT & Publik)</option>
                                <option value="UPT">UPT Spesifik</option>
                            </select>
                        </div>
                        <div class="col-12" id="add_upt_container" style="display: none;">
                            <label class="form-label small fw-semibold">Pilih UPT Target</label>
                            <select name="upt_id" class="form-select form-select-sm">
                                <option value="">-- Pilih UPT --</option>
                                @foreach ($uptList as $upt)
                                <option value="{{ $upt->id }}">{{ $upt->nama_upt }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Isi Pengumuman <span class="text-danger">*</span></label>
                        <textarea name="isi" class="form-control form-control-sm" rows="4" required placeholder="Tuliskan isi pengumuman atau informasi lengkap"></textarea>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Tanggal Mulai (Opsional)</label>
                            <input type="date" name="tanggal_mulai" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Tanggal Selesai (Opsional)</label>
                            <input type="date" name="tanggal_selesai" class="form-control form-control-sm">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Lampiran Gambar (Opsional)</label>
                        <input type="file" name="gambar" class="form-control form-control-sm" accept="image/*">
                    </div>
                    <div class="d-flex gap-4">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="aktif" id="add_aktif" checked>
                            <label class="form-check-label small fw-semibold" for="add_aktif">Status Aktif</label>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="tampilkan_marquee" id="add_marquee" checked>
                            <label class="form-check-label small fw-semibold" for="add_marquee">Tampilkan di Running Text Bawah</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary btn-sm px-3">Simpan</button>
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
                <h5 class="modal-title fs-6"><i class="fas fa-edit me-2"></i>Edit Pengumuman</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="formEdit" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Judul Pengumuman <span class="text-danger">*</span></label>
                        <input type="text" name="judul" id="edit_judul" class="form-control form-control-sm" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Kategori <span class="text-danger">*</span></label>
                            <select name="kategori" id="edit_kategori" class="form-select form-select-sm" required>
                                <option value="Penting">Penting</option>
                                <option value="Biasa">Biasa</option>
                                <option value="Agenda">Agenda</option>
                                <option value="Peringatan">Peringatan</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Tipe Sasaran</label>
                            <select name="tipe" id="edit_tipe" class="form-select form-select-sm" onchange="toggleUptSelect('edit')">
                                <option value="Global">Global (Semua UPT & Publik)</option>
                                <option value="UPT">UPT Spesifik</option>
                            </select>
                        </div>
                        <div class="col-12" id="edit_upt_container" style="display: none;">
                            <label class="form-label small fw-semibold">Pilih UPT Target</label>
                            <select name="upt_id" id="edit_upt_id" class="form-select form-select-sm">
                                <option value="">-- Pilih UPT --</option>
                                @foreach ($uptList as $upt)
                                <option value="{{ $upt->id }}">{{ $upt->nama_upt }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Isi Pengumuman <span class="text-danger">*</span></label>
                        <textarea name="isi" id="edit_isi" class="form-control form-control-sm" rows="4" required></textarea>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Tanggal Mulai</label>
                            <input type="date" name="tanggal_mulai" id="edit_tanggal_mulai" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Tanggal Selesai</label>
                            <input type="date" name="tanggal_selesai" id="edit_tanggal_selesai" class="form-control form-control-sm">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Ganti Gambar (Opsional)</label>
                        <div id="edit_gambar_preview" class="mb-2"></div>
                        <input type="file" name="gambar" class="form-control form-control-sm" accept="image/*">
                    </div>
                    <div class="d-flex gap-4">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="aktif" id="edit_aktif">
                            <label class="form-check-label small fw-semibold" for="edit_aktif">Status Aktif</label>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="tampilkan_marquee" id="edit_marquee">
                            <label class="form-check-label small fw-semibold" for="edit_marquee">Tampilkan di Running Text Bawah</label>
                        </div>
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
    function toggleUptSelect(prefix) {
        const tipe = document.getElementById(prefix + '_tipe').value;
        const container = document.getElementById(prefix + '_upt_container');
        if (container) container.style.display = tipe === 'UPT' ? 'block' : 'none';
    }

    function editData(p) {
        document.getElementById('formEdit').action = '/pengumuman/' + p.id;
        document.getElementById('edit_judul').value = p.judul;
        document.getElementById('edit_kategori').value = p.kategori;
        document.getElementById('edit_tipe').value = p.tipe;
        document.getElementById('edit_isi').value = p.isi;
        document.getElementById('edit_tanggal_mulai').value = p.tanggal_mulai ? p.tanggal_mulai.substring(0, 10) : '';
        document.getElementById('edit_tanggal_selesai').value = p.tanggal_selesai ? p.tanggal_selesai.substring(0, 10) : '';
        document.getElementById('edit_aktif').checked = !!p.aktif;
        document.getElementById('edit_marquee').checked = !!p.tampilkan_marquee;

        if (p.upt_id) {
            document.getElementById('edit_upt_id').value = p.upt_id;
        }
        toggleUptSelect('edit');

        const preview = document.getElementById('edit_gambar_preview');
        if (p.gambar) {
            preview.innerHTML = '<div class="d-flex align-items-center gap-2"><img src="/' + p.gambar + '" style="max-height: 45px;" class="img-thumbnail"><div class="form-check"><input class="form-check-input" type="checkbox" name="delete_gambar" value="1" id="del_gbr"><label class="form-check-label small text-danger" for="del_gbr">Hapus gambar ini</label></div></div>';
        } else {
            preview.innerHTML = '';
        }

        new bootstrap.Modal(document.getElementById('modalEdit')).show();
    }
</script>
@endpush
