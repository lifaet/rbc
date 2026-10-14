<x-layout>
    <div class="min-h-screen flex items-center justify-center bg-canvas px-4">
        <div class="w-full max-w-sm">
            <div class="flex flex-col items-center mb-6">
                <svg width="52" height="52" viewBox="0 0 32 32" fill="none">
                    <rect width="32" height="32" rx="9" fill="#5B5FEF"/>
                    <path d="M16 8 C 12 8, 9 11, 9 16 C 13 16, 16 13, 16 8 Z" fill="#F1F1FE"/>
                    <path d="M16 8 C 20 8, 23 11, 23 16 C 19 16, 16 13, 16 8 Z" fill="#C4C4F9"/>
                    <path d="M16 24 C 12 24, 9 21, 9 16 C 13 16, 16 19, 16 24 Z" fill="#C4C4F9"/>
                    <path d="M16 24 C 20 24, 23 21, 23 16 C 19 16, 16 19, 16 24 Z" fill="#F1F1FE"/>
                </svg>
                <h1 class="font-display font-bold text-2xl text-brand-900 mt-3">FLAXA</h1>
                <p class="text-xs text-gray-500 mt-1 uppercase tracking-widest">Sustainability Data Portal</p>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8">
                @if ($errors->any())
                    <div class="mb-4 p-3 rounded-lg bg-red-50 text-alert text-sm border border-red-100">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input type="email" name="email" value="{{ old('email') }}" required autofocus
                            class="w-full rounded-lg border-gray-300 focus:ring-brand-600 focus:border-brand-600" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                        <input type="password" name="password" required
                            class="w-full rounded-lg border-gray-300 focus:ring-brand-600 focus:border-brand-600" />
                    </div>
                    <label class="flex items-center gap-2 text-sm text-gray-600">
                        <input type="checkbox" name="remember" class="rounded border-gray-300 text-brand-700" /> Remember me
                    </label>
                    <button type="submit" class="w-full py-2.5 rounded-lg bg-brand-700 text-white font-medium hover:bg-brand-800 transition-colors">
                        Log In
                    </button>
                </form>
            </div>
        </div>
    </div>
</x-layout>
