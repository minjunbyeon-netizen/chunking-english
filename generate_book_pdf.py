"""
청킹 영어 전체 PDF 생성기
저작권 등록용 완전본

사용법:
  python generate_book_pdf.py

출력:
  바탕화면/chunking_english_book.pdf
"""

import os, sys, io, tempfile
from pathlib import Path
from PIL import Image
from playwright.sync_api import sync_playwright
import fitz  # PyMuPDF

# ── 설정 ────────────────────────────────────────
LOCAL_URL   = "http://localhost/chunking-english"
BOOK_URL    = f"{LOCAL_URL}/book-check.php"
TOTAL_DAYS  = 250
OUTPUT_PATH = str(Path.home() / "Desktop" / "chunking_english_book.pdf")

# 이미지 압축 설정 (route intercept)
MAX_DIM  = 400   # 최대 이미지 크기 (px)
QUALITY  = 70    # JPEG 품질
# ────────────────────────────────────────────────


def intercept_image(route, request):
    """Playwright route intercept: 이미지를 PDF 삽입 전에 리사이즈/JPEG 변환"""
    try:
        resp = route.fetch()
        if not resp.ok:
            route.continue_()
            return
        body = resp.body()
        pil = Image.open(io.BytesIO(body)).convert('RGB')
        w, h = pil.size
        if max(w, h) > MAX_DIM:
            ratio = MAX_DIM / max(w, h)
            pil = pil.resize((int(w * ratio), int(h * ratio)), Image.LANCZOS)
        buf = io.BytesIO()
        pil.save(buf, "JPEG", quality=QUALITY, optimize=True)
        route.fulfill(
            body=buf.getvalue(),
            content_type="image/jpeg",
            headers={"content-type": "image/jpeg"},
        )
    except Exception:
        route.continue_()


def render_page(page, url, out_path):
    page.route("**/*.png",  intercept_image)
    page.route("**/*.jpg",  intercept_image)
    page.route("**/*.jpeg", intercept_image)

    page.goto(url, timeout=120_000)
    page.wait_for_load_state("domcontentloaded", timeout=120_000)
    try:
        page.wait_for_load_state("networkidle", timeout=30_000)
    except Exception:
        pass
    page.pdf(
        path=out_path,
        format="A4",
        print_background=True,
        margin={"top":"0mm","right":"0mm","bottom":"0mm","left":"0mm"},
        prefer_css_page_size=True,
    )
    doc = fitz.open(out_path)
    n = doc.page_count
    doc.close()

    page.unroute("**/*.png")
    page.unroute("**/*.jpg")
    page.unroute("**/*.jpeg")

    return n


def main():
    print("=" * 55)
    print("  Chunking English - Full Book PDF")
    print("  Copyright Registration Edition")
    print("=" * 55)

    tmp_dir = Path(tempfile.mkdtemp(prefix="chunking_pdf_"))
    print(f"\nTemp dir: {tmp_dir}")

    pdf_parts = []

    with sync_playwright() as p:
        print("\n[1] Browser launching...")
        browser = p.chromium.launch(headless=True)
        context = browser.new_context(viewport={"width": 1280, "height": 900})
        page = context.new_page()

        # 표지
        print("[2] Cover pages...")
        out = str(tmp_dir / "000_cover.pdf")
        n = render_page(page, f"{BOOK_URL}?mode=cover", out)
        print(f"    {n} pages")
        pdf_parts.append(out)

        # Day 1 ~ 250 (1일씩)
        print(f"\n[3] Rendering Day 1 ~ {TOTAL_DAYS}...")
        for day in range(1, TOTAL_DAYS + 1):
            out = str(tmp_dir / f"{day:03d}_day.pdf")
            url = f"{BOOK_URL}?mode=days&from={day}&to={day}"
            try:
                n = render_page(page, url, out)
                print(f"    Day {day:3d}: {n} pages", flush=True)
                pdf_parts.append(out)
            except Exception as e:
                print(f"    Day {day:3d}: ERROR - {e}", flush=True)

        # 서문 / 뒷표지
        print("\n[4] Last page...")
        out = str(tmp_dir / "999_last.pdf")
        n = render_page(page, f"{BOOK_URL}?mode=last", out)
        print(f"    {n} pages")
        pdf_parts.append(out)

        browser.close()

    # PDF 합본
    print(f"\n[5] Merging {len(pdf_parts)} PDFs...")
    merged = fitz.open()
    total_pages = 0
    for path in pdf_parts:
        try:
            doc = fitz.open(path)
            n = doc.page_count
            merged.insert_pdf(doc)
            doc.close()
            total_pages += n
        except Exception as e:
            print(f"    skip {path}: {e}")

    # 임시 파일 정리
    for path in pdf_parts:
        try: os.remove(path)
        except: pass
    try: tmp_dir.rmdir()
    except: pass

    merged.save(OUTPUT_PATH, deflate=True, garbage=4)
    merged.close()

    out_file = Path(OUTPUT_PATH)
    if out_file.exists():
        size_mb = out_file.stat().st_size / (1024 * 1024)
        print(f"\n{'=' * 55}")
        print(f"  DONE!")
        print(f"  File  : {OUTPUT_PATH}")
        print(f"  Pages : {total_pages}")
        print(f"  Size  : {size_mb:.1f} MB")
        print(f"{'=' * 55}\n")
    else:
        print("\n  ERROR: PDF not created.")
        sys.exit(1)


if __name__ == "__main__":
    main()
