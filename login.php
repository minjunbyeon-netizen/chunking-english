<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Jua&family=Chewy&display=swap" rel="stylesheet">
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

        /* 배경 고정 */
        .login-bg {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background-image:
                    radial-gradient(#FFE4E8 2px, transparent 2px),
                    linear-gradient(to bottom, #FFF5F7 0%, #FFE4E8 100%);
            background-size: 30px 30px, 100% 100%;
            z-index: -1;
        }

        /* 전체 레이아웃 컨테이너 */
        .wrapper {
            width: 100%;
            max-width: 400px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        /* 상단 외부 제목 스타일 */
        .main-title {
            font-family: 'Chewy', cursive;
            font-size: 3.2rem;
            color: #2D2D2D;
            text-align: center;
            line-height: 1.1;
            margin-bottom: 30px;
            text-shadow: 4px 4px 0px #FF8FA3;
            word-break: keep-all;
        }

        /* 로그인 메인 컨테이너 */
        .login-card {
            width: 100%;
            background: #FFFFFF;
            border: 4px solid #2D2D2D;
            border-radius: 40px;
            padding: 50px 40px;
            box-shadow: 12px 12px 0px #2D2D2D;
            position: relative;
            box-sizing: border-box;
        }

        .field-label {
            display: block;
            color: #2D2D2D;
            font-size: 1rem;
            margin-bottom: 10px;
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
            margin-bottom: 25px;
            box-sizing: border-box;
        }

        .input-box:focus {
            background-color: #FFF5F7;
            border-color: #FF8FA3;
            transform: translateY(-2px);
        }

        .btn-enter {
            width: 100%;
            height: 65px;
            background-color: #FF8FA3;
            color: #FFFFFF;
            border: 3px solid #2D2D2D;
            border-radius: 22px;
            font-size: 1.4rem;
            cursor: pointer;
            box-shadow: 0 6px 0px #2D2D2D;
            transition: all 0.1s;
            margin-top: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .btn-enter:active {
            transform: translateY(4px);
            box-shadow: 0 2px 0px #2D2D2D;
        }

        .find-pw-link {
            display: block;
            text-align: center;
            margin-top: 25px;
            color: #9CA3AF;
            font-size: 0.9rem;
            text-decoration: none;
            transition: color 0.2s;
        }

        .find-pw-link:hover {
            color: #FF5C77;
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

        /* --- 모바일 반응형 대응 --- */
        @media (max-width: 480px) {
            .main-title {
                font-size: 2.5rem;
                margin-bottom: 20px;
            }

            .login-card {
                padding: 40px 25px;
                border-radius: 30px;
                box-shadow: 8px 8px 0px #2D2D2D;
            }

            .input-box {
                height: 55px;
                font-size: 1rem;
                margin-bottom: 20px;
            }

            .btn-enter {
                height: 60px;
                font-size: 1.2rem;
            }

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
    <h1 class="main-title">chunking english<br>kids&mom</h1>

    <div class="login-card">
        <form id="loginForm">
            <div class="form-group">
                <label class="field-label">이메일</label>
                <input type="email" name="email" id="loginEmail" class="input-box" placeholder="이메일 입력" required>
            </div>

            <div class="form-group">
                <label class="field-label">비밀번호</label>
                <input type="password" name="password" id="loginPassword" class="input-box" placeholder="비밀번호 입력" required>
            </div>

            <div id="loginError" style="color:#FA4252;font-size:0.85rem;margin-bottom:8px;display:none;text-align:center;"></div>

            <button type="submit" class="btn-enter">
                ENTER
            </button>
        </form>
        <script>
        document.getElementById('loginForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const email    = document.getElementById('loginEmail').value.trim();
            const password = document.getElementById('loginPassword').value;
            const errEl    = document.getElementById('loginError');
            errEl.style.display = 'none';
            try {
                const res  = await fetch('./api/auth/login.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ email, password })
                });
                const data = await res.json();
                if (data.success) {
                    location.href = './index.php';
                } else {
                    errEl.textContent = data.error || '로그인 실패';
                    errEl.style.display = 'block';
                }
            } catch(err) {
                errEl.textContent = '서버 오류가 발생했습니다.';
                errEl.style.display = 'block';
            }
        });
        </script>

        <a href="find_password.php" class="find-pw-link">비밀번호 찾기</a>
    </div>
</div>

</body>
</html>