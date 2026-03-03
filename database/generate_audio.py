"""
청킹잉글리시 MP3 생성 스크립트 (gTTS)
----------------------------------------
실행: python database/generate_audio.py
옵션: --days 1-5  (기본값: 1-5)
      --days all  (전체 50일)
결과: asset/audio/day N/NN. verb_en/expression_snake.mp3
"""

import os
import sys
import time
import argparse

BASE_PATH  = r"C:\xampp\htdocs\03_chunking"
AUDIO_BASE = os.path.join(BASE_PATH, "asset", "audio")

# DB 접속 (PyMySQL)
try:
    import pymysql
    DB_AVAILABLE = True
except ImportError:
    DB_AVAILABLE = False

# gTTS
from gtts import gTTS


def get_db():
    return pymysql.connect(
        host="localhost", user="root", password="",
        database="chunking_english", charset="utf8mb4"
    )


def to_snake(text):
    return str(text).strip().replace(" ", "_")


def audio_path_rel(day_num, global_num, verb_en, expression_en):
    """상대 경로 반환"""
    folder   = f"day {day_num}"
    subfolder = f"{global_num:02d}. {verb_en}"
    filename  = to_snake(expression_en) + ".mp3"
    return f"asset/audio/{folder}/{subfolder}/{filename}"


def audio_path_full(day_num, global_num, verb_en, expression_en):
    folder    = f"day {day_num}"
    subfolder = f"{global_num:02d}. {verb_en}"
    filename  = to_snake(expression_en) + ".mp3"
    return os.path.join(AUDIO_BASE, folder, subfolder, filename)


def generate_mp3(text, full_path, slow=False):
    """gTTS로 MP3 생성. 이미 있으면 스킵."""
    if os.path.exists(full_path):
        return "skip"
    os.makedirs(os.path.dirname(full_path), exist_ok=True)
    try:
        tts = gTTS(text=text, lang="en", slow=slow)
        tts.save(full_path)
        return "ok"
    except Exception as e:
        return f"error: {e}"


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("--days", default="1-5",
                        help="생성할 Day 범위. 예: 1-5 또는 all")
    args = parser.parse_args()

    # Day 범위 파싱
    if args.days == "all":
        day_start, day_end = 1, 50
    elif "-" in args.days:
        s, e = args.days.split("-")
        day_start, day_end = int(s), int(e)
    else:
        day_start = day_end = int(args.days)

    print(f"Day {day_start}~{day_end} MP3 생성 시작")
    print(f"저장 위치: {AUDIO_BASE}\n")

    if not DB_AVAILABLE:
        print("ERROR: PyMySQL 미설치. 'python -m pip install pymysql' 실행 후 재시도")
        sys.exit(1)

    db  = get_db()
    cur = db.cursor()

    # Day 범위 조회
    cur.execute("""
        SELECT d.id, d.day_number,
               v.id AS verb_id, v.global_num, v.verb_en,
               e.id AS expr_id, e.order_num, e.expression_en
        FROM days d
        JOIN verbs v ON v.day_id = d.id
        JOIN expressions e ON e.verb_id = v.id
        WHERE d.day_number BETWEEN %s AND %s
        ORDER BY d.day_number, v.order_num, e.order_num
    """, (day_start, day_end))
    rows = cur.fetchall()

    stats = {"ok": 0, "skip": 0, "error": 0}
    audio_updates = []  # (rel_path, expr_id)

    total = len(rows)
    for i, row in enumerate(rows, 1):
        day_num, global_num, verb_en, expr_id, expr_order, expression_en = (
            row[1], row[3], row[4], row[5], row[6], row[7]
        )

        # TTS 텍스트: "I {expression}."
        tts_text  = f"I {expression_en}."
        full_path = audio_path_full(day_num, global_num, verb_en, expression_en)
        rel_path  = audio_path_rel(day_num, global_num, verb_en, expression_en)

        result = generate_mp3(tts_text, full_path)
        stats[result if result in stats else "error"] += 1

        label = {"ok": "OK  ", "skip": "SKIP", "error": "ERR "}.get(
            result if result in ("ok", "skip") else "error", "ERR "
        )
        print(f"[{i:4d}/{total}] {label} Day{day_num:02d} | {verb_en:15s} | {expression_en}")

        if result == "ok":
            audio_updates.append((rel_path, expr_id))
            time.sleep(0.15)  # gTTS API 부하 방지

    # DB audio_path 업데이트
    if audio_updates:
        print(f"\nDB 업데이트 중... ({len(audio_updates)}건)")
        for rel_path, expr_id in audio_updates:
            cur.execute(
                "UPDATE expressions SET audio_path = %s WHERE id = %s",
                (rel_path, expr_id)
            )
        db.commit()
        print("DB 업데이트 완료")

    db.close()

    print(f"""
==============================================
완료!
  생성(OK)  : {stats['ok']}개
  스킵(기존): {stats['skip']}개
  오류(ERR) : {stats['error']}개
==============================================
""")


if __name__ == "__main__":
    main()
