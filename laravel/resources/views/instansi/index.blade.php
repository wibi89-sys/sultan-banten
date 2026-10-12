@extends('layouts.app')

@section('title', 'Pengaturan Instansi - Sultan Banten')
@section('page_title')
    <i class="fas fa-cog me-2"></i>Pengaturan Instansi
@endsection

@section('content')
<div class="content-card">
    <div class="mb-4">
        <h5 class="mb-1 fw-bold"><i class="fas fa-building text-primary me-2"></i>Pengaturan Instansi & Tampilan</h5>
        <p class="text-muted small mb-0">Ubah identitas instansi, batas jam input harian, running text pengumuman, dan aset logo/stempel</p>
    </div>

    <form method="POST" action="{{ route('instansi.update') }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="row g-4">
            <!-- Informasi Umum -->
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-primary text-white py-2.5 fw-semibold small">
                        <i class="fas fa-info-circle me-1"></i> Informasi Instansi
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Nama Instansi <span class="text-danger">*</span></label>
                            <input type="text" name="nama_instansi" class="form-control form-control-sm" value="{{ $instansi->nama_instansi }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Alamat Kantor</label>
                            <textarea name="alamat" class="form-control form-control-sm" rows="3">{{ $instansi->alamat }}</textarea>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label small fw-semibold">Telepon</label>
                                <input type="text" name="telepon" class="form-control form-control-sm" value="{{ $instansi->telepon }}">
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-semibold">Email</label>
                                <input type="email" name="email" class="form-control form-control-sm" value="{{ $instansi->email }}">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Website</label>
                            <input type="text" name="website" class="form-control form-control-sm" value="{{ $instansi->website }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Running Text (Marquee Bar Bawah)</label>
                            <textarea name="running_text" class="form-control form-control-sm" rows="3" placeholder="Teks berjalan di footer dashboard">{{ $instansi->running_text }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pembatasan Jam & Aset Gambar -->
            <div class="col-lg-6">
                <!-- Pembatasan Jam -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-warning text-dark py-2.5 fw-semibold small">
                        <i class="fas fa-clock me-1"></i> Batasan Waktu Input Operator
                    </div>
                    <div class="card-body">
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" name="batas_jam_aktif" id="batas_jam_aktif" {{ $instansi->batas_jam_aktif ? 'checked' : '' }}>
                            <label class="form-check-label small fw-semibold" for="batas_jam_aktif">Aktifkan Pembatasan Jam Input Harian</label>
                        </div>
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="form-label small">Jam Mulai</label>
                                <input type="time" name="jam_mulai" class="form-control form-control-sm" value="{{ substr($instansi->jam_mulai ?? '06:00:00', 0, 5) }}">
                            </div>
                            <div class="col-6">
                                <label class="form-label small">Jam Selesai</label>
                                <input type="time" name="jam_selesai" class="form-control form-control-sm" value="{{ substr($instansi->jam_selesai ?? '08:00:00', 0, 5) }}">
                            </div>
                        </div>
                        <small class="text-muted d-block mt-2" style="font-size: 11px;">Jika aktif, operator UPT hanya dapat menginput data pada jam yang ditentukan. Admin tetap dapat input kapan saja.</small>
                    </div>
                </div>

                <!-- Logo & Favicon -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-secondary text-white py-2.5 fw-semibold small">
                        <i class="fas fa-image me-1"></i> Logo & Ikon
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-sm-6">
                                <label class="form-label small fw-semibold">Logo Instansi</label>
                                @if($instansi->logo)
                                <div class="mb-2 d-flex align-items-center gap-2">
                                    <img src="{{ asset($instansi->logo) }}" alt="Logo" class="img-thumbnail" style="max-height: 50px;">
                                    <button type="submit" name="delete_logo" value="1" class="btn btn-outline-danger btn-sm py-0 px-2" onclick="return confirm('Hapus logo?')"><i class="fas fa-trash"></i></button>
                                </div>
                                @endif
                                <input type="file" name="logo" class="form-control form-control-sm" accept="image/*">
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label small fw-semibold">Favicon</label>
                                @if($instansi->favicon)
                                <div class="mb-2 d-flex align-items-center gap-2">
                                    <img src="{{ asset($instansi->favicon) }}" alt="Favicon" class="img-thumbnail" style="max-height: 32px;">
                                    <button type="submit" name="delete_favicon" value="1" class="btn btn-outline-danger btn-sm py-0 px-2" onclick="return confirm('Hapus favicon?')"><i class="fas fa-trash"></i></button>
                                </div>
                                @endif
                                <input type="file" name="favicon" class="form-control form-control-sm" accept="image/*">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Banner & Kop Surat -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-dark text-white py-2.5 fw-semibold small">
                        <i class="fas fa-file-signature me-1"></i> Welcome Banner & TTD
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-sm-6">
                                <label class="form-label small fw-semibold">Welcome Banner (Popup)</label>
                                @if($instansi->welcome_banner)
                                <div class="mb-2 d-flex align-items-center gap-2">
                                    <img src="{{ asset($instansi->welcome_banner) }}" alt="Banner" class="img-thumbnail" style="max-height: 50px;">
                                    <button type="submit" name="delete_welcome_banner" value="1" class="btn btn-outline-danger btn-sm py-0 px-2" onclick="return confirm('Hapus banner?')"><i class="fas fa-trash"></i></button>
                                </div>
                                @endif
                                <input type="file" name="welcome_banner" class="form-control form-control-sm" accept="image/*">
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label small fw-semibold">Gambar Tanda Tangan</label>
                                @if($instansi->ttd_image)
                                <div class="mb-2 d-flex align-items-center gap-2">
                                    <img src="{{ asset($instansi->ttd_image) }}" alt="TTD" class="img-thumbnail" style="max-height: 50px;">
                                    <button type="submit" name="delete_ttd_image" value="1" class="btn btn-outline-danger btn-sm py-0 px-2" onclick="return confirm('Hapus TTD?')"><i class="fas fa-trash"></i></button>
                                </div>
                                @endif
                                <input type="file" name="ttd_image" class="form-control form-control-sm" accept="image/*">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end mt-4">
            <button type="submit" class="btn btn-primary px-4">
                <i class="fas fa-save me-1"></i> Simpan Pengaturan
            </button>
        </div>
    </form>
</div>
@endsection
