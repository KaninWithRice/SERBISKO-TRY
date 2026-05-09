<header class="flex justify-between items-center px-16 py-8 bg-transparent w-full">
    <style>
        [x-cloak] { display: none !important; }
    </style>
    <div class="page-title">
        <h1 class="text-[#00923F] text-5xl font-[800] tracking-tight">
            @yield('page_title', 'Dashboard') 
        </h1>
    </div>

    <div class="flex-1 flex justify-end px-6">
        @yield('header_content')
    </div>

    <div class="flex items-center gap-8">
        <div x-data="{ 
            open: false, 
            notifications: [], 
            unreadCount: 0,
            loading: false,
            fetchNotifications() {
                this.loading = true;
                fetch('{{ route('admin.notifications.index') }}')
                    .then(res => res.json())
                    .then(data => {
                        this.notifications = data.notifications;
                        this.unreadCount = data.count;
                        this.loading = false;
                    });
            },
            init() {
                this.fetchNotifications();
                // Refresh every 30 seconds
                setInterval(() => this.fetchNotifications(), 30000);
            }
        }" class="relative">
            <button @click="open = !open" 
                    class="text-[#00923F] p-2 hover:bg-[#00923F]/5 rounded-full transition-colors relative focus:outline-none">
                <svg class="w-9 h-9 fill-current" viewBox="0 0 24 24">
                    <path d="M12 22a2 2 0 0 0 2-2h-4a2 2 0 0 0 2 2m6-6v-5c0-3.07-1.63-5.64-4.5-6.32V4a1.5 1.5 0 0 0-3 0v.68C7.64 5.36 6 7.92 6 11v5l-2 2v1h16v-1z"/>
                </svg>
                <template x-if="unreadCount > 0">
                    <span class="absolute top-2.5 right-2.5 w-4 h-4 bg-red-500 text-white text-[10px] flex items-center justify-center rounded-full border-2 border-[#F1F3F2] font-bold" x-text="unreadCount"></span>
                </template>
            </button>

            {{-- Notification Dropdown --}}
            <div x-show="open" 
                x-cloak
                @click.away="open = false"
                x-transition:enter="transition ease-out duration-100"
                x-transition:enter-start="transform opacity-0 scale-95"
                x-transition:enter-end="transform opacity-100 scale-100"
                class="absolute right-0 mt-2 w-80 bg-white rounded-2xl shadow-2xl z-50 border border-gray-100 overflow-hidden">
                
                <div class="px-5 py-4 border-b border-gray-50 flex justify-between items-center bg-gray-50/50">
                    <div>
                        <h3 class="text-[#003918] font-bold text-sm">Notifications</h3>
                        <p class="text-[10px] text-gray-500 uppercase tracking-wider font-semibold">Latest updates</p>
                    </div>
                    <template x-if="unreadCount > 0">
                        <span class="bg-[#00923F]/10 text-[#00923F] text-[10px] px-2 py-0.5 rounded-full font-bold" x-text="unreadCount + ' New'"></span>
                    </template>
                </div>

                <div class="max-h-96 overflow-y-auto custom-scrollbar">
                    <template x-if="loading && notifications.length === 0">
                        <div class="p-8 text-center">
                            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-[#00923F] mx-auto"></div>
                        </div>
                    </template>

                    <template x-if="!loading && notifications.length === 0">
                        <div class="p-8 text-center">
                            <svg class="w-12 h-12 text-gray-200 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                            <p class="text-gray-400 text-sm font-medium">No new notifications</p>
                        </div>
                    </template>

                    <template x-for="notification in notifications" :key="notification.id">
                        <a :href="notification.link" 
                           class="block px-5 py-4 hover:bg-green-50/50 transition-colors border-b border-gray-50 last:border-0 group">
                            <div class="flex gap-3">
                                <div class="flex-shrink-0 mt-1">
                                    <template x-if="notification.type === 'sync_conflict'">
                                        <div class="w-8 h-8 rounded-full bg-amber-100 flex items-center justify-center">
                                            <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                                        </div>
                                    </template>
                                    <template x-if="notification.type === 'verification'">
                                        <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center">
                                            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                        </div>
                                    </template>
                                    <template x-if="notification.type === 'rejected_paper'">
                                        <div class="w-8 h-8 rounded-full bg-red-100 flex items-center justify-center">
                                            <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                        </div>
                                    </template>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h4 class="text-[#003918] font-bold text-xs group-hover:text-[#00923F] transition-colors" x-text="notification.title"></h4>
                                    <p class="text-gray-500 text-[11px] mt-0.5 line-clamp-2" x-text="notification.description"></p>
                                    <p class="text-[10px] text-gray-400 mt-1 font-semibold" x-text="notification.time"></p>
                                </div>
                            </div>
                        </a>
                    </template>
                </div>

                <div class="p-3 bg-gray-50/50 border-t border-gray-50">
                    <a href="{{ route('admin.action-center') }}" class="block text-center text-[#00923F] text-[10px] font-bold uppercase tracking-widest hover:underline py-1">
                        View All Activities
                    </a>
                </div>
            </div>
        </div>

        <div class="h-10 w-[1.5px] bg-gray-300/60"></div>

        <div x-data="{ open: false }" class="relative">
            <button @click="open = !open" @click.away="open = false" 
                    class="flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-[#00923F]/5 transition-all duration-200 focus:outline-none group">
                
                <div class="text-right">
                    <p class="text-[#003918] font-bold text-sm leading-tight">
                        {{ auth()->user()?->first_name ?? 'Guest' }} {{ auth()->user()?->last_name }}
                    </p>
                    <p class="text-gray-500 text-[11px] font-semibold uppercase tracking-wide">
                        {{ str_replace('_', ' ', auth()->user()?->role ?? 'No Role') }}
                    </p>
                </div>

                <svg class="w-5 h-5 text-gray-400 group-hover:text-[#00923F] transition-transform duration-200" 
                    :class="{'rotate-180': open}"
                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" />
                </svg>
            </button>

            <div x-show="open" 
                x-cloak
                x-transition:enter="transition ease-out duration-100"
                x-transition:enter-start="transform opacity-0 scale-95"
                x-transition:enter-end="transform opacity-100 scale-100"
                class="absolute right-0 mt-2 w-52 bg-white rounded-xl shadow-xl py-2 z-50 border border-gray-100 overflow-hidden">
                
                <div class="px-4 py-2 border-b border-gray-50 mb-1">
                    <p class="text-[10px] uppercase tracking-wider text-gray-400 font-bold">Account Actions</p>
                </div>

                <a href="{{ route('admin.accountsettings') }}" class="flex items-center px-4 py-2.5 text-sm text-gray-700 hover:bg-green-50 hover:text-[#00923F] transition-colors">
                    <svg class="w-4 h-4 mr-3 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    Change Password
                </a>
                
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="flex w-full items-center px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 transition-colors">
                        <svg class="w-4 h-4 mr-3 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                        Log Out
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>