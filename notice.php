<?php require_once 'config/db.php'; require_once 'config/auth.php'; ?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>청킹 잉글리시 - 문의 게시판</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Gowun+Dodum&family=Jua&family=Noto+Sans+KR:wght@400;500;700&display=swap" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'brand-main': '#FF7E96',      /* 메인 핑크 */
                        'brand-bg': '#FFF0F3',        /* 배경 연한 핑크 */
                        'brand-text': '#333333',      /* 진한 회색 텍스트 */
                        'brand-text-light': '#666666', /* 보조 텍스트 */
                        'accent-blue': '#4A90E2',     /* 포인트 블루 */
                        'accent-yellow': '#FFC107',   /* 포인트 옐로우 */
                        'status-green': '#28A745',    /* 완료 상태 초록 */
                        'status-gray': '#888888',     /* 대기 상태 회색 */
                    },
                    fontFamily: {
                        'display': ['Jua', 'sans-serif'],
                        'body': ['Gowun Dodum', 'Noto Sans KR', 'sans-serif'],
                    },
                    animation: {
                        'slide-up': 'slideUp 0.5s ease-out forwards',
                        'fade-in': 'fadeIn 0.3s ease-out forwards',
                    },
                    keyframes: {
                        slideUp: { '0%': { transform: 'translateY(10px)', opacity: '0' }, '100%': { transform: 'translateY(0)', opacity: '1' } },
                        fadeIn: { '0%': { opacity: '0', transform: 'scale(0.98)' }, '100%': { opacity: '1', transform: 'scale(1)' } }
                    }
                }
            }
        }
    </script>

    <style>
        .bg-pattern {
            background-color: #FFF0F3;
            background-image: radial-gradient(#FFB6C1 1.5px, transparent 1.5px);
            background-size: 24px 24px;
        }
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 4px; }
        ::-webkit-scrollbar-thumb { background: #FF7E96; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #FF5C77; }
    </style>
</head>

<body class="font-body text-brand-text antialiased bg-pattern h-[100dvh] overflow-hidden relative flex flex-col">

<header class="w-full px-4 md:px-6 py-3 md:py-4 z-50 flex-none bg-white/80 backdrop-blur-md border-b border-white/50 shadow-sm transition-all">
    <div class="max-w-5xl mx-auto flex justify-between items-center">
        <button onclick="history.back()" class="group bg-white border border-gray-200 rounded-full px-3 py-1.5 md:px-4 md:py-2 shadow-sm hover:border-brand-main hover:text-brand-main transition-all flex items-center gap-2">
            <i class="fa-solid fa-arrow-left text-sm md:text-base"></i>
            <span class="font-display pt-0.5 text-sm md:text-base hidden md:inline">뒤로가기</span>
        </button>
        <div class="font-display text-xl md:text-2xl text-brand-text drop-shadow-sm tracking-wide">
            <span class="text-brand-main">Chunking</span> 고객센터
        </div>
    </div>
</header>

<main class="w-full flex-1 flex items-center justify-center p-2 md:p-6 overflow-hidden">
    <div class="relative w-full max-w-5xl h-full bg-white rounded-2xl md:rounded-[1.5rem] shadow-xl border border-gray-100 flex flex-col overflow-hidden animate-fade-in">

        <div class="pt-5 pb-3 px-5 md:pt-6 md:pb-4 md:px-8 bg-white border-b border-gray-100 flex flex-col md:flex-row justify-between items-end md:items-center gap-3 md:gap-4 flex-none z-20">
            <div class="text-left w-full md:w-auto">
                <div class="inline-block bg-accent-blue text-white font-body text-[10px] md:text-xs px-2 py-0.5 md:px-2.5 md:py-1 rounded-full mb-1 md:mb-2 font-bold tracking-wider">
                    Q&A BOARD
                </div>
                <h1 class="font-display text-2xl md:text-4xl text-brand-text flex items-center gap-2">
                    문의하기
                    <i class="fa-solid fa-pencil text-xl md:text-2xl text-brand-main"></i>
                </h1>
            </div>
            <div class="w-full md:w-auto relative">
                <div class="relative flex shadow-sm rounded-full">
                    <input type="text" placeholder="검색어를 입력하세요" class="w-full md:w-72 bg-gray-50 border border-gray-300 rounded-full py-2 pl-4 md:pl-5 pr-10 md:pr-12 text-sm focus:outline-none focus:border-brand-main focus:ring-1 focus:ring-brand-main transition-all font-body">
                    <button class="absolute right-1 top-1/2 -translate-y-1/2 w-8 h-8 md:w-9 md:h-9 bg-brand-main text-white rounded-full hover:bg-rose-500 transition-colors flex items-center justify-center shadow-md">
                        <i class="fa-solid fa-magnifying-glass text-xs md:text-sm"></i>
                    </button>
                </div>
            </div>
        </div>

        <div class="flex-1 bg-gray-50/50 overflow-y-auto relative">
            <div class="p-3 md:p-6 min-h-full">
                <div class="hidden md:flex justify-between px-6 py-3 bg-brand-text text-white rounded-lg mb-3 font-display text-base tracking-wider shadow-md sticky top-0 z-30">
                    <div class="w-16 text-center">번호</div>
                    <div class="flex-1 text-center">제목</div>
                    <div class="w-28 text-center">작성자</div>
                    <div class="w-28 text-center">작성일</div>
                    <div class="w-24 text-center">상태</div>
                </div>
                <div id="board-list" class="space-y-2 md:space-y-2.5"></div>
            </div>
        </div>

        <div class="p-3 md:p-4 bg-white border-t border-gray-200 flex justify-between items-center gap-2 md:gap-3 relative z-40 flex-none shadow-[0_-5px_15px_rgba(0,0,0,0.02)]">
            <div class="flex gap-1 items-center">
                <button class="w-8 h-8 md:w-9 md:h-9 rounded-lg border border-gray-200 text-gray-400 hover:border-brand-main hover:text-brand-main hover:bg-rose-50 transition-all flex items-center justify-center">
                    <i class="fa-solid fa-chevron-left text-[10px] md:text-xs"></i>
                </button>
                <button class="w-8 h-8 md:w-9 md:h-9 rounded-lg bg-brand-main text-white font-display text-base md:text-lg shadow-md hover:bg-rose-500 transition-colors">1</button>
                <button class="w-8 h-8 md:w-9 md:h-9 rounded-lg border border-gray-200 text-gray-400 hover:border-brand-main hover:text-brand-main hover:bg-rose-50 transition-all flex items-center justify-center">
                    <i class="fa-solid fa-chevron-right text-[10px] md:text-xs"></i>
                </button>
            </div>
            <button onclick="openWriteModal()" class="bg-brand-text text-white border-2 border-brand-text rounded-full px-4 py-2 md:px-6 md:py-2.5 font-display text-base md:text-lg shadow-lg hover:bg-white hover:text-brand-text hover:shadow-xl transition-all flex items-center justify-center gap-2 group transform active:scale-95 whitespace-nowrap">
                <i class="fa-solid fa-pen text-xs md:text-sm group-hover:rotate-12 transition-transform"></i>
                <span>글쓰기</span>
            </button>
        </div>
    </div>
</main>

<div id="write-modal" class="fixed inset-0 z-[100] hidden flex items-center justify-center bg-black/60 backdrop-blur-sm px-4">
    <div class="bg-white rounded-2xl md:rounded-[1.5rem] p-5 md:p-8 w-full max-w-xl shadow-2xl relative animate-fade-in flex flex-col max-h-[90vh]">
        <div class="flex justify-between items-center mb-4 md:mb-6">
            <h2 class="font-display text-xl md:text-3xl text-brand-text flex items-center gap-2">
                <i class="fa-solid fa-envelope-open-text text-accent-blue"></i> 문의 작성
            </h2>
            <button onclick="closeModal('write-modal')" class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center text-gray-400 hover:bg-red-50 hover:text-red-500 transition-colors">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="space-y-4 md:space-y-5 font-body flex-1 overflow-y-auto pr-1">
            <div>
                <label class="block text-xs font-bold text-gray-500 mb-1 ml-1">작성자 이름</label>
                <input type="text" placeholder="홍길동" class="w-full bg-gray-50 border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:border-brand-main focus:ring-1 focus:ring-brand-main transition-all">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-500 mb-1 ml-1">제목</label>
                <input type="text" placeholder="문의 제목을 입력하세요" class="w-full bg-gray-50 border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:border-brand-main focus:ring-1 focus:ring-brand-main transition-all">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-500 mb-1 ml-1">내용</label>
                <textarea placeholder="궁금한 점을 자유롭게 남겨주세요." class="w-full h-48 md:h-64 bg-white border border-gray-300 rounded-lg px-4 py-3 text-sm resize-none focus:outline-none focus:border-brand-main focus:ring-1 focus:ring-brand-main leading-relaxed"></textarea>
            </div>
        </div>

        <div class="mt-6 md:mt-8 flex justify-end gap-3 pt-4 border-t border-gray-100">
            <button onclick="closeModal('write-modal')" class="px-4 py-2 md:px-5 md:py-2.5 rounded-xl border border-gray-300 font-bold text-xs md:text-sm text-gray-500 hover:bg-gray-50 hover:text-gray-700 transition-all">
                취소
            </button>
            <button onclick="submitPost()" class="px-6 py-2 md:px-8 md:py-2.5 rounded-xl bg-brand-text text-white font-display text-base md:text-lg shadow-md hover:bg-brand-main transition-all transform active:scale-95">
                등록하기
            </button>
        </div>
    </div>
</div>

<script>
    // 샘플 데이터에서 secret 속성 무시됨
    const boardData = [
        { id: 108, title: "학습 진도가 업데이트되지 않습니다.", writer: "김*수", date: "2026.02.12", status: "waiting", new: true },
        { id: 107, title: "이벤트 당첨자 확인 부탁드려요", writer: "이*민", date: "2026.02.11", status: "complete", new: true },
        { id: 106, title: "강의가 너무 재미있어요! 후기 남깁니다.", writer: "박*준", date: "2026.02.10", status: "complete", new: false },
        { id: 105, title: "교재 배송은 언제 시작되나요?", writer: "최*우", date: "2026.02.09", status: "complete", new: false },
        { id: 104, title: "아이디 변경 요청합니다", writer: "정*윤", date: "2026.02.08", status: "waiting", new: false },
        { id: 103, title: "모바일에서 화면이 깨져 보입니다", writer: "강*원", date: "2026.02.07", status: "complete", new: false },
        { id: 102, title: "환불 규정 문의", writer: "조*은", date: "2026.02.05", status: "complete", new: false },
        { id: 101, title: "선생님 감사합니다^^", writer: "윤*호", date: "2026.02.03", status: "complete", new: false },
        { id: 100, title: "레벨 테스트 관련 질문", writer: "장*현", date: "2026.02.01", status: "complete", new: false },
        { id: 99, title: "로그인이 자꾸 풀려요", writer: "서*희", date: "2026.01.29", status: "waiting", new: false },
    ];

    function renderBoard() {
        const list = document.getElementById('board-list');
        list.innerHTML = '';
        boardData.forEach((item, index) => {
            const delay = index * 50;
            const isComplete = item.status === 'complete';

            const badge = isComplete
                ? `<span class="inline-flex items-center justify-center gap-1 bg-green-100 text-green-700 border border-green-200 w-16 md:w-20 py-0.5 md:py-1 rounded-md text-[10px] md:text-[11px] font-bold"><i class="fa-solid fa-check"></i> 답변완료</span>`
                : `<span class="inline-flex items-center justify-center gap-1 bg-gray-100 text-gray-500 border border-gray-200 w-16 md:w-20 py-0.5 md:py-1 rounded-md text-[10px] md:text-[11px] font-bold"><i class="fa-regular fa-clock"></i> 대기중</span>`;

            const newBadge = item.new ? `<span class="absolute -top-1 -left-1 bg-red-500 text-white text-[9px] w-4 h-4 flex items-center justify-center rounded-full font-bold shadow-sm z-10">N</span>` : ``;

            const row = document.createElement('div');
            row.className = "group relative bg-white border border-gray-200 rounded-xl p-3 md:px-5 md:py-3.5 flex flex-col md:flex-row items-start md:items-center gap-2 md:gap-4 shadow-sm hover:shadow-md hover:border-brand-main/50 hover:bg-rose-50/30 transition-all duration-200 cursor-pointer animate-slide-up";
            row.style.animationDelay = `${delay}ms`;

            row.onclick = () => {
                alert('게시글 상세 페이지로 이동합니다. (데모)');
            };

            row.innerHTML = `
                ${newBadge}
                <div class="hidden md:block w-16 text-center font-display text-lg text-gray-400 group-hover:text-brand-main transition-colors">${item.id}</div>
                <div class="flex-1 w-full min-w-0 pr-2">
                    <div class="flex items-center">
                        <span class="md:hidden text-xs font-bold text-brand-main mr-2">No.${item.id}</span>
                        <span class="font-body font-bold text-[14px] md:text-[15px] lg:text-base text-brand-text truncate group-hover:text-accent-blue transition-colors">${item.title}</span>
                    </div>
                </div>
                <div class="w-full md:w-auto flex justify-between md:contents items-center text-xs text-gray-500 font-medium">
                    <div class="flex items-center gap-4 md:contents">
                        <div class="w-auto md:w-28 md:text-center flex items-center gap-1.5 md:justify-center">
                            <i class="fa-regular fa-user md:hidden text-gray-400"></i> ${item.writer}
                        </div>
                        <div class="w-auto md:w-28 md:text-center flex items-center gap-1.5 md:justify-center font-normal text-gray-400">
                            <i class="fa-regular fa-calendar md:hidden"></i> ${item.date}
                        </div>
                    </div>
                    <div class="w-auto md:w-24 text-right md:text-center">${badge}</div>
                </div>
            `;
            list.appendChild(row);
        });
    }

    function openModal(id) {
        document.getElementById(id).classList.remove('hidden');
    }

    function closeModal(id) {
        document.getElementById(id).classList.add('hidden');
    }

    function openWriteModal() {
        openModal('write-modal');
    }

    function submitPost() {
        alert("게시글이 성공적으로 등록되었습니다.");
        closeModal('write-modal');
    }

    window.onload = renderBoard;
</script>
</body>
</html>