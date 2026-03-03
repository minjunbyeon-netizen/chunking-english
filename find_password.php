<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>비밀번호 찾기 - Chunking English</title>
    <link href="https://fonts.googleapis.com/css2?family=Jua&family=Nanum+Brush+Script&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: 'Jua', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #FFF5F7;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
        }

        .login-bg {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background-image:
                    radial-gradient(#FFE4E8 2px, transparent 2px),
                    linear-gradient(to bottom, #FFF5F7 0%, #FFE4E8 100%);
            background-size: 30px 30px, 100% 100%;
            z-index: -1;
        }

        .wrapper {
            width: 100%;
            max-width: 420px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        /* 한글 타이틀 스타일 */
        .main-title {
            font-family: 'Jua', sans-serif;
            font-size: 3.2rem;
            color: #2D2D2D;
            text-align: center;
            line-height: 1.2;
            margin-bottom: 25px;
            text-shadow: 4px 4px 0px #FF8FA3;
            word-break: keep-all;
            letter-spacing: -1px;
        }

        .find-card {
            width: 100%;
            background: #FFFFFF;
            border: 4px solid #2D2D2D;
            border-radius: 40px;
            padding: 50px 35px; /* 하단 링크 삭제로 여백 조정 */
            box-shadow: 10px 10px 0px #2D2D2D;
            position: relative;
            box-sizing: border-box;
        }

        .info-text {
            color: #555555;
            text-align: center;
            font-size: 1.1rem;
            margin-bottom: 30px;
            line-height: 1.6;
        }

        .field-label {
            display: block;
            color: #2D2D2D;
            font-size: 1.05rem;
            margin-bottom: 8px;
            padding-left: 5px;
        }

        .input-box {
            width: 100%;
            height: 60px;
            background-color: #FFFFFF;
            border: 3px solid #2D2D2D;
            border-radius: 20px;
            padding: 0 20px;
            font-size: 1.1rem;
            transition: all 0.2s;
            outline: none;
            margin-bottom: 20px;
            box-sizing: border-box;
            text-align: center;
        }

        .input-box:focus {
            background-color: #FFF5F7;
            border-color: #FF8FA3;
        }

        .btn-find {
            width: 100%;
            height: 65px;
            background-color: #FF8FA3;
            color: #FFFFFF;
            border: 3px solid #2D2D2D;
            border-radius: 20px;
            font-size: 1.4rem;
            cursor: pointer;
            box-shadow: 0 5px 0px #2D2D2D;
            transition: all 0.1s;
            margin-top: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .btn-find:active {
            transform: translateY(4px);
            box-shadow: 0 1px 0px #2D2D2D;
        }

        .highlight {
            color: #FF5C77;
            font-weight: bold;
        }

        /* --- 플로팅 홈 버튼 스타일 --- */
        .floating-home {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 65px;
            height: 65px;
            background-color: #FFFFFF;
            border: 3px solid #2D2D2D;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 6px 6px 0px #2D2D2D;
            cursor: pointer;
            transition: all 0.2s;
            z-index: 100;
            text-decoration: none;
        }

        .floating-home:hover {
            background-color: #FFF5F7;
            transform: scale(1.05);
        }

        .floating-home:active {
            transform: translateY(3px) scale(0.95);
            box-shadow: 2px 2px 0px #2D2D2D;
        }

        .home-icon {
            width: 30px;
            height: 30px;
            fill: #2D2D2D;
        }

        @media (max-width: 480px) {
            .main-title { font-size: 2.5rem; }
            .find-card { padding: 40px 25px; }
            .info-text { font-size: 1rem; }
            .btn-find { height: 60px; font-size: 1.25rem; }

            .floating-home {
                bottom: 20px;
                right: 20px;
                width: 55px;
                height: 55px;
            }
        }
    </style>
</head>
<body>

<div class="login-bg"></div>

<a href="./index.php" class="floating-home" title="홈으로 이동">
    <svg class="home-icon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
        <path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/>
    </svg>
</a>

<div class="wrapper">
    <h1 class="main-title">비밀번호 찾기</h1>

    <div class="find-card">
        <p class="info-text">
            가입할 때 발급받은 <span class="highlight">아이디</span>를<br>입력하시면 비밀번호를 확인해 드려요!
        </p>

        <form id="findPwForm">
            <div class="form-group">
                <label class="field-label">아이디</label>
                <input type="text"
                       id="username"
                       name="username"
                       class="input-box"
                       placeholder="아이디를 입력하세요"
                       required>
            </div>

            <button type="submit" class="btn-find">
                확인하기
            </button>
        </form>
    </div>
</div>

<script>
    document.getElementById('findPwForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const username = document.getElementById('username').value;

        if (username.trim() !== "") {
            alert("✅ 조회가 완료되었습니다!\n\n[" + username + "] 님의 비밀번호는\n'chunking777' 입니다.\n\n다시 로그인을 진행해 주세요.");
            // 알림 확인 후 홈으로 이동시키고 싶다면 아래 주석을 해제하세요.
            // window.location.href = "./index.php";
        }
    });
</script>

</body>
</html>