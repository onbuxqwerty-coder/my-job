# Prompt 8.5: THE HARD OVERRIDE (Paste this exact code)

**Claude, stop trying to design. You are failing at layouts.**. 
Replace the ENTIRE `index.blade.php` content with this exact structural template. Use these specific Tailwind classes.

---

## 1. PAGE STRUCTURE
<div class="min-h-screen bg-[#F8FAFC] antialiased">
    <x-navigation />

    <main class="max-w-5xl mx-auto px-4 py-12">
        <div class="text-center mb-12">
            <h1 class="text-5xl font-black text-slate-900 mb-4 tracking-tight">Знайдіть роботу</h1>
            <p class="text-lg text-slate-500 mb-8">50 000+ актуальних вакансій</p>
            
            <div class="max-w-3xl mx-auto relative group">
                <input wire:model.live.debounce.300ms="search" type="text" 
                    class="w-full h-16 pl-14 pr-6 rounded-2xl border-none bg-white shadow-xl focus:ring-2 focus:ring-indigo-600 transition-all text-lg"
                    placeholder="Посада, компанія...">
                <div class="absolute left-5 top-5 text-slate-400">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
            </div>
        </div>

        <div class="flex flex-col lg:flex-row gap-8">
            <aside class="w-full lg:w-72 shrink-0">
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200 sticky top-24">
                    <h3 class="text-sm font-bold text-slate-400 uppercase tracking-widest mb-6">Фільтри</h3>
                    
                    <div class="space-y-8">
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-3">Категорія</label>
                            <select wire:model.live="category_id" class="w-full rounded-xl border-slate-200 bg-slate-50 focus:bg-white focus:ring-indigo-600">
                                <option value="">Всі категорії</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-3">Тип зайнятості</label>
                            <div class="flex flex-wrap gap-2">
                                @foreach(\App\Enums\EmploymentType::cases() as $type)
                                    <label class="cursor-pointer">
                                        <input type="radio" wire:model.live="employment_type" value="{{ $type->value }}" class="hidden peer">
                                        <span class="px-3 py-2 rounded-lg border border-slate-200 text-sm block peer-checked:bg-indigo-600 peer-checked:text-white peer-checked:border-indigo-600 transition-all">
                                            {{ $type->label() }}
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </aside>

            <div class="flex-1 space-y-4">
                @forelse($vacancies as $vacancy)
                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 hover:shadow-md transition-all flex items-start justify-between group">
                        <div class="flex gap-5">
                            <div class="w-14 h-14 bg-indigo-50 rounded-xl flex items-center justify-center text-indigo-600 font-bold text-xl shrink-0">
                                {{ mb_substr($vacancy->company->name, 0, 1) }}
                            </div>
                            <div>
                                <h2 class="text-xl font-bold text-slate-900 group-hover:text-indigo-600 transition-colors">
                                    {{ $vacancy->title }}
                                </h2>
                                <p class="text-indigo-600 font-medium mb-3">{{ $vacancy->company->name }}</p>
                                <div class="flex gap-2">
                                    <span class="px-3 py-1 bg-slate-100 text-slate-600 rounded-lg text-xs font-bold">{{ $vacancy->location }}</span>
                                    <span class="px-3 py-1 bg-emerald-50 text-emerald-700 rounded-lg text-xs font-bold uppercase">{{ $vacancy->employment_type->label() }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="text-right shrink-0">
                            <div class="text-xl font-black text-slate-900 tracking-tight">
                                {{ number_format($vacancy->salary_from, 0, '.', ' ') }} ₴
                            </div>
                            <div class="text-xs text-slate-400 mt-1">{{ $vacancy->published_at->diffForHumans() }}</div>
                        </div>
                    </div>
                @empty
                    @endforelse
                
                <div class="pt-6">
                    {{ $vacancies->links() }}
                </div>
            </div>
        </div>
    </main>

    <x-footer />
</div>