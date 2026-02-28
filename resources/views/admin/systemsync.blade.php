@extends('admin.layout')

@section('page_title', 'System Sync')

@section('content')
    <div class="w-full h-[100px] bg-[#F0F2F0] rounded-[10px] shadow-[0_3px_3px_0_rgba(0,0,0,0.25)] flex items-center px-12 justify-between">
        <div class="flex flex-col justify-center">
            <div class="flex items-center gap-4">
                <div class="w-4 h-4 {{ $isConnected ? 'bg-[#00923F] shadow-[0_0_10px_rgba(0,146,63,0.6)]' : 'bg-red-500 shadow-[0_0_10px_rgba(239,68,68,0.6)]' }} rounded-full blur-[0.5px] shrink-0"></div>
                
                <h2 class="text-[#333333] text-3xl font-extrabold tracking-normal leading-none">
                    {{ $isConnected ? 'Active' : 'Offline' }}
                </h2>
            </div>
            <div class="ml-8 mt-1">
                <p class="text-[#5F748D] text-sm font-medium leading-tight">
                    {{ $isConnected ? 'Form Is Currently Accepting Responses' : 'Cloud Connection Interrupted' }}
                </p>
                <p class="text-[#94A3B8] text-[10px] font-bold uppercase tracking-widest">
                    Last Updated: {{ $lastSync ? \Carbon\Carbon::parse($lastSync->created_at)->format('M d, Y - h:i A') : 'No Sync Recorded' }}
                </p>
            </div>
        </div>

        <div class="flex items-center gap-4"> 
            <form action="{{ route('admin.sync.perform') }}" method="POST" style="display: contents;">
                @csrf
                <button type="submit" class="bg-[#00923F] hover:bg-[#04578F] text-white px-6 py-2.5 rounded-lg flex items-center gap-3 shadow-md transition-all active:scale-95 group">
                    <svg class="w-5 h-5 fill-current group-hover:rotate-180 transition-transform duration-700" viewBox="0 0 30 30">
                        <path d="M15.0127 5V1.25L10.0127 6.25L15.0127 11.25V7.5C19.1502 7.5 22.5127 10.8625 22.5127 15C22.5127 16.2625 22.2002 17.4625 21.6377 18.5L23.4627 20.325C24.4755 18.7336 25.0132 16.8863 25.0127 15C25.0127 9.475 20.5377 5 15.0127 5ZM15.0127 22.5C10.8752 22.5 7.5127 19.1375 7.5127 15C7.5127 13.7375 7.8252 12.5375 8.3877 11.5L6.5627 9.675C5.54989 11.2664 5.01217 13.1137 5.0127 15C5.0127 20.525 9.48769 25 15.0127 25V28.75L20.0127 23.75L15.0127 18.75V22.5Z" />
                    </svg>
                    <span class="text-lg font-bold">Sync Data</span>
                </button>
            </form>
        </div>
    </div>
    <div class="w-full flex items-center justify-between my-10"> 
        <div class="flex items-center gap-10">
            <a href="https://docs.google.com/forms/d/1u8itWURr6Fl6617hMaiwFH_AYqeK0zojuvSEChNPjmI/edit" target="_blank" class="flex items-center gap-2 text-[#00923F] font-bold border-b-2 text-md border-[#00923F] pb-0.5 hover:opacity-30 transition-all">
                <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04a.996.996 0 0 0 0-1.41l-2.34-2.34a.996.996 0 0 0-1.41 0l-1.83 1.83l3.75 3.75l1.83-1.83z"/></svg>
                Edit Registration Form
            </a>
            <a href="https://docs.google.com/spreadsheets/d/1pUdqUbAMQEZ4Kg2V6A05orHY9xnDCJLp2QWLQaXXmSk/edit?resourcekey=&gid=1456187132#gid=1456187132" target="_blank" class="flex items-center gap-2 text-[#00923F] font-bold border-b-2 text-md border-[#00923F] pb-0.5 hover:opacity-30 transition-all">
                <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6zM18 20H6V4h7v5h5v11zM16 11h-8v2h8v-2zm0 4h-8v2h8v-2z"/></svg>
                Open Response Sheet
            </a>
        </div>

        <div class="flex items-center gap-4">
            <div class="bg-white border border-gray-200 rounded-[10px] px-6 py-2.5 w-[400px] text-gray-500 text-md text-center shadow-inner overflow-hidden whitespace-nowrap">
                https://forms.gle/7wrtrGWf2nDCWcz9A
            </div>
            <input type="hidden" id="responderUrl" value="https://forms.gle/7wrtrGWf2nDCWcz9A">
            <button onclick="copyToClipboard()" class="bg-[#00923F] hover:bg-[#04578F] text-white px-6 py-2.5 rounded-[10px] flex items-center gap-2 font-bold shadow-md transition-all active:scale-95">
                <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M16 1H4c-1.1 0-2 .9-2 2v14h2V3h12V1zm3 4H8c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h11c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2zm0 16H8V7h11v14z"/></svg>
                Copy URL
            </button>

            <script>
            function copyToClipboard() {
                var copyText = document.getElementById("responderUrl");
                navigator.clipboard.writeText(copyText.value);
                alert("Responder link copied to clipboard!");
            }
            </script>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-12 gap-6 mt-6">
        <div class="overflow-y-auto custom-scrollbar md:col-span-9 pr-4" style="max-height: 320px;">
            <h3 class="text-[#004225] text-2xl font-black mb-4 tracking-normal">SYNC HISTORY TABLE</h3>
            <table class="min-w-full">
                <thead>
                    <tr class="border-y border-gray-500">
                        <th class="py-2 text-[#004225] font-bold text-xs uppercase tracking-widest text-center">Date</th>
                        <th class="py-2 px-10 text-[#004225] font-bold text-xs uppercase tracking-widest text-center">Time</th>
                        <th class="py-2 text-[#004225] font-bold text-xs uppercase tracking-widest text-center">No. of Records</th>
                        <th class="py-2 text-[#004225] font-bold text-xs uppercase tracking-widest text-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($syncHistory as $item)
                        <tr class="border-b border-gray-300 text-center">
                            <td class="py-4 text-sm">{{ \Carbon\Carbon::parse($item->created_at)->format('M d, Y') }}</td>
                            <td class="py-4 text-sm">{{ \Carbon\Carbon::parse($item->created_at)->format('h:i A') }}</td>
                            <td class="py-4 text-sm font-bold">{{ $item->records_synced }}</td>
                            <td class="py-4">
                                <span class="px-3 py-1 rounded-full text-[10px] font-bold uppercase {{ $item->status == 'Success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                    {{ $item->status }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-10 text-center text-gray-400 italic">No sync history found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="md:col-span-3 flex flex-col items-center justify-center">
            <div class="qr-code-holder" style="background: white; padding: 15px; border: 1px solid #ddd; text-align: center;">
                <img src="{{ asset('images/registration-qr.png') }}" alt="Registration QR Code" style="max-width: 100%; height: auto;">
            </div>
            <a href="{{ asset('images/registration-qr.png') }}" download="Registration_QR.png" class="btn-download" style="text-decoration: none; color: #008000;">
                <i class="fas fa-download flex items-center gap-2 text-[#00923F] font-bold border-b-2 text-l border-[#00923F] leading-tight hover:opacity-30 transition-all"></i>
                Download QR Code
            </a>
        </div>
    </div>
</div>
@endsection