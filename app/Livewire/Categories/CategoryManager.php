<?php

namespace App\Livewire\Categories;

use App\Models\Category;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Categories')]
class CategoryManager extends Component
{
    #[Rule('required|string|max:255')]
    public string $name = '';

    public ?int $categoryId = null;

    public function save(): void
    {
        $data = $this->validate();
        $data['user_id'] = Auth::id();

        Category::updateOrCreate([
            'id' => $this->categoryId,
            'user_id' => Auth::id(),
        ], $data);

        $this->resetForm();
        session()->flash('status', 'Category saved.');
    }

    public function edit(int $categoryId): void
    {
        $category = Category::where('user_id', Auth::id())->findOrFail($categoryId);
        $this->categoryId = $category->id;
        $this->name = $category->name;
    }

    public function delete(int $categoryId): void
    {
        Category::where('user_id', Auth::id())->where('id', $categoryId)->delete();
        session()->flash('status', 'Category removed.');
    }

    public function render(): View
    {
        $categories = Category::where('user_id', Auth::id())
            ->orderBy('name')
            ->get();

        return view('livewire.categories.manager', [
            'categories' => $categories,
        ]);
    }

    public function resetForm(): void
    {
        $this->categoryId = null;
        $this->name = '';

        $this->resetValidation();
        $this->resetErrorBag();
    }
}
