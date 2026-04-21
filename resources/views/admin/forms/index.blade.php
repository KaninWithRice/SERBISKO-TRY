@extends('admin.layout')

@section('page_title', 'Form Builder')

@section('content')
<div x-data="{}" class="py-8">

    {{-- Notifications --}}
    @if(session('success'))
        <div class="mb-6 p-4 bg-green-500 text-white rounded-lg shadow-lg">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="mb-6 p-4 bg-red-600 text-white rounded-lg shadow-lg">
            {{ session('error') }}
        </div>
    @endif

    {{-- Header Card --}}
    <div class="w-full h-[100px] bg-[#F7FBF9]/50 rounded-[10px] shadow-[0_3px_3px_0_rgba(0,0,0,0.25)] flex items-center px-12 justify-between mb-10">
        <div class="flex flex-col justify-center">
            <div class="flex items-center gap-4">
                <div class="w-4 h-4 bg-[#00923F] rounded-full shrink-0"></div>
                <h2 class="text-[#333333] text-3xl font-extrabold tracking-normal uppercase leading-none">
                    Enrollment Forms
                </h2>
            </div>
            <div class="ml-8 mt-1">
                <p class="text-[#5F748D] text-sm font-medium leading-tight">
                    Build forms for students · Sync schemas to Firestore · Generate QR codes
                </p>
            </div>
        </div>
        <a href="{{ route('admin.forms.create') }}"
           class="inline-flex items-center gap-2 bg-[#00923F] hover:bg-[#004225] text-white text-sm font-black uppercase tracking-widest px-6 py-3 rounded-xl transition shadow-lg shadow-green-200">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path d="M12 5v14M5 12h14"/>
            </svg>
            New Form
        </a>
    </div>

    {{-- Forms Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        @forelse($forms as $form)
            <div class="bg-white rounded-2xl shadow-sm hover:shadow-xl transition-all duration-300 border border-gray-100 flex flex-col h-full group">
                {{-- Card Header --}}
                <div class="p-6 pb-4 flex-grow">
                    <div class="flex justify-between items-start mb-4">
                        <div class="w-10 h-10 bg-green-50 rounded-xl flex items-center justify-center text-[#00923F] group-hover:bg-[#00923F] group-hover:text-white transition-colors duration-300">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <div class="flex flex-col items-end">
                            @if($form->firestore_doc_id)
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-bold bg-green-50 text-green-700 border border-green-100 uppercase tracking-wider">
                                    <span class="w-1.5 h-1.5 bg-green-500 rounded-full"></span> Synced
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-100 uppercase tracking-wider">
                                    <span class="w-1.5 h-1.5 bg-amber-500 rounded-full animate-pulse"></span> Offline
                                </span>
                            @endif
                        </div>
                    </div>

                    <h3 class="text-xl font-bold text-[#333333] mb-2 line-clamp-1 uppercase">{{ $form->title }}</h3>
                    <p class="text-sm text-[#5F748D] line-clamp-2 min-h-[40px] leading-relaxed">
                        {{ $form->description ?? 'No description provided for this form.' }}
                    </p>

                    <div class="mt-6 flex items-center gap-4">
                        <div class="flex flex-col">
                            <span class="text-[10px] uppercase font-bold text-[#94A3B8] tracking-widest mb-1">Questions</span>
                            <span class="text-sm font-bold text-[#003918]">
                                {{ count($form->schema) }} fields
                            </span>
                        </div>
                        <div class="h-8 w-px bg-gray-100"></div>
                        <div class="flex flex-col">
                            <span class="text-[10px] uppercase font-bold text-[#94A3B8] tracking-widest mb-1">Last Update</span>
                            <span class="text-sm font-bold text-[#003918]">
                                {{ $form->updated_at->diffForHumans() }}
                            </span>
                        </div>
                    </div>
                </div>

                {{-- Card Footer Actions --}}
                <div class="p-6 pt-0 bg-gray-50/50 rounded-b-2xl border-t border-gray-50 mt-auto">
                    <div class="flex items-center gap-2 pt-4">
                        <a href="{{ route('admin.forms.show', $form) }}"
                           class="flex-1 bg-white hover:bg-green-50 text-[#00923F] border border-green-100 px-4 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-widest transition text-center">
                            Share / QR
                        </a>
                        <a href="{{ route('admin.forms.edit', $form) }}"
                           class="flex-1 bg-[#00923F] hover:bg-[#004225] text-white px-4 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-widest transition text-center shadow-lg shadow-green-100">
                            Edit
                        </a>
                        <form method="POST" action="{{ route('admin.forms.destroy', $form) }}"
                              onsubmit="return confirm('Archive this form? It will be soft-deleted.')"
                              class="shrink-0">
                            @csrf @method('DELETE')
                            <button class="w-10 h-10 flex items-center justify-center bg-white hover:bg-red-50 text-gray-400 hover:text-red-500 border border-gray-100 rounded-xl transition">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full py-20 bg-white rounded-3xl border-2 border-dashed border-gray-100 flex flex-col items-center justify-center text-center">
                <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mb-6">
                    <svg class="w-10 h-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6M9 16h6M9 8h6M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-400 mb-2 uppercase tracking-wide">No Forms Created Yet</h3>
                <p class="text-gray-400 text-sm mb-8 max-w-xs mx-auto italic">Start by building your first enrollment form schema to begin receiving applications.</p>
                <a href="{{ route('admin.forms.create') }}"
                   class="inline-flex items-center gap-2 bg-[#00923F] hover:bg-[#004225] text-white text-xs font-black uppercase tracking-widest px-8 py-4 rounded-2xl transition shadow-xl shadow-green-100">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                        <path d="M12 5v14M5 12h14"/>
                    </svg>
                    Create First Form
                </a>
            </div>
        @endforelse
    </div>

    @if($forms->hasPages())
        <div class="mt-6">{{ $forms->links() }}</div>
    @endif

</div>
@endsection
