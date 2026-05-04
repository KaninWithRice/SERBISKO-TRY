<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SerbIsko - First Login</title>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Google Sans', sans-serif; }
        .custom-gradient { background: linear-gradient(90deg, #1b5e20 0%, #2e7d32 40%, #f3f4f6 100%); }
    </style>
</head>
<body class="custom-gradient min-h-screen flex items-center justify-center p-8">

    <div class="w-full max-w-md bg-white rounded-3xl shadow-2xl overflow-hidden mb-20">
        <div class="bg-blue-900 px-8 py-6 text-white text-center">
            <h1 class="text-2xl font-bold">Welcome, Ka-Compre!</h1>
            <p class="text-sm opacity-80 mt-1">Please set your new account password to continue.</p>
        </div>

        <div class="p-8">
            <form action="{{ url('/first-login/update') }}" method="POST" class="space-y-6" 
                autocomplete="off"
                x-data="{ loading: false, showNew: false, showConfirm: false }" @submit="loading = true">
                @csrf
                
                <div class="space-y-1">
                    <label class="text-xs font-bold text-gray-500 uppercase tracking-wider ml-1">New Password</label>
                    <div class="relative">
                        <input :type="showNew ? 'text' : 'password'" name="new_password" required
                            autocomplete="new-password"
                            class="w-full px-5 py-3 rounded-xl border-2 border-gray-100 bg-gray-50 focus:border-blue-900 focus:bg-white outline-none transition-all pr-12"
                            placeholder="••••••••">
                        <button type="button" @click="showNew = !showNew" class="absolute inset-y-0 right-0 pr-4 flex items-center text-blue-900">
                            <svg x-show="!showNew" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                            <svg x-show="showNew" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a10.025 10.025 0 014.132-5.403m5.417-1.071A10.05 10.05 0 0112 5c4.478 0 8.268 2.943 9.542 7a10.025 10.025 0 01-4.132 5.403m-5.417 1.071L17.25 17.25M3.75 3.75l16.5 16.5" /></svg>
                        </button>
                    </div>
                    @error('new_password')
                        <p class="text-red-600 text-[10px] mt-1 italic ml-1 font-bold">{{ $message }}</p>
                    @enderror
                </div>

                <div class="space-y-1">
                    <label class="text-xs font-bold text-gray-500 uppercase tracking-wider ml-1">Confirm Password</label>
                    <div class="relative">
                        <input :type="showConfirm ? 'text' : 'password'" name="new_password_confirmation" required
                            autocomplete="new-password"
                            class="w-full px-5 py-3 rounded-xl border-2 border-gray-100 bg-gray-50 focus:border-blue-900 focus:bg-white outline-none transition-all pr-12"
                            placeholder="••••••••">
                        <button type="button" @click="showConfirm = !showConfirm" class="absolute inset-y-0 right-0 pr-4 flex items-center text-blue-900">
                            <svg x-show="!showConfirm" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                            <svg x-show="showConfirm" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a10.025 10.025 0 014.132-5.403m5.417-1.071A10.05 10.05 0 0112 5c4.478 0 8.268 2.943 9.542 7a10.025 10.025 0 01-4.132 5.403m-5.417 1.071L17.25 17.25M3.75 3.75l16.5 16.5" /></svg>
                        </button>
                    </div>
                </div>

                <div class="bg-blue-50 rounded-xl p-4 border border-blue-100">
                    <h3 class="text-blue-900 text-[10px] font-black uppercase tracking-widest mb-2 flex items-center">
                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path></svg>
                        Security Requirement
                    </h3>
                    <ul class="text-[10px] text-blue-800 space-y-1">
                        <li class="flex items-center"><svg class="w-2 h-2 mr-1" fill="currentColor" viewBox="0 0 8 8"><circle cx="4" cy="4" r="3"></circle></svg> Minimum 8 characters</li>
                        <li class="flex items-center"><svg class="w-2 h-2 mr-1" fill="currentColor" viewBox="0 0 8 8"><circle cx="4" cy="4" r="3"></circle></svg> Must include uppercase & lowercase</li>
                        <li class="flex items-center"><svg class="w-2 h-2 mr-1" fill="currentColor" viewBox="0 0 8 8"><circle cx="4" cy="4" r="3"></circle></svg> Must include numbers & symbols</li>
                    </ul>
                </div>

                <div class="pt-2">
                    <button type="submit" :disabled="loading"
                        class="w-full bg-blue-900 hover:bg-blue-800 text-white font-bold py-4 rounded-xl shadow-lg transition transform hover:-translate-y-0.5 active:translate-y-0 flex items-center justify-center space-x-2 disabled:opacity-70">
                        <svg x-show="loading" class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span x-text="loading ? 'Updating Password...' : 'Save & Logout'"></span>
                    </button>
                    <p class="text-center text-[10px] text-gray-400 mt-4 italic">
                        After updating, you will be required to log in again with your new password.
                    </p>
                </div>
            </form>
        </div>
    </div>

    @include('includes.keyboard')
</body>
</html>