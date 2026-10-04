<?php

namespace App\Livewire\Products;

use App\Models\Category;
use App\Models\Product;
use App\Support\Audit;
use App\Support\ProductBulkImporter;
use App\Support\ProductImportReader;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Layout('layouts.app')]
class ProductList extends Component
{
    use WithFileUploads;

    public ?int $editingId = null;

    public ?int $categoryId = null;

    public string $sku = '';

    public string $barcode = '';

    public string $name = '';

    public string $costPrice = '0.00';

    public string $sellingPrice = '';

    public int $stockQuantity = 0;

    public int $lowStockLevel = 5;

    public string $status = Product::STATUS_ACTIVE;

    public string $taxType = Product::TAX_VATABLE;

    public bool $isSeniorPwdDiscountEligible = false;

    public bool $showForm = false;

    public bool $showImport = false;

    public $importFile = null;

    /** @var array{imported:int,failed:int}|null */
    public ?array $importSummary = null;

    /** @var array<int, array{row:int,message:string}> */
    public array $importErrors = [];

    public function mount(): void
    {
        $this->showForm = request()->boolean('create');
    }

    public function boot(): void
    {
        abort_unless(
            auth()->check() && auth()->user()->isActive() && auth()->user()->isAdmin(),
            403,
        );
    }

    public function create(): void
    {
        $this->resetForm();
        $this->showImport = false;
        $this->showForm = true;
    }

    public function openImport(): void
    {
        $this->resetForm();
        $this->resetImportState();
        $this->showImport = true;
    }

    public function closeImport(): void
    {
        $this->resetImportState();
        $this->showImport = false;
    }

    public function importProducts(): void
    {
        $this->resetValidation('importFile');
        $this->importSummary = null;
        $this->importErrors = [];

        $this->validate([
            'importFile' => ['required', 'file', 'max:5120'],
        ], [
            'importFile.max' => 'The import file may not be larger than 5 MB.',
        ]);

        $extension = strtolower((string) $this->importFile->getClientOriginalExtension());

        if (! in_array($extension, ['csv', 'xlsx'], true)) {
            $this->addError('importFile', 'Only CSV and XLSX files are supported.');
            return;
        }

        $reader = app(ProductImportReader::class);
        $importer = app(ProductBulkImporter::class);

        $rows = $reader->read($this->importFile->getRealPath(), $extension);
        $result = $importer->import($rows, $this->importFile->getClientOriginalName());

        $this->importSummary = [
            'imported' => $result['imported'],
            'failed' => $result['failed'],
        ];
        $this->importErrors = $result['errors'];
        $this->importFile = null;

        if ($result['imported'] > 0) {
            session()->flash(
                'success',
                $result['imported'].' product'.($result['imported'] === 1 ? '' : 's').' imported successfully.'
            );
        }

        if ($result['failed'] > 0) {
            session()->flash(
                'error',
                $result['failed'].' row'.($result['failed'] === 1 ? '' : 's').' could not be imported. Review the import results below.'
            );
        }
    }

    public function downloadImportTemplate(): StreamedResponse
    {
        return response()->streamDownload(function (): void {
            $handle = fopen('php://output', 'wb');

            fputcsv($handle, [
                'category',
                'sku',
                'barcode',
                'name',
                'cost_price',
                'selling_price',
                'stock_quantity',
                'low_stock_level',
                'status',
                'tax_type',
                'senior_pwd_eligible',
            ]);

            fputcsv($handle, [
                'Supermarket',
                'SAMPLE-001',
                '480000000001',
                'Sample Product',
                '50.00',
                '65.00',
                '20',
                '5',
                'active',
                'vatable',
                'no',
            ]);

            fclose($handle);
        }, 'sniperpos-product-import-template.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function edit(int $productId): void
    {
        $product = Product::query()->findOrFail($productId);

        $this->editingId = $product->id;
        $this->categoryId = $product->category_id;
        $this->sku = $product->sku;
        $this->barcode = $product->barcode ?? '';
        $this->name = $product->name;
        $this->costPrice = (string) $product->cost_price;
        $this->sellingPrice = (string) $product->selling_price;
        $this->stockQuantity = $product->stock_quantity;
        $this->lowStockLevel = $product->low_stock_level;
        $this->status = $product->status;
        $this->taxType = $product->tax_type;
        $this->isSeniorPwdDiscountEligible = $product->is_senior_pwd_discount_eligible;
        $this->showImport = false;
        $this->showForm = true;
        $this->resetValidation();
    }

    public function save(): void
    {
        $rules = [
            'categoryId' => ['required', 'integer', Rule::exists('categories', 'id')->where(fn ($query) => $query->where('status', Category::STATUS_ACTIVE)->where('account_id', auth()->user()->account_id))],
            'sku' => ['required', 'string', 'max:100', Rule::unique('products', 'sku')->where(fn ($query) => $query->where('account_id', auth()->user()->account_id))->ignore($this->editingId)],
            'barcode' => ['nullable', 'string', 'max:100', Rule::unique('products', 'barcode')->where(fn ($query) => $query->where('account_id', auth()->user()->account_id))->ignore($this->editingId)],
            'name' => ['required', 'string', 'max:150'],
            'costPrice' => ['required', 'numeric', 'min:0'],
            'sellingPrice' => ['required', 'numeric', 'min:0'],
            'lowStockLevel' => ['required', 'integer', 'min:0'],
            'status' => ['required', Rule::in([Product::STATUS_ACTIVE, Product::STATUS_INACTIVE])],
            'taxType' => ['required', Rule::in([
                Product::TAX_VATABLE,
                Product::TAX_VAT_EXEMPT,
                Product::TAX_ZERO_RATED,
            ])],
            'isSeniorPwdDiscountEligible' => ['boolean'],
        ];

        if ($this->editingId === null) {
            $rules['stockQuantity'] = ['required', 'integer', 'min:0'];
        }

        $validated = $this->validate($rules);

        if ($validated['isSeniorPwdDiscountEligible'] && $validated['taxType'] !== Product::TAX_VATABLE) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'isSeniorPwdDiscountEligible' => 'Senior/PWD eligibility may only be enabled for VATable products.',
            ]);
        }

