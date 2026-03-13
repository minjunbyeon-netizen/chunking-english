const puppeteer = require('puppeteer');
const fs = require('fs');
const path = require('path');

const delay = ms => new Promise(r => setTimeout(r, ms));
const BASE_URL = 'http://localhost/chunking-english/book-check.php';
const OUTPUT_DIR = path.join(__dirname, 'pdf_output');
const BATCH_SIZE = 25;

async function makePdf(browser, url, outPath, label) {
    console.log(`  ${label}...`);
    const page = await browser.newPage();
    await page.setViewport({ width: 1200, height: 1600 });
    
    await page.goto(url, { waitUntil: 'networkidle0', timeout: 300000 });
    // 폰트+이미지+텍스트 완전 로딩 대기
    await delay(5000);
    // 모든 이미지가 로딩될 때까지 추가 대기
    await page.evaluate(() => {
        return Promise.all(
            Array.from(document.images)
                .filter(img => !img.complete)
                .map(img => new Promise(resolve => {
                    img.onload = img.onerror = resolve;
                }))
        );
    });
    await delay(2000);

    await page.pdf({
        path: outPath,
        format: 'A4',
        printBackground: true,
        margin: { top: 0, right: 0, bottom: 0, left: 0 },
        timeout: 300000
    });
    
    const sz = fs.statSync(outPath).size / 1024 / 1024;
    console.log(`  ${label} done (${sz.toFixed(1)} MB)`);
    await page.close();
}

async function main() {
    if (!fs.existsSync(OUTPUT_DIR)) fs.mkdirSync(OUTPUT_DIR, { recursive: true });
    // 이전 PDF 정리
    fs.readdirSync(OUTPUT_DIR).filter(f => f.endsWith('.pdf')).forEach(f => fs.unlinkSync(path.join(OUTPUT_DIR, f)));

    const browser = await puppeteer.launch({
        headless: 'new',
        args: ['--no-sandbox', '--disable-setuid-sandbox', '--disable-gpu', '--disable-dev-shm-usage', '--font-render-hinting=none'],
        protocolTimeout: 600000
    });

    // 1. 표지 + 서문
    await makePdf(browser, 
        `${BASE_URL}?mode=cover`,
        path.join(OUTPUT_DIR, 'part_000_cover.pdf'),
        'Cover + Preface');

    // 2. Day 배치 (25일씩)
    for (let from = 1; from <= 250; from += BATCH_SIZE) {
        const to = Math.min(from + BATCH_SIZE - 1, 250);
        const idx = String(Math.ceil(from / BATCH_SIZE)).padStart(3, '0');
        await makePdf(browser,
            `${BASE_URL}?mode=days&from=${from}&to=${to}`,
            path.join(OUTPUT_DIR, `part_${idx}_day${from}-${to}.pdf`),
            `Day ${from}-${to}`);
    }

    // 3. 뒷표지
    await makePdf(browser,
        `${BASE_URL}?mode=last`,
        path.join(OUTPUT_DIR, 'part_999_back.pdf'),
        'Back Cover');

    await browser.close();

    // 결과 요약
    const files = fs.readdirSync(OUTPUT_DIR).filter(f => f.endsWith('.pdf')).sort();
    let total = 0;
    console.log(`\n=== ${files.length} PDF files ===`);
    files.forEach(f => {
        const s = fs.statSync(path.join(OUTPUT_DIR, f)).size;
        total += s;
        console.log(`  ${f} (${(s/1024/1024).toFixed(1)} MB)`);
    });
    console.log(`Total: ${(total/1024/1024).toFixed(1)} MB`);
    console.log('\nNext: python merge_pdfs.py && python compress_pdf.py');
}

main().catch(e => { console.error('Fatal:', e.message); process.exit(1); });
