// =============================================================
// /api/admin  - endpoint admin untuk Vercel+Neon
//  POST ?action=login            {username, password} -> {token}
//  GET  ?action=list             (Bearer) -> daftar
//  GET  ?action=get&id=123       (Bearer) -> detail satu baris
//  POST ?action=status           (Bearer) {id, status, catatan_admin}
//  GET  ?action=export           (Bearer) -> CSV
//  POST ?action=logout           (opsional)
// =============================================================
const { handleOptions, readJsonBody, query, json, getAdminCreds, verifyPass, jwtSign, requireAuth } = require('./_lib');

function csvEscape(v) {
  if (v == null) return '';
  const s = String(v);
  if (/[",\n\r]/.test(s)) return '"' + s.replace(/"/g,'""') + '"';
  return s;
}

module.exports = async (req, res) => {
  if (handleOptions(req, res)) return;

  // allow query parsing: req.query may not exist on Vercel raw, so parse from url
  const url = new URL(req.url, `https://${req.headers.host || 'localhost'}`);
  const action = (req.query?.action || url.searchParams.get('action') || '').toLowerCase();

  // -------- LOGIN (tanpa auth) --------
  if (action === 'login') {
    if (req.method !== 'POST') return json(res, 405, { ok:false, message:'Gunakan POST' });
    let body;
    try { body = await readJsonBody(req); } catch { return json(res, 400, {ok:false, message:'Body tidak valid'}); }
    const username = String(body.username || body.user || '').trim();
    const password = String(body.password || body.pass || '').trim();
    if (!username || !password) return json(res, 400, { ok:false, message:'Username dan password wajib diisi' });

    const { user: envUser, pass: envPass } = getAdminCreds();
    const { getSecret } = require('./_lib');
    // timing-safe user compare + pass verify
    let userOk = false;
    try { userOk = (username.length===envUser.length) && require('crypto').timingSafeEqual(Buffer.from(username), Buffer.from(envUser)); }
    catch { userOk = username===envUser; }
    const passOk = verifyPass(password, envPass);

    if (!userOk || !passOk) {
      return json(res, 401, { ok:false, message:'Username atau password salah.' });
    }
    const secret = getSecret();
    const token = jwtSign({ role:'admin', user: envUser }, secret, 8*3600);
    return json(res, 200, { ok:true, message:'Login berhasil', token, user: envUser });
  }

  // -------- Semua aksi lain butuh Bearer ----------
  const auth = requireAuth(req);
  if (!auth) {
    return json(res, 401, { ok:false, message:'Belum login / token tidak valid. Silakan login ulang.' });
  }

  // -------- LIST --------
  if (action === 'list' || action === '' ) {
    if (req.method !== 'GET') return json(res, 405, { ok:false, message:'Gunakan GET' });
    try {
      const r = await query('SELECT * FROM pengisian ORDER BY created_at DESC, id DESC', []);
      return json(res, 200, { ok:true, data: r.rows, total: r.rowCount });
    } catch (e) {
      console.error('admin list', e);
      return json(res, 500, { ok:false, message: e.message });
    }
  }

  // -------- GET detail --------
  if (action === 'get') {
    const id = parseInt(url.searchParams.get('id') || req.query?.id || '0',10);
    if (!id) return json(res, 400, { ok:false, message:'ID tidak valid' });
    try {
      const r = await query('SELECT * FROM pengisian WHERE id=$1', [id]);
      if (!r.rowCount) return json(res, 404, { ok:false, message:'Data tidak ditemukan' });
      return json(res, 200, { ok:true, data: r.rows[0] });
    } catch (e) { return json(res, 500, { ok:false, message:e.message }); }
  }

  // -------- STATUS update --------
  if (action === 'status') {
    if (req.method !== 'POST') return json(res, 405, { ok:false, message:'Gunakan POST' });
    let body;
    try { body = await readJsonBody(req); } catch { return json(res,400,{ok:false,message:'Body tidak valid'}); }
    const id = parseInt(body.id,10);
    let status = String(body.status||'').trim().toUpperCase().slice(0,20);
    const catatan = String(body.catatan_admin || body.catatan || '').slice(0,255);
    const allowed = ['BARU','DIPROSES','SELESAI','DITOLAK'];
    if (!id) return json(res, 400, {ok:false, message:'ID wajib'});
    if (!allowed.includes(status)) status = 'BARU';
    try {
      const r = await query('UPDATE pengisian SET status=$1, catatan_admin=$2 WHERE id=$3 RETURNING *', [status, catatan, id]);
      if (!r.rowCount) return json(res,404,{ok:false,message:'Data tidak ditemukan'});
      return json(res,200,{ok:true, message:'Status diperbarui', data:r.rows[0]});
    } catch (e){ return json(res,500,{ok:false,message:e.message}); }
  }

  // -------- EXPORT CSV --------
  if (action === 'export') {
    try {
      const r = await query('SELECT * FROM pengisian ORDER BY created_at DESC, id DESC', []);
      // set CSV headers
      res.setHeader('Content-Type', 'text/csv; charset=utf-8');
      res.setHeader('Content-Disposition', `attachment; filename="rekap-pengisian-${new Date().toISOString().slice(0,10)}.csv"`);
      // BOM for Excel
      res.statusCode = 200;
      let out = '\uFEFF';
      const header = ['ID','Nama','NIP','Pangkat/Gol','Jabatan','Perangkat Daerah','Unit Kerja','Tanggal Isi','Bagian A','Bagian B','Bagian C','Bagian D','Bagian E','Bagian F','Ada Tanda Tangan','Nama Ttd','NIP Ttd','Status','Catatan Admin','Dibuat pada'];
      out += header.map(csvEscape).join(',')+'\n';
      for (const row of r.rows) {
        out += [
          row.id, row.nama, row.nip, row.pangkat, row.jabatan, row.perangkat, row.unit_kerja, row.tanggal_isi,
          row.bagian_a, row.bagian_b, row.bagian_c, row.bagian_d, row.bagian_e, row.bagian_f,
          row.ttd ? 'YA':'TIDAK', row.ttd_nama, row.ttd_nip, row.status, row.catatan_admin, row.created_at
        ].map(csvEscape).join(',')+'\n';
      }
      res.end(out);
      return;
    } catch (e){ return json(res,500,{ok:false,message:e.message}); }
  }

  // -------- ME / VERIFY --------
  if (action === 'me' || action === 'verify') {
    return json(res,200,{ok:true, user: auth.user, exp: auth.exp});
  }

  return json(res, 400, { ok:false, message:'Aksi tidak dikenal: '+action });
};
