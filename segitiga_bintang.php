<?php

$tinggi     = '';      // nilai yang dikirim user (untuk sticky form)
$arah       = 'atas';  // arah segitiga yang dipilih user
$arahLabel  = '';      // nama arah untuk keterangan hasil
$keterangan = '';      // pola jumlah bintang untuk keterangan hasil
$error      = '';      // pesan validasi
$hasil      = '';      // teks segitiga (mode satu arah), ditampilkan di dalam <pre>
$semua      = null;    // data keempat segitiga (mode "semua arah"), tiap item: sisi + teks

// ============================================================
// FUNGSI BANGUN SATU SEGITIGA - PERULANGAN BERSARANG
// Dipakai oleh mode satu arah maupun mode "semua arah"
// ============================================================
if (!function_exists('bangunSegitiga')) {
    function bangunSegitiga($arah, $n) {
        $teks = '';

        switch ($arah) {

            case 'bawah':   // piramida terbalik: baris terpanjang di atas
                for ($i = $n; $i >= 1; $i--) {             // LOOP LUAR: menurun dari n ke 1
                    for ($j = 1; $j <= $n - $i; $j++) {    // LOOP DALAM 1: spasi makin banyak ke bawah
                        $teks .= ' ';                      //   -> menjaga tetap simetris
                    }
                    for ($k = 1; $k <= 2 * $i - 1; $k++) { // LOOP DALAM 2: bintang ganjil menurun
                        $teks .= '*';                      //   -> 2n-1, ..., 5, 3, 1
                    }
                    $teks .= "\n";
                }
                break;

            case 'kiri':    // siku kiri: tanpa spasi, bintang bertambah tiap baris
                for ($i = 1; $i <= $n; $i++) {             // LOOP LUAR: baris 1 s/d n
                    for ($k = 1; $k <= $i; $k++) {         // LOOP DALAM: i bintang
                        $teks .= '*';                      //   -> 1, 2, 3, ..., n
                    }
                    $teks .= "\n";
                }
                break;

            case 'kanan':   // siku kanan: spasi kiri dulu, lalu bintang
                for ($i = 1; $i <= $n; $i++) {             // LOOP LUAR: baris 1 s/d n
                    for ($j = 1; $j <= $n - $i; $j++) {    // LOOP DALAM 1: spasi kiri
                        $teks .= ' ';                      //   -> mendorong bintang ke kanan
                    }
                    for ($k = 1; $k <= $i; $k++) {         // LOOP DALAM 2: i bintang
                        $teks .= '*';                      //   -> 1, 2, 3, ..., n
                    }
                    $teks .= "\n";
                }
                break;

            default:        // 'atas': piramida simetris (versi awal)
                for ($i = 1; $i <= $n; $i++) {             // LOOP LUAR: baris 1 s/d n
                    for ($j = 1; $j <= $n - $i; $j++) {    // LOOP DALAM 1: spasi agar simetris
                        $teks .= ' ';
                    }
                    for ($k = 1; $k <= 2 * $i - 1; $k++) { // LOOP DALAM 2: bintang ganjil 1,3,5,...
                        $teks .= '*';
                    }
                    $teks .= "\n";
                }
        }

        return $teks;
    }
}

// Label arah + pola keterangan jumlah bintang untuk tiap arah
if (!function_exists('infoSegitiga')) {
    function infoSegitiga($arah, $n) {
        switch ($arah) {
            case 'bawah':
                return array('ke bawah (piramida terbalik)', 'jumlah bintang tiap baris: ' . (2 * $n - 1) . ', ..., 5, 3, 1');
            case 'kiri':
                return array('siku kiri', 'jumlah bintang tiap baris: 1, 2, 3, ..., ' . $n);
            case 'kanan':
                return array('siku kanan', 'jumlah bintang tiap baris: 1, 2, 3, ..., ' . $n);
            default:
                return array('ke atas (piramida simetris)', 'jumlah bintang tiap baris: 1, 3, 5, ..., ' . (2 * $n - 1));
        }
    }
}

