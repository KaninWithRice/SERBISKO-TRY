@extends('admin.layout')

@section('page_title', 'Sync Configuration')

@section('content')

<div class="max-w-full mx-auto p-4 space-y-6 font-inter">
    
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>

    {{-- Data Source Connection Section --}}
    @include('admin.systemconfigurationpage.partials.sourceconnection')

    {{-- System Health Bar (Static or dynamic metrics) --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        @include('admin.systemconfigurationpage.partials.healthbar')
    </div>

    {{-- Information Alignment Matrix Section --}}
    @include('admin.systemconfigurationpage.partials.alignmentmatrix')

</div>
@endsection