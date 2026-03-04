"""
청킹잉글리시 MP3 생성 스크립트 (Typecast TTS)
--------------------------------------------------
사전 준비:
  pip install requests pymysql

실행 예시:
  python database/generate_audio_typecast.py --days 1-3
  python database/generate_audio_typecast.py --days all

API 키 설정 (둘 중 하나):
  1) 환경변수 : set TYPECAST_API_KEY=__plt...
  2) 실행 인자: --api-key __plt...

목소리 변경:
  아래 VOICE_ID / VOICE_NAME 값을 바꾸세요
  주요 영어 목소리:
    tc_6777669145604e14c7ff8f03  Victoria  (female, middle_age, E-learning) ← 기본값
    tc_641c10bfb62ae5eee6db3f9e  Jenna     (female, teenager)
    tc_63c76c7474190a31f3d02cc3  Maddie    (female, young_adult, Audiobook)
    tc_6620ee223bc61e2f6b79fdb5  Ron       (male, middle_age, Conversational)
"""

import os
import sys
import time
import argparse
import requests

# ── 경로 설정 ────────────────────────────────────────────────
BASE_PATH  = r"C:\xampp\htdocs\03_chunking"
AUDIO_BASE = os.path.join(BASE_PATH, "asset", "audio")

# ── Typecast 음성 설정 ────────────────────────────────────────
VOICE_ID   = "tc_6777669145604e14c7ff8f03"   # Victoria - E-learning/Explainer 여성
VOICE_NAME = "Victoria"
MODEL_ID   = "ssfm-v21"
OUTPUT_FMT = "mp3"

API_URL    = "https://api.typecast.ai/v1/text-to-speech"


# ── 유틸 함수 ────────────────────────────────────────────────
def to_snake(text: str) -> str:
    return str(text).strip().replace(" ", "_")


def audio_dir(day_num: int, global_num: int, verb_en: str) -> str:
    return os.path.join(AUDIO_BASE, f"day {day_num}", f"{global_num:02d}. {verb_en}")


def audio_full_path(day_num: int, global_num: int, verb_en: str, expression_en: str) -> str:
    return os.path.join(audio_dir(day_num, global_num, verb_en), to_snake(expression_en) + ".mp3")


def audio_rel_path(day_num: int, global_num: int, verb_en: str, expression_en: str) -> str:
    return f"asset/audio/day {day_num}/{global_num:02d}. {verb_en}/{to_snake(expression_en)}.mp3"


def get_db():
    import pymysql
    return pymysql.connect(
        host="localhost", user="root", password="",
        database="chunking_english", charset="utf8mb4"
    )


def generate_mp3(api_key: str, text: str, full_path: str) -> str:
    """Typecast TTS로 MP3 생성. 이미 있으면 스킵."""
    if os.path.exists(full_path):
        return "skip"

    os.makedirs(os.path.dirname(full_path), exist_ok=True)

    headers = {
        "X-API-KEY": api_key,
        "Content-Type": "application/json",
    }
    payload = {
        "text": text,
        "model": MODEL_ID,
        "voice_id": VOICE_ID,
        "output_format": OUTPUT_FMT,
    }

    try:
        resp = requests.post(API_URL, headers=headers, json=payload, timeout=30)
        if resp.status_code != 200:
            return f"error: HTTP {resp.status_code} - {resp.text[:120]}"

        with open(full_path, "wb") as f:
            f.write(resp.content)
        return "ok"
    except Exception as e:
        return f"error: {e}"


# ── 메인 ─────────────────────────────────────────────────────
def main():
    parser = argparse.ArgumentParser(description="Typecast TTS MP3 생성")
    parser.add_argument("--days",    default="1-3",  help="Day 범위: 1-3 또는 all")
    parser.add_argument("--api-key", default="",     help="Typecast API 키 (없으면 환경변수 TYPECAST_API_KEY 사용)")
    args = parser.parse_args()

    api_key = args.api_key or os.environ.get("TYPECAST_API_KEY", "")
    if not api_key:
        print("ERROR: Typecast API 키가 없습니다.")
        print("  방법 1) --api-key __plt... 인자로 전달")
        print("  방법 2) 환경변수: set TYPECAST_API_KEY=__plt...")
        sys.exit(1)

    # Day 범위 파싱
    if args.days == "all":
        day_start, day_end = 1, 50
    elif "-" in args.days:
        s, e = args.days.split("-")
        day_start, day_end = int(s), int(e)
    else:
        day_start = day_end = int(args.days)

    # pymysql 확인
    try:
        import pymysql
    except ImportError:
        print("ERROR: pymysql 패키지가 없습니다. pip install pymysql")
        sys.exit(1)

    db  = get_db()
    cur = db.cursor()

    cur.execute("""
        SELECT d.day_number,
               v.global_num, v.verb_en,
               e.id AS expr_id, e.expression_en
        FROM days d
        JOIN verbs v ON v.day_id = d.id
        JOIN expressions e ON e.verb_id = v.id
        WHERE d.day_number BETWEEN %s AND %s
        ORDER BY d.day_number, v.order_num, e.order_num
    """, (day_start, day_end))
    rows = cur.fetchall()

    total = len(rows)
    print(f"Day {day_start}~{day_end} / 총 {total}개 표현")
    print(f"저장 위치: {AUDIO_BASE}")
    print(f"목소리   : {VOICE_NAME} (voice_id={VOICE_ID})")
    print(f"모델     : {MODEL_ID}\n")

    stats         = {"ok": 0, "skip": 0, "error": 0}
    audio_updates = []

    for i, row in enumerate(rows, 1):
        day_num, global_num, verb_en, expr_id, expression_en = row

        full_path = audio_full_path(day_num, global_num, verb_en, expression_en)
        rel_path  = audio_rel_path(day_num, global_num, verb_en, expression_en)

        result = generate_mp3(api_key, expression_en, full_path)

        key   = result if result in ("ok", "skip") else "error"
        label = {"ok": "OK  ", "skip": "SKIP", "error": "ERR "}.get(key, "ERR ")
        stats[key] += 1

        print(f"[{i:3d}/{total}] {label} Day{day_num:02d} | {verb_en:15s} | {expression_en}")
        if key == "error":
            print(f"         └─ {result}")

        if result == "ok":
            audio_updates.append((rel_path, expr_id))
            time.sleep(0.2)

    # DB 업데이트
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
  생성(OK)  : {stats['ok']:3d}개
  스킵(기존): {stats['skip']:3d}개
  오류(ERR) : {stats['error']:3d}개
==============================================
""")


if __name__ == "__main__":
    main()
