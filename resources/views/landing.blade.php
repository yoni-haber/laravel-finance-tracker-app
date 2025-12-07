<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="bg-gradient-to-b from-slate-950 via-slate-900 to-slate-950 text-slate-100 min-h-screen font-sans">
        <header class="max-w-6xl mx-auto px-6 py-8 flex items-center justify-between">
            <a href="{{ route('home') }}" class="flex items-center gap-3 font-semibold text-lg" wire:navigate>
                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-800/70 border border-slate-700">
                    <x-app-logo-icon class="size-8 fill-current" />
                </span>
                <div class="leading-tight">
                    <p class="text-slate-50">{{ config('app.name') }}</p>
                    <p class="text-slate-400 text-sm">Personal Finance Tracker</p>
                </div>
            </a>
            <nav class="flex items-center gap-3 text-sm">
                <a href="{{ route('login') }}" class="text-slate-300 hover:text-white transition-colors" wire:navigate>Log in</a>
                <a href="{{ route('register') }}" class="rounded-full bg-emerald-400 px-4 py-2 font-semibold text-emerald-950 shadow-lg shadow-emerald-500/20 hover:bg-emerald-300 transition-colors" wire:navigate>Get started</a>
            </nav>
        </header>

        <main class="max-w-6xl mx-auto px-6 pb-16">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                <div class="flex flex-col gap-6">
                    <span class="inline-flex w-fit items-center gap-2 rounded-full border border-emerald-500/50 bg-emerald-500/10 px-3 py-1 text-xs uppercase tracking-[0.2em] text-emerald-200">
                        Smarter money habits
                    </span>
                    <h1 class="text-4xl sm:text-5xl font-bold leading-tight text-white">
                        Own your financial story with clarity, calm, and confidence.
                    </h1>
                    <p class="text-lg text-slate-300 leading-relaxed">
                        Monitor transactions, set intentional budgets, and visualize your spending trends in one cohesive workspace designed for modern money management.
                    </p>
                    <div class="flex flex-wrap gap-3">
                        <a href="{{ route('register') }}" class="rounded-lg bg-emerald-400 px-5 py-3 text-base font-semibold text-emerald-950 shadow-xl shadow-emerald-500/30 hover:bg-emerald-300 transition-colors" wire:navigate>
                            Create your free account
                        </a>
                        <a href="{{ route('login') }}" class="rounded-lg border border-slate-700 px-5 py-3 text-base font-semibold text-slate-100 hover:border-slate-500 transition-colors" wire:navigate>
                            I already have an account
                        </a>
                    </div>
                    <div class="grid grid-cols-2 gap-4 mt-4">
                        <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-4 shadow-lg shadow-slate-900/50">
                            <p class="text-3xl font-bold text-white">$8,420</p>
                            <p class="text-sm text-slate-400">Average tracked savings per month</p>
                        </div>
                        <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-4 shadow-lg shadow-slate-900/50">
                            <p class="text-3xl font-bold text-white">4.9/5</p>
                            <p class="text-sm text-slate-400">User satisfaction across dashboards</p>
                        </div>
                    </div>
                </div>
                <div class="relative">
                    <div class="absolute inset-0 -translate-x-12 -translate-y-6 scale-110 rounded-[34px] bg-emerald-500/10 blur-3xl"></div>
                    <div class="relative rounded-[28px] border border-slate-800 bg-slate-900/80 p-6 shadow-2xl shadow-slate-900/70">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-slate-400">Current balance</p>
                                <p class="text-3xl font-semibold text-white">$12,760.45</p>
                            </div>
                            <span class="rounded-full bg-emerald-500/20 px-3 py-1 text-sm font-semibold text-emerald-200">+12.4%</span>
                        </div>
                        <div class="mt-6 grid grid-cols-3 gap-4 text-sm">
                            <div class="rounded-xl border border-slate-800 bg-slate-950/80 p-4">
                                <p class="text-slate-400">Budgets</p>
                                <p class="mt-2 text-xl font-semibold text-white">$3,200</p>
                                <p class="text-emerald-300">On track</p>
                            </div>
                            <div class="rounded-xl border border-slate-800 bg-slate-950/80 p-4">
                                <p class="text-slate-400">Spending</p>
                                <p class="mt-2 text-xl font-semibold text-white">$1,860</p>
                                <p class="text-amber-300">Review</p>
                            </div>
                            <div class="rounded-xl border border-slate-800 bg-slate-950/80 p-4">
                                <p class="text-slate-400">Investments</p>
                                <p class="mt-2 text-xl font-semibold text-white">$7,700</p>
                                <p class="text-emerald-300">Growing</p>
                            </div>
                        </div>
                        <div class="mt-6 h-48 rounded-2xl border border-slate-800 bg-linear-to-b from-slate-900 to-slate-950 p-4">
                            <div class="flex items-center justify-between text-sm text-slate-400">
                                <p>Spending overview</p>
                                <p>Last 90 days</p>
                            </div>
                            <div class="mt-4 flex h-28 items-end justify-between gap-2">
                                <div class="w-full rounded-t-lg bg-emerald-400/70" style="height: 50%"></div>
                                <div class="w-full rounded-t-lg bg-emerald-500/70" style="height: 70%"></div>
                                <div class="w-full rounded-t-lg bg-emerald-300/70" style="height: 35%"></div>
                                <div class="w-full rounded-t-lg bg-emerald-500/70" style="height: 80%"></div>
                                <div class="w-full rounded-t-lg bg-emerald-400/70" style="height: 60%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-16 grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-6 shadow-lg shadow-slate-900/70">
                    <p class="text-emerald-300 text-sm font-semibold">Automated visibility</p>
                    <h3 class="mt-2 text-xl font-semibold text-white">See every transaction in context</h3>
                    <p class="mt-3 text-slate-300 text-sm leading-relaxed">Consolidate accounts, categorize spending, and surface trends with the dashboards you use every day.</p>
                </div>
                <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-6 shadow-lg shadow-slate-900/70">
                    <p class="text-emerald-300 text-sm font-semibold">Intentional planning</p>
                    <h3 class="mt-2 text-xl font-semibold text-white">Budgets that adapt to you</h3>
                    <p class="mt-3 text-slate-300 text-sm leading-relaxed">Set guardrails that flex with real-world spending so you stay on track without sacrificing what matters.</p>
                </div>
                <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-6 shadow-lg shadow-slate-900/70">
                    <p class="text-emerald-300 text-sm font-semibold">Confident decisions</p>
                    <h3 class="mt-2 text-xl font-semibold text-white">Reports built for clarity</h3>
                    <p class="mt-3 text-slate-300 text-sm leading-relaxed">Visualize month-over-month changes, uncover growth opportunities, and celebrate the milestones you hit.</p>
                </div>
            </div>
        </main>
        @fluxScripts
    </body>
</html>