        $data = [
            'category_id' => $validated['categoryId'],
            'sku' => trim($validated['sku']),
            'barcode' => filled($validated['barcode']) ? trim($validated['barcode']) : null,
            'name' => trim($validated['name']),
            'cost_price' => $validated['costPrice'],
            'selling_price' => $validated['sellingPrice'],
            'low_stock_level' => $validated['lowStockLevel'],
            'status' => $validated['status'],
            'tax_type' => $validated['taxType'],
            'is_senior_pwd_discount_eligible' => $validated['isSeniorPwdDiscountEligible'],
        ];

        if ($this->editingId !== null) {
            $product = Product::query()->findOrFail($this->editingId);
            $before = $product->only([
                'category_id',
                'sku',
                'barcode',
                'name',
                'cost_price',
                'selling_price',
                'low_stock_level',
                'status',
                'tax_type',
                'is_senior_pwd_discount_eligible',
            ]);
            $oldSellingPrice = (string) $product->selling_price;

            $product->update($data);
            $product->refresh();

            Audit::record(
                'product.updated',
                $product,
                'Product updated.',
                [
                    'before' => $before,
                    'after' => $product->only(array_keys($before)),
                ],
            );

            if ($oldSellingPrice !== (string) $product->selling_price) {
                Audit::record(
                    'product.price_changed',
                    $product,
                    'Product selling price changed.',
                    [
                        'old_selling_price' => $oldSellingPrice,
                        'new_selling_price' => (string) $product->selling_price,
                    ],
                );
            }

            session()->flash('success', 'Product updated successfully.');
        } else {
            $data['stock_quantity'] = $validated['stockQuantity'];
            $product = Product::query()->create($data);

            Audit::record(
                'product.created',
                $product,
                'Product created.',
                [
                    'sku' => $product->sku,
                    'name' => $product->name,
                    'selling_price' => (string) $product->selling_price,
                    'stock_quantity' => $product->stock_quantity,
                    'status' => $product->status,
                ],
            );

            session()->flash('success', 'Product created successfully.');
        }

        $this->resetForm();
    }

    public function delete(int $productId): void
    {
        $product = Product::query()->findOrFail($productId);

        if ($product->stockMovements()->exists()) {
            session()->flash('error', 'This product has inventory history and cannot be deleted. Set it to inactive instead.');
            return;
        }

        Audit::record(
            'product.deleted',
            $product,
            'Product deleted before any inventory history existed.',
            [
                'sku' => $product->sku,
                'name' => $product->name,
            ],
        );

        $product->delete();

        if ($this->editingId === $productId) {
            $this->resetForm();
        }

        session()->flash('success', 'Product deleted successfully.');
    }

    public function cancel(): void
    {
        $this->resetForm();
    }

    protected function resetForm(): void
    {
        $this->editingId = null;
        $this->categoryId = null;
        $this->sku = '';
        $this->barcode = '';
        $this->name = '';
        $this->costPrice = '0.00';
        $this->sellingPrice = '';
        $this->stockQuantity = 0;
        $this->lowStockLevel = 5;
        $this->status = Product::STATUS_ACTIVE;
        $this->taxType = Product::TAX_VATABLE;
        $this->isSeniorPwdDiscountEligible = false;
        $this->showForm = false;
        $this->resetValidation();
    }

    protected function resetImportState(): void
    {
        $this->importFile = null;
        $this->importSummary = null;
        $this->importErrors = [];
        $this->resetValidation('importFile');
    }

    public function render()
    {
        return view('livewire.products.product-list', [
            'products' => Product::query()->with('category')->orderBy('name')->get(),
            'categories' => Category::query()->where('status', Category::STATUS_ACTIVE)->orderBy('name')->get(),
        ]);
    }
}
