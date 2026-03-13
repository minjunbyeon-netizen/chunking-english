"""shots_full 폴더의 모든 jpg → 바탕화면에 PDF 저장"""
from PIL import Image
import os, glob

shots_dir = r'C:\work\chunking-english\pdf_output\shots_full'
output = r'C:\Users\USER\Desktop\chunking_english_250days.pdf'

files = sorted(glob.glob(os.path.join(shots_dir, '*.jpg')))
print(f"총 {len(files)}개 이미지 → PDF 변환 중...")

A4_W, A4_H = 1240, 1754
images = []
for i, f in enumerate(files):
    img = Image.open(f).convert('RGB').resize((A4_W, A4_H), Image.LANCZOS)
    images.append(img)
    if (i+1) % 50 == 0:
        print(f"  {i+1}/{len(files)} 처리 중...")

print("PDF 저장 중...")
images[0].save(
    output,
    save_all=True,
    append_images=images[1:],
    format='PDF',
    resolution=150
)

size_mb = os.path.getsize(output) / 1024 / 1024
print(f"\n완료!")
print(f"총 페이지: {len(images)}p")
print(f"파일 크기: {size_mb:.1f} MB")
print(f"저장 위치: {output}")
