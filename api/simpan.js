// =============================================================
// POST /api/simpan  - simpan isian formulir ke Neon PostgreSQL
// Body: { nama, nip, pangkat, jabatan, perangkat, unit_kerja,
//         tanggal_isi, bagian_a..bagian_f (JSON string), ttd, ttd_nama, ttd_nip }
// =============================================================
const { handleOptions, readJsonBody, query, json } = require('./_lib');

module.exports = async (req, res) => {
  if (handleOptions(req, res)) return;

  if (req.method !== 'POST') {
    return json(res, 405, { ok: false, message: 'Metode tidak diizinkan. Gunakan POST.' });
  }

  let body;
  try { body = await readJsonBody(req); }
  catch { return json(res, 400, { ok: false, message: 'Body JSON tidak valid' }); }

  // Sanitasi key
  const d = {};
  for (const k in body) {
    const cleanK = String(k).replace(/[^A-Za-z0-9_]/g, '');
    d[cleanK] = typeof body[k] === 'string' ? String(body[k]).trim() : body[k];
  }

  // Validasi wajib
  const kurang = [];
  if (!d.nama) kurang.push('nama');
  if (!d.nip)  kurang.push('nip');
  if (kurang.length) {
    return json(res, 422, { ok: false, message: 'Kolom wajib belum diisi: ' + kurang.join(', ') });
  }

  // TTD validasi
  function bersihkanTtd(v) {
    if (!v) return null;
    const s = String(v);
    if (s.length > 5 * 1024 * 1024) return null; // 5 MB
    if (!/^data:image\/(png|jpeg|jpg);base64,[A-Za-z0-9+/=]+$/.test(s)) return null;
    return s;
  }

  const nilai = {
    nama:        String(d.nama || '').slice(0,150),
    nip:         String(d.nip || '').slice(0,50),
    pangkat:     String(d.pangkat || '').slice(0,100),
    jabatan:     String(d.jabatan || '').slice(0,150),
    perangkat:   String(d.perangkat || '').slice(0,150),
    unit_kerja:  String(d.unit_kerja || '').slice(0,150),
    tanggal_isi: String(d.tanggal_isi || '').slice(0,50),
    bagian_a:    d.bagian_a != null ? String(d.bagian_a) : null,
    bagian_b:    d.bagian_b != null ? String(d.bagian_b) : null,
    bagian_c:    d.bagian_c != null ? String(d.bagian_c) : null,
    bagian_d:    d.bagian_d != null ? String(d.bagian_d) : null,
    bagian_e:    d.bagian_e != null ? String(d.bagian_e) : null,
    bagian_f:    d.bagian_f != null ? String(d.bagian_f) : null,
    ttd:         bersihkanTtd(d.ttd || null),
    ttd_nama:    String(d.ttd_nama || '').slice(0,150),
    ttd_nip:     String(d.ttd_nip || '').slice(0,50),
  };

  // Pastikan bagian_a..f valid JSON jika tidak kosong
  for (const k of ['bagian_a','bagian_b','bagian_c','bagian_d','bagian_e','bagian_f']) {
    if (nilai[k]) {
      try { JSON.parse(nilai[k]); } catch { nilai[k] = null; }
    }
  }

  try {
    const sql = `INSERT INTO pengisian
      (nama, nip, pangkat, jabatan, perangkat, unit_kerja, tanggal_isi,
       bagian_a, bagian_b, bagian_c, bagian_d, bagian_e, bagian_f,
       ttd, ttd_nama, ttd_nip)
      VALUES
      ($1,$2,$3,$4,$5,$6,$7,$8,$9,$10,$11,$12,$13,$14,$15,$16)
      RETURNING id`;
    const params = [
      nilai.nama, nilai.nip, nilai.pangkat, nilai.jabatan, nilai.perangkat, nilai.unit_kerja, nilai.tanggal_isi,
      nilai.bagian_a, nilai.bagian_b, nilai.bagian_c, nilai.bagian_d, nilai.bagian_e, nilai.bagian_f,
      nilai.ttd, nilai.ttd_nama, nilai.ttd_nip
    ];
    const r = await query(sql, params);
    const id = r.rows[0]?.id;
    return json(res, 200, { ok: true, message: 'Data berhasil disimpan.', id });
  } catch (e) {
    console.error('simpan error', e);
    const msg = (e.message || '').includes('DATABASE_URL') ? e.message
      : 'Gagal menyimpan: ' + (e.message || 'unknown');
    return json(res, 500, { ok: false, message: msg });
  }
};
