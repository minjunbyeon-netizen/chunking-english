const puppeteer = require('puppeteer');
const fs = require('fs');
const path = require('path');
const delay = ms => new Promise(r => setTimeout(r, ms));

(async () => {
    const browser = await puppeteer.launch({ 
        headless: 'new',
        executablePath: 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
        args: ['--no-sandbox', '--disable-gpu'],
        protocolTimeout: 300000 
    });
    const page = await browser.newPage();
    
    // 뷰포트 = A4 width (210mm ≈ 794px)
    await page.setViewport({ width: 794, height: 1123 });
    
    console.log('Loading Day 1-3...');
    await page.goto('http://localhost/chunking-english/book-check.php?mode=days&from=1&to=3', {
        waitUntil: 'networkidle0', timeout: 300000
    });
    
    console.log('Waiting 15s for fonts + images...');
    await delay(15000);
    
    // 폰트/이미지 확인
    const check = await page.evaluate(() => {
        const fonts = Array.from(document.fonts).filter(f => f.status === 'loaded').length;
        const imgs = document.querySelectorAll('img');
        const loaded = Array.from(imgs).filter(i => i.complete).length;
        return { fonts, imgs: `${loaded}/${imgs.length}` };
    });
    console.log(`Fonts loaded: ${check.fonts}, Images: ${check.imgs}`);
    
    // ★ print CSS 적용: body padding:0, sheet margin:0
    await page.emulateMediaType('print');
    await delay(2000);
    
    // ★ body의 flex center 정렬 제거 (print시 좌측정렬로)
    await page.addStyleTag({ content: `
        body { 
            display: block !important; 
            padding: 0 !important; 
            margin: 0 !important;
            background: white !important;
        }
        .sheet { 
            margin: 0 !important; 
            width: 100% !important;
            min-height: 297mm !important;
            box-shadow: none !important;
            border-radius: 0 !important;
        }
        .top-controls, .no-print { display: none !important; }
    `});
    await delay(1000);
    
    const outPath = path.join(__dirname, 'pdf_output', 'test_day1-3_v7.pdf');
    await page.pdf({
        path: outPath,
        width: '210mm',
        height: '297mm',
        printBackground: true,
        margin: { top: 0, right: 0, bottom: 0, left: 0 },
        timeout: 180000
    });
    
    console.log(`PDF: ${outPath} (${(fs.statSync(outPath).size/1024/1024).toFixed(1)} MB)`);
    await browser.close();
    console.log('Done!');
})();
