<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>Chunking English E-Book</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Chewy&family=Jua&family=Quicksand:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        /* [수정됨] 배경색: 아주 은은한 핑크빛이 도는 화이트 (Rose White) */
                        'brand-bg': '#FFF9FB',
                        'brand-white': '#FFFFFF',
                        'primary': '#FF8FA3',   /* Soft Coral */
                        'secondary': '#8AC9A6', /* Soft Mint */
                        'accent': '#FFD166',    /* Warm Yellow */
                        'text-main': '#2D3436', /* Charcoal */
                        'text-sub': '#636E72',  /* Slate Gray */
                        'line-gray': '#F2E8EB', /* 라인 색상도 핑크톤에 맞춰 살짝 웜하게 조정 */
                    },
                    fontFamily: {
                        'display': ['Chewy', 'cursive'],
                        'body': ['Quicksand', 'sans-serif'],
                        'kor': ['Jua', 'sans-serif'],
                    },
                    boxShadow: {
                        'soft': '0 4px 20px rgba(255, 143, 163, 0.08)', /* 그림자도 핑크빛이 살짝 돌게 수정 */
                        'float': '0 10px 30px rgba(255, 143, 163, 0.12)',
                        'inner-soft': 'inset 0 2px 4px rgba(0,0,0,0.02)',
                    },
                    spacing: {
                        'a4-w': '210mm',
                        'a4-h': '297mm',
                    }
                }
            }
        }
    </script>
    <style>
        /* 인쇄 및 PDF 변환 설정 */
        @media print {
            @page { size: A4 portrait; margin: 0; }
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .page-break { break-before: always; }
            .no-print { display: none; }
        }

        body {
            background-color: #555; /* 브라우저 배경 (어둡게 하여 종이가 돋보이게) */
            font-family: 'Quicksand', 'Jua', sans-serif;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 40px 0;
            color: #2D3436;
        }

        /* A4 시트 정의 */
        .sheet {
            width: 210mm;
            height: 297mm;
            /* 배경색 적용 (Tailwind config의 brand-bg와 동일) */
            background-color: #FFF9FB;
            position: relative;
            box-shadow: 0 0 30px rgba(0,0,0,0.2);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            margin-bottom: 40px; /* 화면상 페이지 간격 */
            padding: 15mm; /* 안전 여백 */
        }

        /* 배경 데코레이션 (아주 은은한 도트) */
        .bg-deco {
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            /* 도트 색상을 핑크빛 회색으로 변경하여 배경과 어우러지게 */
            background-image: radial-gradient(#F0E1E4 1.5px, transparent 1.5px);
            background-size: 24px 24px;
            z-index: 0;
            opacity: 0.6;
            pointer-events: none;
        }

        /* 공책 라인 - 프리미엄 노트 느낌 */
        .writing-lines {
            background-image: linear-gradient(transparent 96%, #E0E7FF 96%);
            background-size: 100% 48px;
            line-height: 48px;
        }

        /* 컨텐츠 레이어 (배경 위에 올라감) */
        .z-content { position: relative; z-index: 10; height: 100%; display: flex; flex-direction: column; }
    </style>
</head>
<body>

<div class="no-print mb-6 text-center text-white font-kor opacity-80">
    <p class="mb-2">✨ <strong>PDF 저장 방법:</strong> Ctrl+P (인쇄) > 대상: 'PDF로 저장' > 설정: '배경 그래픽' 체크</p>
</div>

<div class="sheet">
    <div class="bg-deco"></div>
    <div class="z-content">

        <header class="flex justify-between items-center mb-8">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-primary rounded-xl flex items-center justify-center text-white shadow-soft transform rotate-3">
                    <i class="fa-solid fa-shapes"></i>
                </div>
                <div>
                    <h1 class="font-display text-xl text-text-main leading-none">Chunking English</h1>
                    <span class="text-[10px] text-text-sub font-bold tracking-widest uppercase">Kids & Mom Basic Course</span>
                </div>
            </div>
            <div class="bg-white px-4 py-1.5 rounded-full border border-line-gray shadow-sm flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-secondary"></span>
                <span class="font-display text-lg text-text-main">Day 01</span>
            </div>
        </header>

        <section class="mb-8">
            <div class="bg-white rounded-[2rem] p-8 text-center shadow-float border border-white relative overflow-hidden group">
                <div class="absolute top-0 left-0 w-full h-1.5 bg-gradient-to-r from-primary via-accent to-secondary"></div>
                <div class="absolute -right-6 -bottom-6 w-32 h-32 bg-primary/5 rounded-full blur-3xl"></div>

                <span class="inline-block px-3 py-1 bg-brand-bg rounded-full text-xs font-bold text-gray-400 mb-4 border border-line-gray">Today's Key Sentence</span>

                <h2 class="font-display text-5xl text-text-main mb-3 tracking-wide drop-shadow-sm">
                    " I <span class="text-primary">have</span> a dream. "
                </h2>
                <p class="font-kor text-xl text-text-sub font-medium opacity-80">나는 꿈을 가지고 있어요.</p>
            </div>
        </section>

        <section class="grid grid-cols-2 gap-5 flex-grow">
            <div class="bg-white p-4 rounded-[1.5rem] shadow-soft border border-white flex flex-col hover:border-primary/20 transition-colors">
                <div class="relative w-full aspect-[4/3] bg-brand-bg rounded-2xl mb-4 overflow-hidden group border border-line-gray">
                    <img src="https://placehold.co/600x450/FF8FA3/FFF?text=Img+1" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105">
                    <div class="absolute top-3 left-3 w-8 h-8 bg-white/90 backdrop-blur-sm rounded-full flex items-center justify-center font-display text-lg shadow-sm text-text-main">1</div>
                </div>
                <div class="text-center mt-auto pb-1">
                    <h3 class="font-display text-2xl text-text-main mb-1">HAVE A DREAM</h3>
                    <p class="font-kor text-sm text-gray-400 font-bold">꿈을 가지다</p>
                </div>
            </div>

            <div class="bg-white p-4 rounded-[1.5rem] shadow-soft border border-white flex flex-col hover:border-secondary/20 transition-colors">
                <div class="relative w-full aspect-[4/3] bg-brand-bg rounded-2xl mb-4 overflow-hidden group border border-line-gray">
                    <img src="https://placehold.co/600x450/8AC9A6/FFF?text=Img+2" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105">
                    <div class="absolute top-3 left-3 w-8 h-8 bg-white/90 backdrop-blur-sm rounded-full flex items-center justify-center font-display text-lg shadow-sm text-text-main">2</div>
                </div>
                <div class="text-center mt-auto pb-1">
                    <h3 class="font-display text-2xl text-text-main mb-1">HAVE A CHANCE</h3>
                    <p class="font-kor text-sm text-gray-400 font-bold">기회를 가지다</p>
                </div>
            </div>

            <div class="bg-white p-4 rounded-[1.5rem] shadow-soft border border-white flex flex-col hover:border-accent/20 transition-colors">
                <div class="relative w-full aspect-[4/3] bg-brand-bg rounded-2xl mb-4 overflow-hidden group border border-line-gray">
                    <img src="https://placehold.co/600x450/FFD166/FFF?text=Img+3" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105">
                    <div class="absolute top-3 left-3 w-8 h-8 bg-white/90 backdrop-blur-sm rounded-full flex items-center justify-center font-display text-lg shadow-sm text-text-main">3</div>
                </div>
                <div class="text-center mt-auto pb-1">
                    <h3 class="font-display text-2xl text-text-main mb-1">HAVE A GOAL</h3>
                    <p class="font-kor text-sm text-gray-400 font-bold">목표를 가지다</p>
                </div>
            </div>

            <div class="bg-white p-4 rounded-[1.5rem] shadow-soft border border-white flex flex-col hover:border-text-sub/20 transition-colors">
                <div class="relative w-full aspect-[4/3] bg-brand-bg rounded-2xl mb-4 overflow-hidden group border border-line-gray">
                    <img src="https://placehold.co/600x450/A3D8F4/FFF?text=Img+4" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105">
                    <div class="absolute top-3 left-3 w-8 h-8 bg-white/90 backdrop-blur-sm rounded-full flex items-center justify-center font-display text-lg shadow-sm text-text-main">4</div>
                </div>
                <div class="text-center mt-auto pb-1">
                    <h3 class="font-display text-2xl text-text-main mb-1">HAVE A PLAN</h3>
                    <p class="font-kor text-sm text-gray-400 font-bold">계획을 가지다</p>
                </div>
            </div>
        </section>

        <footer class="mt-6 flex justify-between text-[10px] text-gray-400 font-body uppercase tracking-widest border-t border-line-gray pt-4">
            <span>© Wizard Chunking</span>
            <span>Page 01</span>
        </footer>
    </div>
</div>

<div class="sheet page-break">
    <div class="bg-deco"></div>
    <div class="z-content">

        <header class="flex items-center gap-3 mb-6">
            <div class="h-8 w-1 bg-accent rounded-full"></div>
            <h2 class="font-display text-2xl text-text-main">More Chunks & Practice</h2>
        </header>

        <section class="grid grid-cols-2 gap-5 mb-8">
            <div class="bg-white p-3 rounded-[1.25rem] shadow-soft border border-white flex items-center gap-4">
                <div class="w-20 h-20 bg-brand-bg rounded-xl shrink-0 overflow-hidden relative border border-line-gray">
                    <img src="https://placehold.co/200x200/FF8FA3/FFF?text=5" class="w-full h-full object-cover">
                </div>
                <div>
                    <h3 class="font-display text-lg text-text-main leading-tight mb-0.5">HAVE AN IDEA</h3>
                    <p class="font-kor text-xs text-gray-400 font-bold">아이디어를 가지다</p>
                </div>
            </div>

            <div class="bg-white p-3 rounded-[1.25rem] shadow-soft border border-white flex items-center gap-4">
                <div class="w-20 h-20 bg-brand-bg rounded-xl shrink-0 overflow-hidden relative border border-line-gray">
                    <img src="https://placehold.co/200x200/8AC9A6/FFF?text=6" class="w-full h-full object-cover">
                </div>
                <div>
                    <h3 class="font-display text-lg text-text-main leading-tight mb-0.5">HAVE A WISH</h3>
                    <p class="font-kor text-xs text-gray-400 font-bold">소망을 가지다</p>
                </div>
            </div>

            <div class="bg-white p-3 rounded-[1.25rem] shadow-soft border border-white flex items-center gap-4">
                <div class="w-20 h-20 bg-brand-bg rounded-xl shrink-0 overflow-hidden relative border border-line-gray">
                    <img src="https://placehold.co/200x200/FFD166/FFF?text=7" class="w-full h-full object-cover">
                </div>
                <div>
                    <h3 class="font-display text-lg text-text-main leading-tight mb-0.5">HAVE HOPE</h3>
                    <p class="font-kor text-xs text-gray-400 font-bold">희망을 가지다</p>
                </div>
            </div>

            <div class="bg-gradient-to-br from-[#FFF0F3] to-white p-4 rounded-[1.25rem] border border-primary/10 flex flex-col justify-center relative overflow-hidden shadow-soft">
                <div class="absolute -right-2 -top-2 text-6xl text-primary/5"><i class="fa-solid fa-hat-wizard"></i></div>
                <div class="flex items-center gap-2 mb-2 relative z-10">
                    <span class="bg-primary text-white text-[9px] font-bold px-2 py-0.5 rounded-full tracking-wide">TIP</span>
                    <span class="font-display text-primary text-base">Wizard Says</span>
                </div>
                <p class="font-kor text-xs text-text-sub leading-relaxed relative z-10">
                    "Have"는 마법 같은 단어예요.<br> '가지다' 말고도 '먹다'로도 쓸 수 있어요!
                </p>
            </div>
        </section>

        <section class="flex-grow bg-white rounded-[2rem] border border-line-gray shadow-sm relative overflow-hidden flex flex-col">
            <div class="h-12 bg-gray-50/50 border-b border-gray-100 flex items-center px-6 justify-between">
                <span class="font-display text-gray-400 text-sm tracking-wide"><i class="fa-solid fa-pencil mr-2"></i>My English Note</span>
                <div class="flex gap-1.5">
                    <div class="w-2.5 h-2.5 rounded-full bg-primary/40"></div>
                    <div class="w-2.5 h-2.5 rounded-full bg-secondary/40"></div>
                    <div class="w-2.5 h-2.5 rounded-full bg-accent/40"></div>
                </div>
            </div>

            <div class="flex-grow p-8 writing-lines relative">
                <div class="absolute left-5 top-0 bottom-0 flex flex-col justify-evenly py-6 opacity-10 pointer-events-none">
                    <div class="w-3 h-3 rounded-full bg-black mb-auto"></div>
                    <div class="w-3 h-3 rounded-full bg-black mb-auto"></div>
                    <div class="w-3 h-3 rounded-full bg-black mb-auto"></div>
                    <div class="w-3 h-3 rounded-full bg-black mb-auto"></div>
                    <div class="w-3 h-3 rounded-full bg-black mb-auto"></div>
                </div>

                <div class="ml-10 font-display text-4xl text-gray-200 tracking-wide select-none pointer-events-none mb-10">
                    I have a dream.
                </div>
                <div class="ml-10 font-display text-4xl text-gray-200 tracking-wide select-none pointer-events-none mb-10">
                    I have a plan.
                </div>
            </div>
        </section>

        <footer class="mt-6 flex justify-between text-[10px] text-gray-400 font-body uppercase tracking-widest border-t border-line-gray pt-4">
            <span>© Wizard Chunking</span>
            <span>Page 02</span>
        </footer>
    </div>
</div>

<div class="sheet page-break">
    <div class="absolute inset-0 z-0 opacity-20"
         style="background-image: linear-gradient(#BCCCDC 1px, transparent 1px), linear-gradient(90deg, #BCCCDC 1px, transparent 1px); background-size: 40px 40px;">
    </div>

    <div class="z-content flex flex-col h-full">

        <header class="w-full mb-8 pt-4 flex flex-col items-center justify-center text-center">
            <div class="inline-flex items-center gap-3 bg-white px-5 py-2 rounded-full shadow-sm border border-line-gray mb-3">
                <span class="w-1.5 h-1.5 rounded-full bg-accent animate-pulse"></span>
                <span class="font-display text-text-sub text-[10px] tracking-[0.2em] uppercase">Review Session</span>
                <span class="w-1.5 h-1.5 rounded-full bg-accent animate-pulse"></span>
            </div>
            <h1 class="font-display text-5xl text-text-main mb-2 tracking-tight drop-shadow-sm">Chunking Together</h1>
            <div class="w-12 h-1 bg-primary rounded-full opacity-50"></div>
        </header>

        <section class="flex-grow flex flex-col gap-5 px-4">

            <div class="bg-white rounded-[1.5rem] p-5 shadow-soft border border-white flex items-center gap-5 relative overflow-hidden group hover:border-primary/30 transition-all">
                <div class="absolute left-0 top-0 bottom-0 w-1.5 bg-primary"></div>

                <div class="w-12 h-12 rounded-2xl bg-primary/5 text-primary font-display text-2xl flex items-center justify-center shrink-0">
                    01
                </div>

                <div class="flex-grow">
                    <p class="font-display text-2xl text-text-main mb-1 group-hover:text-primary transition-colors">
                        I have a dream to change my life.
                    </p>
                    <p class="font-kor text-sm text-gray-400 font-medium tracking-wide">
                        나는 내 삶을 바꿀 꿈을 가지고 있어요.
                    </p>
                </div>

                <div class="w-6 h-6 rounded-full border-2 border-gray-200 group-hover:border-primary transition-colors bg-white"></div>
            </div>

            <div class="bg-white rounded-[1.5rem] p-5 shadow-soft border border-white flex items-center gap-5 relative overflow-hidden group hover:border-secondary/30 transition-all">
                <div class="absolute left-0 top-0 bottom-0 w-1.5 bg-secondary"></div>
                <div class="w-12 h-12 rounded-2xl bg-secondary/5 text-secondary font-display text-2xl flex items-center justify-center shrink-0">
                    02
                </div>
                <div class="flex-grow">
                    <p class="font-display text-2xl text-text-main mb-1 group-hover:text-secondary transition-colors">
                        Having a dream changes my future.
                    </p>
                    <p class="font-kor text-sm text-gray-400 font-medium tracking-wide">
                        꿈을 갖는 것은 나의 미래를 바꿔요.
                    </p>
                </div>
                <div class="w-6 h-6 rounded-full border-2 border-gray-200 group-hover:border-secondary transition-colors bg-white"></div>
            </div>

            <div class="bg-white rounded-[1.5rem] p-5 shadow-soft border border-white flex items-center gap-5 relative overflow-hidden group hover:border-accent/30 transition-all">
                <div class="absolute left-0 top-0 bottom-0 w-1.5 bg-accent"></div>
                <div class="w-12 h-12 rounded-2xl bg-accent/5 text-accent font-display text-2xl flex items-center justify-center shrink-0">
                    03
                </div>
                <div class="flex-grow">
                    <p class="font-display text-2xl text-text-main mb-1 group-hover:text-accent transition-colors">
                        I start my trip with a big dream.
                    </p>
                    <p class="font-kor text-sm text-gray-400 font-medium tracking-wide">
                        나는 큰 꿈을 가지고 여행을 시작해요.
                    </p>
                </div>
                <div class="w-6 h-6 rounded-full border-2 border-gray-200 group-hover:border-accent transition-colors bg-white"></div>
            </div>

        </section>

        <section class="mt-6 bg-white rounded-[2rem] border border-gray-200 p-8 shadow-sm flex justify-between items-stretch">

            <div class="flex flex-col justify-center space-y-4 w-3/5 border-r border-gray-100 pr-8">
                <div class="flex items-center justify-between group cursor-pointer">
                    <span class="font-kor text-sm text-text-sub font-bold group-hover:text-text-main transition-colors">큰 소리로 3번 읽기</span>
                    <div class="w-10 h-5 bg-gray-100 rounded-full relative transition-colors group-hover:bg-gray-200">
                        <div class="absolute left-1 top-1 w-3 h-3 bg-white shadow-sm rounded-full"></div>
                    </div>
                </div>
                <div class="flex items-center justify-between group cursor-pointer">
                    <span class="font-kor text-sm text-text-sub font-bold group-hover:text-text-main transition-colors">모르는 단어 체크하기</span>
                    <div class="w-10 h-5 bg-gray-100 rounded-full relative transition-colors group-hover:bg-gray-200">
                        <div class="absolute left-1 top-1 w-3 h-3 bg-white shadow-sm rounded-full"></div>
                    </div>
                </div>
                <div class="flex items-center justify-between group cursor-pointer">
                    <span class="font-kor text-sm text-text-sub font-bold group-hover:text-text-main transition-colors">부모님 확인 받기</span>
                    <div class="w-10 h-5 bg-gray-100 rounded-full relative transition-colors group-hover:bg-gray-200">
                        <div class="absolute left-1 top-1 w-3 h-3 bg-white shadow-sm rounded-full"></div>
                    </div>
                </div>
            </div>

            <div class="w-2/5 flex flex-col items-center justify-center relative pl-4">
                <div class="absolute top-0 right-0 bg-secondary/10 text-secondary text-[9px] font-bold px-2 py-0.5 rounded-md uppercase tracking-wide">
                    Mission Clear
                </div>

                <div class="w-24 h-24 rounded-full border-[1.5px] border-dashed border-gray-300 flex items-center justify-center bg-gray-50/50">
                    <div class="text-center opacity-40">
                        <i class="fa-solid fa-stamp text-2xl mb-1 text-gray-400"></i>
                        <p class="font-display text-[9px] text-gray-400 uppercase tracking-widest">Stamp<br>Here</p>
                    </div>
                </div>
            </div>

        </section>

        <footer class="mt-6 flex justify-between text-[10px] text-gray-400 font-body uppercase tracking-widest border-t border-line-gray pt-4">
            <span>© Wizard Chunking English</span>
            <span>Day 01 - Page 03</span>
        </footer>

    </div>
</div>

</body>
</html>