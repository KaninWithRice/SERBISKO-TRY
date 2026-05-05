@extends('admin.layout')

@section('page_title', 'Hardware Management')

@section('header_content')
<div class="flex items-center gap-3">
    <div class="text-right">
        <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest">Hardware Status</p>
        <div class="flex items-center gap-2 justify-end">
            <div class="w-2 h-2 rounded-full {{ $arduinoStatus === 'online' ? 'bg-green-500 animate-pulse' : ($arduinoStatus === 'offline' ? 'bg-red-500' : 'bg-amber-500') }}"></div>
            <p class="text-[#003918] font-black text-sm uppercase">
                {{ str_replace('_', ' ', $arduinoStatus) }}
            </p>
        </div>
    </div>
    <div class="h-8 w-[1px] bg-gray-300 mx-2"></div>
    <div class="bg-[#005288] p-2 rounded-full shadow-sm">
        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
            <path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z" />
        </svg>
    </div>
</div>
@endsection

@section('content')
<div class="space-y-10 font-sans tracking-tight" x-data="{ 
    loading: false,
    message: '',
    error: false,
    async collectDocuments() {
        if (!confirm('This will trigger the hardware to move documents to the collection bin. Proceed?')) return;
        
        this.loading = true;
        this.message = 'Sending command...';
        this.error = false;
        
        try {
            const response = await fetch('{{ route('admin.hardware.collect') }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                }
            });
            
            const data = await response.json();
            this.message = data.message;
            this.error = !data.success;
            
            if (data.success) {
                setTimeout(() => { this.message = ''; }, 5000);
            }
        } catch (e) {
            this.error = true;
            this.message = 'System Error: Could not reach the server.';
        } finally {
            this.loading = false;
        }
    }
}">

    <!-- Alert Messages (Dynamic) -->
    <template x-if="message">
        <div :class="error ? 'bg-red-50 border-red-500 text-red-700' : 'bg-green-50 border-[#00923F] text-[#003918]'" 
             class="border-l-4 px-6 py-4 rounded-xl shadow-sm mb-6 flex items-center gap-3 animate-fadeIn">
            <svg x-show="!error" class="w-5 h-5 text-[#00923F]" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
            <svg x-show="error" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path></svg>
            <p class="text-sm font-bold" x-text="message"></p>
        </div>
    </template>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        
        <!-- Document Retrieval Card -->
        <div class="bg-white rounded-[2.5rem] shadow-2xl shadow-gray-200/50 border border-gray-100 overflow-hidden group hover:shadow-green-900/10 transition-all duration-500">
            <div class="p-10 flex flex-col h-full">
                <div class="flex items-center gap-5 mb-8">
                    <div class="w-16 h-16 bg-green-50 rounded-2xl flex items-center justify-center text-[#00923F] group-hover:scale-110 transition-transform duration-500">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-[#003918] text-2xl font-black uppercase tracking-tight">Physical Retrieval</h2>
                        <p class="text-[11px] text-gray-400 font-bold uppercase tracking-[0.2em] mt-1">Manual Document Collection</p>
                    </div>
                </div>

                <div class="flex-1 space-y-4 mb-10">
                    <p class="text-gray-500 leading-relaxed font-medium">
                        Trigger this action to physically retrieve all verified documents from the kiosk's internal trays. The hardware will initiate a collection sequence to move papers to the primary retrieval bin.
                    </p>
                    <div class="bg-[#F7FBF9] p-5 rounded-2xl border border-green-100/50">
                        <div class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-[#00923F] shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            <p class="text-[11px] text-[#003918] font-bold uppercase leading-tight opacity-70">
                                This command sends the <span class="bg-green-100 px-1.5 py-0.5 rounded text-[#00923F]">b5</span> signal to the controller.
                            </p>
                        </div>
                    </div>
                </div>

                <button 
                    @click="collectDocuments()"
                    :disabled="loading || '{{ $arduinoStatus }}' === 'offline'"
                    class="w-full relative overflow-hidden bg-[#00923F] hover:bg-[#007a34] disabled:bg-gray-200 disabled:cursor-not-allowed text-white font-black py-6 rounded-3xl shadow-xl shadow-green-900/20 transition-all uppercase text-sm tracking-[0.3em] active:scale-[0.98] group/btn">
                    
                    <span x-show="!loading" class="relative z-10 flex items-center justify-center gap-3">
                        Collect Documents
                        <svg class="w-5 h-5 animate-bounce-x" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
                    </span>
                    
                    <div x-show="loading" class="flex items-center justify-center">
                        <svg class="animate-spin h-6 w-6 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>
                </button>
            </div>
        </div>

        <!-- Status & Diagnostics -->
        <div class="bg-[#003918] rounded-[2.5rem] shadow-2xl border border-white/10 p-10 flex flex-col">
            <h2 class="text-white text-xl font-black uppercase tracking-widest mb-8 flex items-center gap-3">
                <span class="w-2 h-2 rounded-full bg-green-400"></span>
                System Diagnostics
            </h2>

            <div class="space-y-6">
                <div class="flex justify-between items-center py-4 border-b border-white/10">
                    <span class="text-white/60 text-xs font-bold uppercase tracking-widest">Arduino Controller</span>
                    <span class="px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest {{ $arduinoStatus === 'online' ? 'bg-green-500/20 text-green-400 border border-green-500/30' : 'bg-red-500/20 text-red-400 border border-red-500/30' }}">
                        {{ $arduinoStatus === 'online' ? 'Connected' : 'Disconnected' }}
                    </span>
                </div>
            </div>

            <div class="mt-auto pt-10">
                <div class="bg-white/5 p-6 rounded-3xl border border-white/10">
                    <p class="text-white/40 text-[10px] font-bold uppercase tracking-[0.2em] mb-2">Technical Note</p>
                    <p class="text-white/70 text-xs leading-relaxed">
                        Ensure the Hardware Controller service is running in the background. If the status is offline, check the physical connection and the local Python bridge.
                    </p>
                </div>
            </div>
        </div>

    </div>
</div>

<style>
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .animate-fadeIn {
        animation: fadeIn 0.3s ease-out forwards;
    }
    .animate-bounce-x {
        animation: bounce-x 1s infinite;
    }
    @keyframes bounce-x {
        0%, 100% { transform: translateX(0); }
        50% { transform: translateX(5px); }
    }
</style>
@endsection
