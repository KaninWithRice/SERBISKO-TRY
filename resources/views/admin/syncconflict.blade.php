@extends('admin.layout')

@section('page_title', 'Data Conflict Resolution')

@section('content')
<div x-data="{ 
    openModal: false, 
    activeConflict: {},
    getStatusClass(status) {
        if (status === 'pending') return 'bg-yellow-100 text-yellow-700 border-yellow-200';
        if (status === 'resolved') return 'bg-green-100 text-green-700 border-green-200';
        return 'bg-gray-100 text-gray-700 border-gray-200';
    }
}" class="p-6">
    
    {{-- 1. Notification Area (Add this to see why it fails) --}}
    @if(session('success'))
        <div class="mb-6 p-4 bg-green-500 text-white rounded-lg shadow-lg animate-bounce">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mb-6 p-4 bg-red-600 text-white rounded-lg shadow-lg">
            <strong>Error:</strong> {{ session('error') }}
        </div>
    @endif

    {{-- Header Section --}}
    <div class="w-full h-[100px] bg-[#F7FBF9]/50 rounded-[10px] shadow-[0_3px_3px_0_rgba(0,0,0,0.25)] flex items-center px-12 justify-between mb-10">
        <div class="flex flex-col justify-center">
            <div class="flex items-center gap-4">
                <div class="w-4 h-4 {{ $conflicts->count() > 0 ? 'bg-orange-500 animate-pulse' : 'bg-[#00923F]' }} rounded-full shrink-0"></div>
                <h2 class="text-[#333333] text-3xl font-extrabold tracking-normal uppercase leading-none">
                    {{ $conflicts->count() > 0 ? 'Conflicts Detected' : 'Data In Sync' }}
                </h2>
            </div>
            <div class="ml-8 mt-1">
                <p class="text-[#5F748D] text-sm font-medium leading-tight">
                    {{ $conflicts->count() }} pending discrepancies between Google Sheets and Local Database
                </p>
            </div>
        </div>
        <a href="{{ route('admin.systemsync') }}" class="text-[#00923F] font-bold hover:underline">← Back to System Sync</a>
    </div>

    {{-- Data Table --}}
    <div class="bg-white rounded-xl shadow-md overflow-hidden border border-gray-100">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-4 text-left text-xs font-bold text-[#004225] uppercase tracking-widest">LRN (Reference)</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-[#004225] uppercase tracking-widest">Status & Type</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-[#004225] uppercase tracking-widest">Existing Record</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-[#004225] uppercase tracking-widest">Detected At</th>
                    <th class="px-6 py-4 text-center text-xs font-bold text-[#004225] uppercase tracking-widest">Action</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($conflicts as $conflict)
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-6 py-4 font-mono text-sm font-bold text-gray-700">
                        {{ $conflict->lrn }}
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex flex-col gap-1">
                            <span class="inline-flex items-center w-fit px-2 py-0.5 rounded text-[10px] font-black uppercase border {{ $conflict->conflict_type === 'identity_mismatch' ? 'bg-red-50 text-red-700 border-red-200' : 'bg-blue-50 text-blue-700 border-blue-200' }}">
                                {{ str_replace('_', ' ', $conflict->conflict_type) }}
                            </span>
                            <span :class="getStatusClass('{{ $conflict->status }}')" class="inline-flex items-center w-fit px-2 py-0.5 rounded text-[9px] font-bold uppercase border">
                                ● {{ $conflict->status }}
                            </span>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm font-semibold text-gray-900">
                            {{ $conflict->existingUser->first_name ?? 'Unknown' }} {{ $conflict->existingUser->last_name ?? '' }}
                        </div>
                        <div class="text-xs text-gray-500 italic">S.Y. {{ $conflict->school_year }}</div>
                    </td>
                    <td class="px-6 py-4 text-xs text-gray-400 font-medium">
                        {{ $conflict->created_at->format('M d, Y') }}<br>
                        {{ $conflict->created_at->diffForHumans() }}
                    </td>
                    <td class="px-6 py-4 text-center">
                        <button 
                            @click="activeConflict = { 
                                ...@js($conflict), 
                                live_lrn: '{{ $conflict->existingUser->student->lrn ?? '—' }}', 
                                live_first_name: '{{ $conflict->existingUser->first_name ?? '—' }}',
                                live_last_name: '{{ $conflict->existingUser->last_name ?? '—' }}',
                                // FIX: Make sure this points to the existingUser, not the sheet data
                                live_birthday: '{{ $conflict->existingUser->birthday ?? '—' }}'
                            }; openModal = true"
                            class="bg-[#00923F] hover:bg-[#04578F] text-white px-5 py-2 rounded-lg text-xs font-black uppercase tracking-widest transition-all active:scale-95 shadow-sm">
                            Review
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-6 py-20 text-center">
                        <div class="flex flex-col items-center justify-center text-gray-400">
                            <svg class="w-12 h-12 mb-4 opacity-20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            <p class="italic text-lg font-medium">All data is currently synchronized.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Resolution Modal --}}
    <div 
        x-show="openModal" 
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4" 
        x-cloak>
        
        <div 
            x-show="openModal"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            class="bg-white w-full max-w-5xl rounded-2xl shadow-2xl overflow-hidden border border-gray-200" 
            @click.away="openModal = false">
            
            <div class="bg-gray-50 px-8 py-6 border-b flex justify-between items-center">
                <div>
                    <h3 class="font-black text-2xl text-[#004225] uppercase tracking-tight">Resolve Data Conflict</h3>
                    <p class="text-xs text-gray-500 font-bold uppercase tracking-widest mt-1">
                        Conflict ID: <span class="text-[#00923F]" x-text="activeConflict.id"></span>
                    </p>
                </div>
                <button @click="openModal = false" class="text-gray-400 hover:text-red-500 transition-colors">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>

            {{-- 2. Fixed Form Action (Matching your route exactly) --}}
            <form 
                :action="'{{ route('admin.admin.conflicts.resolve', ['id' => ':id']) }}'.replace(':id', activeConflict.id)" 
                method="POST">
                @csrf
                <div class="grid grid-cols-2">
                    {{-- Left side: Database --}}
                    <div class="p-8 border-r border-gray-100 bg-gray-50/30">
                        <h4 class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] mb-6 flex items-center gap-2">
                            <span class="w-2 h-2 bg-gray-300 rounded-full"></span> Existing System Record (Live)
                        </h4>
                        <div class="space-y-4">
                            <div class="flex flex-col p-2 bg-gray-100 rounded-lg border border-gray-200">
                                <label class="text-[10px] text-gray-500 font-bold uppercase">System LRN</label>
                                <p class="text-lg font-mono font-bold text-gray-700" x-text="activeConflict.live_lrn"></p>
                            </div>
                            <div class="flex flex-col">
                                <label class="text-[10px] text-gray-400 font-bold uppercase">First Name</label>
                                <p class="text-lg font-bold text-gray-700" x-text="activeConflict.live_first_name"></p>
                            </div>
                            <div class="flex flex-col">
                                <label class="text-[10px] text-gray-400 font-bold uppercase">Last Name</label>
                                <p class="text-lg font-bold text-gray-700" x-text="activeConflict.live_last_name"></p>
                            </div>
                            <div class="flex flex-col">
                                <label class="text-[10px] text-gray-400 font-bold uppercase">Birthday</label>
                                <p class="text-lg font-bold text-gray-700" 
                                   x-text="activeConflict.live_birthday ? activeConflict.live_birthday.split(' ')[0] : '—'">
                                </p>                             
                            </div>
                        </div>
                    </div>

                    {{-- Right side: Google Sheet --}}
                    <div class="p-8 bg-[#F7FBF9]">
                        <h4 class="text-[10px] font-black text-[#00923F] uppercase tracking-[0.2em] mb-6 flex items-center gap-2">
                            <span class="w-2 h-2 bg-[#00923F] rounded-full animate-ping"></span> Incoming Sheet Data
                        </h4>
                        <div class="space-y-4">
                            <div class="flex flex-col p-2 bg-green-50 rounded-lg border border-green-100">
                                <label class="text-[10px] text-[#00923F] font-bold uppercase">Sheet LRN</label>
                                <p class="text-lg font-mono font-bold text-[#004225]" x-text="activeConflict.incoming_data_json?.lrn || '—'"></p>
                            </div>
                            <div class="flex flex-col">
                                <label class="text-[10px] text-[#00923F] font-bold uppercase">First Name</label>
                                <p class="text-lg font-bold text-[#004225]" x-text="activeConflict.incoming_data_json?.first_name || '—'"></p>
                            </div>
                            <div class="flex flex-col">
                                <label class="text-[10px] text-[#00923F] font-bold uppercase">Last Name</label>
                                <p class="text-lg font-bold text-[#004225]" x-text="activeConflict.incoming_data_json?.last_name || '—'"></p>
                            </div>
                            <div class="flex flex-col">
                                <label class="text-[10px] text-[#00923F] font-bold uppercase">Birthday</label>
                                <p class="text-lg font-bold text-[#004225]" x-text="activeConflict.incoming_data_json?.birthday || '—'"></p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="p-8 bg-white border-t flex flex-col gap-6">
                    <div class="w-full">
                        <label class="text-[10px] font-black text-gray-400 uppercase mb-2 block">Resolution Audit Notes</label>
                        <textarea name="notes" rows="2" placeholder="e.g., 'Verified via student ID card'..." class="w-full border-2 border-gray-100 rounded-xl p-3 text-sm focus:border-[#00923F] focus:outline-none transition-colors"></textarea>
                    </div>
                    
                    <div class="flex justify-end gap-4">
                        <button type="submit" name="action" value="ignore" 
                            class="px-8 py-3 border-2 border-gray-200 rounded-xl text-gray-500 hover:bg-gray-50 font-black uppercase text-xs tracking-widest transition-all">
                            Keep Existing
                        </button>
                        <button type="submit" name="action" value="accept_new" 
                            class="px-8 py-3 bg-[#00923F] text-white rounded-xl hover:bg-[#004225] font-black uppercase text-xs tracking-widest shadow-lg shadow-green-200 transition-all active:scale-95">
                            Update with New Data
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    [x-cloak] { display: none !important; }
</style>
@endsection