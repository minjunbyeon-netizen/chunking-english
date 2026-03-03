<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>회원가입 · Chunking English</title>
    <link href="https://fonts.googleapis.com/css2?family=Chewy&family=Jua&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Jua', sans-serif;
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
        .main-title {
            font-family: 'Chewy', cursive;
            font-size: 2.8rem;
            color: #2D2D2D;
            text-align: center;
            line-height: 1.1;
            margin-bottom: 24px;
            text-shadow: 4px 4px 0px #FF8FA3;
        }
        .login-card {
            width: 100%;
            background: #FFFFFF;
            border: 4px solid #2D2D2D;
            border-radius: 40px;
            padding: 44px 40px;
            box-shadow: 12px 12px 0px #2D2D2D;
        }
        .field-label {
            display: block;
            color: #2D2D2D;
            font-size: .95rem;
            margin-bottom: 8px;
            padding-left: 5px;
        }
        .input-box {
            width: 100%;
            height: 58px;
            background-color: #FFFFFF;
            border: 3px solid #2D2D2D;
            border-radius: 20px;
            padding: 0 20px;
            font-size: 1rem;
            font-family: 'Jua', sans-serif;
            transition: all 0.2s;
            outline: none;
            margin-bottom: 18px;
        }
        .input-box:focus {
            background-color: #FFF5F7;
            border-color: #FF8FA3;
            transform: translateY(-2px);
        }
        .hint {
            font-size: .76rem;
            color: #9CA3AF;
            margin-top: -14px;
            margin-bottom: 18px;
            padding-left: 6px;
        }
        .btn-enter {
            width: 100%;
            height: 62px;
            background-color: #FF8FA3;
            color: #FFFFFF;
            border: 3px solid #2D2D2D;
            border-radius: 22px;
            font-size: 1.3rem;
            font-family: 'Chewy', cursive;
            letter-spacing: 1px;
            cursor: pointer;
            box-shadow: 0 6px 0px #2D2D2D;
            transition: all 0.1s;
            margin-top: 8px;
        }
        .btn-enter:active { transform: translateY(4px); box-shadow: 0 2px 0px #2D2D2D; }
        .btn-enter:disabled { opacity: .6; cursor: not-allowed; }
        .error-msg {
            display: none;
            background: #fff1f2;
            border: 2px solid #fca5a5;
            border-radius: 12px;
            padding: 10px 16px;
            color: #991b1b;
            font-size: .88rem;
            margin-bottom: 16px;
            text-align: center;
        }
        .success-msg {
            display: none;
            background: #f0fdf4;
            border: 2px solid #86efac;
            border-radius: 12px;
            padding: 10px 16px;
            color: #166534;
            font-size: .88rem;
            margin-bottom: 16px;
            text-align: center;
        }
        .bottom-links {
            margin-top: 24px;
            text-align: center;
        }
        .bottom-links a {
            color: #9CA3AF;
            font-size: 0.88rem;
            text-decoration: none;
            transition: color 0.2s;
        }
        .bottom-links a:hover { color: #FF5C77; }
        .floating-home {
            position: fixed;
            bottom: 30px; right: 30px;
            width: 65px; height: 65px;
            background-color: #FFFFFF;
            border: 3px solid #2D2D2D;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 6px 6px 0px #2D2D2D;
            text-decoration: none;
            transition: all 0.2s;
        }
        .floating-home:hover { background-color: #FFF5F7; transform: scale(1.05); }
        .home-icon { width: 30px; height: 30px; fill: #2D2D2D; }
        @media (max-width: 480px) {
            .main-title { font-size: 2.2rem; }
            .login-card { padding: 36px 24px; border-radius: 30px; box-shadow: 8px 8px 0px #2D2D2D; }
            .input-box { height: 54px; }
            .btn-enter { height: 58px; font-size: 1.1rem; }
            .floating-home { bottom: 20px; right: 20px; width: 55px; height: 55px; }
        }
    </style>
</head>
<body>

<div class="login-bg"></div>

<a href="./index.php" class="floating-home" title="홈으로 이동">
    <svg class="home-icon" viewBox="0 0 24 24"><path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/></svg>
</a>

<div class="wrapper">
    <h1 class="main-title">회원가입</h1>

    <div class="login-card">
        <div class="error-msg"   id="error-msg"></div>
        <div class="success-msg" id="success-msg"></div>

        <label class="field-label">이메일</label>
        <input type="email" id="email" class="input-box" placeholder="이메일 입력" autocomplete="email">

        <label class="field-label">닉네임</label>
        <input type="text"  id="nickname" class="input-box" placeholder="닉네임 입력 (선택)">

        <label class="field-label">비밀번호</label>
        <input type="password" id="password" class="input-box" placeholder="비밀번호 입력">
        <p class="hint">6자 이상</p>

        <label class="field-label">비밀번호 확인</label>
        <input type="password" id="password2" class="input-box" placeholder="비밀번호 재입력">

        <button class="btn-enter" id="btn-reg" onclick="doRegister()">JOIN</button>

        <div class="bottom-links">
            <a href="login.php">이미 계정이 있으신가요? 로그인</a>
        </div>
    </div>
</div>

<script>
document.getElementById('password2').addEventListener('keydown', e => {
    if (e.key === 'Enter') doRegister();
});

async function doRegister() {
    const email     = document.getElementById('email').value.trim();
    const nickname  = document.getElementById('nickname').value.trim();
    const password  = document.getElementById('password').value;
    const password2 = document.getElementById('password2').value;
    const btn       = document.getElementById('btn-reg');

    hideMessages();

    if (!email)              return showError('이메일을 입력해주세요.');
    if (password.length < 6) return showError('비밀번호는 6자 이상이어야 합니다.');
    if (password !== password2) return showError('비밀번호가 일치하지 않습니다.');

    btn.disabled = true;
    btn.textContent = '...';

    try {
        const res  = await fetch('/chunking-english/api/auth/register.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify({ email, password, nickname }),
        });
        const data = await res.json();

        if (res.ok) {
            showSuccess('인증 이메일을 발송했습니다. 메일함을 확인하고 링크를 클릭해주세요.');
            document.getElementById('btn-reg').disabled = true;
        } else {
            showError(data.error || '회원가입에 실패했습니다.');
        }
    } catch {
        showError('서버에 연결할 수 없습니다.');
    } finally {
        btn.disabled = false;
        btn.textContent = 'JOIN';
    }
}

function showError(msg) {
    const el = document.getElementById('error-msg');
    el.textContent = msg;
    el.style.display = 'block';
}
function showSuccess(msg) {
    const el = document.getElementById('success-msg');
    el.textContent = msg;
    el.style.display = 'block';
}
function hideMessages() {
    document.getElementById('error-msg').style.display = 'none';
    document.getElementById('success-msg').style.display = 'none';
}
</script>
</body>
</html>
