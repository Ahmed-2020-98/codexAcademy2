<?php

namespace App\Livewire;

use App\Enums\CourseType;
use App\Models\Course;
use App\Models\Department;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class CourseCatalog extends Component
{
    use WithPagination;

    #[Url(except: '')]
    public string $q = '';

    #[Url(except: '')]
    public string $type = '';

    #[Url(except: '')]
    public string $department = '';

    #[Url(except: 'featured')]
    public string $sort = 'featured';

    public function updating($name): void
    {
        if (in_array($name, ['q', 'type', 'department', 'sort'], true)) {
            $this->resetPage();
        }
    }

    public function clear(): void
    {
        $this->reset('q', 'type', 'department', 'sort');
        $this->resetPage();
    }

    public function render()
    {
        $courses = Course::published()
            ->with(['department', 'instructor'])
            ->when($this->q, fn ($query) => $query->where(fn ($w) => $w->where('title', 'like', "%{$this->q}%")->orWhere('subtitle', 'like', "%{$this->q}%")))
            ->when(CourseType::tryFrom($this->type), fn ($query, $type) => $query->where('type', $type))
            ->when($this->department, fn ($query) => $query->whereHas('department', fn ($d) => $d->where('slug', $this->department)))
            ->when($this->sort === 'price_asc', fn ($query) => $query->orderByRaw('COALESCE(sale_price, price) asc'))
            ->when($this->sort === 'price_desc', fn ($query) => $query->orderByRaw('COALESCE(sale_price, price) desc'))
            ->when($this->sort === 'newest', fn ($query) => $query->latest())
            ->when($this->sort === 'featured', fn ($query) => $query->orderByDesc('is_featured')->latest())
            ->paginate(9);

        return view('livewire.course-catalog', [
            'courses' => $courses,
            'departments' => Department::active()->get(),
            'types' => CourseType::cases(),
        ]);
    }
}
