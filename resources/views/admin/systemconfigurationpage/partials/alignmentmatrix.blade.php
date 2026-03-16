<form id="re-scan-form" action="{{ route('admin.settings.refresh') }}" method="POST" class="hidden">
    @csrf
</form>

<form id="alignment-matrix-form" action="{{ route('admin.settings.mapping.save') }}" method="POST">
    @csrf
    <div class="bg-white rounded-2xl shadow-lg border-l-8 border-[#1a8a44] overflow-hidden relative">
        <div class="p-8 pl-10 pr-10">
            
            {{-- Header and Action Buttons --}}
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-8">
                <div class="flex-1">
                    <h1 class="text-3xl font-black text-[#003918] uppercase tracking-tight">Alignment Matrix</h1>
                    <p class="text-gray-500 font-regular italic mt-1 text-sm">
                        Map Google Form questions to SerbIsko database destinations.
                    </p>
                </div>

                <div class="flex items-center gap-3 shrink-0">
                    
                    {{-- BOX 1: This button only handles Re-scanning --}}
                        <button type="submit" form="re-scan-form" class="bg-[#F3F4F6] text-[#003918] px-5 py-3 rounded-xl font-medium flex items-center gap-2 hover:bg-gray-200 transition text-sm">
                            <svg class="w-4 h-4 fill-current" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd"/></svg>
                            Re-scan Headers
                        </button>

                    {{-- BOX 2: This button only handles Saving the Table --}}
                    <button type="submit" form="alignment-matrix-form" 
                        onclick="this.innerHTML='<i class=\'fas fa-spinner fa-spin\'></i> Saving & Syncing...'; this.classList.add('opacity-50');"
                        class="bg-[#00923F] text-white px-8 py-3 rounded-xl font-bold flex items-center gap-2 hover:bg-[#007a35] transition shadow-md text-sm">
                        <svg class="w-4 h-4 fill-current" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                        Save Mappings
                    </button>

                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full table-auto border-collapse">
                    <thead>
                        <tr class="sticky top-0 z-10 border-b border-t border-gray-500">
                            <th class="py-4 text-[#004225] font-bold text-xs uppercase tracking-widest text-left">Order</th>
                            <th class="py-4 px-6 text-[#004225] font-bold text-xs uppercase tracking-widest text-left">Form Question</th>
                            <th class="py-4 text-[#004225] font-bold text-xs uppercase tracking-widest text-center">Display Label</th>
                            <th class="py-4 text-[#004225] font-bold text-xs uppercase tracking-widest text-center">System Destination</th>
                            <th class="py-4 text-[#004225] font-bold text-xs uppercase tracking-widest text-center">Category</th>
                            <th class="py-4 text-[#004225] font-bold text-xs uppercase tracking-widest text-center">Status</th>
                            <th class="py-4 text-[#004225] font-bold text-xs uppercase tracking-widest text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($mappings as $index => $map)
                            <tr>
                                <td class="py-4 text-xs font-bold text-gray-400">#{{ $index + 1 }}</td>

                                {{-- Form Question --}}
                                <td class="py-4 px-6">
                                    <span class="text-sm font-medium text-gray-700 block max-w-xs truncate" title="{{ $map->google_header }}">
                                        {{ $map->google_header }}
                                    </span>
                                    {{-- HIDDEN INPUTS: Required for the Controller to see the data --}}
                                    <input type="hidden" name="mappings[{{ $map->id }}][google_header]" value="{{ $map->google_header }}">
                                </td>

                                {{-- Display Label Input --}}
                                <td class="py-4 px-2">
                                    <input type="text" 
                                        name="mappings[{{ $map->id }}][display_label]" 
                                        value="{{ $map->display_label }}"
                                        class="w-full text-xs border-gray-200 rounded-lg focus:ring-[#1a8a44] focus:border-[#1a8a44] text-center"
                                        placeholder="e.g. LRN">
                                </td>

                                {{-- System Destination Dropdown --}}
                                <td class="py-4 px-2">
                                    <select name="mappings[{{ $map->id }}][database_field]" 
                                        class="destination-select w-full text-xs border-gray-200 rounded-lg bg-gray-50 focus:ring-[#1a8a44]">
                                        <option value="">-- Skip Data --</option>
                                        @foreach($systemDestinations as $group => $fields)
                                            <optgroup label="{{ $group }}">
                                                @foreach($fields as $column => $label)
                                                    <option value="{{ $column }}" {{ $map->database_field == $column ? 'selected' : '' }}>
                                                        {{ $label }}
                                                    </option>
                                                @endforeach
                                            </optgroup>
                                        @endforeach
                                    </select>
                                </td>

                                {{-- Category Dropdown --}}
                                <td class="py-4 px-2">
                                    <select name="mappings[{{ $map->id }}][category]" 
                                        class="w-full text-xs border-gray-200 rounded-lg bg-white focus:ring-[#1a8a44]">
                                        @foreach(['Academic', 'Personal', 'Family', 'Requirement'] as $cat)
                                            <option value="{{ $cat }}" {{ $map->category == $cat ? 'selected' : '' }}>{{ $cat }}</option>
                                        @endforeach
                                    </select>
                                </td>

                                {{-- Status Badge --}}
                                <td class="py-4 text-center status-badge-container">
                                    @if($map->database_field)
                                        <span class="status-badge px-2 py-1 bg-green-100 text-green-700 text-[9px] font-black uppercase rounded-full tracking-tighter">Operational</span>
                                    @else
                                        <span class="status-badge px-2 py-1 bg-yellow-100 text-yellow-700 text-[9px] font-black uppercase rounded-full tracking-tighter">Pending</span>
                                    @endif
                                </td>

                                {{-- Actions --}}
                                <td class="py-4 text-right">
                                    <button type="button" class="text-red-300 hover:text-red-600 transition">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-12 text-center text-gray-400 italic text-sm font-medium">
                                    No form headers found. Click "Re-scan Headers" to pull data from your Google Sheet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="border-t border-gray-500 mt-4"></div>

                {{-- Bottom Action Buttons --}}
                <div class="flex items-center justify-end gap-3 mt-8 pb-4">
                    
                    {{-- Secondary Button: Re-scan --}}
                    <button type="submit" form="re-scan-form" 
                        class="bg-[#F3F4F6] text-[#003918] px-5 py-3 rounded-xl font-medium flex items-center gap-2 hover:bg-gray-200 transition text-sm">
                        <svg class="w-4 h-4 fill-current" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd"/>
                        </svg>
                        Re-scan Headers
                    </button>

                    {{-- Primary Button: Save --}}
                    <button type="submit" form="alignment-matrix-form" 
                        onclick="this.innerHTML='<i class=\'fas fa-spinner fa-spin\'></i> Saving & Syncing...'; this.classList.add('opacity-50');"
                        class="bg-[#00923F] text-white px-8 py-3 rounded-xl font-bold flex items-center gap-2 hover:bg-[#007a35] transition shadow-md text-sm">
                        <svg class="w-4 h-4 fill-current" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                        Save Mappings
                    </button>
                </div>
            </div> 
        </div>
    </div>
