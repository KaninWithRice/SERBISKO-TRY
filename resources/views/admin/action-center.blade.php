@extends('admin.layout')

@section('page_title', 'Admin Action Center')

@section('content')
@php
    $totalPending = $pendingScans->count() + $conflicts->count() + $rejectedPapers->count();
    $currentTab = request('tab', 'verify');
@endphp

<div x-data="{ 
    activeTab: '{{ $currentTab }}',
    openModal: false, 
    activeConflict: {},
    getStatusClass(status) {
        if (status === 'pending') return 'bg-yellow-100 text-yellow-700 border-yellow-200';
        if (status === 'resolved') return 'bg-green-100 text-green-700 border-green-200';
        return 'bg-gray-100 text-gray-700 border-gray-200';
    }
}" class="px-6 py-8 w-full max-w-7xl mx-auto">
    
    {{-- Header --}}
    <div class="flex items-center gap-4 mb-8">
        <h1 class="text-3xl font-black text-[#003918] uppercase tracking-tighter">Admin Action Center</h1>
        @if($totalPending > 0)
            <span class="bg-red-600 text-white px-3 py-1 rounded-full text-sm font-black shadow-lg animate-pulse">
                {{ $totalPending }}
            </span>
        @endif
    </div>

    {{-- Messages --}}
    @if(session('success'))
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6 shadow-sm" id="success-message">
        {{ session('success') }}
    </div>
    @endif

    @if(session('error'))
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6 shadow-sm">
        {{ session('error') }}
    </div>
    @endif

    {{-- Tabs --}}
    <div class="flex border-b border-gray-200 mb-8 gap-8">
        <button @click="activeTab = 'verify'" 
                :class="activeTab === 'verify' ? 'border-[#1a8a44] text-[#1a8a44]' : 'border-transparent text-gray-500 hover:text-gray-700'"
                class="pb-4 px-2 border-b-4 font-bold text-sm uppercase tracking-widest transition-all flex items-center gap-2">
            Manual Verification
            @if($pendingScans->count() > 0)
                <span class="bg-red-100 text-red-700 px-2 py-0.5 rounded-full text-[10px] font-black">
                    {{ $pendingScans->count() }}
                </span>
            @endif
        </button>

        <button @click="activeTab = 'conflict'" 
                :class="activeTab === 'conflict' ? 'border-[#1a8a44] text-[#1a8a44]' : 'border-transparent text-gray-500 hover:text-gray-700'"
                class="pb-4 px-2 border-b-4 font-bold text-sm uppercase tracking-widest transition-all flex items-center gap-2">
            Data Conflicts
            @if($conflicts->count() > 0)
                <span class="bg-red-100 text-red-700 px-2 py-0.5 rounded-full text-[10px] font-black">
                    {{ $conflicts->count() }}
                </span>
            @endif
        </button>

        <button @click="activeTab = 'bin'" 
                :class="activeTab === 'bin' ? 'border-[#1a8a44] text-[#1a8a44]' : 'border-transparent text-gray-500 hover:text-gray-700'"
                class="pb-4 px-2 border-b-4 font-bold text-sm uppercase tracking-widest transition-all flex items-center gap-2">
            Rejection Bin
            @if($rejectedPapers->count() > 0)
                <span class="bg-amber-100 text-amber-700 px-2 py-0.5 rounded-full text-[10px] font-black">
                    {{ $rejectedPapers->count() }}
                </span>
            @endif
        </button>
    </div>

    {{-- Tab Content: Manual Verification --}}
    <div x-show="activeTab === 'verify'" x-cloak>
        <div id="verification-table-container" class="bg-white shadow-md rounded-lg overflow-hidden border border-gray-200">
            @include('admin.partials.verification-table')
        </div>
    </div>

    {{-- Tab Content: Data Conflicts --}}
    <div x-show="activeTab === 'conflict'" x-cloak>
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
    </div>

    {{-- Tab Content: Rejection Bin --}}
    <div x-show="activeTab === 'bin'" x-cloak>
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-xl font-bold text-red-700 flex items-center gap-2 uppercase tracking-tighter">
                <span class="text-2xl">🗑️</span> Physical Rejections
            </h2>
            <div class="flex items-center gap-2 bg-red-50 text-red-700 px-3 py-1 rounded-full border border-red-100">
                <span class="relative flex h-2 w-2">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-red-500"></span>
                </span>
                <span class="text-[10px] font-black uppercase tracking-widest">Live Monitoring</span>
            </div>
        </div>
        
        <div class="bg-white shadow-md rounded-lg overflow-hidden border border-gray-200">
            <div id="rejected-papers-container">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Student Name</th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Grade Level</th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Document Type</th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Rejected At</th>
                            <th class="px-6 py-3 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Action</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($rejectedPapers as $rej)
                        <tr class="hover:bg-red-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900">
                                {{ $rej->last_name }}, {{ $rej->first_name }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $rej->display_grade }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-orange-100 text-orange-800 border border-orange-200">
                                    {{ $rej->document_type }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-400 font-mono">
                                {{ \Carbon\Carbon::parse($rej->rejected_at)->format('M d, g:i A') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <form action="{{ route('admin.collect-rejected-paper') }}" method="POST" onsubmit="return confirm('Mark this paper as collected?')">
                                    @csrf
                                    <input type="hidden" name="user_id" value="{{ $rej->user_id }}">
                                    <input type="hidden" name="rejected_at" value="{{ $rej->rejected_at }}">
                                    <button type="submit" class="bg-red-600 hover:bg-red-700 text-white font-bold py-1 px-4 rounded-full text-xs shadow-sm transition-all transform hover:scale-105">
                                        Collect
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-6 py-10 text-center text-gray-400 italic">
                                No physical paper rejections recorded.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Shared Modals: Image Preview --}}
    <div id="imageModal" class="fixed inset-0 z-[100] hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" onclick="closeModal()"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-3xl w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">Document Preview</h3>
                            <div class="mt-4 flex justify-center bg-gray-100 rounded p-2">
                                <img id="modalImage" src="" alt="Document Scan" class="max-h-[70vh] object-contain rounded border border-gray-300">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="button" onclick="closeModal()" class="w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm">
                        Close Preview
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Shared Modals: Data Conflict --}}
    <div 
        x-show="openModal" 
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        class="fixed inset-0 z-[100] flex items-center justify-center bg-black/60 backdrop-blur-sm p-4" 
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

            <form 
                :action="'{{ route('admin.admin.conflicts.resolve', ['id' => ':id']) }}'.replace(':id', activeConflict.id)" 
                method="POST">
                @csrf
                <div class="grid grid-cols-2">
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

<script>
    function openModal(imageUrl) {
        document.getElementById('modalImage').src = imageUrl;
        document.getElementById('imageModal').classList.remove('hidden');
    }
    function closeModal() {
        document.getElementById('imageModal').classList.add('hidden');
        document.getElementById('modalImage').src = '';
    }

    // REAL-TIME REFRESH LOGIC (Only for verification tab)
    function refreshVerificationTable() {
        // Only refresh if we are on the verification tab and modal is hidden
        const alpine = Alpine.find(document.querySelector('[x-data]'));
        if (alpine && alpine.activeTab !== 'verify') return;

        const modal = document.getElementById('imageModal');
        if (modal && modal.classList.contains('hidden')) {
            fetch('{{ route('admin.action-center') }}', {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(response => response.text())
            .then(html => {
                const container = document.getElementById('verification-table-container');
                if (container) container.innerHTML = html;
            })
            .catch(error => console.error('Error refreshing table:', error));
        }
    }

    setInterval(refreshVerificationTable, 5000);

    setTimeout(() => {
        const successMsg = document.getElementById('success-message');
        if (successMsg) successMsg.style.display = 'none';
    }, 5000);
</script>

<style>
    [x-cloak] { display: none !important; }
</style>
@endsection
