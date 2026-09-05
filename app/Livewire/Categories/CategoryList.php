<?php

namespace App\Livewire\Categories;

use App\Models\Category;
use Illuminate\Validation\Rule;
use Livewire\Component;

class CategoryList extends Component
{
    public ?int $editingId = null;

    public string $name = '';

    public string $description = '';

    public string $status = Category::STATUS_ACTIVE;

    public bool $showForm = false;

    public function create(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $categoryId): void
    {
        $category = Category::query()->findOrFail($categoryId);

        $this->editingId = $category->id;
        $this->name = $category->name;
        $this->description = $category->description ?? '';
        $this->status = $category->status;
        $this->showForm = true;
        $this->resetValidation();
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('categories', 'name')->ignore($this->editingId),
            ],
            'description' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in([
                Category::STATUS_ACTIVE,
                Category::STATUS_INACTIVE,
            ])],
        ]);

        if ($this->editingId !== null) {
            $category = Category::query()->findOrFail($this->editingId);
            $category->update($validated);
            session()->flash('success', 'Category updated successfully.');
        } else {
            Category::query()->create($validated);
            session()->flash('success', 'Category created successfully.');
        }

        $this->resetForm();
    }

    public function delete(int $categoryId): void
    {
        $category = Category::query()->findOrFail($categoryId);
        $category->delete();

        if ($this->editingId === $categoryId) {
            $this->resetForm();
        }

        session()->flash('success', 'Category deleted successfully.');
    }

    public function cancel(): void
    {
        $this->resetForm();
    }

    protected function resetForm(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->description = '';
        $this->status = Category::STATUS_ACTIVE;
        $this->showForm = false;
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.categories.category-list', [
            'categories' => Category::query()->orderBy('name')->get(),
        ]);
    }
}
