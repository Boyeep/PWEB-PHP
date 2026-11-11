"""Automated development layout QA and documentation captures on localhost."""
import json
import os
from pathlib import Path
from playwright.sync_api import sync_playwright

BASE = os.environ.get('TEST_BASE_URL','http://127.0.0.1:4188').rstrip('/')
OUT = Path('screenshots')
OUT.mkdir(exist_ok=True)
checks = []
errors = []
with sync_playwright() as p:
    chrome = Path('C:/Program Files/Google/Chrome/Application/chrome.exe')
    browser = p.chromium.launch(headless=True,**({'executable_path':str(chrome)} if chrome.exists() else {}))
    context = browser.new_context(viewport={'width':1440,'height':1100},reduced_motion='reduce')
    page = context.new_page()
    page.on('pageerror',lambda error:errors.append(str(error)))
    page.on('console',lambda message:errors.append(message.text) if message.type=='error' else None)
    for path,filename in [('/','ringkasan-desktop.png'),('/form-daftar.php','form-desktop.png'),('/list-siswa.php','data-desktop.png')]:
        assert page.goto(BASE+path).status==200
        page.evaluate('document.fonts.ready')
        page.screenshot(path=str(OUT/filename),full_page=True)
    for width,height in [(375,812),(390,844),(768,1024),(812,375),(1024,768),(1440,1000)]:
        page.set_viewport_size({'width':width,'height':height})
        for path in ['/','/form-daftar.php','/list-siswa.php','/panduan.php']:
            assert page.goto(BASE+path).status==200
            assert page.evaluate('document.documentElement.scrollWidth <= window.innerWidth'), f'Overflow: {width} {path}'
            checks.append(f'No page overflow {width}x{height}: {path}')
    page.set_viewport_size({'width':390,'height':844})
    page.goto(BASE+'/')
    page.screenshot(path=str(OUT/'ringkasan-mobile.png'))
    toggle=page.locator('.menu-toggle')
    toggle.click()
    assert toggle.get_attribute('aria-expanded')=='true'
    page.keyboard.press('Escape')
    assert toggle.get_attribute('aria-expanded')=='false'
    checks.append('Mobile navigation opens and closes with Escape')
    page.goto(BASE+'/form-daftar.php')
    page.screenshot(path=str(OUT/'form-mobile.png'),full_page=True)
    # CRUD HTML forms continue to work with JavaScript disabled.
    no_js=browser.new_context(java_script_enabled=False)
    plain=no_js.new_page()
    plain.goto(BASE+'/form-daftar.php')
    plain.locator('#nama').fill('QA Tanpa JavaScript')
    plain.locator('#jenis_kelamin').select_option(label='Laki-laki')
    plain.locator('#agama').select_option(label='Islam')
    plain.locator('#alamat').fill('Jalan Contoh Nomor 100, Surabaya')
    plain.locator('#sekolah_asal').fill('SMP Contoh Fiktif')
    plain.locator('#program').select_option(label='Rekayasa Perangkat Lunak')
    plain.get_by_role('button',name='Simpan pendaftaran',exact=True).click()
    assert 'berhasil disimpan' in plain.locator('body').inner_text()
    checks.append('Registration works with JavaScript disabled')
    browser.close()
assert not errors, errors
report={'passed':len(checks),'checks':checks,'browser_errors':errors}
Path('qa-report-layout.json').write_text(json.dumps(report,indent=2),encoding='utf-8')
print(json.dumps(report,indent=2))
