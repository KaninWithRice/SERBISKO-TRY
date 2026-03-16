@props(['name', 'label', 'description', 'options' => [], 'selected' => ''])

<div x-data="{ edited: false }" {{ $attributes->merge(['class' => '']) }}>
    <label class="text-sm font-bold text-[#003918]/70 uppercase tracking-tight">{{ $label }}</label>
    <p class="text-[#003918]/50 font-regular mb-3 text-xs">{{ $description }}</p>
    
    <div class="relative">
        <select name="{{ $name }}" 
            @change="edited = true"
            class="w-full bg-[#f1f3f5] border-none rounded-lg px-4 py-3 pr-16 text-base text-gray-700 focus:bg-white focus:border-[#00923F] focus:ring-2 focus:ring-[#1a8a44] appearance-none cursor-pointer transition-all duration-200">
            
            <option value="" disabled {{ empty($selected) ? 'selected' : '' }}>Select School Year</option>
            
            @foreach($options as $value)
                <option value="{{ $value }}" {{ old($name, $selected) == $value ? 'selected' : '' }}>
                    {{ $value }}
                </option>
            @endforeach
        </select>
        
        {{-- Custom Green Arrow --}}
        <div class="absolute inset-y-0 right-6 flex items-center pointer-events-none text-[#1a8a44]">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"></path>
            </svg>
        </div>
    </div>

    @error($name)
        <p x-show="!edited" class="text-red-600 text-xs mt-2 font-medium italic">{{ $message }}</p>
    @enderror
</div>