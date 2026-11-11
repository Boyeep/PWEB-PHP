"""End-to-end checks against a running PHP server. Only creates fictional data."""
import json
import os
import re
import urllib.error
import urllib.parse
import urllib.request
import http.cookiejar
from pathlib import Path
from bs4 import BeautifulSoup

BASE = os.environ.get('TEST_BASE_URL', 'http://127.0.0.1:4188').rstrip('/')
checks = []

def client():
    opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
    if os.environ.get('TEST_BYPASS_TOKEN'):
        opener.addheaders.append(('x-vercel-protection-bypass',os.environ['TEST_BYPASS_TOKEN']))
    return opener

def request(opener, path, data=None):
    body = urllib.parse.urlencode(data).encode() if data is not None else None
    try:
        with opener.open(BASE + path, body, timeout=60) as response:
            return response.status, response.read().decode(), response.geturl()
    except urllib.error.HTTPError as error:
        return error.code, error.read().decode(), error.url

def parse(html): return BeautifulSoup(html, 'html.parser')
def token(html): return parse(html).select_one('input[name=csrf]')['value']
def ok(name, condition):
    assert condition, name
    checks.append(name)

c = client()
status, body, _ = request(c, '/')
ok('Home returns PHP-rendered records', status == 200 and len(parse(body).select('tbody tr')) == 6)
for path in ['/list-siswa.php', '/form-daftar.php', '/panduan.php']:
    ok('GET ' + path, request(c, path)[0] == 200)
status, form, _ = request(c, '/form-daftar.php')
csrf = token(form)
ok('CSRF required', request(c, '/proses-pendaftaran.php', {'nama':'Test'})[0] == 403)
ok('Process endpoint rejects GET', request(c, '/proses-pendaftaran.php')[0] == 405)
status, body, _ = request(c, '/proses-pendaftaran.php', {'csrf':csrf, 'nama':'A'})
ok('Server validation keeps errors and old input', 'Data belum tersimpan' in body and '2–64 karakter' in body and parse(body).select_one('#nama')['value'] == 'A')
record = {'csrf':csrf,'nama':'QA Siswa <script>alert(1)</script>','alamat':'Jl. Contoh No. 123, Surabaya','jenis_kelamin':'Perempuan','agama':'Islam','sekolah_asal':'SMP Uji Fiktif','jurusan':'Rekayasa Perangkat Lunak'}
status, body, _ = request(c, '/proses-pendaftaran.php', record)
ok('Create persists with success notice', status == 200 and 'berhasil disimpan' in body and len(parse(body).select('tbody tr')) == 7)
ok('Stored markup escaped', '<script>alert(1)</script>' not in body and '&lt;script&gt;alert(1)&lt;/script&gt;' in body)
status, body, _ = request(c, '/list-siswa.php?q=QA+Siswa')
links = parse(body).select('.student-name')
ok('Search finds created student on a later request', len(links) == 1)
detail_path = links[0]['href']
record_id = urllib.parse.parse_qs(urllib.parse.urlsplit(detail_path).query)['id'][0]
ok('Detail renders all fields', 'Jl. Contoh No. 123' in request(c,detail_path)[1])
other = client()
request(other, '/')
ok('Another session cannot see record', request(other,detail_path)[0] == 404)
ok('Another session cannot edit record', request(other,'/form-edit.php?id='+record_id)[0] == 404)
ok('Another session cannot delete record', request(other,'/hapus.php?id='+record_id)[0] == 404)
status, body, _ = request(c, '/hapus.php?id='+record_id)
ok('GET delete only confirms', status == 200 and 'Hapus pendaftaran ini?' in body and request(c,detail_path)[0] == 200)
record.update(id=record_id, nama='QA Siswa Diperbarui',jurusan='Desain Komunikasi Visual')
status, body, _ = request(c, '/proses-edit.php', record)
ok('Update persists', status == 200 and 'berhasil diperbarui' in body and 'QA Siswa Diperbarui' in request(c,detail_path)[1])
filtered = request(c,'/list-siswa.php?'+urllib.parse.urlencode({'jurusan':'Desain Komunikasi Visual','q':'QA Siswa'}))[1]
ok('Program filter and search combine', len(parse(filtered).select('.student-name')) == 1)
if not os.environ.get('TEST_SKIP_FIREWALL_PROBES'):
    injection = request(c,'/list-siswa.php?'+urllib.parse.urlencode({'q':"' OR 1=1 --"}))[1]
    ok('SQL injection is treated as a search value', not parse(injection).select('.student-name'))
    wildcard = request(c,'/list-siswa.php?q=%25')[1]
    ok('Search wildcard is escaped', not parse(wildcard).select('.student-name'))
bad = dict(record,jenis_kelamin='invalid',agama='invalid',jurusan='invalid')
status, body, _ = request(c,'/proses-edit.php',bad)
ok('Allowlisted choices validated on server', status == 200 and len(parse(body).select('[aria-invalid=true]')) == 3)
ok('Array query rejected safely', request(c,'/list-siswa.php?q%5B%5D=x')[0] == 200)
if not os.environ.get('TEST_SKIP_FIREWALL_PROBES'):
    ok('Invalid ID gives 404', request(c,'/detail.php?id=1%20OR%201=1')[0] == 404)
ok('CSRF blocks update', request(c,'/proses-edit.php',dict(record,csrf='wrong'))[0] == 403)
ok('CSRF blocks deletion', request(c,'/hapus.php',{'id':record_id,'csrf':'wrong'})[0] == 403)
status, body, _ = request(c,'/hapus.php',{'id':record_id,'csrf':csrf})
if status != 200 or 'berhasil dihapus' not in body:
    print('Delete diagnostic:', status, parse(body).get_text(' ',strip=True)[:350],flush=True)
ok('Delete persists after explicit POST', status == 200 and 'berhasil dihapus' in body and request(c,detail_path)[0] == 404)
ok('Flash notice only shown once', 'berhasil dihapus' not in request(c,'/list-siswa.php')[1])
report = {'base_url':BASE,'passed':len(checks),'automation_auth':bool(os.environ.get('TEST_BYPASS_TOKEN')),'firewall_probe_tests':'verified on local SQLite and MySQL only' if os.environ.get('TEST_SKIP_FIREWALL_PROBES') else 'included','checks':checks}
Path(os.environ.get('TEST_REPORT','qa-report.json')).write_text(json.dumps(report,ensure_ascii=False,indent=2),encoding='utf-8')
print(json.dumps(report,ensure_ascii=True,indent=2))
