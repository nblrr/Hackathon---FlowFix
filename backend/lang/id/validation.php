<?php
return [
    'required'=>'Kolom :attribute wajib diisi.', 'present'=>'Kolom :attribute harus disertakan.',
    'string'=>'Kolom :attribute harus berupa teks.', 'integer'=>'Kolom :attribute harus berupa bilangan bulat.',
    'array'=>'Format :attribute tidak sesuai atau memuat kolom yang tidak diizinkan.',
    'boolean'=>'Pilihan :attribute harus benar atau salah.', 'email'=>'Alamat email tidak valid.',
    'date_format'=>'Kolom :attribute harus berupa tanggal valid dengan format :format.',
    'regex'=>'Format :attribute tidak sesuai.', 'in'=>'Pilihan :attribute tidak diizinkan.',
    'distinct'=>'Pilihan :attribute tidak boleh berulang.', 'file'=>'Pilih berkas yang valid.',
    'uploaded'=>'Berkas gagal diunggah. Batas aplikasi 10 MiB; periksa juga konfigurasi ukuran unggahan PHP.',
    'mimetypes'=>'Berkas :attribute harus berupa PDF asli.',
    'max'=>['string'=>':attribute maksimal :max karakter.','file'=>':attribute maksimal :max KiB.','numeric'=>':attribute maksimal :max.'],
    'min'=>['string'=>':attribute minimal :min karakter.','file'=>':attribute harus berisi data (minimal :min KiB).','numeric'=>':attribute minimal :min.'],
    'between'=>['numeric'=>':attribute harus antara :min dan :max.'],
    'attributes'=>['email'=>'email','password'=>'kata sandi','file'=>'berkas','comment'=>'catatan','data_version'=>'versi data','form_data'=>'informasi pengajuan'],
];
