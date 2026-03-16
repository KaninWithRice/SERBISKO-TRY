<form action="{{ route('admin.settings.save') }}" method="POST">
    @csrf

    {{-- Success Feedback --}}
    @if(session('success'))
        <div class="mb-6 p-4 bg-green-50 border-l-4 border-[#1a8a44] text-[#003918] text-sm font-bold rounded-r-xl shadow-sm animate-fade-in-down">
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-white rounded-2xl shadow-lg border-l-8 border-[#1a8a44] overflow-hidden relative">
        <div class="p-8 pl-10 pr-10">
            {{-- Header Section --}}
            <div class="mb-6">
                <h1 class="text-3xl font-black text-[#003918] uppercase tracking-tight">Data Source Connection</h1>
                <p class="text-gray-500 font-regular italic mt-1 text-sm">Enable data integration between Google Workspace and the SerbIsko local database.</p>
            </div>

            <div class="w-full bg-[#E7F3ED] rounded-lg p-4 flex items-center justify-between border border-[#D1E7DD] shadow-sm mb-6">
                <div class="flex items-start gap-3">
                    {{-- Success/Info Icon --}}
                    <div class="mt-1 text-[#00923F]">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 22 22">
                            <path fill="currentColor" d="M12 2C6.477 2 2 6.477 2 12s4.477 10 10 10s10-4.477 10-10S17.523 2 12 2m1 15h-2v-2h2zm0-4h-2l-.5-6h3z" />
                        </svg>
                    </div>
                    
                    {{-- Text Content --}}
                    <div>
                        <h4 class="text-[#00923F] font-bold text-md mb-1">Action Required</h4>
                        <p class="text-[#003918] text-xs leading-relaxed max-w-xl">
                            To allow <span class="font-bold">SerbIsko</span> to sync data, you must share your Google Sheet with <br> the system's authorized email as an <span class="font-bold uppercase">Editor</span>.
                        </p>
                    </div>
                </div>

                {{-- The Copy Button --}}
                <div x-data="{ 
                        serviceEmail: '{{ config('services.google.service_account_email') }}',
                        copied: false 
                    }">
                    <button type="button" 
                        @click="navigator.clipboard.writeText(serviceEmail); copied = true; setTimeout(() => copied = false, 2000)"
                        class="bg-[#00923F] hover:bg-[#007A35] text-white px-6 py-3 rounded-xl flex items-center gap-3 transition-all active:scale-95 shadow-md">
                        
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 22 22">
                            <path fill="currentColor" d="M4.616 22q-.691 0-1.153-.462T3 20.385V8h1v12.385q0 .23.192.423t.423.192H14v1zm4-4q-.691 0-1.153-.462T7 16.384V3.616q0-.691.463-1.153T8.616 2H15.5L20 6.5v9.885q0 .69-.462 1.153T18.384 18zM15 7h4l-4-4z" />
                        </svg>

                        <span class="font-medium text-sm tracking-tight" x-text="copied ? 'Email Copied!' : 'Copy System Email to Clipboard'"></span>
                    </button>
                </div>
            </div>

            <div class="space-y-8">
                {{-- Spreadsheet ID --}}
                <x-input 
                    name="active_spreadsheet_id"
                    label="REFERENCE SPREADSHEET ID"
                    description="Unique identifier for the Google Response Sheet."
                    placeholder="Enter the response sheet ID between /d/ and /edit from URL."
                    :value="$settings->active_spreadsheet_id ?? ''"
                />
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-12 gap-y-8 mt-8">
                {{-- Sheet Range --}}
                <x-input 
                    name="active_sheet_range"
                    label="SHEET DATA RANGE"
                    description="The sheet tab name and cell range (e.g., Sheet1!A1:Z)."
                    placeholder="e.g., Form_Responses!A1:ZZ"
                    :value="$settings->active_sheet_range ?? ''"
                />

                {{-- School Year (Dynamic Select) --}}
                @php
                    $currentYear = date('Y');
                    $years = [
                        ($currentYear - 1) . '-' . $currentYear,
                        $currentYear . '-' . ($currentYear + 1),
                        ($currentYear + 1) . '-' . ($currentYear + 2),
                        ($currentYear + 2) . '-' . ($currentYear + 3),
                    ];
                @endphp

                <x-select 
                    name="active_school_year"
                    label="ACTIVE SCHOOL YEAR"
                    description="The school year assigned to incoming applications."
                    :options="$years"
                    :selected="$settings->active_school_year ?? ''"
                />
            </div>

            {{-- Form Editor URL --}}
            <x-input 
                name="edit_form_url"
                label="Form Editor URL"
                description="Direct link to the Google Form 'Edit' view for modifying questions."
                placeholder="https://docs.google.com/forms/d/.../edit"
                :value="$settings->edit_form_url ?? ''"
                class="mt-8"
            />

            {{-- Public Registration Link --}}
            <x-input 
                name="public_form_url"
                label="Public Registration Link"
                description="The URL distributed to students for registration."
                placeholder="https://forms.gle/..."
                :value="$settings->public_form_url ?? ''"
                class="mt-8"
            />

            {{-- Action Button --}}
            <div class="mt-10 flex justify-end">
                <button type="submit" class="bg-[#1a8a44] text-white px-10 py-4 rounded-xl font-medium hover:bg-[#156e36] transition-all shadow-md active:scale-95 tracking-tight text-sm">
                    Update Connection Settings
                </button>
            </div>
        </div>
    </div>
</form>