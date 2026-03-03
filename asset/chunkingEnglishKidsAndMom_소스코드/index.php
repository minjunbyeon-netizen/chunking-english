<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chunking English Kids & Mom</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Chewy&family=Quicksand:wght@400;600;700&family=Jua&family=Gowun+Dodum&family=Press+Start+2P&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="css/style.css">

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/webfont/1.6.26/webfont.js"></script>

    <script src="js/script.js" defer></script>
</head>
<body class="font-body text-brand-text antialiased bg-brand-cream">

<div id="main-menu-container" class="fixed top-4 left-4 md:top-6 md:left-6 z-[200]">
    <div class="relative">

        <button onclick="toggleMainMenu(event)"
                class="group flex items-center justify-center w-12 h-12
                       bg-white/80 backdrop-blur-md
                       border border-white/60
                       rounded-full
                       shadow-[0_4px_10px_rgba(255,143,163,0.2)]
                       hover:shadow-[0_10px_25px_rgba(255,143,163,0.4)] hover:-translate-y-1
                       text-brand-text hover:text-brand-pink-dark
                       transition-all duration-300 ease-out cursor-pointer active:scale-95 active:translate-y-0">

            <i class="fa-solid fa-bars-staggered text-xl transition-colors duration-300"></i>
        </button>

        <div id="main-menu-dropdown"
             class="hidden absolute top-16 left-0 w-48
                    bg-white/90 backdrop-blur-xl
                    rounded-[1.5rem]
                    shadow-[0_15px_35px_rgba(0,0,0,0.1)]
                    border border-white/40 ring-1 ring-gray-50
                    overflow-hidden origin-top-left
                    transition-all duration-300 z-50">

            <div class="py-2">
                <a href="./login.php" class="group/item flex items-center gap-3 px-5 py-3.5 hover:bg-white/60 transition-colors">
                    <div class="w-8 h-8 rounded-full bg-brand-pink/10 text-brand-pink-dark flex items-center justify-center group-hover/item:bg-brand-pink group-hover/item:text-white transition-all duration-300">
                        <i class="fa-solid fa-arrow-right-to-bracket text-xs"></i>
                    </div>
                    <span class="font-display text-sm text-gray-500 font-bold group-hover/item:text-brand-pink-dark transition-colors">로그인</span>
                </a>

                <div class="h-[1px] bg-gradient-to-r from-transparent via-gray-200 to-transparent mx-2"></div>

                <a href="./notice.php" class="group/item flex items-center gap-3 px-5 py-3.5 hover:bg-white/60 transition-colors">
                    <div class="w-8 h-8 rounded-full bg-retro-yellow/10 text-retro-yellow flex items-center justify-center group-hover/item:bg-retro-yellow group-hover/item:text-white transition-all duration-300">
                        <i class="fa-solid fa-table-list text-xs"></i>
                    </div>
                    <span class="font-display text-sm text-gray-500 font-bold group-hover/item:text-retro-yellow transition-colors">게시판</span>
                </a>
            </div>
        </div>
    </div>
</div>

<div class="fixed top-4 right-4 md:top-6 md:right-6 z-[50] animate-float">
    <button onclick="openTogetherModal()"
            class="group flex items-center gap-1.5 md:gap-2 bg-white border-[3px] border-brand-pink-dark rounded-full p-1 pr-3 md:p-1.5 md:pr-5 shadow-[3px_3px_0px_rgba(45,45,45,0.1)] hover:shadow-[1px_1px_0px_rgba(45,45,45,0.1)] hover:translate-y-[2px] hover:translate-x-[2px] transition-all duration-200 cursor-pointer">

        <div class="w-8 h-8 md:w-10 md:h-10 bg-brand-pink-dark text-white rounded-full flex items-center justify-center text-sm md:text-lg shadow-inner group-hover:rotate-12 transition-transform">
            <i class="fa-solid fa-wand-magic-sparkles"></i>
        </div>

        <div class="flex flex-col items-start">
            <span class="font-display text-sm md:text-lg text-brand-text leading-none mt-0.5 group-hover:text-map-pink-dark transition-colors">Together</span>
            <span class="text-[9px] md:text-[11px] font-bold text-gray-400">마법사 청킹</span>
        </div>
    </button>
</div>

