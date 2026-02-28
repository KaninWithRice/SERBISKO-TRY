@extends('admin.layout')

@section('content')
    <div class="mt-8">
        <h2 class="text-[#003918] text-xl font-bold mb-6">Admin Overview</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div class="bg-white p-8 rounded-3xl shadow-sm">
                <p class="text-gray-500 font-semibold text-sm">Total Students</p>
                <h3 class="text-5xl font-extrabold text-[#003918] mt-2">1,240</h3>
                <p class="text-[#00923F] font-bold text-sm mt-4">+12 today</p>
            </div>

            <div class="bg-white p-8 rounded-3xl shadow-sm">
                <p class="text-gray-500 font-semibold text-sm">System Status</p>
                <div class="flex items-center gap-2 mt-2">
                    <span class="w-2 h-2 bg-green-500 rounded-full"></span>
                    <span class="text-[#00923F] font-bold">Active</span>
                </div>
                <p class="text-[#003918] font-bold text-sm mt-4">Manage Sync</p>
            </div>
        </div>
    </div>
@endsection