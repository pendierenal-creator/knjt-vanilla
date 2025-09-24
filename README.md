# HorseVanilla

Plugin PocketMine-MP sederhana yang menambahkan kuda bergaya vanilla ke dalam server Bedrock Anda. Selain perintah pemanggilan, HorseVanilla kini menghadirkan mekanik penjinakan, pemberian makan, dan perkembangbiakan seperti gim aslinya.

## Fitur

- Registrasi entitas Horse yang kompatibel dengan ID vanilla `minecraft:horse`.
- Perintah `/horse [nama]` untuk memanggil kuda liar yang siap dijinakkan.
- Sistem temper yang meniru proses penjinakan vanilla: pegang tangan kosong, naiki berulang, atau beri camilan emas untuk meningkatkan peluang.
- Dukungan pemberian makan untuk memasukkan kuda ke mode kawin serta mempercepat pertumbuhan anak kuda.
- Perkembangbiakan kuda jinak menggunakan golden apple atau golden carrot menghasilkan foal baru.
- Pesan kematian khusus untuk kuda jinak layaknya Bedrock Edition.

## Instalasi

1. Kompilasi atau unduh berkas plugin ini.
2. Salin folder plugin ke direktori `plugins/` pada server PocketMine-MP Anda.
3. Mulai ulang server. Anda akan melihat pesan bahwa HorseVanilla telah aktif.

## Penggunaan

- Gunakan perintah `/horse` untuk memanggil kuda liar. Opsional tambahkan nama, contoh: `/horse Thunder`.
- Jinakkan kuda dengan tangan kosong: klik kanan/ketuk berulang hingga hati muncul. Memberi golden apple/carrot meningkatkan temper dan menenangkan kuda lebih cepat.
- Setelah jinak, beri golden apple/carrot untuk mengaktifkan love mode. Bawa dua kuda jinak yang sedang love mode agar melahirkan foal.
- Beri camilan emas ke foal untuk mempercepat pertumbuhannya menjadi kuda dewasa.
- Pastikan Anda memiliki izin `horsevanilla.command.spawn` (default diaktifkan).

## Persyaratan

- PocketMine-MP API 5.0.0 atau lebih tinggi.
- PHP 8.0 atau lebih baru.
