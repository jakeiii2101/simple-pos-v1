<?php

namespace App\Livewire\Pos;

use App\Models\Payment;
use App\Models\Product;
use App\Models\Sale;
use App\Models\StockMovement;
use App\Support\Audit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
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

    public string $paymentMethod = Payment::METHOD_CASH;

    public string $paymentReference = '';

    public string $discountType = Sale::DISCOUNT_FIXED;

    public string $discountValue = '';

    public ?string $appliedDiscountType = null;

    public float $appliedDiscountValue = 0.0;

    public ?int $lastSaleId = null;

    public function boot(): void
    {
        $user = auth()->user();

        abort_unless(
            $user !== null && $user->isActive() && ($user->isAdmin() || $user->isCashier()),
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
        $this->resetDiscountState();
        $this->resetPaymentState();
        $this->resetValidation();
    }

    public function applyDiscount(): void
    {
        if ($this->cart === []) {
            $this->addError('cart', 'Add at least one product before applying a discount.');
            return;
        }

        $validated = $this->validate([
            'discountType' => ['required', Rule::in([
                Sale::DISCOUNT_FIXED,
                Sale::DISCOUNT_PERCENTAGE,
            ])],
            'discountValue' => ['required', 'numeric', 'gt:0'],
        ]);

        $value = round((float) $validated['discountValue'], 2);
        $subtotal = $this->cartSubtotal();

        if ($validated['discountType'] === Sale::DISCOUNT_PERCENTAGE && $value > 100) {
            $this->addError('discountValue', 'Percentage discount cannot exceed 100%.');
            return;
        }

        if ($validated['discountType'] === Sale::DISCOUNT_FIXED && $value > $subtotal) {
            $this->addError('discountValue', 'Fixed discount cannot exceed the current subtotal.');
            return;
        }

        $this->appliedDiscountType = $validated['discountType'];
        $this->appliedDiscountValue = $value;
        $this->resetErrorBag('discountType');
        $this->resetErrorBag('discountValue');
    }

    public function clearDiscount(): void
    {
        $this->resetDiscountState();
        $this->resetErrorBag('discountType');
        $this->resetErrorBag('discountValue');
    }

    public function completeSale(): void
    {
        if ($this->cart === []) {
            $this->addError('cart', 'Add at least one product before completing the sale.');
            return;
        }

        $rules = [
            'paymentMethod' => ['required', Rule::in([
                Payment::METHOD_CASH,
                Payment::METHOD_GCASH,
                Payment::METHOD_CARD,
                Payment::METHOD_OTHER,
            ])],
        ];

        if ($this->paymentMethod === Payment::METHOD_CASH) {
            $rules['cashReceived'] = ['required', 'numeric', 'min:0'];
        } else {
            $rules['paymentReference'] = ['required', 'string', 'max:100'];
        }

        $validated = $this->validate($rules, [
            'paymentReference.required' => 'A payment reference is required for non-cash payments.',
        ]);

        $discountType = $this->appliedDiscountType;
        $discountValue = $this->appliedDiscountValue;
        $paymentMethod = $validated['paymentMethod'];

        $sale = DB::transaction(function () use ($validated, $discountType, $discountValue, $paymentMethod): Sale {
            $lines = [];
            $subtotal = 0.0;

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
                $unitPrice = round((float) $product->selling_price, 2);
                $lineTotal = round($unitPrice * $quantity, 2);
                $subtotal = round($subtotal + $lineTotal, 2);

                $lines[] = [
                    'product' => $product,
                    'quantity' => $quantity,
                    'before' => $before,
                    'after' => $after,
                    'unit_price' => $unitPrice,
                    'line_total' => $lineTotal,
                ];
            }

            $discountAmount = $this->discountAmountForSubtotal(
                $subtotal,
                $discountType,
                $discountValue,
            );
            $total = round(max(0, $subtotal - $discountAmount), 2);

            if ($paymentMethod === Payment::METHOD_CASH) {
                $amountTendered = round((float) $validated['cashReceived'], 2);

                if ($amountTendered < $total) {
                    throw ValidationException::withMessages([
                        'cashReceived' => 'Cash received must be at least the sale total.',
                    ]);
                }

                $changeDue = round($amountTendered - $total, 2);
                $paymentReference = null;
                $saleCashReceived = $amountTendered;
                $saleChangeDue = $changeDue;
            } else {
                $paymentReference = trim((string) $validated['paymentReference']);

                if ($paymentReference === '') {
                    throw ValidationException::withMessages([
                        'paymentReference' => 'A payment reference is required for non-cash payments.',
                    ]);
                }

                $amountTendered = $total;
                $changeDue = 0.0;
                $saleCashReceived = 0.0;
                $saleChangeDue = 0.0;
            }

            $sale = Sale::query()->create([
                'sale_number' => 'POS-'.now()->format('YmdHis').'-'.strtoupper(Str::random(4)),
                'user_id' => auth()->id(),
                'subtotal' => $subtotal,
                'discount_type' => $discountType,
                'discount_value' => $discountType === null ? 0 : $discountValue,
                'discount_amount' => $discountAmount,
                'total' => $total,
                'cash_received' => $saleCashReceived,
                'change_due' => $saleChangeDue,
                'status' => Sale::STATUS_COMPLETED,
                'completed_at' => now(),
            ]);

            $sale->payment()->create([
                'method' => $paymentMethod,
                'amount' => $total,
                'amount_tendered' => $amountTendered,
                'change_due' => $changeDue,
                'reference' => $paymentReference,
            ]);

            foreach ($lines as $line) {
                /** @var Product $product */
                $product = $line['product'];

                $sale->items()->create([
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'sku' => $product->sku,
                    'unit_price' => $line['unit_price'],
                    'quantity' => $line['quantity'],
                    'line_total' => $line['line_total'],
                ]);

                $product->update(['stock_quantity' => $line['after']]);

                StockMovement::query()->create([
                    'product_id' => $product->id,
                    'user_id' => auth()->id(),
                    'type' => StockMovement::TYPE_SALE,
                    'quantity' => -$line['quantity'],
                    'stock_before' => $line['before'],
                    'stock_after' => $line['after'],
                    'reference' => $sale->sale_number,
                    'reason' => 'POS sale',
                ]);
            }

            Audit::record(
                'sale.completed',
                $sale,
                'POS sale completed: '.$sale->sale_number,
                [
                    'sale_number' => $sale->sale_number,
                    'subtotal' => $subtotal,
                    'discount_amount' => $discountAmount,
                    'total' => $total,
                    'payment_method' => $paymentMethod,
                    'item_count' => (int) collect($lines)->sum(fn (array $line): int => $line['quantity']),
                ],
            );

            return $sale;
        });

        $this->lastSaleId = $sale->id;
        $this->cart = [];
        $this->search = '';
        $this->resetDiscountState();
        $this->resetPaymentState();
        $this->resetValidation();
        session()->flash('success', 'Sale completed successfully.');
    }

    private function cartSubtotal(): float
    {
        return round(collect($this->cart)->sum(
            fn (array $item): float => $item['price'] * $item['quantity']
        ), 2);
    }

    private function discountAmountForSubtotal(float $subtotal, ?string $type, float $value): float
    {
        if ($type === null) {
            return 0.0;
        }

        if ($value <= 0) {
            throw ValidationException::withMessages([
                'discountValue' => 'Discount value must be greater than zero.',
            ]);
        }

        if ($type === Sale::DISCOUNT_FIXED) {
            if ($value > $subtotal) {
                throw ValidationException::withMessages([
                    'discountValue' => 'Fixed discount cannot exceed the sale subtotal.',
                ]);
            }

            return round($value, 2);
        }

        if ($type === Sale::DISCOUNT_PERCENTAGE) {
            if ($value > 100) {
                throw ValidationException::withMessages([
                    'discountValue' => 'Percentage discount cannot exceed 100%.',
                ]);
            }

            return round($subtotal * ($value / 100), 2);
        }

        throw ValidationException::withMessages([
            'discountType' => 'Invalid discount type.',
        ]);
    }

    private function previewDiscountAmount(float $subtotal): float
    {
        if ($this->appliedDiscountType === Sale::DISCOUNT_FIXED) {
            return round(min($this->appliedDiscountValue, $subtotal), 2);
        }

        if ($this->appliedDiscountType === Sale::DISCOUNT_PERCENTAGE) {
            return round($subtotal * (min($this->appliedDiscountValue, 100) / 100), 2);
        }

        return 0.0;
    }

    private function resetDiscountState(): void
    {
        $this->discountType = Sale::DISCOUNT_FIXED;
        $this->discountValue = '';
        $this->appliedDiscountType = null;
        $this->appliedDiscountValue = 0.0;
    }

    private function resetPaymentState(): void
    {
        $this->paymentMethod = Payment::METHOD_CASH;
        $this->paymentReference = '';
        $this->cashReceived = '';
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

        $subtotal = $this->cartSubtotal();
        $discountAmount = $this->previewDiscountAmount($subtotal);
        $total = round(max(0, $subtotal - $discountAmount), 2);
        $cash = is_numeric($this->cashReceived) ? (float) $this->cashReceived : 0.0;
        $changeDue = $this->paymentMethod === Payment::METHOD_CASH
            ? max(0, round($cash - $total, 2))
            : 0.0;

        return view('livewire.pos.sale-terminal', [
            'products' => $products,
            'subtotal' => $subtotal,
            'discountAmount' => $discountAmount,
            'total' => $total,
            'changeDue' => $changeDue,
        ]);
    }
}
