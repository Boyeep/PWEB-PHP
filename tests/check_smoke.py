"""Normal-user deployment smoke test. Security probes belong to local QA."""
import http.cookiejar
import json
import os
from pathlib import Path
import urllib.error
import urllib.parse
import urllib.request
from bs4 import BeautifulSoup

base=os.environ.get('TEST_BASE_URL','http://127.0.0.1:4188').rstrip('/')
cookies=http.cookiejar.CookieJar()
client=urllib.request.build_opener(urllib.request.HTTPCookieProcessor(cookies))
if os.environ.get('TEST_BYPASS_TOKEN'):
    client.addheaders.append(('x-vercel-protection-bypass',os.environ['TEST_BYPASS_TOKEN']))

def request(path,data=None):
    body=urllib.parse.urlencode(data).encode() if data is not None else None
    try:
        with client.open(base+path,body,timeout=60) as response:
            return response.status,response.read().decode()
    except urllib.error.HTTPError as error:
        return error.code,error.read().decode()

checks=[]
def check(label,condition):
    assert condition,label
    checks.append(label)
    print('PASS:',label,flush=True)

status,body=request('/')
check('PHP home renders database records',status==200 and len(BeautifulSoup(body,'html.parser').select('tbody tr'))==6 and '<?php' not in body)
if base.startswith('https:'):
    check('Session cookie uses Secure and HttpOnly',any(cookie.secure and cookie.has_nonstandard_attr('HttpOnly') for cookie in cookies))
status,body=request('/form-daftar.php')
csrf=BeautifulSoup(body,'html.parser').select_one('[name=csrf]')['value']
data={'csrf':csrf,'nama':'QA Smoke Fiktif','alamat':'Jalan Uji Nomor 88, Surabaya','jenis_kelamin':'Perempuan','agama':'Islam','sekolah_asal':'SMP Contoh Fiktif','jurusan':'Rekayasa Perangkat Lunak'}
status,body=request('/proses-pendaftaran.php',data)
check('Create returns saved record',status==200 and 'berhasil disimpan' in body and 'QA Smoke Fiktif' in body)
status,body=request('/list-siswa.php?q=QA+Smoke+Fiktif')
link=BeautifulSoup(body,'html.parser').select_one('.student-name')['href']
record_id=urllib.parse.parse_qs(urllib.parse.urlsplit(link).query)['id'][0]
data.update(id=record_id,nama='QA Smoke Diperbarui')
status,body=request('/proses-edit.php',data)
check('Update is saved',status==200 and 'berhasil diperbarui' in body)
status,body=request(link)
check('Later request reads updated data',status==200 and 'QA Smoke Diperbarui' in body)
status,body=request('/hapus.php',{'id':record_id,'csrf':csrf})
check('Delete confirms success',status==200 and 'berhasil dihapus' in body)
check('Deleted record is absent on later request',request(link)[0]==404)
status,body=request('/blog.php')
check('Blog article is available',status==200 and 'Membuat Aplikasi Pendaftaran Siswa Baru' in body)
report={'base_url':base,'passed':len(checks),'checks':checks,'automation_auth':bool(os.environ.get('TEST_BYPASS_TOKEN')),'security_probe_tests':'See local SQLite/MySQL QA reports.'}
Path('qa-report-production.json').write_text(json.dumps(report,indent=2),encoding='utf-8')
print(json.dumps(report,indent=2))
