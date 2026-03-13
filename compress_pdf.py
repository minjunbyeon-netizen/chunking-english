"""PDF 이미지 압축 (pikepdf + Pillow) - 4GB → 목표 100-300MB"""
import pikepdf
from pikepdf import Pdf, PdfImage
from PIL import Image
import io, os, sys, time

input_path = r'C:\work\chunking-english\chunking_english_book.pdf'
output_path = r'C:\work\chunking-english\chunking_english_book_compressed.pdf'

print(f"Input: {input_path}")
print(f"Size: {os.path.getsize(input_path) / 1024 / 1024:.1f} MB")
print("Compressing images to JPEG quality=30, max 150px...")

start = time.time()
pdf = Pdf.open(input_path)
total_pages = len(pdf.pages)
compressed = 0

for i, page in enumerate(pdf.pages):
    if (i + 1) % 50 == 0 or i == 0:
        print(f"  Processing page {i+1}/{total_pages}...")
    
    try:
        for name, raw_image in list(page.images.items()):
            try:
                pdfimg = PdfImage(raw_image)
                pil_img = pdfimg.as_pil_image()
                
                # 이미지 크기 축소 (최대 150px)
                w, h = pil_img.size
                if w > 150 or h > 150:
                    ratio = min(150/w, 150/h)
                    new_w, new_h = int(w * ratio), int(h * ratio)
                    pil_img = pil_img.resize((new_w, new_h), Image.LANCZOS)
                
                # RGB 변환 후 JPEG 압축
                if pil_img.mode in ('RGBA', 'P', 'LA'):
                    bg = Image.new('RGB', pil_img.size, (255, 255, 255))
                    if pil_img.mode == 'P':
                        pil_img = pil_img.convert('RGBA')
                    bg.paste(pil_img, mask=pil_img.split()[-1] if pil_img.mode == 'RGBA' else None)
                    pil_img = bg
                elif pil_img.mode != 'RGB':
                    pil_img = pil_img.convert('RGB')
                
                buf = io.BytesIO()
                pil_img.save(buf, format='JPEG', quality=30, optimize=True)
                buf.seek(0)
                
                raw_image.write(buf.read(), filter=pikepdf.Name.DCTDecode)
                raw_image.ColorSpace = pikepdf.Name.DeviceRGB
                raw_image.Width = pil_img.width
                raw_image.Height = pil_img.height
                raw_image.BitsPerComponent = 8
                
                compressed += 1
            except Exception as e:
                pass  # 일부 이미지는 스킵
    except Exception:
        pass

print(f"\nCompressed {compressed} images in {time.time()-start:.0f}s")
print("Saving compressed PDF...")

pdf.save(output_path, linearize=True, compress_streams=True, 
         object_stream_mode=pikepdf.ObjectStreamMode.generate)
pdf.close()

final_size = os.path.getsize(output_path) / 1024 / 1024
print(f"\nOutput: {output_path}")
print(f"Size: {final_size:.1f} MB")
print(f"Reduction: {os.path.getsize(input_path)/1024/1024:.0f} → {final_size:.0f} MB")
