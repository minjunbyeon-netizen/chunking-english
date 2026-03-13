"""PDF 합치기 (PyPDF2) + Ghostscript 방식 압축"""
import os, sys, subprocess

try:
    from PyPDF2 import PdfMerger
except ImportError:
    os.system(f'{sys.executable} -m pip install PyPDF2')
    from PyPDF2 import PdfMerger

pdf_dir = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'pdf_output')
merged_path = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'chunking_english_book_raw.pdf')
final_path = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'chunking_english_book.pdf')

# 1. PDF 합치기
pdf_files = sorted([f for f in os.listdir(pdf_dir) if f.endswith('.pdf')])
print(f"Found {len(pdf_files)} PDF files to merge")

merger = PdfMerger()
for f in pdf_files:
    fpath = os.path.join(pdf_dir, f)
    sz = os.path.getsize(fpath) / 1024 / 1024
    print(f"  Adding: {f} ({sz:.1f} MB)")
    merger.append(fpath)

merger.write(merged_path)
merger.close()

raw_size = os.path.getsize(merged_path) / 1024 / 1024
print(f"\nMerged PDF: {merged_path}")
print(f"Size: {raw_size:.1f} MB")

# 2. Ghostscript 압축 시도
gs_paths = [
    r'C:\Program Files\gs\gs10.0\bin\gswin64c.exe',
    r'C:\Program Files\gs\gs9.56.1\bin\gswin64c.exe',
    r'C:\Program Files (x86)\gs\gs10.0\bin\gswin32c.exe',
    'gswin64c', 'gswin32c', 'gs'
]

gs_exe = None
for gp in gs_paths:
    try:
        subprocess.run([gp, '--version'], capture_output=True, timeout=5)
        gs_exe = gp
        break
    except:
        continue

if gs_exe:
    print(f"\nCompressing with Ghostscript ({gs_exe})...")
    cmd = [
        gs_exe, '-sDEVICE=pdfwrite', '-dCompatibilityLevel=1.4',
        '-dPDFSETTINGS=/screen',  # 최소 품질 (72dpi)
        '-dNOPAUSE', '-dBATCH', '-dQUIET',
        '-dColorImageResolution=72',
        '-dGrayImageResolution=72',
        '-dMonoImageResolution=72',
        f'-sOutputFile={final_path}',
        merged_path
    ]
    result = subprocess.run(cmd, capture_output=True, text=True, timeout=600)
    if result.returncode == 0 and os.path.exists(final_path):
        comp_size = os.path.getsize(final_path) / 1024 / 1024
        print(f"Compressed PDF: {final_path}")
        print(f"Size: {comp_size:.1f} MB (reduced from {raw_size:.1f} MB)")
    else:
        print(f"Ghostscript failed: {result.stderr}")
        os.rename(merged_path, final_path)
        print(f"Using uncompressed: {final_path} ({raw_size:.1f} MB)")
else:
    print("\nGhostscript not found. Using merged PDF as-is.")
    os.rename(merged_path, final_path)
    print(f"Final PDF: {final_path} ({raw_size:.1f} MB)")
    print("To compress, install Ghostscript: https://www.ghostscript.com/releases/gsdnld.html")

print("\nDone!")