<div class="w-full relative z-10 flex-grow flex flex-col">

    <div id="logo-container" class="fixed top-0 left-0 w-full h-100vh pointer-events-none z-50 flex flex-col items-center pt-24 md:pt-48 origin-top will-change-transform">

        <h1 id="main-logo" class="select-none flex flex-col items-center justify-center text-center transition-transform duration-100 ease-linear pointer-events-auto">

            <div class="font-display text-[8vw] md:text-[6vw] flex flex-wrap items-center justify-center gap-2 md:gap-3 leading-tight">
                <span class="text-brand-text relative">
                    Chunking English
                    <span class="absolute top-1 left-1 text-brand-pink -z-10 blur-[1px]">Chunking English</span>
                </span>

                <span class="whitespace-nowrap flex items-center relative">
                    <span id="main-logo-subtitle" class="text-brand-pink-dark">
                        &nbsp;Kids & Mom
                    </span>
                    <span id="main-logo-level" class="absolute left-full whitespace-nowrap font-display text-retro-green ml-2 opacity-0 translate-y-4 transition-all duration-500 ease-out">
                        - 쉽게
                    </span>
                </span>
            </div>

            <div class="mt-4 md:mt-6 flex flex-col items-center gap-4 relative">

                <div id="wizard-badge"
                     onclick="openTogetherModal()"
                     class="animate-button-feedback pointer-events-auto cursor-pointer rounded-full px-6 py-2 inline-flex items-center justify-center transition-all duration-300 hover:bg-brand-pink/20 active:scale-95 group">

                    <span class="font-display text-[4vw] md:text-[1.5vw] text-brand-text tracking-wide group-hover:text-map-pink-dark transition-colors">
                        Wizard Chunking <span class="font-body font-bold text-[3.5vw] md:text-[1.2vw] ml-2 text-brand-text/80">마법사 청킹</span>
                    </span>

                    <div class="absolute z-50 pointer-events-none text-brand-pink-dark drop-shadow-md text-2xl md:text-3xl animate-fake-cursor opacity-0">
                        <i class="fa-solid fa-arrow-pointer -rotate-12"></i>
                    </div>
                </div>

                <div class="relative mt-4 md:mt-0 md:self-center ml-0 flex flex-col items-center justify-center visible z-30">

                    <div id="main-exc-img" class="relative w-44 h-44 md:w-52 md:h-52 animate-float z-20 mb-6">
                        <img src="./img/exc_n1.png" alt="Seed" class="w-full h-full object-contain filter drop-shadow-xl">
                    </div>

                    <div id="guide-bubble" class="relative w-[95vw] md:w-[92vw] max-w-[550px] h-[10.5rem] md:h-auto bg-white rounded-[2rem] border-[3px] border-brand-text shadow-[0_8px_0_rgba(45,45,45,0.15)] p-1 z-30 opacity-0 transition-all duration-500 transform translate-y-4 flex flex-col items-center">

                        <div class="absolute -top-3 left-1/2 -translate-x-1/2 w-6 h-6 bg-white border-t-[3px] border-l-[3px] border-brand-text transform rotate-45 z-40"></div>

                        <div class="w-full h-full bg-brand-cream/30 rounded-[1.8rem] border border-gray-100 px-3.5 py-3 md:px-5 md:py-3 flex items-stretch gap-3 md:gap-4 relative overflow-hidden">

                            <div class="absolute top-0 right-0 w-20 h-20 bg-brand-pink/10 rounded-full blur-xl -z-10"></div>

                            <div class="flex flex-col items-center justify-center shrink-0 w-auto self-center">
                                <div class="relative">
                                    <div id="guide-icon-bg" class="w-12 h-12 md:w-14 md:h-14 rounded-2xl bg-white border-2 border-brand-text shadow-[3px_3px_0_rgba(0,0,0,0.1)] flex items-center justify-center text-xl md:text-2xl transition-colors duration-300">
                                        <i id="guide-icon" class="fa-solid fa-gamepad text-brand-text"></i>
                                    </div>
                                    <div class="absolute -top-2 -left-2 bg-retro-yellow border-2 border-brand-text text-brand-text font-display text-[10px] md:text-xs px-1.5 md:px-2 py-0.5 rounded-full shadow-sm rotate-[-10deg]">
                                        STEP <span id="guide-step-num">1</span>
                                    </div>
                                </div>
                            </div>

                            <div class="flex-1 flex flex-col justify-between min-w-0 py-0.5">

                                <div id="guide-text-area" class="transition-opacity duration-200 flex flex-col justify-center flex-grow">
                                    <h3 id="guide-title" class="font-display text-lg md:text-2xl text-brand-text leading-tight mb-1 break-keep whitespace-normal">
                                        Let's Play!
                                    </h3>
                                    <p id="guide-desc" class="font-body text-xs md:text-base text-gray-500 font-bold leading-relaxed break-keep whitespace-normal">
                                        주사위 게임으로<br>영어 여행을 시작해요.
                                    </p>
                                </div>

                                <div class="flex justify-between items-center w-full pt-2 border-t border-gray-200/60 mt-auto">
                                    <div class="flex gap-1" id="guide-dots">
                                    </div>

                                    <div class="flex gap-2">
                                        <button onclick="prevGuideStep()" class="w-7 h-7 md:w-7 md:h-7 rounded-full bg-white border border-gray-200 text-gray-400 hover:text-brand-text hover:border-brand-text hover:bg-gray-50 flex items-center justify-center transition-all active:scale-90">
                                            <i class="fa-solid fa-chevron-left text-[10px] md:text-xs"></i>
                                        </button>
                                        <button onclick="nextGuideStep()" class="w-7 h-7 md:w-7 md:h-7 rounded-full bg-brand-pink text-white border border-brand-pink-dark shadow-sm hover:bg-brand-pink-dark flex items-center justify-center transition-all active:scale-90">
                                            <i class="fa-solid fa-chevron-right text-[10px] md:text-xs"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </h1>
    </div>

    <div class="fixed inset-0 pointer-events-none -z-20 overflow-hidden hero-bg">
        <div class="blob blob-1 bg-brand-pink/40"></div>
        <div class="blob blob-2"></div>
        <div class="blob blob-3 bg-brand-pink/30"></div>
    </div>

    <main class="flex-grow flex flex-col">
        <section id="hero" class="min-h-[100vh] w-full relative flex flex-col justify-center items-center overflow-hidden flex-shrink-0 pt-20">

            <div id="game-section" class="relative mt-10 md:mt-0 opacity-0 animate-[fadeIn_1s_ease-out_1s_forwards]">
                <div class="game-board w-[320px] h-[280px] md:w-[500px] md:h-[400px]">
                    <div class="tile start" id="tile-0">START<span class="tile-number">1</span></div>
                    <div class="tile" id="tile-1"><i class="fa-solid fa-apple-whole text-brand-pink-dark"></i><span class="tile-number">2</span></div>
                    <div class="tile" id="tile-2">Ride<span class="tile-number">3</span></div>
                    <div class="tile" id="tile-3"><i class="fa-solid fa-cat text-retro-yellow"></i><span class="tile-number">4</span></div>
                    <div class="tile special" id="tile-4">JUMP!<span class="tile-number">5</span></div>
                    <div class="tile" id="tile-9">Sing<span class="tile-number">10</span></div>
                    <div class="tile" id="tile-8"><i class="fa-solid fa-music text-retro-blue"></i><span class="tile-number">9</span></div>
                    <div class="tile special" id="tile-7">WAIT<span class="tile-number">8</span></div>
                    <div class="tile" id="tile-6"><i class="fa-solid fa-book text-retro-green"></i><span class="tile-number">7</span></div>
                    <div class="tile" id="tile-5">Read<span class="tile-number">6</span></div>
                    <div class="tile" id="tile-10">Play<span class="tile-number">11</span></div>
                    <div class="tile" id="tile-11"><i class="fa-solid fa-futbol text-brand-text"></i><span class="tile-number">12</span></div>
                    <div class="tile" id="tile-12">Run<span class="tile-number">13</span></div>
                    <div class="tile" id="tile-13"><i class="fa-solid fa-bicycle text-brand-pink-dark"></i><span class="tile-number">14</span></div>
                    <div class="tile special" id="tile-14">GO!<span class="tile-number">15</span></div>
                    <div class="tile goal" id="tile-19">GOAL<span class="tile-number">20</span></div>
                    <div class="tile" id="tile-18"><i class="fa-solid fa-star text-retro-yellow"></i><span class="tile-number">19</span></div>
                    <div class="tile" id="tile-17">Happy<span class="tile-number">18</span></div>
                    <div class="tile" id="tile-16"><i class="fa-solid fa-heart text-brand-pink"></i><span class="tile-number">17</span></div>
                    <div class="tile" id="tile-15">Dream<span class="tile-number">16</span></div>
                    <div id="player-token"><i class="fa-solid fa-face-smile text-brand-text text-xl"></i></div>
                    <div id="dice-container"><div class="dice bg-white shadow-retro"><span id="dice-value">1</span></div></div>
                </div>
                <div class="absolute -bottom-16 left-0 right-0 text-center font-display text-2xl text-brand-text/50 transform rotate-[-2deg]">
                    Let's Play English!
                </div>
            </div>

            <div class="absolute bottom-10 mb-10 text-center text-brand-text/40 text-xs font-bold scroll-bounce tracking-widest z-10">
                SCROLL TO EXPLORE
                <br>
                <i class="fa-solid fa-chevron-down mt-2"></i>
            </div>
        </section>

        <div id="marquee-bar" class="bg-brand-pink-dark py-4 overflow-hidden z-20 relative text-white border-y-4 border-white/20 shadow-sm flex-shrink-0">
            <div class="marquee-container">
                <div class="marquee-content text-sm font-bold tracking-widest space-x-8 font-body opacity-95">
                    <span>HAVE A DREAM</span> <span class="text-[10px]">✦</span>
                    <span>GO TO SCHOOL</span> <span class="text-[10px]">✦</span>
                    <span>BRUSH MY TEETH</span> <span class="text-[10px]">✦</span>
                    <span>WASH MY FACE</span> <span class="text-[10px]">✦</span>
                    <span>EAT BREAKFAST</span> <span class="text-[10px]">✦</span>
                    <span>READ A BOOK</span> <span class="text-[10px]">✦</span>
                    <span>RIDE A BIKE</span> <span class="text-[10px]">✦</span>
                    <span>PLAY SOCCER</span> <span class="text-[10px]">✦</span>
                    <span>DRAW A PICTURE</span> <span class="text-[10px]">✦</span>
                    <span>SING A SONG</span> <span class="text-[10px]">✦</span>
                    <span>LISTEN TO MUSIC</span> <span class="text-[10px]">✦</span>
                    <span>WRITE A LETTER</span> <span class="text-[10px]">✦</span>
                    <span>MEET MY FRIENDS</span> <span class="text-[10px]">✦</span>
                    <span>TAKE A BUS</span> <span class="text-[10px]">✦</span>
                    <span>CLEAN MY ROOM</span> <span class="text-[10px]">✦</span>
                    <span>HELP MY MOM</span> <span class="text-[10px]">✦</span>
                    <span>WASH THE DISHES</span> <span class="text-[10px]">✦</span>
                    <span>WATCH TV</span> <span class="text-[10px]">✦</span>
                    <span>TAKE A SHOWER</span> <span class="text-[10px]">✦</span>
                    <span>GO TO BED</span> <span class="text-[10px]">✦</span>
                    <span>SAY HELLO</span> <span class="text-[10px]">✦</span>
                    <span>OPEN THE DOOR</span> <span class="text-[10px]">✦</span>
                    <span>CLOSE THE WINDOW</span> <span class="text-[10px]">✦</span>
                    <span>TURN ON THE LIGHT</span> <span class="text-[10px]">✦</span>
                    <span>TURN OFF THE LIGHT</span> <span class="text-[10px]">✦</span>
                    <span>DRINK MILK</span> <span class="text-[10px]">✦</span>
                    <span>EAT AN APPLE</span> <span class="text-[10px]">✦</span>
                    <span>MAKE A ROBOT</span> <span class="text-[10px]">✦</span>
                    <span>FLY A KITE</span> <span class="text-[10px]">✦</span>
                    <span>SWIM IN THE POOL</span> <span class="text-[10px]">✦</span>
                    <span>BUILD A SANDCASTLE</span> <span class="text-[10px]">✦</span>
                    <span>CATCH A BALL</span> <span class="text-[10px]">✦</span>
                    <span>THROW A BALL</span> <span class="text-[10px]">✦</span>
                    <span>KICK A BALL</span> <span class="text-[10px]">✦</span>
                    <span>RUN FAST</span> <span class="text-[10px]">✦</span>
                    <span>WALK SLOWLY</span> <span class="text-[10px]">✦</span>
                    <span>DANCE TOGETHER</span> <span class="text-[10px]">✦</span>
                    <span>CLAP MY HANDS</span> <span class="text-[10px]">✦</span>
                    <span>STAMP MY FEET</span> <span class="text-[10px]">✦</span>
                    <span>LOOK AT THE SKY</span> <span class="text-[10px]">✦</span>
                    <span>SMELL A FLOWER</span> <span class="text-[10px]">✦</span>
                    <span>TOUCH A DOG</span> <span class="text-[10px]">✦</span>
                    <span>HEAR A BIRD</span> <span class="text-[10px]">✦</span>
                    <span>TASTE A CANDY</span> <span class="text-[10px]">✦</span>
                    <span>FEEL HAPPY</span> <span class="text-[10px]">✦</span>
                    <span>FEEL SAD</span> <span class="text-[10px]">✦</span>
                    <span>GET ANGRY</span> <span class="text-[10px]">✦</span>
                    <span>BE CAREFUL</span> <span class="text-[10px]">✦</span>
                    <span>HURRY UP</span> <span class="text-[10px]">✦</span>
                    <span>SLOW DOWN</span> <span class="text-[10px]">✦</span>
                    <span>COME HERE</span> <span class="text-[10px]">✦</span>
                    <span>GO THERE</span> <span class="text-[10px]">✦</span>
                    <span>STAND UP</span> <span class="text-[10px]">✦</span>
                    <span>SIT DOWN</span> <span class="text-[10px]">✦</span>
                    <span>RAISE YOUR HAND</span> <span class="text-[10px]">✦</span>
                    <span>DO HOMEWORK</span> <span class="text-[10px]">✦</span>
                    <span>STUDY ENGLISH</span> <span class="text-[10px]">✦</span>
                    <span>PLAY A GAME</span> <span class="text-[10px]">✦</span>
                    <span>USE A COMPUTER</span> <span class="text-[10px]">✦</span>
                    <span>WEAR A COAT</span>
                </div>
            </div>
        </div>
        <section id="map-section" class="relative w-full h-screen overflow-hidden">

            <div class="absolute top-0 left-0 w-full z-[100] flex justify-center pointer-events-none">

                <div class="flex flex-col items-center animate-sway origin-top absolute top-0 left-0 w-full z-30 pointer-events-none">

                    <div class="w-[60vw] md:w-[40vw] lg:w-[25vw] max-w-[450px] h-[8vh] md:h-[10vh] lg:h-[12vh] flex justify-between px-6 transition-all duration-300">
                        <div class="w-0.5 md:w-1 h-full bg-brand-pink-dark/50 relative"></div>
                        <div class="w-0.5 md:w-1 h-full bg-brand-pink-dark/50 relative"></div>
                    </div>

                    <div class="relative bg-[#fff0f5] border-[3px] md:border-[4px] border-brand-pink-dark rounded-2xl md:rounded-3xl
                        w-[65vw] md:w-[55vw] lg:w-[32vw] max-w-[600px]
                        py-[1.5vh] md:py-[2vh] px-[1.5vw]
                        shadow-[3px_4px_0px_#FF8FA3] md:shadow-[6px_8px_0px_#FF8FA3]
                        -mt-1 transform rotate-1 flex flex-col items-center justify-center gap-1 md:gap-2 transition-all duration-300">

                        <div class="absolute top-2 left-2 w-1.5 h-1.5 md:w-2.5 md:h-2.5 rounded-full bg-brand-pink border border-brand-pink-dark"></div>
                        <div class="absolute top-2 right-2 w-1.5 h-1.5 md:w-2.5 md:h-2.5 rounded-full bg-brand-pink border border-brand-pink-dark"></div>
                        <div class="absolute bottom-2 left-2 w-1.5 h-1.5 md:w-2.5 md:h-2.5 rounded-full bg-brand-pink border border-brand-pink-dark"></div>
                        <div class="absolute bottom-2 right-2 w-1.5 h-1.5 md:w-2.5 md:h-2.5 rounded-full bg-brand-pink border border-brand-pink-dark"></div>

                        <div class="flex items-baseline justify-center gap-[0.8vw] whitespace-nowrap z-10">
                    <span class="font-display text-[3vw] md:text-[2.5vw] lg:text-[1.5vw] text-brand-text text-distinct tracking-tight transition-all duration-300">
                        청킹으로
                    </span>

                            <div class="relative inline-block mx-0.5 transform -rotate-2">
                        <span class="relative z-10 font-display text-[4.5vw] md:text-[4vw] lg:text-[2.4vw] text-brand-text text-distinct px-1 block transition-all duration-300">
                            쉽게
                        </span>
                                <div class="absolute bottom-[10%] left-0 h-[15%] bg-brand-pink-dark/70 -z-0 rounded-sm animate-draw-line mix-blend-multiply w-full"></div>
                                <i class="fa-solid fa-star text-[1.5vw] md:text-[1.5vw] lg:text-[0.8vw] text-yellow-300 absolute -top-[15%] -right-[15%] animate-twinkle z-20 drop-shadow-sm"></i>
                            </div>

                            <span class="font-display text-[3vw] md:text-[2.5vw] lg:text-[1.5vw] text-brand-text text-distinct tracking-tight transition-all duration-300">
                        영어말하기
                    </span>
                        </div>

                        <div class="w-full flex items-center justify-center gap-2 opacity-80 mt-[0.5vh]">
                            <div class="h-[1px] md:h-[1.5px] w-[8%] bg-brand-pink-dark/40 rounded-full"></div>
                            <p class="font-body font-bold text-[2.2vw] md:text-[13px] text-brand-pink-dark tracking-widest uppercase whitespace-nowrap">
                                Chunking-Based Easy Speaking
                            </p>
                            <div class="h-[1px] md:h-[1.5px] w-[8%] bg-brand-pink-dark/40 rounded-full"></div>
                        </div>

                    </div>
                </div>
            </div>









            <div id="bg-layer-day" class="bg-layer"></div>
            <div id="bg-layer-sunset" class="bg-layer"></div>
            <div id="bg-layer-night" class="bg-layer"></div>

            <div class="absolute top-0 right-0 w-full h-full pointer-events-none z-10 overflow-hidden">
                <div id="celestial-body" class="absolute -top-10 -right-10 w-[300px] h-[300px] bg-[radial-gradient(circle,_rgba(255,253,150,0.8)_0%,_rgba(255,230,100,0)_70%)] blur-2xl animate-sun-glow transition-all duration-1000"></div>
                <div class="absolute top-0 right-0 w-[800px] h-[800px] bg-[radial-gradient(circle_at_top_right,_rgba(255,250,220,0.2)_0%,_transparent_60%)]"></div>
                <div id="stars-container" class="absolute inset-0 w-full h-full opacity-0 transition-opacity duration-1000"></div>
            </div>

            <div id="alphabet-container" class="absolute inset-0 w-full h-full pointer-events-none z-10 overflow-hidden"></div>

            <div id="drag-hint" class="absolute inset-0 z-40 pointer-events-none flex items-center justify-center transition-opacity duration-500 opacity-0">
                <div class="bg-black/40 backdrop-blur-sm text-white px-8 py-4 rounded-full flex flex-col items-center gap-2 animate-pulse-slow">
                    <i class="fa-solid fa-hand-pointer text-4xl animate-hand-swipe-vertical md:animate-hand-swipe"></i>
                    <span class="font-bold font-display text-xl tracking-wide">Drag to Explore!</span>
                </div>
            </div>

            <div class="absolute bottom-0 left-0 w-full pointer-events-none z-10 transition-all duration-1000" id="ground-layer">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320" class="w-full h-auto opacity-40">
                    <path fill="#8AC9A6" id="ground-path-1" fill-opacity="1" d="M0,224L48,213.3C96,203,192,181,288,181.3C384,181,480,203,576,224C672,245,768,267,864,250.7C960,235,1056,181,1152,165.3C1248,149,1344,171,1392,181.3L1440,192L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path>
                </svg>
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320" class="w-full h-auto absolute bottom-0 left-0 opacity-60">
                    <path fill="#A5D6A7" id="ground-path-2" fill-opacity="1" d="M0,288L48,272C96,256,192,224,288,213.3C384,203,480,213,576,229.3C672,245,768,267,864,261.3C960,256,1056,224,1152,213.3C1248,203,1344,213,1392,218.7L1440,224L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path>
                </svg>
            </div>

            <div id="game-container" class="h-full w-full overflow-hidden relative z-20">
                <div id="map-view" class="w-full h-full relative cursor-grab active:cursor-grabbing transition-all duration-500">
                    <div id="map-scroll-container" class="w-full h-full overflow-hidden relative hide-scrollbar">
                        <div id="map-nodes-container" class="relative flex items-center">
                            <svg id="track-svg" class="absolute top-0 left-0 w-full h-full pointer-events-none z-0" style="filter: drop-shadow(0px 8px 12 rgba(141, 110, 99, 0.15));">
                                <path id="path-ties-shadow" d="" fill="none" stroke="rgba(0,0,0,0.1)" stroke-width="140" stroke-dasharray="16, 30" stroke-linecap="butt" />
                                <path id="path-ties" d="" fill="none" stroke="#E0C097" stroke-width="130" stroke-dasharray="16, 30" stroke-linecap="butt" />
                                <path id="path-rail-base" d="" fill="none" stroke="#BCAAA4" stroke-width="60" stroke-linecap="round" />
                                <path id="path-rail-inner" d="" fill="none" stroke="#ECEFF1" stroke-width="45" stroke-linecap="round" />
                                <path id="path-rail-center" d="" fill="none" stroke="#B0BEC5" stroke-width="6" stroke-linecap="round" stroke-dasharray="1, 15" />
                            </svg>
                            <div id="park-train" class="hidden items-center justify-center pointer-events-none origin-center" style="top: 0; left: 0;">

                                <div id="train-body" class="relative">

                                    <img src="./img/ck_train.png"
                                         onerror="this.src='https://cdn-icons-png.flaticon.com/512/3063/3063823.png'"
                                         alt="Train"
                                         class="w-24 h-auto"
                                         style="object-fit: contain;">

                                    <div class="steam-puff w-3 h-3 -top-2 right-6" style="animation-delay: 0s;"></div>
                                    <div class="steam-puff w-4 h-4 -top-3 right-5" style="animation-delay: 0.5s;"></div>
                                    <div class="steam-puff w-3 h-3 -top-2 right-7" style="animation-delay: 1.0s;"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div id="day-intro-view" class="hidden absolute inset-0 z-[200] overflow-y-auto bg-white/60 backdrop-blur-xl animate-fade">

                <div class="max-w-5xl mx-auto pt-24 px-4 flex flex-col items-center">

                    <button onclick="backToMap()" class="self-start mt-2 mb-4 bg-white border-2 border-brand-pink-dark px-5 py-2 rounded-full font-bold text-sm text-brand-text hover:bg-brand-pink-dark hover:text-white transition-all shadow-[4px_4px_0px_rgba(45,45,45,0.1)] active:shadow-none active:translate-y-1">
                        <i class="fa-solid fa-arrow-left mr-2"></i> 뒤로가기
                    </button>

                    <div class="relative w-full max-w-4xl mx-auto mb-6 mt-0 px-4 flex flex-col items-center">

                        <div class="relative inline-block mb-3 animate-pop-in">
                            <div id="intro-subtitle" class="relative z-10 font-body font-bold text-gray-500 text-sm md:text-base px-1">
                                음식 & 요리
                            </div>
                            <div class="absolute bottom-0.5 left-0 h-2.5 bg-brand-pink-dark/60 -z-0 rounded-sm animate-draw-line mix-blend-multiply transform -rotate-1 w-full"></div>
                        </div>

                        <div class="relative inline-block text-center z-10 mb-6">
                            <h2 class="relative z-10 font-display text-4xl md:text-6xl text-brand-text text-distinct leading-tight tracking-tight px-4" id="intro-title">
                                Food & Cooking
                            </h2>
                            <div class="absolute bottom-2 left-0 h-3 md:h-5 bg-brand-pink-dark/90 -z-0 rounded-sm animate-draw-line mix-blend-multiply transform -rotate-1 w-full"></div>
                        </div>

                        <div class="relative group mt-2">
                            <div class="font-display text-7xl md:text-9xl text-retro-yellow transform -rotate-3 transition-transform group-hover:rotate-0 flex items-baseline justify-center"
                                 style="text-shadow: 3px 3px 0px #2D2D2D, 6px 6px 0px rgba(0,0,0,0.15); -webkit-text-stroke: 3px #2D2D2D;">
                    <span class="font-display text-3xl md:text-5xl mr-3 text-brand-pink-dark"
                          style="-webkit-text-stroke: 1.5px #2D2D2D; text-shadow: 2px 2px 0px #2D2D2D;">
                        Day
                    </span>
                                <span id="intro-day-number">1</span>
                            </div>
                        </div>

                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 md:gap-6 w-full mt-2 pb-10" id="mission-grid"></div>
                </div>
            </div>

            <div id="summary-view" class="hidden absolute inset-0 z-[200] overflow-y-auto animate-fade bg-gradient-to-br from-indigo-50 to-map-pink/10 backdrop-blur-lg">
                <div class="min-h-full flex flex-col items-center justify-center p-6">
                    <div class="text-center mb-8">
                        <div class="inline-block bg-seed-green text-white px-4 py-1 rounded-full font-bold text-sm mb-2 shadow-sm animate-pulse-slow">Mission Complete!</div>
                        <h2 id="summary-title" class="font-display text-5xl text-brand-text text-distinct mb-2 animate-soft-bounce">Day 1 Clear!</h2>
                        <p class="text-gray-500 font-bold text-lg">오늘 배운 핵심 표현 3가지</p>
                    </div>
                    <div id="summary-grid" class="grid grid-cols-1 md:grid-cols-3 gap-6 w-full max-w-5xl mb-12"></div>
                    <button onclick="completeDayAndReturnToMap()" class="group relative bg-map-pink-dark text-white font-display text-2xl px-10 py-4 rounded-full shadow-xl hover:bg-map-pink hover:scale-105 transition-all duration-300">
                        <span class="relative z-10 flex items-center gap-3">Grow My Tree! <i class="fa-solid fa-tree animate-wiggle"></i></span>
                        <div class="absolute inset-0 bg-white/20 rounded-full scale-0 group-hover:scale-110 transition-transform duration-500 opacity-0 group-hover:opacity-100"></div>
                    </button>
                </div>
            </div>

            <div id="drill-view" class="hidden absolute inset-0 z-[200] overflow-y-auto pb-20 animate-fade bg-gradient-to-b from-brand-cream/95 to-map-pink/20 backdrop-blur-md">
                <div class="max-w-5xl mx-auto pt-4 px-4 h-full flex flex-col">
                    <div class="flex justify-between items-center mb-6 pt-20">
                        <button onclick="exitDrillConfirmation()" class="bg-white border border-gray-200 w-10 h-10 rounded-full flex items-center justify-center hover:bg-red-50 hover:text-red-500 text-gray-400 transition-colors shadow-sm"><i class="fa-solid fa-xmark"></i></button>
                        <div class="bg-white px-6 py-2 rounded-full shadow-sm flex items-center">
                            <span id="current-verb-title" class="font-display text-xl mr-2 text-brand-text">have</span>
                            <span class="text-xs font-bold bg-seed-green text-white px-2 py-1 rounded-full">Found: <span id="found-count">0</span>/7</span>
                        </div>
                        <div class="w-10"></div>
                    </div>
                    <div class="p-4 md:p-8 flex-1 relative flex flex-col items-center justify-start min-h-[60vh]">
                        <h3 class="font-display text-2xl mb-8 text-center text-brand-text bg-white/60 px-6 py-2 rounded-full inline-block shadow-sm">Pick a card! <i class="fa-solid fa-wand-magic-sparkles ml-2 text-map-pink-dark"></i></h3>
                        <div id="card-grid" class="flex flex-col items-center gap-6 w-full max-w-5xl mx-auto mb-auto"></div>
                        <div class="w-full max-w-3xl mt-12 p-6 z-20">
                            <div class="flex items-center justify-between mb-4">
                                <span class="font-display text-xl text-map-pink-dark drop-shadow-sm">My Seed Pocket <i class="fa-solid fa-seedling"></i></span>
                                <span class="text-xs text-brand-text font-bold opacity-70">Collect 7 seeds!</span>
                            </div>
                            <div id="seed-pocket" class="flex justify-between items-center gap-2 bg-white/80 p-4 rounded-xl border border-white/50 shadow-inner backdrop-blur-sm"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div id="focus-overlay" class="absolute inset-0 bg-white/60 backdrop-blur-md z-[210] hidden flex items-center justify-center p-3 md:p-4">
                <div id="focus-card" class="relative bg-white rounded-2xl md:rounded-3xl border border-map-pink p-3 md:p-8 shadow-2xl flex flex-col md:grid md:grid-cols-2 gap-3 md:gap-10 w-full max-w-4xl h-[85vh] md:h-auto md:max-h-[90vh] overflow-hidden">

                    <button onclick="closeFocusOverlay()"
                            class="absolute top-3 right-3 z-50
                   w-8 h-8 md:w-12 md:h-12 rounded-full
                   bg-white text-brand-pink-dark
                   border-[2px] md:border-[3px] border-brand-pink-dark
                   shadow-[2px_2px_0px_rgba(0,0,0,0.1)]
                   hover:bg-brand-pink-dark hover:text-white hover:border-white hover:shadow-md
                   flex items-center justify-center
                   transition-all duration-200 active:scale-90">
                        <i class="fa-solid fa-xmark text-lg md:text-2xl font-black"></i>
                    </button>

                    <div class="flex flex-col items-center pt-6 md:pt-0 shrink-0">

                        <div id="main-focus-display" class="w-full rounded-xl border border-map-pink/30 p-2 md:p-6 flex flex-col items-center justify-center shadow-sm bg-gradient-to-br from-white to-brand-cream">

                            <div id="focus-img-container" class="cloud-blob-container w-[50%] md:w-full h-28 md:h-64 md:flex-1 mb-2 md:mb-6 flex items-center justify-center relative shrink-0 mx-auto">
                                <img id="focus-img" src="" alt="Word" class="cloud-img-effect drop-shadow-sm w-full h-full object-contain p-1 md:p-2">
                            </div>

                            <div class="text-center">
                                <h2 id="focus-eng" class="font-display text-2xl md:text-4xl text-brand-text leading-none mb-0.5 md:mb-3"></h2>
                                <p id="focus-kor" class="font-bold text-xs md:text-xl text-gray-400 font-body"></p>
                            </div>
                        </div>

                        <div id="action-buttons-row" class="w-full flex gap-2 mt-2 md:mt-6 h-10 md:h-12 shrink-0">
                        </div>
                    </div>

                    <div class="flex flex-col border border-map-pink/30 rounded-xl shadow-sm overflow-hidden bg-brand-cream/30 flex-1 min-h-0">

                        <div class="flex border-b border-map-pink/20 bg-white shrink-0 z-10">
                            <button id="tab-basic" onclick="showUsageTab('basic')" class="tab-button w-1/2 p-2 md:p-4 font-body font-bold text-sm md:text-lg transition-all">Basic</button>
                            <button id="tab-applied" onclick="showUsageTab('applied')" class="tab-button w-1/2 p-2 md:p-4 font-body font-bold text-sm md:text-lg transition-all">Applied</button>
                        </div>

                        <div class="flex-1 relative overflow-hidden bg-white/50">

                            <div id="tab-content-basic" class="tab-content h-full flex flex-col absolute inset-0 w-full">
                                <div class="flex-1 overflow-y-auto p-2 md:p-4 scroll-smooth">
                                    <div id="basic-usage-table" class="pb-2"></div>
                                </div>
                                <div id="btn-wrapper-basic" class="shrink-0 p-2 md:p-4 pt-0 mt-auto bg-gradient-to-t from-white via-white to-transparent"></div>
                            </div>

                            <div id="tab-content-applied" class="tab-content hidden h-full flex flex-col absolute inset-0 w-full">
                                <div class="flex-1 overflow-y-auto p-2 md:p-4 scroll-smooth">
                                    <div id="applied-usage-table" class="pb-2"></div>
                                </div>
                                <div id="btn-wrapper-applied" class="shrink-0 p-2 md:p-4 pt-0 mt-auto bg-gradient-to-t from-white via-white to-transparent"></div>
                            </div>

                        </div>
                    </div>

                </div>
            </div>
    </main>
</div>

<button id="scroll-to-top" title="Go to top">
    <i class="fa-solid fa-chevron-up"></i>
</button>

<div id="together-modal" class="fixed inset-0 z-[90] hidden pointer-events-none transition-opacity duration-500 ease-out opacity-0">
    <div class="absolute inset-0 bg-black/80 backdrop-blur-sm" onclick="closeTogetherModal()"></div>

    <div class="relative w-full h-full flex items-center justify-center p-4 md:p-6">

        <button onclick="closeTogetherModal()"
                class="fixed top-4 right-4 z-[100] w-10 h-10 rounded-full bg-white text-brand-text border border-gray-200 shadow-xl flex md:hidden items-center justify-center active:scale-90 transition-transform">
            <i class="fa-solid fa-xmark text-xl"></i>
        </button>

        <div id="together-wrapper" class="relative w-full h-full md:w-[88vw] md:h-[92vh] bg-white rounded-[1.5rem] md:rounded-[2.5rem] overflow-hidden shadow-2xl border-4 border-white/50 transform scale-90 transition-all duration-500 ease-out flex flex-col">

            <button onclick="closeTogetherModal()"
                    class="absolute top-6 right-6 z-50 w-12 h-12 rounded-full bg-white/80 hover:bg-white text-brand-text hover:text-red-500 border border-gray-200 shadow-md backdrop-blur-md hidden md:flex items-center justify-center transition-all cursor-pointer hover:scale-110 active:scale-95">
                <i class="fa-solid fa-xmark text-2xl"></i>
            </button>

            <iframe id="together-frame" src="" class="w-full h-full border-0 bg-brand-cream" allowtransparency="true"></iframe>
        </div>
    </div>
</div>

<div id="drill-guide-modal" class="hidden fixed inset-0 z-[100] flex items-center justify-center bg-black/60 backdrop-blur-sm p-4 animate-fade">

    <div class="bg-white rounded-[2.5rem] p-6 md:p-8 max-w-md w-full shadow-2xl border-4 border-seed-green relative text-center overflow-hidden">

        <div class="absolute -top-12 -left-12 w-40 h-40 bg-seed-green/10 rounded-full blur-2xl"></div>
        <div class="absolute bottom-0 right-0 w-32 h-32 bg-brand-pink/10 rounded-full blur-xl"></div>

        <button onclick="closeDrillGuide()" class="absolute top-5 right-5 z-50 text-gray-400 hover:text-red-500 transition-colors w-10 h-10 flex items-center justify-center rounded-full bg-gray-50 hover:bg-red-50 shadow-sm cursor-pointer">
            <i class="fa-solid fa-xmark text-xl"></i>
        </button>

        <div class="relative z-10 mb-6">
            <h3 class="font-display text-3xl md:text-4xl text-brand-text mb-2">
                <span class="text-seed-green">How to</span> Play?
            </h3>
            <p class="font-body text-gray-400 text-sm font-bold bg-gray-50 inline-block px-4 py-1 rounded-full border border-gray-100">
                마법사가 되는 3가지 단계! 🧙‍♂️
            </p>
        </div>

        <div class="space-y-4 mb-8 relative z-10 text-left">

            <div class="group flex items-start gap-4 bg-white border-2 border-gray-100 hover:border-brand-pink/50 p-4 rounded-2xl shadow-sm transition-all duration-300 hover:-translate-y-1">
                <div class="w-12 h-12 bg-brand-pink text-white rounded-2xl flex items-center justify-center text-xl shadow-md shrink-0 rotate-3 group-hover:rotate-12 transition-transform">
                    <i class="fa-solid fa-hand-pointer"></i>
                </div>
                <div>
                    <h4 class="font-display text-lg text-brand-text mb-0.5 group-hover:text-brand-pink-dark transition-colors">1. Pick a Card</h4>
                    <p class="text-xs text-gray-500 font-body leading-relaxed">
                        화면의 카드를 <span class="font-bold text-brand-text">클릭</span>하세요.<br>
                        숨겨진 영어 문장이 나타납니다!
                    </p>
                </div>
            </div>

            <div class="group flex items-start gap-4 bg-white border-2 border-gray-100 hover:border-retro-blue/50 p-4 rounded-2xl shadow-sm transition-all duration-300 hover:-translate-y-1">
                <div class="w-12 h-12 bg-retro-blue text-white rounded-2xl flex items-center justify-center text-xl shadow-md shrink-0 -rotate-2 group-hover:-rotate-12 transition-transform">
                    <i class="fa-solid fa-volume-high"></i>
                </div>
                <div>
                    <h4 class="font-display text-lg text-brand-text mb-0.5 group-hover:text-retro-blue transition-colors">2. Listen & Speak</h4>
                    <p class="text-xs text-gray-500 font-body leading-relaxed">
                        원어민 소리를 듣고 <span class="font-bold text-brand-text">큰 소리로</span> 따라하세요.<br>
                        문장을 외우면 버튼이 열립니다.
                    </p>
                </div>
            </div>

            <div class="group flex items-start gap-4 bg-white border-2 border-gray-100 hover:border-seed-green/50 p-4 rounded-2xl shadow-sm transition-all duration-300 hover:-translate-y-1">
                <div class="w-12 h-12 bg-seed-green text-white rounded-2xl flex items-center justify-center text-xl shadow-md shrink-0 rotate-3 group-hover:rotate-12 transition-transform">
                    <i class="fa-solid fa-seedling animate-wiggle"></i>
                </div>
                <div>
                    <h4 class="font-display text-lg text-brand-text mb-0.5 group-hover:text-seed-green transition-colors">3. Collect 7 Seeds</h4>
                    <p class="text-xs text-gray-500 font-body leading-relaxed">
                        씨앗 <span class="font-bold text-brand-text">7개</span>를 모두 모으면 성공!<br>
                        나만의 마법 나무를 키워보세요. 🌳
                    </p>
                </div>
            </div>

        </div>

        <button onclick="closeDrillGuide()" class="btn-glow-border relative z-10 w-full bg-seed-green text-white font-display text-xl py-4 rounded-2xl shadow-lg active:shadow-none active:translate-y-1 transition-all hover:brightness-105 group overflow-hidden">
            <span class="relative z-10 flex items-center justify-center gap-2">
                Let's Start! <span class="text-sm font-body font-normal opacity-90">(시작하기)</span>
                <i class="fa-solid fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
            </span>
            <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent -translate-x-full group-hover:animate-[shimmer_1.5s_infinite]"></div>
        </button>

    </div>
</div>

<div id="clear-modal" class="fixed inset-0 z-[100] hidden flex items-center justify-center">
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm animate-fade"></div>

    <div class="relative bg-white rounded-[3rem] p-8 md:p-12 text-center max-w-lg w-[90%] mx-auto shadow-2xl border-8 border-seed-green transform scale-100 animate-pop-in">

        <div class="absolute -top-16 -right-16 w-32 h-32 md:w-40 md:h-40 z-20">
            <i id="success-bird" class="fa-solid fa-dove text-white text-6xl md:text-7xl drop-shadow-md"></i>
        </div>

        <div class="mb-6 relative">
            <div id="success-tree" class="text-9xl text-seed-green drop-shadow-md transform origin-bottom">
                <i class="fa-solid fa-tree"></i>
            </div>
            <i class="fa-solid fa-star text-yellow-300 text-4xl absolute top-0 left-10 animate-twinkle"></i>
            <i class="fa-solid fa-star text-yellow-300 text-2xl absolute top-10 right-10 animate-twinkle" style="animation-delay: 0.5s"></i>
        </div>

        <h2 class="font-display text-4xl md:text-5xl text-brand-text mb-2">Mission Complete!</h2>
        <p class="font-body text-gray-500 font-bold text-lg mb-8">씨앗을 모두 모았어요!</p>

        <button onclick="finishVerb()" class="w-full bg-brand-text text-white font-display text-2xl py-4 rounded-full shadow-lg hover:bg-gray-800 hover:scale-105 transition-all active:scale-95 flex items-center justify-center gap-3">
            <span>Get a Stamp!</span>
            <i class="fa-solid fa-stamp text-yellow-300"></i>
        </button>
    </div>
</div>

<div id="intro-reading-modal" class="hidden fixed inset-0 z-[120] flex items-center justify-center overflow-hidden">

    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm animate-fade"></div>

    <div id="modal-rain-container" class="absolute inset-0 pointer-events-none z-0"></div>

    <button onclick="skipIntroReading()" class="absolute top-6 right-6 group bg-white hover:bg-red-50 text-gray-400 hover:text-red-400 border-2 border-transparent hover:border-red-200 rounded-full px-5 py-2 font-display text-sm shadow-sm transition-all z-50 flex items-center gap-2">
        <span>SKIP</span> <i class="fa-solid fa-forward-step"></i>
    </button>

    <div class="relative w-[90vw] max-w-md bg-white rounded-[2.5rem] shadow-[0_25px_50px_rgba(0,0,0,0.25)] transform scale-100 animate-pop-in flex flex-col items-center overflow-hidden ring-4 ring-white z-10">

        <div class="absolute top-0 left-0 w-full h-[45%] bg-gradient-to-b from-[#FFF0F5] to-white z-0"></div>

        <div class="w-full flex flex-col items-center p-6 pb-24 gap-4 relative z-10">

            <div class="relative w-full flex justify-center mt-4">
                <div class="intro-shape-frame w-56 h-56 md:w-64 md:h-64 flex items-center justify-center p-2 bg-white relative z-10 shadow-xl ring-4 ring-white/50">
                    <img id="intro-big-img" src="" alt="Intro Image" class="w-full h-full object-cover rounded-full">
                </div>
            </div>

            <div class="w-full relative group mt-4">
                <div class="notebook-paper w-full rounded-2xl shadow-sm border border-gray-200 p-0 overflow-hidden relative min-h-[180px] flex flex-col justify-center bg-white">

                    <div class="absolute top-0 left-3 w-full h-4 flex gap-3 opacity-30">
                    </div>

                    <div class="px-6 py-4 text-center z-10 flex flex-col justify-center h-full">
                        <h2 id="intro-big-eng" class="font-display text-3xl md:text-4xl text-brand-text mb-3 break-keep leading-tight drop-shadow-sm">
                        </h2>

                        <p id="intro-big-kor" class="font-body text-base md:text-lg text-gray-400 font-bold break-keep leading-snug">
                        </p>
                    </div>
                </div>

                <div class="absolute -top-3 left-1/2 -translate-x-1/2 w-16 h-6 bg-yellow-200/60 rotate-1 shadow-sm backdrop-blur-sm border-l border-r border-white/40"></div>
            </div>

        </div>

        <div class="absolute bottom-0 w-full bg-white/95 backdrop-blur-md border-t border-gray-100 px-6 py-4 z-20 flex flex-col gap-2">

            <div class="flex justify-between items-end w-full px-1">
                <div class="flex items-center gap-2 text-gray-400">
                    <i class="fa-solid fa-headphones-simple text-brand-pink-dark animate-bounce"></i>
                    <span class="text-xs font-bold uppercase tracking-widest font-body">Listen & Repeat</span>
                </div>

                <div class="flex items-baseline gap-1">
                    <span id="intro-read-count" class="font-display text-3xl text-brand-pink-dark leading-none drop-shadow-sm">1</span>
                    <span class="font-display text-lg text-gray-300 leading-none">/ 7</span>
                </div>
            </div>

            <div class="w-full h-3 bg-gray-100 rounded-full relative overflow-visible mt-1">
                <div id="intro-progress-bar" class="h-full bg-gradient-to-r from-brand-pink to-brand-pink-dark bg-candy-stripe rounded-full transition-all duration-500 ease-out relative shadow-inner" style="width: 0%">

                    <div class="absolute -right-5 -top-5 w-10 h-10 transition-transform duration-500 pointer-events-none filter drop-shadow-md">
                        <img src="./img/ck_train.png" onerror="this.src='https://cdn-icons-png.flaticon.com/512/3063/3063823.png'" class="w-full h-full object-contain animate-train-chug">
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<div id="tree-php-modal" class="fixed inset-0 z-[300] hidden transition-opacity duration-500 ease-out opacity-0">
    <div class="absolute inset-0 bg-black/80 backdrop-blur-sm"></div>

    <div class="relative w-full h-full flex items-center justify-center p-4 md:p-8">

        <div id="tree-php-wrapper" class="relative w-full h-full max-w-none bg-brand-cream rounded-[2.5rem] overflow-hidden shadow-2xl border-4 border-wood-light transform scale-90 transition-all duration-500 ease-out flex flex-col">

            <button onclick="closeTreeModal()" class="absolute top-4 right-4 z-50 w-12 h-12 rounded-full bg-white/80 hover:bg-white text-wood-dark hover:text-red-500 border border-wood-light shadow-md backdrop-blur-md flex items-center justify-center transition-all cursor-pointer hover:scale-110 active:scale-95">
                <i class="fa-solid fa-xmark text-2xl"></i>
            </button>

            <iframe id="tree-php-frame" src="" class="w-full h-full border-0" allowtransparency="true"></iframe>
        </div>
    </div>
</div>

</body>
</html>