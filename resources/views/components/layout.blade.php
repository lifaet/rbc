<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'ESG Data Platform' }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50:  '#F1F1FE',
                            100: '#E1E1FC',
                            200: '#C4C4F9',
                            600: '#5B5FEF',
                            700: '#4338CA',
                            800: '#332D9E',
                            900: '#211D63',
                        },
                        alert: '#F0483E',
                        canvas: '#F7F7FB',
                    },
                    fontFamily: {
                        display: ['"Space Grotesk"', 'sans-serif'],
                        sans: ['Inter', 'sans-serif'],
                    },
                }
            }
        }
    </script>

    <style>
        body { font-family: 'Inter', sans-serif; background: #F7F7FB; }
        h1, h2, h3, .font-display { font-family: '"Space Grotesk"', sans-serif; }

        /* Signature element: an asymmetric underline accent instead of a generic rule */
        .accent-mark {
            display: inline-block;
            width: 28px;
            height: 4px;
            border-radius: 2px;
            background: linear-gradient(90deg, #5B5FEF 0%, #C4C4F9 100%);
            margin-top: 6px;
        }
    </style>

    @livewireStyles
</head>
<body class="min-h-screen">

    @auth
    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <aside class="w-64 shrink-0 bg-brand-900 text-white flex flex-col">
            <div class="px-6 py-6">
                <div class="flex items-center gap-2.5">
                    <svg width="32" height="32" viewBox="0 0 32 32" fill="none">
                        <rect width="32" height="32" rx="9" fill="#5B5FEF"/>
                        <path d="M16 8 C 12 8, 9 11, 9 16 C 13 16, 16 13, 16 8 Z" fill="#F1F1FE"/>
                        <path d="M16 8 C 20 8, 23 11, 23 16 C 19 16, 16 13, 16 8 Z" fill="#C4C4F9"/>
                        <path d="M16 24 C 12 24, 9 21, 9 16 C 13 16, 16 19, 16 24 Z" fill="#C4C4F9"/>
                        <path d="M16 24 C 20 24, 23 21, 23 16 C 19 16, 16 19, 16 24 Z" fill="#F1F1FE"/>
                    </svg>
                    <div class="leading-tight">
                        <p class="font-display font-bold text-lg tracking-tight">ESG Data Platform</p>
                        <p class="text-[10.5px] text-brand-200 tracking-widest uppercase">ESG Data</p>
                    </div>
                </div>
            </div>

            <nav class="flex-1 px-4 mt-4 space-y-1 text-sm">
                @if (auth()->user()->isAdmin())
                    <a href="{{ route('admin.dashboard') }}"
                       class="flex items-center gap-2.5 px-3 py-2.5 rounded-lg transition-colors {{ request()->routeIs('admin.dashboard') ? 'bg-brand-700 font-semibold text-white' : 'hover:bg-white/5 text-brand-100' }}">
                        <span>Dashboard</span>
                    </a>
                    <a href="{{ route('admin.cycles') }}"
                       class="flex items-center gap-2.5 px-3 py-2.5 rounded-lg transition-colors {{ request()->routeIs('admin.cycles') || request()->routeIs('admin.questions') ? 'bg-brand-700 font-semibold text-white' : 'hover:bg-white/5 text-brand-100' }}">
                        <span>Survey Cycles</span>
                    </a>
                    <a href="{{ route('admin.exports.submissions.excel') }}" class="flex items-center gap-2.5 px-3 py-2.5 rounded-lg transition-colors hover:bg-white/5 text-brand-100"><span>Export Excel (all)</span></a>
                    <a href="{{ route('admin.users') }}" class="flex items-center gap-2.5 px-3 py-2.5 rounded-lg transition-colors {{ request()->routeIs('admin.users') ? 'bg-brand-700 font-semibold text-white' : 'hover:bg-white/5 text-brand-100' }}"><span>Users &amp; Roles</span></a>
                    <a href="{{ route('admin.factories') }}"
                       class="flex items-center gap-2.5 px-3 py-2.5 rounded-lg transition-colors {{ request()->routeIs('admin.factories') ? 'bg-brand-700 font-semibold text-white' : 'hover:bg-white/5 text-brand-100' }}">
                        <span>Factories</span>
                    </a>
                @else
                    <a href="{{ route('survey.index') }}"
                       class="flex items-center gap-2.5 px-3 py-2.5 rounded-lg transition-colors {{ request()->routeIs('survey.index') ? 'bg-brand-700 font-semibold text-white' : 'hover:bg-white/5 text-brand-100' }}">
                        <span>My Survey</span>
                    </a>
                @endif
                <a href="{{ route('profile.edit') }}"
                   class="flex items-center gap-2.5 px-3 py-2.5 rounded-lg transition-colors {{ request()->routeIs('profile.edit') ? 'bg-brand-700 font-semibold text-white' : 'hover:bg-white/5 text-brand-100' }}">
                    <span>My Profile</span>
                </a>
            </nav>

            <div class="px-6 py-5 border-t border-white/10">
                <p class="text-xs text-brand-100 mb-2 truncate">{{ auth()->user()->name }}</p>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-xs text-brand-200 hover:text-white underline underline-offset-2">
                        Log out
                    </button>
                </form>
            </div>
        </aside>

        <!-- Main content -->
        <div class="flex-1 min-w-0">
            <main>
                {{ $slot }}
            </main>
        </div>
    </div>
    @else
        <main>
            {{ $slot }}
        </main>
    @endauth

    @livewireScripts
</body>
</html>
