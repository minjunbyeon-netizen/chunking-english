const puppeteer = require('puppeteer');
const { execSync } = require('child_process');
const fs = require('fs');
const path = require('path');

const delay = ms => new Promise(r => setTimeout(r, ms));
const BASE_URL = 'http://localhost/chunking-english/book-check.php';
const SHOTS_DIR = path.join(__dirname, 'pdf_output', 'shots_full');
const BATCH_SIZE = 25;

async function captureSheets(page, url, label) {
    await page.goto(url, { waitUntil: 'networkidle0', timeout: 300000 });
    await page.evaluate(() => document.fonts.ready);
    await delay(3000);
    await page.evaluate(() => Promise.all(
        Array.from(document.images)
            .filter(img => !img.complete)
            .map(img => new Promise(r => { img.onload = img.onerror = r; }))
    ));
    await delay(1500);
    await page.addStyleTag({ content: `.top-controls { display: none !important; }` });

    const sheets = await page.$$('.sheet');
    process.stdout.write(`  ${label}: ${sheets.length}시트 캡처 중...`);

    const captured = [];
    for (let i = 0; i < sheets.length; i++) {
        const outPath = path.join(SHOTS_DIR, `${label}_${String(i).padStart(3,'0')}.jpg`);
        await sheets[i].screenshot({ path: outPath, type: 'jpeg', quality: 60 });
        captured.push(outPath);
        process.stdout.write(`\r  ${label}: ${i+1}/${sheets.length} 완료   `);
    }
    console.log('');
    return captured;
}

async function main() {
    fs.mkdirSync(SHOTS_DIR, { recursive: true });
    // 이전 shots 정리
    fs.readdirSync(SHOTS_DIR).forEach(f => fs.unlinkSync(path.join(SHOTS_DIR, f)));

    const browser = await puppeteer.launch({
        headless: 'new',
        args: ['--no-sandbox', '--disable-setuid-sandbox', '--disable-gpu',
               '--disable-dev-shm-usage', '--font-render-hinting=none'],
        protocolTimeout: 600000
    });
    const page = await browser.newPage();
    await page.setViewport({ width: 1240, height: 1754, deviceScaleFactor: 1 });
    await page.emulateMediaType('screen');

    const allFiles = [];
    let batchNum = 0;

    // Day 배치 (25일씩)
    for (let from = 1; from <= 250; from += BATCH_SIZE) {
        const to = Math.min(from + BATCH_SIZE - 1, 250);
        const label = `batch${String(batchNum).padStart(3,'0')}_day${from}-${to}`;
        const url = `${BASE_URL}?mode=days&from=${from}&to=${to}`;
        const files = await captureSheets(page, url, label);
        allFiles.push(...files);
        batchNum++;
        console.log(`  → 누적 시트: ${allFiles.length}개`);
    }

    await browser.close();
    console.log(`\n총 ${allFiles.length}개 시트 캡처 완료`);
    console.log('PDF 변환 중...');

    // shots_full 디렉토리 경로를 Python에 넘겨서 변환
    execSync('python C:/work/chunking-english/shots_to_pdf_full.py', { encoding: 'utf8', stdio: 'inherit' });
}

main().catch(e => { console.error('오류:', e.message); process.exit(1); });
