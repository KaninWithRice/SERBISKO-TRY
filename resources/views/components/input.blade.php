@props(['name', 'label', 'description', 'placeholder', 'value' => '', 'type' => 'text'])

<div x-data="{ edited: false }" {{ $attributes->merge(['class' => '']) }}>
    <label class="text-sm font-bold text-[#003918]/70 uppercase tracking-tight">{{ $label }}</label>
    <p class="text-[#003918]/50 font-regular mb-3 text-xs">{{ $description }}</p>
    
    <input type="{{ $type }}" 
        name="{{ $name }}"
        @input="edited = true"
        value="{{ old($name, $value) }}"
        placeholder="{{ $placeholder }}" 
        class="w-full bg-[#f1f3f5] border-none rounded-lg px-4 py-3 text-gray-700 focus:ring-2 focus:ring-[#1a8a44] transition-all pr-12">
    
    @error($name)
        <p x-show="!edited" class="text-red-600 text-xs mt-2 font-medium italic">{{ $message }}</p>
    @enderror
</div>