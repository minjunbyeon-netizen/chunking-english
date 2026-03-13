"""스크린샷 이미지들 → 압축 PDF"""
from PIL import Image
import os, glob

shots_dir = r'C:\work\chunking-english\pdf_output\shots'
output = r'C:\Users\USER\Desktop\chunking_english_day1-3.pdf'

files = sorted(glob.glob(os.path.join(shots_dir, 'sheet_*.jpg')))
print(f"이미지 {len(files)}개 → PDF 변환 중...")

images = []
for f in files:
    img = Image.open(f).convert('RGB')
    images.append(img)

if not images:
    print("ERROR: 이미지 없음")
    exit(1)

# A4 비율로 리사이즈 (너비 기준 1240px, 높이 1754px)
A4_W, A4_H = 1240, 1754
resized = []
for img in images:
    img = img.resize((A4_W, A4_H), Image.LANCZOS)
    resized.append(img)

resized[0].save(
    output,
    save_all=True,
    append_images=resized[1:],
    format='PDF',
    resolution=150
)

size_mb = os.path.getsize(output) / 1024 / 1024
print(f"완료: {size_mb:.2f} MB")
print(f"저장: {output}")
