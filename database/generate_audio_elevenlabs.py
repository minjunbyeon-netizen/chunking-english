"""
청킹잉글리시 MP3 생성 스크립트 (ElevenLabs TTS)
--------------------------------------------------
사전 준비:
  pip install elevenlabs pymysql

실행 예시:
  python database/generate_audio_elevenlabs.py --days 1-3
  python database/generate_audio_elevenlabs.py --days all

API 키 설정 (둘 중 하나):
  1) 환경변수 : set ELEVEN_API_KEY=sk-...
  2) 실행 인자: --api-key sk-...
"""

import os
import sys
import time
import argparse

# ── 경로 설정 ────────────────────────────────────────────────
BASE_PATH  = r"C:\xampp\htdocs\chunking-english"
AUDIO_BASE = os.path.join(BASE_PATH, "asset", "audio")

# ── ElevenLabs 음성 설정 ──────────────────────────────────────
# 아이 영어 학습용 추천 목소리
# 변경하려면 아래 VOICE_ID 값을 바꾸세요
# 목소리 목록 확인: https://elevenlabs.io/voice-library
VOICE_ID   = "21m00Tcm4TlvDq8ikWAM"   # Rachel - 차분하고 또렷한 여성 목소리
MODEL_ID   = "eleven_turbo_v2_5"       # 빠르고 저렴 (고품질은 eleven_multilingual_v2)
OUTPUT_FMT = "mp3_44100_128"           # 44kHz / 128kbps MP3


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


def generate_mp3(client, text: str, full_path: str) -> str:
    """ElevenLabs TTS로 MP3 생성. 이미 있으면 스킵."""
    if os.path.exists(full_path):
        return "skip"

    os.makedirs(os.path.dirname(full_path), exist_ok=True)

    try:
        audio_bytes = client.text_to_speech.convert(
            voice_id=VOICE_ID,
            text=text,
            model_id=MODEL_ID,
            output_format=OUTPUT_FMT,
        )
        # SDK가 generator를 반환하는 경우 처리
        if hasattr(audio_bytes, '__iter__') and not isinstance(audio_bytes, (bytes, bytearray)):
            audio_bytes = b"".join(audio_bytes)

        with open(full_path, "wb") as f:
            f.write(audio_bytes)
        return "ok"
    except Exception as e:
        return f"error: {e}"


# ── 메인 ─────────────────────────────────────────────────────
def main():
    parser = argparse.ArgumentParser(description="ElevenLabs TTS MP3 생성")
    parser.add_argument("--days",    default="1-3",  help="Day 범위: 1-3 또는 all")
    parser.add_argument("--api-key", default="",     help="ElevenLabs API 키 (없으면 환경변수 ELEVEN_API_KEY 사용)")
    args = parser.parse_args()

    # API 키 확인
    api_key = args.api_key or os.environ.get("ELEVEN_API_KEY", "")
    if not api_key:
        print("ERROR: ElevenLabs API 키가 없습니다.")
        print("  방법 1) --api-key sk-xxxx 인자로 전달")
        print("  방법 2) 환경변수: set ELEVEN_API_KEY=sk-xxxx")
        sys.exit(1)

    # Day 범위 파싱
    if args.days == "all":
        day_start, day_end = 1, 50
    elif "-" in args.days:
        s, e = args.days.split("-")
        day_start, day_end = int(s), int(e)
    else:
        day_start = day_end = int(args.days)

    # ElevenLabs 클라이언트 초기화
    try:
        from elevenlabs.client import ElevenLabs
    except ImportError:
        print("ERROR: elevenlabs 패키지가 없습니다.")
        print("  pip install elevenlabs")
        sys.exit(1)

    client = ElevenLabs(api_key=api_key)

    # DB 연결
    try:
        import pymysql
    except ImportError:
        print("ERROR: pymysql 패키지가 없습니다.")
        print("  pip install pymysql")
        sys.exit(1)

    db  = get_db()
    cur = db.cursor()

    # Day 범위 표현 조회
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
    print(f"목소리   : Rachel (voice_id={VOICE_ID})")
    print(f"모델     : {MODEL_ID}\n")

    stats        = {"ok": 0, "skip": 0, "error": 0}
    audio_updates = []  # (rel_path, expr_id)

    for i, row in enumerate(rows, 1):
        day_num, global_num, verb_en, expr_id, expression_en = row

        tts_text  = expression_en          # 표현만 읽어줌 (깔끔하게)
        full_path = audio_full_path(day_num, global_num, verb_en, expression_en)
        rel_path  = audio_rel_path(day_num, global_num, verb_en, expression_en)

        result = generate_mp3(client, tts_text, full_path)

        key   = result if result in ("ok", "skip") else "error"
        label = {"ok": "OK  ", "skip": "SKIP", "error": "ERR "}.get(key, "ERR ")
        stats[key] += 1

        print(f"[{i:3d}/{total}] {label} Day{day_num:02d} | {verb_en:15s} | {expression_en}")
        if key == "error":
            print(f"         └─ {result}")

        if result == "ok":
            audio_updates.append((rel_path, expr_id))
            time.sleep(0.3)  # API 호출 간격

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
  생성(OK)  : {stats['ok']:3d}개
  스킵(기존): {stats['skip']:3d}개
  오류(ERR) : {stats['error']:3d}개
==============================================
""")


if __name__ == "__main__":
    main()
