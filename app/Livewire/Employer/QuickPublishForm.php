<?php

declare(strict_types=1);

namespace App\Livewire\Employer;

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Vacancy;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Livewire\Component;

final class QuickPublishForm extends Component
{
    public bool    $show        = false;
    public string  $title       = '';
    public ?int    $category_id = null;
    public string  $city_id     = '';
    public ?int    $salary_from = null;

    #[On('open-quick-publish')]
    public function open(): void
    {
        $this->show = true;
    }

    /** @return array<string, string> */
    private function validationRules(): array
    {
        return [
            'title'       => 'required|string|min:5|max:255',
            'category_id' => 'required|integer|exists:categories,id',
            'city_id'     => 'required|exists:cities,id',
            'salary_from' => 'nullable|integer|min:100|max:999999',
        ];
    }

    /** @return array<string, string> */
    private function validationMessages(): array
    {
        return [
            'title.required'       => 'Введіть назву посади',
            'title.min'            => 'Назва посади повинна містити мінімум 5 символів',
            'title.max'            => 'Назва посади не може перевищувати 255 символів',
            'category_id.required' => 'Виберіть категорію',
            'category_id.exists'   => 'Вибрана категорія не існує',
            'city_id.required'     => 'Виберіть місто',
            'city_id.exists'       => 'Вибране місто не існує',
            'salary_from.integer'  => 'Зарплата повинна бути числом',
            'salary_from.min'      => 'Зарплата мінімум 100 грн',
            'salary_from.max'      => 'Зарплата не може перевищувати 999999 грн',
        ];
    }

    public function publish(): void
    {
        $validated = $this->validate($this->validationRules(), $this->validationMessages());

        if (auth()->check() && auth()->user()->role === UserRole::Employer) {
            $company = auth()->user()->company;

            if ($company === null) {
                session(['pending_vacancy' => $validated]);
                $this->resetForm();
                $this->redirect(route('employer.profile'), navigate: true);
                return;
            }

            $vacancy = Vacancy::create([
                'company_id'      => $company->id,
                'category_id'     => $validated['category_id'],
                'city_id'         => $validated['city_id'],
                'title'           => $validated['title'],
                'slug'            => Str::slug($validated['title']) . '-' . Str::random(6),
                'salary_from'     => $validated['salary_from'] ?? null,
                'salary_to'       => null,
                'currency'        => 'UAH',
                'employment_type' => ['full-time'],
                'is_active'       => false,
                'is_featured'     => false,
                'is_top'          => false,
                'languages'       => [],
                'suitability'     => [],
            ]);

            $this->resetForm();
            $this->redirect(route('employer.vacancies.edit', ['vacancyId' => $vacancy->id]), navigate: true);
            return;
        }

        session(['pending_vacancy' => $validated]);
        $this->resetForm();
        $this->redirect(route('login'), navigate: true);
    }

    public function resetForm(): void
    {
        $this->title       = '';
        $this->category_id = null;
        $this->city_id     = '';
        $this->salary_from = null;
        $this->show        = false;
    }

    public function getCategories(): Collection
    {
        $data = Cache::remember('categories_all_select_v2', now()->addHours(6), fn (): array =>
            Category::orderBy('name')->get(['id', 'name'])->toArray()
        );

        return collect($data)->map(fn (array $c) => (object) $c);
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.employer.quick-publish-form', [
            'categories' => $this->getCategories(),
        ]);
    }
}