// ============================================================
// 1) PROSES FORM - hanya jika halaman dikirim dengan method POST
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Ambil masukan, buang spasi di awal dan akhir
    $tinggi = isset($_POST['tinggi']) && is_string($_POST['tinggi'])
            ? trim($_POST['tinggi'])
            : '';

    // Ambil pilihan arah segitiga (whitelist: hanya nilai ini yang diizinkan)
    $arahDaftar = array('semua', 'atas', 'bawah', 'kiri', 'kanan');
    $arah = isset($_POST['arah']) && is_string($_POST['arah'])
                 && in_array($_POST['arah'], $arahDaftar, true)
          ? $_POST['arah']
          : 'atas';

    // ==========================================================
    // 2) VALIDASI MASUKAN (server-side)
    // ==========================================================
    if ($tinggi === '') {
        $error = 'Input tidak boleh kosong. Silakan masukkan tinggi segitiga terlebih dahulu.';
    } elseif (!ctype_digit($tinggi)) {
        // ctype_digit menolak: huruf, angka desimal (3.5), dan tanda negatif (-3)
        $error = 'Nilai harus berupa bilangan bulat positif, tanpa huruf dan tanpa desimal. Contoh yang benar: 5.';
    } elseif ((int)$tinggi < 1 || (int)$tinggi > 20) {
        $error = 'Tinggi segitiga harus bilangan bulat antara 1 sampai 20 (masukan Anda: ' . (int)$tinggi . ').';
    } else {

        // ======================================================
        // 3) BANGUN SEGITIGA - PERULANGAN BERSARANG (NESTED LOOP)
        //    Arah menentukan pola spasi & bintang tiap baris
        // ======================================================
        $n = (int)$tinggi;

        if ($arah === 'semua') {
            // Mode "Semua arah sekaligus": bangun keempat bentuk sekaligus
            $judulSingkat = array('atas' => 'KE ATAS', 'bawah' => 'KE BAWAH', 'kiri' => 'SIKU KIRI', 'kanan' => 'SIKU KANAN');
            $semua = array();
            foreach (array('atas', 'bawah', 'kiri', 'kanan') as $sisi) {
                list(, $ket) = infoSegitiga($sisi, $n);
                $semua[] = array(
                    'sisi'  => $sisi,
                    'judul' => $judulSingkat[$sisi],
                    'ket'   => $ket,
                    'teks'  => bangunSegitiga($sisi, $n),
                );
            }
        } else {
            // Mode satu arah
            $hasil = bangunSegitiga($arah, $n);
            list($arahLabel, $keterangan) = infoSegitiga($arah, $n);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Segitiga Bintang &mdash; PHP Native Nested Loop</title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }

  body {
    font-family: "Segoe UI", Tahoma, Arial, sans-serif;
    background: linear-gradient(135deg, #16213e 0%, #1e3c72 55%, #2a5298 100%);
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 24px;
  }

  .card {
    background: #ffffff;
    width: 100%;
    max-width: 520px;
    border-radius: 16px;
    box-shadow: 0 20px 50px rgba(0, 0, 0, .30);
    overflow: hidden;
  }

  .card header {
    background: #16213e;
    color: #ffffff;
    text-align: center;
    padding: 22px 20px;
  }

  .card header h1 { font-size: 21px; letter-spacing: .5px; }
  .card header p  { font-size: 13px; opacity: .80; margin-top: 5px; }

  .content { padding: 26px; }

  label {
    display: block;
    font-size: 14px;
    font-weight: 600;
    color: #333333;
    margin-bottom: 8px;
  }

  input[type="text"] {
    width: 100%;
    padding: 12px 14px;
    font-size: 16px;
    border: 2px solid #d5dbe5;
    border-radius: 10px;
    outline: none;
    transition: border-color .2s;
  }

  input[type="text"]:focus,
  select:focus { border-color: #2a5298; }

  .field { margin-top: 16px; }

  select {
    width: 100%;
    padding: 12px 14px;
    font-size: 16px;
    color: #333333;
    background: #ffffff;
    border: 2px solid #d5dbe5;
    border-radius: 10px;
    outline: none;
    transition: border-color .2s;
  }

  button {
    width: 100%;
    margin-top: 14px;
    padding: 12px;
    font-size: 16px;
    font-weight: 600;
    color: #ffffff;
    background: #2a5298;
    border: none;
    border-radius: 10px;
    cursor: pointer;
    transition: background .2s;
  }

  button:hover { background: #16213e; }

  .error {
    margin-top: 18px;
    background: #fdecea;
    border: 1px solid #f5c6cb;
    border-left: 5px solid #d9534f;
    color: #a94442;
    padding: 12px 16px;
    border-radius: 10px;
    font-size: 14px;
    line-height: 1.5;
  }

  .result { margin-top: 20px; text-align: center; }

  .result .layar {
    display: inline-block;
    background: #0f1b33;
    border-radius: 12px;
    padding: 20px 26px;
    max-width: 100%;
    overflow-x: auto;
  }

  .result pre {
    font-family: Consolas, "Courier New", monospace;
    font-size: 18px;
    line-height: 1.4;
    color: #ffd85e;
    text-align: left;   /* spasi pada kode sudah menengahkan bintang per baris */
  }

  .caption {
    margin-top: 10px;
    font-size: 13px;
    color: #666666;
    text-align: center;
  }

  /* ---- Tata letak 4 panel untuk mode "semua arah" ---- */
  .grid-arah {
    margin-top: 20px;
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 14px;
  }

  .panel-arah {
    background: #0f1b33;
    border-radius: 12px;
    padding: 14px 16px;
    text-align: center;
    overflow-x: auto;
  }

  .panel-arah .judul {
    font-size: 11px;
    font-weight: 600;
    letter-spacing: .8px;
    color: #9fb3d9;
    margin-bottom: 8px;
  }

  .panel-arah pre {
    font-family: Consolas, "Courier New", monospace;
    font-size: 14px;
    line-height: 1.4;
    color: #ffd85e;
    text-align: left;   /* spasi pada kode sudah menengahkan bintang per baris */
    display: inline-block;
  }

  @media (max-width: 480px) {
    .result pre { font-size: 14px; }
    .grid-arah { grid-template-columns: 1fr; }
  }
</style>
</head>
<body>

  <div class="card">
    <header>
      <h1>SEGITIGA BINTANG</h1>
      <p>PHP Native &middot; Perulangan Bersarang (Nested Loop)</p>
    </header>

    <div class="content">

      <!-- ================= FORM INPUT ================= -->
      <form method="post" action="">
        <label for="tinggi">Masukkan tinggi segitiga (bilangan bulat 1 &ndash; 20):</label>
        <input type="text" id="tinggi" name="tinggi" inputmode="numeric"
               placeholder="Contoh: 5" value="<?= htmlspecialchars($tinggi) ?>"
               autocomplete="off" autofocus>

        <div class="field">
          <label for="arah">Pilih arah segitiga:</label>
          <select id="arah" name="arah">
            <option value="semua" <?= $arah === 'semua' ? 'selected' : '' ?>>Semua arah sekaligus (atas, bawah, kiri, kanan)</option>
            <option value="atas"  <?= $arah === 'atas'  ? 'selected' : '' ?>>Segitiga ke atas &mdash; piramida simetris</option>
            <option value="bawah" <?= $arah === 'bawah' ? 'selected' : '' ?>>Segitiga ke bawah &mdash; piramida terbalik</option>
            <option value="kiri"  <?= $arah === 'kiri'  ? 'selected' : '' ?>>Segitiga siku kiri</option>
            <option value="kanan" <?= $arah === 'kanan' ? 'selected' : '' ?>>Segitiga siku kanan</option>
          </select>
        </div>

        <button type="submit">Tampilkan Segitiga</button>
      </form>

      <!-- ============== PESAN ERROR / HASIL ============== -->
      <?php if ($error !== ''): ?>
        <div class="error">
          <strong>Masukan tidak valid!</strong><br>
          <?= htmlspecialchars($error) ?>
        </div>
      <?php elseif ($semua !== null): ?>
        <div class="grid-arah">
          <?php foreach ($semua as $s): ?>
            <div class="panel-arah">
              <div class="judul">ARAH: <?= htmlspecialchars($s['judul']) ?></div>
              <pre><?= htmlspecialchars($s['teks']) ?></pre>
            </div>
          <?php endforeach; ?>
        </div>
        <p class="caption">
          Mode semua arah &middot; tinggi = <?= (int)$tinggi ?> baris &middot;
          keempat bentuk ditampilkan sekaligus dengan perulangan bersarang
        </p>
      <?php elseif ($hasil !== ''): ?>
        <div class="result">
          <div class="layar">
            <pre><?= htmlspecialchars($hasil) ?></pre>
          </div>
        </div>
        <p class="caption">
          Arah: <?= htmlspecialchars($arahLabel) ?> &middot; tinggi = <?= (int)$tinggi ?> baris &middot;
          <?= htmlspecialchars($keterangan) ?>
        </p>
      <?php endif; ?>

    </div>
  </div>

</body>
</html>
