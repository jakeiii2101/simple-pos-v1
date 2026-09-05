<?php

namespace App\Livewire\Pos;

use App\Models\Product;
use App\Models\Sale;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class SaleTerminal extends Component
{
    public string $search = '';

    /** @var array<int, array{id:int,name:string,sku:string,barcode:?string,price:float,quantity:int,stock:int}> */
    public array $cart = [];

    public string $cashReceived = '';

    public ?int $lastSaleId = null;

    public function boot(): void
    {
        abort_unless(
            auth()->check() && auth()->user()->isActive(),
            403,
        );
    }

    public function addBySearch(): void
    {
        $term = trim($this->search);

        if ($term === '') {
            return;
        }

        $product = Product::query()
            ->where('status', Product::STATUS_ACTIVE)
            ->where(function ($query) use ($term): void {
                $query->where('barcode', $term)
                    ->orWhere('sku', $term);
            })
            ->first();

        if ($product === null) {
            $this->addError('search', 'No active product found with that barcode or SKU.');
            return;
        }

        $this->addProduct($product->id);
        $this->search = '';
        $this->resetErrorBag('search');
    }

    public function addProduct(int $productId): void
    {
        $product = Product::query()
            ->where('status', Product::STATUS_ACTIVE)
            ->findOrFail($productId);

        if ($product->stock_quantity < 1) {
            $this->addError('cart', $product->name.' is out of stock.');
            return;
        }

        if (isset($this->cart[$productId])) {
            $this->increase($productId);
            return;
        }

        $this->cart[$productId] = [
            'id' => $product->id,
            'name' => $product->name,
            'sku' => $product->sku,
            'barcode' => $product->barcode,
            'price' => (float) $product->selling_price,
            'quantity' => 1,
            'stock' => $product->stock_quantity,
        ];

        $this->resetErrorBag('cart');
    }

    public function increase(int $productId): void
    {
        if (! isset($this->cart[$productId])) {
            return;
        }

        $product = Product::query()->findOrFail($productId);
        $nextQuantity = $this->cart[$productId]['quantity'] + 1;

        if ($nextQuantity > $product->stock_quantity) {
            $this->addError('cart', 'Not enough stock for '.$product->name.'.');
            return;
        }

        $this->cart[$productId]['quantity'] = $nextQuantity;
        $this->cart[$productId]['stock'] = $product->stock_quantity;
        $this->resetErrorBag('cart');
    }

    public function decrease(int $productId): void
    {
        if (! isset($this->cart[$productId])) {
            return;
        }

        if ($this->cart[$productId]['quantity'] <= 1) {
            $this->remove($productId);
            return;
        }

        $this->cart[$productId]['quantity']--;
    }

    public function remove(int $productId): void
    {
        unset($this->cart[$productId]);
        $this->resetErrorBag('cart');
    }

    public function clearCart(): void
    {
        $this->cart = [];
        $this->cashReceived = '';
        $this->resetValidation();
    }

    public function completeSale(): void
    {
        if ($this->cart === []) {
            $this->addError('cart', 'Add at least one product before completing the sale.');
            return;
        }

        $total = $this->cartTotal();

        $validated = $this->validate([
            'cashReceived' => ['required', 'numeric', 'min:'.$total],
        ], [
            'cashReceived.min' => 'Cash received must be at least the sale total.',
        ]);

        $sale = DB::transaction(function () use ($validated, $total): Sale {
            $sale = Sale::query()->create([
                'sale_number' => 'POS-'.now()->format('YmdHis').'-'.strtoupper(Str::random(4)),
                'user_id' => auth()->id(),
                'subtotal' => $total,
                'total' => $total,
                'cash_received' => $validated['cashReceived'],
                'change_due' => (float) $validated['cashReceived'] - $total,
                'status' => Sale::STATUS_COMPLETED,
                'completed_at' => now(),
            ]);

            foreach (collect($this->cart)->sortKeys() as $item) {
                $product = Product::query()->lockForUpdate()->findOrFail($item['id']);
                $quantity = (int) $item['quantity'];

                if (! $product->isActive()) {
                    throw ValidationException::withMessages([
                        'cart' => $product->name.' is no longer active.',
                    ]);
                }

                if ($quantity > $product->stock_quantity) {
                    throw ValidationException::withMessages([
                        'cart' => 'Not enough stock for '.$product->name.'. Available: '.$product->stock_quantity.'.',
                    ]);
                }

                $before = $product->stock_quantity;
                $after = $before - $quantity;
                $unitPrice = (float) $product->selling_price;
                $lineTotal = $unitPrice * $quantity;

                $sale->items()->create([
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'sku' => $product->sku,
                    'unit_price' => $unitPrice,
                    'quantity' => $quantity,
                    'line_total' => $lineTotal,
                ]);

                $product->update(['stock_quantity' => $after]);

                StockMovement::query()->create([
                    'product_id' => $product->id,
                    'user_id' => auth()->id(),
                    'type' => StockMovement::TYPE_SALE,
                    'quantity' => -$quantity,
                    'stock_before' => $before,
                    'stock_after' => $after,
                    'reference' => $sale->sale_number,
                    'reason' => 'POS sale',
                ]);
            }

            return $sale;
        });

        $this->lastSaleId = $sale->id;
        $this->cart = [];
        $this->cashReceived = '';
        $this->search = '';
        $this->resetValidation();
        session()->flash('success', 'Sale completed successfully.');
    }

    private function cartTotal(): float
    {
        return round(collect($this->cart)->sum(
            fn (array $item): float => $item['price'] * $item['quantity']
        ), 2);
    }

    public function render()
    {
        $term = trim($this->search);

        $products = Product::query()
            ->where('status', Product::STATUS_ACTIVE)
            ->where('stock_quantity', '>', 0)
            ->when($term !== '', function ($query) use ($term): void {
                $query->where(function ($query) use ($term): void {
                    $query->where('name', 'like', '%'.$term.'%')
                        ->orWhere('sku', 'like', '%'.$term.'%')
                        ->orWhere('barcode', 'like', '%'.$term.'%');
                });
            })
            ->orderBy('name')
            ->limit(20)
            ->get();

        $total = $this->cartTotal();
        $cash = is_numeric($this->cashReceived) ? (float) $this->cashReceived : 0.0;

        return view('livewire.pos.sale-terminal', [
            'products' => $products,
            'subtotal' => $total,
            'total' => $total,
            'changeDue' => max(0, $cash - $total),
        ]);
    }
}
