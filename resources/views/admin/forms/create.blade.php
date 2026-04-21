@extends('admin.layout')

@section('page_title', isset($form) ? 'Edit Form' : 'New Form')

@section('content')

{{-- Two-column layout: sticky sidebar + scrollable main --}}
<div class="py-8 max-w-5xl mx-auto">
    {{-- ── MAIN CONTENT ────────────────────────────────────────────────── --}}
    <div class="min-w-0"
         x-data="formBuilder({{ isset($form) ? json_encode($form->schema) : '[]' }}, '{{ old('school_year', $form->school_year ?? '2026-2027') }}')"
         x-init="initSortable()"
         x-cloak
         @add-question.window="addQuestion($event.detail.qtype)"
         @submit-form.window="submitForm($refs.mainForm)"
    >
        <div class="mb-6">
            <a href="{{ route('admin.forms.index') }}"
               class="text-sm text-[#00923F] font-bold hover:underline">← Back to Forms</a>
        </div>

        <form
            x-ref="mainForm"
            method="POST"
            action="{{ isset($form) ? route('admin.forms.update', $form) : route('admin.forms.store') }}"
            @submit.prevent="submitForm($el)"
        >
            @csrf
            @if(isset($form)) @method('PUT') @endif

            {{-- ── Meta Card ──────────────────────────────────────────── --}}
            <div class="bg-white rounded-xl shadow-md border border-gray-100 p-8 mb-6">
                <h3 class="text-xs font-black text-[#004225] uppercase tracking-widest mb-5">Form Details</h3>
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div class="col-span-2">
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">
                            Form Title <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="text" name="title"
                            value="{{ old('title', $form->title ?? '') }}"
                            required
                            class="w-full border-2 border-gray-100 rounded-xl px-4 py-3 text-[#003918] placeholder-gray-300 text-sm focus:border-[#00923F] focus:outline-none transition"
                            placeholder="e.g. Pre-Enrollment Form SY 2026–2027"
                        >
                        @error('title') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    {{-- School Year Selector --}}
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">
                            Active School Year <span class="text-red-500">*</span>
                        </label>
                        <select
                            name="school_year"
                            x-model="schoolYear"
                            class="w-full border-2 border-gray-100 rounded-xl px-4 py-3 text-[#003918] text-sm focus:border-[#00923F] focus:outline-none transition"
                        >
                            @foreach(['2024-2025','2025-2026','2026-2027','2027-2028','2028-2029'] as $sy)
                                <option value="{{ $sy }}" {{ old('school_year', $form->school_year ?? '2026-2027') === $sy ? 'selected' : '' }}>
                                    {{ $sy }}
                                </option>
                            @endforeach
                        </select>
                        <p class="text-xs text-gray-400 mt-1">
                            Responses will be tagged with this S.Y.
                        </p>
                    </div>

                    {{-- Description --}}
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">
                            Description
                        </label>
                        <textarea
                            name="description"
                            rows="3"
                            class="w-full border-2 border-gray-100 rounded-xl px-4 py-3 text-[#003918] placeholder-gray-300 text-sm focus:border-[#00923F] focus:outline-none transition resize-none"
                            placeholder="Optional instructions shown to students"
                        >{{ old('description', $form->description ?? '') }}</textarea>
                    </div>
                </div>
            </div>

            {{-- ── Field ID Cheat Sheet ────────────────────────────────── --}}
            <div class="bg-[#005288]/5 border border-[#005288]/20 rounded-xl p-5 mb-6">
                <p class="text-[#005288] font-black text-xs uppercase tracking-widest mb-2">
                    ⚠️ Field ID Reference — Must match sync.js field names exactly
                </p>
                <p class="text-[#005288]/75 text-xs mb-3">
                    The <span class="font-mono font-bold">Field ID</span> becomes the key in the Firestore response document.
                    sync.js reads <span class="font-mono">raw.lrn</span>, <span class="font-mono">raw.first_name</span>, etc.
                    <strong>Click any name to copy it.</strong>
                </p>
                <div class="grid grid-cols-4 gap-x-6 gap-y-1.5">
                    @foreach ([
                        'lrn','first_name','last_name','middle_name','extension_name','birthday','sex','age',
                        'place_of_birth','mother_tongue','school_year',
                        'curr_house_number','curr_street','curr_barangay','curr_city','curr_province','curr_zip_code','curr_country',
                        'is_perm_same_as_curr','perm_house_number','perm_street','perm_barangay','perm_city','perm_province','perm_zip_code','perm_country',
                        'mother_last_name','mother_first_name','mother_middle_name','mother_contact_number',
                        'father_last_name','father_first_name','father_middle_name','father_contact_number',
                        'guardian_last_name','guardian_first_name','guardian_middle_name','guardian_contact_number',
                    ] as $field)
                        <span
                            class="font-mono text-xs text-[#005288] hover:text-[#00923F] hover:font-bold cursor-pointer transition"
                            onclick="navigator.clipboard.writeText('{{ $field }}').then(()=>{ this.style.color='#00923F'; setTimeout(()=>this.style.color='',800) })"
                        >{{ $field }}</span>
                    @endforeach
                </div>
            </div>

            {{-- ── Questions List ──────────────────────────────────────── --}}
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-xs font-black text-[#004225] uppercase tracking-widest">
                    Questions (<span x-text="questions.length"></span>)
                </h3>
            </div>

            <div class="space-y-4 mb-6" id="questions-list">
                <template x-for="(question, index) in questions" :key="question._key">
                    <div class="bg-white rounded-xl shadow-md border border-gray-100 transition-all">

                        {{-- Question header bar --}}
                        <div class="flex items-center gap-3 px-5 py-3 bg-gray-50 border-b border-gray-100 rounded-t-xl">
                            {{-- Drag Handle --}}
                            <div class="drag-handle cursor-grab active:cursor-grabbing p-1 text-gray-400 hover:text-gray-600">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16" />
                                </svg>
                            </div>

                            {{-- Type badge --}}
                            <span class="text-[10px] font-black uppercase tracking-wider px-2 py-0.5 rounded-full border"
                                  :class="{
                                    'bg-blue-50 text-blue-700 border-blue-100':   ['text','date'].includes(question.type),
                                    'bg-purple-50 text-purple-700 border-purple-100': question.type === 'dropdown',
                                    'bg-teal-50 text-teal-700 border-teal-100':   question.type === 'radio',
                                    'bg-orange-50 text-orange-700 border-orange-100': question.type === 'checkbox',
                                    'bg-gray-100 text-gray-500 border-gray-200':  question.type === 'section',
                                  }"
                                  x-text="question.type.toUpperCase()">
                            </span>

                            <span class="flex-1 text-sm font-semibold text-[#003918] truncate"
                                  x-text="question.label || '(untitled question)'">
                            </span>

                            {{-- Collapse toggle --}}
                            <button type="button" @click="question._open = !question._open"
                                    class="p-1.5 text-gray-400 hover:text-[#00923F] rounded transition">
                                <svg class="w-4 h-4 transition-transform" :class="question._open ? 'rotate-180' : ''"
                                     fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>

                            {{-- Delete --}}
                            <button type="button" @click="removeQuestion(index)"
                                    class="p-1.5 text-gray-300 hover:text-red-500 hover:bg-red-50 rounded transition">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
                            </button>
                        </div>

                        {{-- Question body (collapsible) --}}
                        <div x-show="question._open" x-collapse class="p-6 rounded-b-xl">

                            {{-- ── SECTION BREAK ────────────────────────── --}}
                            <template x-if="question.type === 'section'">
                                <div>
                                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">Section Title</label>
                                    <textarea
                                        :name="`questions[${index}][label]`"
                                        x-model="question.label"
                                        @input="$el.style.height = 'auto'; $el.style.height = $el.scrollHeight + 'px'"
                                        x-init="$nextTick(() => { $el.style.height = 'auto'; $el.style.height = $el.scrollHeight + 'px' })"
                                        class="w-full border-2 border-gray-100 rounded-xl px-3 py-2.5 text-sm text-[#003918] placeholder-gray-300 focus:border-[#00923F] focus:outline-none transition resize-none overflow-hidden"
                                        placeholder="e.g. Guardian Information"
                                        rows="1"
                                    ></textarea>
                                    <input type="hidden" :name="`questions[${index}][type]`" value="section">
                                    <input type="hidden" :name="`questions[${index}][field_id]`" :value="question.field_id || 'section_' + index">
                                    <input type="hidden" :name="`questions[${index}][validation]`" value="none">
                                    <input type="hidden" :name="`questions[${index}][required]`" value="0">
                                    <p class="text-xs text-gray-400 mt-2">Section breaks visually divide your form into groups. They are not submitted as data.</p>
                                </div>
                            </template>

                            {{-- ── ALL OTHER QUESTION TYPES ─────────────── --}}
                            <template x-if="question.type !== 'section'">
                                <div class="space-y-4">

                                    {{-- Row 1: Label + Field ID --}}
                                    <div class="flex gap-4 items-start">
                                        <div class="flex-1">
                                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">
                                                Question Label <span class="text-red-500">*</span>
                                            </label>
                                            <textarea
                                                :name="`questions[${index}][label]`"
                                                x-model="question.label"
                                                required
                                                @input="$el.style.height = 'auto'; $el.style.height = $el.scrollHeight + 'px'"
                                                x-init="$nextTick(() => { $el.style.height = 'auto'; $el.style.height = $el.scrollHeight + 'px' })"
                                                class="w-full border-2 border-gray-100 rounded-xl px-3 py-2.5 text-sm text-[#003918] placeholder-gray-300 focus:border-[#00923F] focus:outline-none transition resize-none overflow-hidden"
                                                placeholder="e.g. Learner Reference Number"
                                                rows="1"
                                            ></textarea>
                                        </div>
                                        <div class="w-52">
                                            <label class="block text-xs font-bold text-[#005288] uppercase tracking-wider mb-1.5">
                                                Field ID <span class="text-red-500">*</span>
                                            </label>
                                            <input
                                                type="text"
                                                :name="`questions[${index}][field_id]`"
                                                x-model="question.field_id"
                                                required
                                                pattern="^[a-z_]+$"
                                                class="w-full border-2 border-[#005288]/20 rounded-xl px-3 py-2.5 text-sm text-[#005288] placeholder-[#005288]/50 focus:border-[#00923F] focus:outline-none transition font-mono bg-[#005288]/5"
                                                placeholder="e.g. lrn"
                                            >
                                            <p x-show="question.field_id && !/^[a-z_]+$/.test(question.field_id)"
                                               class="text-red-500 text-xs mt-1">Lowercase + underscores only.</p>
                                        </div>
                                    </div>

                                    {{-- Row 2: Type (hidden) / Compact Settings Row --}}
                                    <input type="hidden" :name="`questions[${index}][type]`" :value="question.type">

                                    <div class="flex items-center gap-8 py-3 px-4 bg-gray-50/50 rounded-xl border border-gray-100">
                                        {{-- Validation (Subtle Dropdown) --}}
                                        <div class="relative">
                                            <button type="button" @click="question._showValidation = !question._showValidation" 
                                                    class="flex items-center gap-1.5 text-[10px] font-black text-gray-400 uppercase tracking-widest hover:text-[#00923F] transition group">
                                                Validation: <span class="text-[#003918]" x-text="question.validation === 'none' ? 'None' : (question.validation === 'numeric_only' ? 'Numeric' : 'LRN')"></span>
                                                <svg class="w-3 h-3 transition-transform text-gray-300 group-hover:text-[#00923F]" :class="question._showValidation ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path d="M19 9l-7 7-7-7"/></svg>
                                            </button>
                                            
                                            <div x-show="question._showValidation" @click.away="question._showValidation = false" 
                                                 class="absolute left-0 mt-2 w-48 bg-white border border-gray-100 rounded-xl shadow-xl z-10 p-2 space-y-1">
                                                <template x-for="vopt in [{v:'none', l:'None'}, {v:'numeric_only', l:'Numeric Only'}, {v:'lrn_format', l:'LRN Format (12 digits)'}]">
                                                    <button type="button" @click="question.validation = vopt.v; question._showValidation = false" 
                                                            class="w-full text-left px-3 py-2 text-xs rounded-lg transition"
                                                            :class="question.validation === vopt.v ? 'bg-[#00923F]/10 text-[#00923F] font-bold' : 'text-gray-600 hover:bg-gray-50'">
                                                        <span x-text="vopt.l"></span>
                                                    </button>
                                                </template>
                                            </div>
                                            <input type="hidden" :name="`questions[${index}][validation]`" :value="question.validation">
                                        </div>

                                        {{-- Required (Inline Checkbox) --}}
                                        <label class="flex items-center gap-2 cursor-pointer group">
                                            <input type="hidden" :name="`questions[${index}][required]`" value="0">
                                            <input type="checkbox" :name="`questions[${index}][required]`" value="1"
                                                   x-model="question.required" class="w-4 h-4 rounded border-gray-300 text-[#00923F] focus:ring-[#00923F] accent-[#00923F]">
                                            <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest group-hover:text-[#004225] transition">Required</span>
                                        </label>

                                        {{-- Placeholder (Progressive Disclosure) --}}
                                        <div class="flex-1 flex items-center gap-4" x-show="['text'].includes(question.type)">
                                            <button type="button" @click="question._showPlaceholder = !question._showPlaceholder" 
                                                    class="flex items-center gap-1.5 text-[10px] font-black text-gray-400 uppercase tracking-widest hover:text-[#00923F] transition">
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                    <path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                </svg>
                                                <span x-text="question.placeholder ? 'Placeholder Set' : 'Add Placeholder'"></span>
                                            </button>
                                            
                                            <div x-show="question._showPlaceholder" x-transition class="flex-1">
                                                <input
                                                    type="text"
                                                    :name="`questions[${index}][placeholder]`"
                                                    x-model="question.placeholder"
                                                    class="w-full bg-transparent border-b-2 border-gray-100 py-1 text-sm text-[#003918] placeholder-gray-300 focus:border-[#00923F] focus:outline-none transition"
                                                    placeholder="Hint text shown in the input..."
                                                >
                                            </div>
                                        </div>
                                    </div>

                                    {{-- ── OPTIONS (dropdown / radio / checkbox) ── --}}
                                    <div x-show="['dropdown','radio','checkbox'].includes(question.type)">
                                        <div class="flex items-center justify-between mb-2">
                                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider">
                                                Options <span class="text-red-500">*</span>
                                            </label>
                                            <button type="button" @click="addOption(question)"
                                                    class="text-xs text-[#00923F] font-bold hover:underline">
                                                + Add Option
                                            </button>
                                        </div>

                                        <div class="space-y-2">
                                            <template x-for="(opt, oi) in question.options" :key="opt._okey">
                                                <div class="flex items-center gap-2">
                                                    <span class="text-xs text-gray-400 w-5 text-right flex-shrink-0" x-text="oi + 1 + '.'"></span>
                                                    <input
                                                        type="text"
                                                        :name="`questions[${index}][options][${oi}]`"
                                                        x-model="opt.value"
                                                        class="flex-1 border-2 border-gray-100 rounded-lg px-3 py-2 text-sm text-[#003918] placeholder-gray-300 focus:border-[#00923F] focus:outline-none transition"
                                                        placeholder="Option text"
                                                    >

                                                    {{-- Branch: show next section if this option is picked --}}
                                                    <div class="flex items-center gap-1">
                                                        <span class="text-xs text-gray-400 whitespace-nowrap">→ jump to</span>
                                                        <select
                                                            :name="`questions[${index}][branch][${oi}]`"
                                                            x-model="opt.branch"
                                                            class="border-2 border-gray-100 rounded-lg px-2 py-2 text-xs text-[#003918] focus:border-purple-400 focus:outline-none transition"
                                                        >
                                                            <option value="">Next</option>
                                                            <template x-for="(sec, si) in sectionBreaks(index)" :key="si">
                                                                <option :value="sec.index" x-text="sec.label"></option>
                                                            </template>
                                                            <option value="__end__">End of Form</option>
                                                        </select>
                                                    </div>

                                                    <button type="button" @click="removeOption(question, oi)"
                                                            class="p-1 text-gray-300 hover:text-red-400 rounded transition flex-shrink-0">
                                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
                                                    </button>
                                                </div>
                                            </template>
                                        </div>

                                        <p x-show="question.options.length === 0"
                                           class="text-xs text-red-400 mt-1">At least one option is required.</p>
                                    </div>

                                    {{-- LRN hint --}}
                                    <p x-show="question.validation === 'lrn_format'"
                                       class="text-xs text-[#005288]/75 font-medium">
                                        ⚠️ LRN Format enforces exactly 12 numeric digits.
                                    </p>
                                    <p x-show="question.field_id && question.field_id !== 'lrn' && question.validation === 'lrn_format'"
                                       class="text-xs text-red-500 font-medium">
                                        ⚠️ LRN Format should only be used with Field ID <span class="font-mono">lrn</span>.
                                    </p>

                                </div>
                            </template>

                        </div>{{-- /body --}}
                    </div>
                </template>
            </div>

            <div x-show="questions.length === 0"
                 class="bg-gray-50 border-2 border-dashed border-gray-200 rounded-xl p-12 text-center text-gray-400 text-sm mb-4">
                No questions yet. Click the button below to start adding questions.
            </div>

            {{-- Add Question Dropdown --}}
            <div class="relative mb-8" x-data="{ open: false }">
                <button
                    type="button"
                    @click="open = !open"
                    class="flex items-center gap-2 px-4 py-2.5 bg-white border-2 border-gray-100 rounded-xl text-[#003918] text-sm font-bold hover:border-[#00923F] hover:bg-[#00923F]/5 transition shadow-sm"
                >
                    <svg class="w-5 h-5 text-[#00923F]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path d="M12 4v16m8-8H4"/>
                    </svg>
                    Add Question
                </button>

                <div
                    x-show="open"
                    @click.away="open = false"
                    x-transition:enter="transition ease-out duration-100"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    class="absolute left-0 mt-2 w-56 bg-white border border-gray-100 rounded-xl shadow-xl z-50 p-2 space-y-1"
                >
                    @foreach([
                        ['type' => 'text',      'icon' => 'T',   'label' => 'Text Field'],
                        ['type' => 'date',      'icon' => '📅',  'label' => 'Date Picker'],
                        ['type' => 'dropdown',  'icon' => '▾',   'label' => 'Dropdown Menu'],
                        ['type' => 'radio',     'icon' => '◉',   'label' => 'Multiple Choice'],
                        ['type' => 'checkbox',  'icon' => '☑',   'label' => 'Checkboxes'],
                        ['type' => 'section',   'icon' => '§',   'label' => 'Section Break'],
                    ] as $btn)
                    <button
                        type="button"
                        @click="addQuestion('{{ $btn['type'] }}'); open = false;"
                        class="w-full flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-gray-50 text-[#003918] text-xs font-semibold transition"
                    >
                        <span class="w-6 h-6 rounded bg-gray-100 flex items-center justify-center text-[11px] font-black flex-shrink-0 text-gray-500">{{ $btn['icon'] }}</span>
                        {{ $btn['label'] }}
                    </button>
                    @endforeach
                </div>
            </div>

            @error('questions') <p class="text-red-500 text-sm mt-2">{{ $message }}</p> @enderror
            @error('school_year') <p class="text-red-500 text-sm mt-2">{{ $message }}</p> @enderror

            {{-- Submit row --}}
            <div class="mt-8 flex items-center gap-4">
                <button
                    type="submit"
                    class="bg-[#00923F] hover:bg-[#004225] text-white font-black uppercase tracking-widest px-8 py-3 rounded-xl transition shadow-lg shadow-green-200 text-xs"
                >
                    {{ isset($form) ? '💾 Save & Re-sync to Firestore' : '🚀 Create & Sync to Firestore' }}
                </button>
                <a href="{{ route('admin.forms.index') }}" class="text-gray-400 hover:text-gray-600 text-sm font-medium transition">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

<script>
function formBuilder(initialQuestions, initialSchoolYear) {
    return {
        schoolYear: initialSchoolYear || '2026-2027',
        showAddDropdown: false,

        questions: (initialQuestions || []).map((q, i) => ({
            ...q,
            _key:        q.id || ('new_' + i),
            _open:       true,
            _showPlaceholder: !!q.placeholder,
            _showValidation: false,
            field_id:    q.field_id || q.id || '',
            required:    Boolean(q.required),
            placeholder: q.placeholder || '',
            options:     (q.options || []).map((o, oi) => ({
                _okey: 'o_' + i + '_' + oi,
                value: typeof o === 'string' ? o : (o.value || ''),
                branch: typeof o === 'object' ? (o.branch || '') : '',
            })),
        })),

        initSortable() {
            new Sortable(document.getElementById('questions-list'), {
                animation: 150,
                handle: '.drag-handle',
                ghostClass: 'bg-green-50',
                onEnd: (evt) => {
                    const item = this.questions.splice(evt.oldIndex, 1)[0];
                    this.questions.splice(evt.newIndex, 0, item);
                }
            });
        },

        // ── Question CRUD ──────────────────────────────────────────────────

        addQuestion(type) {
            this.questions.push({
                _key:        'new_' + Date.now(),
                _open:       true,
                _showPlaceholder: false,
                _showValidation: false,
                id:          '',
                field_id:    '',
                label:       '',
                type:        type,
                validation:  'none',
                required:    false,
                placeholder: '',
                options:     type === 'section' ? [] : (
                    ['dropdown','radio','checkbox'].includes(type)
                        ? [{ _okey: 'o_' + Date.now(), value: '', branch: '' }]
                        : []
                ),
            });

            this.showAddDropdown = false;

            // Scroll to the new question after render
            this.$nextTick(() => {
                const list = document.getElementById('questions-list');
                if (list) {
                    list.lastElementChild?.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        },

        removeQuestion(index) {
            if (confirm('Are you sure you want to remove this question?')) {
                this.questions.splice(index, 1);
            }
        },

        // ── Option CRUD ────────────────────────────────────────────────────

        addOption(question) {
            question.options.push({
                _okey: 'o_' + Date.now(),
                value: '',
                branch: '',
            });
        },

        removeOption(question, oi) {
            question.options.splice(oi, 1);
        },

        // ── Branching helpers ──────────────────────────────────────────────

        /**
         * Returns an array of section-break questions that appear AFTER
         * the given question index — used to populate the "jump to" dropdown.
         */
        sectionBreaks(afterIndex) {
            return this.questions
                .map((q, i) => ({ ...q, index: i }))
                .filter(q => q.type === 'section' && q.index > afterIndex)
                .map(q => ({ index: q.index, label: q.label || `Section ${q.index + 1}` }));
        },

        // ── Submit ─────────────────────────────────────────────────────────

        submitForm(formEl) {
            // Validate field IDs
            const invalid = this.questions
                .filter(q => q.type !== 'section')
                .some(q => !q.field_id || !/^[a-z_]+$/.test(q.field_id));

            if (invalid) {
                alert('All questions must have a valid Field ID (lowercase letters and underscores only).');
                return;
            }

            // Validate options for choice questions
            const missingOptions = this.questions
                .filter(q => ['dropdown','radio','checkbox'].includes(q.type))
                .some(q => q.options.length === 0 || q.options.some(o => !o.value.trim()));

            if (missingOptions) {
                alert('All dropdown, radio, and checkbox questions must have at least one non-empty option.');
                return;
            }

            formEl.submit();
        },
    };
}
</script>
@endsection