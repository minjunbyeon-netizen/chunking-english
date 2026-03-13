import pikepdf
from pikepdf import PdfImage
from PIL import Image
import io, os, sys

src = r'C:\work\chunking-english\pdf_output\temp_3days_raw.pdf'
dst = r'C:\Users\USER\Desktop\chunking_english_day1-3.pdf'

if not os.path.exists(src):
    print(f"ERROR: {src} not found")
    sys.exit(1)

raw_size = os.path.getsize(src) / 1024 / 1024
print(f"입력: {raw_size:.1f} MB")
print("이미지 압축 중...")

pdf = pikepdf.open(src)
compressed = 0
for i, page in enumerate(pdf.pages):
    try:
        for name, raw in list(page.images.items()):
            try:
                pil = PdfImage(raw).as_pil_image()
                w, h = pil.size
                MAX = 200
                if w > MAX or h > MAX:
                    r = min(MAX/w, MAX/h)
                    pil = pil.resize((int(w*r), int(h*r)), Image.LANCZOS)
                if pil.mode == 'RGBA':
                    bg = Image.new('RGB', pil.size, (255, 255, 255))
                    bg.paste(pil, mask=pil.split()[-1])
                    pil = bg
                elif pil.mode != 'RGB':
                    pil = pil.convert('RGB')
                buf = io.BytesIO()
                pil.save(buf, format='JPEG', quality=25, optimize=True)
                buf.seek(0)
                raw.write(buf.read(), filter=pikepdf.Name.DCTDecode)
                raw.ColorSpace = pikepdf.Name.DeviceRGB
                raw.Width = pil.width
                raw.Height = pil.height
                raw.BitsPerComponent = 8
                compressed += 1
            except:
                pass
    except:
        pass

print(f"압축된 이미지: {compressed}개")
print("저장 중...")
pdf.save(dst, linearize=True, compress_streams=True,
         object_stream_mode=pikepdf.ObjectStreamMode.generate)
pdf.close()

final = os.path.getsize(dst) / 1024 / 1024
print(f"\n완료!")
print(f"원본:  {raw_size:.1f} MB")
print(f"최종:  {final:.2f} MB")
print(f"저장:  {dst}")
