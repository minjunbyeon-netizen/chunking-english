const puppeteer = require('puppeteer');
const { execSync } = require('child_process');
const fs = require('fs');
const path = require('path');

const delay = ms => new Promise(r => setTimeout(r, ms));
const URL = 'http://localhost/chunking-english/book-check.php?mode=days&from=1&to=3';
const SHOTS_DIR = path.join(__dirname, 'pdf_output', 'shots');

async function main() {
    fs.mkdirSync(SHOTS_DIR, { recursive: true });
    // 이전 shots 정리
    fs.readdirSync(SHOTS_DIR).forEach(f => fs.unlinkSync(path.join(SHOTS_DIR, f)));

    console.log('1. 각 시트 스크린샷 캡처...');
    const browser = await puppeteer.launch({
        headless: 'new',
        args: ['--no-sandbox', '--disable-setuid-sandbox', '--disable-gpu',
               '--disable-dev-shm-usage', '--font-render-hinting=none'],
        protocolTimeout: 600000
    });
    const page = await browser.newPage();
    await page.setViewport({ width: 1240, height: 1754, deviceScaleFactor: 1 });
    await page.emulateMediaType('screen');
    await page.goto(URL, { waitUntil: 'networkidle0', timeout: 300000 });
    await page.evaluate(() => document.fonts.ready);
    await delay(4000);
    await page.evaluate(() => Promise.all(
        Array.from(document.images)
            .filter(img => !img.complete)
            .map(img => new Promise(r => { img.onload = img.onerror = r; }))
    ));
    await delay(2000);

    // top-controls 숨기기
    await page.addStyleTag({ content: `.top-controls { display: none !important; }` });

    // 모든 .sheet 요소 캡처
    const sheets = await page.$$('.sheet');
    console.log(`   시트 ${sheets.length}개 발견`);

    for (let i = 0; i < sheets.length; i++) {
        const outPath = path.join(SHOTS_DIR, `sheet_${String(i).padStart(3,'0')}.jpg`);
        await sheets[i].screenshot({
            path: outPath,
            type: 'jpeg',
            quality: 60
        });
        process.stdout.write(`\r   캡처: ${i+1}/${sheets.length}`);
    }
    console.log('\n   캡처 완료');
    await browser.close();

    console.log('2. 이미지 → PDF 변환 (압축)...');
    const out = execSync('python C:/work/chunking-english/shots_to_pdf.py', { encoding: 'utf8' });
    console.log(out);
}

main().catch(e => { console.error('오류:', e.message); process.exit(1); });
