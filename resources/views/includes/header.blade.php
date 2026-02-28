<header class="flex justify-between items-center px-16 py-8 bg-transparent w-full">
    <div class="page-title">
        <h1 class="text-[#00923F] text-5xl font-[800] tracking-tight">
            @yield('page_title', 'Dashboard') 
        </h1>
    </div>

    <div class="flex items-center gap-8">
        <button class="text-[#00923F] p-2 hover:bg-[#00923F]/5 rounded-full transition-colors relative">
            <svg class="w-9 h-9 fill-current" viewBox="0 0 24 24">
                <path d="M12 22a2 2 0 0 0 2-2h-4a2 2 0 0 0 2 2m6-6v-5c0-3.07-1.63-5.64-4.5-6.32V4a1.5 1.5 0 0 0-3 0v.68C7.64 5.36 6 7.92 6 11v5l-2 2v1h16v-1z"/>
            </svg>
            <span class="absolute top-2 right-2.5 w-2 h-2 bg-red-500 rounded-full border-2 border-[#F1F3F2]"></span>
        </button>

        <div class="h-10 w-[1.5px] bg-gray-300/60"></div>

        <div class="flex items-center gap-5">
            <div class="text-right">
                <p class="text-[#003918] font-bold text-sm tracking-tight leading-tight">Mary Grace J. Dellomos</p>
                <p class="text-gray-500 text-xs font-semibold mt-0.5">Registrar</p>
            </div>
            
            <div class="w-12 h-12 bg-[#00923F] rounded-full flex items-center justify-center text-white font-extrabold text-sm border-2 border-white shadow-sm shrink-0">
                MG
            </div>
        </div>
    </div>
</header>