</form>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        document.querySelectorAll('.destination-select').forEach((el) => {
            // Store the TomSelect instance in a variable
            new TomSelect(el, {
                create: false,
                sortField: null,
                lockOptgroupOrder: true,
                allowEmptyOption: true,
                placeholder: "Search destination...",
                render: {
                    option: function(data, escape) {
                        return `<div class="py-1 px-2 text-xs">${escape(data.text)}</div>`;
                    },
                    item: function(data, escape) {
                        return `<div class="text-xs">${escape(data.text)}</div>`;
                    }
                },
                onChange: function(value) {
                    const row = el.closest('tr');
                    const badge = row.querySelector('.status-badge');

                    // Visual feedback: Highlight the row
                    row.classList.add('row-highlight');
                    setTimeout(() => row.classList.remove('row-highlight'), 1000);

                    if (value && value !== "") {
                        // Update to Operational (Green)
                        badge.innerText = 'Operational';
                        badge.classList.remove('bg-yellow-100', 'text-yellow-700');
                        badge.classList.add('bg-green-100', 'text-green-700');
                    } else {
                        // Update to Pending (Yellow)
                        badge.innerText = 'Pending';
                        badge.classList.remove('bg-green-100', 'text-green-700');
                        badge.classList.add('bg-yellow-100', 'text-yellow-700');
                    }
                }
            });
        });
    });
</script>

<style>
    /* Match your existing table styling */
    .ts-control {
        border-radius: 0.5rem !important; /* rounded-lg */
        padding: 6px 12px !important;
        font-size: 0.75rem !important; /* text-xs */
        border: 1px solid #e5e7eb !important; /* border-gray-200 */
    }
    .ts-wrapper.single .ts-control {
        background-color: #f9fafb !important; /* bg-gray-50 */
    }
    .ts-dropdown {
        font-size: 0.75rem !important;
    }
    /* Targets the group headings in the dropdown list */
    .ts-dropdown .optgroup-header {
        font-weight: 600 !important;
        color: #003918 !important; /* Optional: matches your dark green theme */
        font-size: 0.8rem !important;
        padding-top: 8px !important;
        padding-bottom: 4px !important;
        border-bottom: 1px solid #f0f0f0; /* Adds a nice separator */
    }

    /* Adds a little indentation to the items under the bold header for better hierarchy */
    .ts-dropdown .option {
        padding-left: 15px !important;
    }

    .ts-wrapper.searching .option[data-value=""] {
        display: none !important;
    }

    .row-highlight {
        background-color: #f0fdf4; /* Very light green */
        transition: background-color 0.5s ease;
    }
</style>