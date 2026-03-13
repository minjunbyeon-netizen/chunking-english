const puppeteer = require('puppeteer');
const delay = ms => new Promise(r => setTimeout(r, ms));

async function main() {
    const browser = await puppeteer.launch({
        headless: 'new',
        args: ['--no-sandbox', '--disable-setuid-sandbox', '--disable-gpu', '--disable-dev-shm-usage']
    });
    const page = await browser.newPage();
    await page.setViewport({ width: 1200, height: 1600, deviceScaleFactor: 1 });
    await page.emulateMediaType('screen');
    await page.goto('http://localhost/chunking-english/book-check.php?mode=days&from=1&to=3', { waitUntil: 'networkidle0', timeout: 120000 });
    await page.evaluate(() => document.fonts.ready);
    await delay(3000);
    await page.screenshot({ path: 'C:/Users/USER/Desktop/debug_page.png', fullPage: false });
    console.log('done');
    await browser.close();
}
main().catch(e => console.error(e.message));
