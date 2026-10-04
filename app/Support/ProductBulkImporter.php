<?php

namespace App\Support;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Throwable;

class ProductBulkImporter
{
    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array{
     *     imported:int,
     *     failed:int,
     *     errors:array<int, array{row:int,message:string}>
     * }
     */
    public function import(array $rows, ?string $sourceFileName = null): array
    {
        $categories = Category::query()
            ->where('status', Category::STATUS_ACTIVE)
            ->orderBy('name')
            ->get();

        $categoriesById = $categories->keyBy(fn (Category $category) => (string) $category->id);
        $categoriesByName = $categories->keyBy(fn (Category $category) => mb_strtolower(trim($category->name)));

        $imported = 0;
        $failed = 0;
        $errors = [];

        foreach ($rows as $row) {
            $rowNumber = (int) ($row['_row'] ?? ($imported + $failed + 2));
            $data = $this->normalizeRow($row);

            $categoryValue = trim((string) ($data['category'] ?? ''));
            $category = $categoriesById->get($categoryValue)
                ?? $categoriesByName->get(mb_strtolower($categoryValue));

            $payload = [
                'category_id' => $category?->id,
                'sku' => trim((string) ($data['sku'] ?? '')),
                'barcode' => $this->nullableTrim($data['barcode'] ?? null),
                'name' => trim((string) ($data['name'] ?? '')),
                'cost_price' => $data['cost_price'] ?? '0',
                'selling_price' => $data['selling_price'] ?? null,
                'stock_quantity' => $data['stock_quantity'] ?? 0,
                'low_stock_level' => $data['low_stock_level'] ?? 5,
                'status' => $this->normalizeStatus($data['status'] ?? Product::STATUS_ACTIVE),
                'tax_type' => $this->normalizeTaxType($data['tax_type'] ?? Product::TAX_VATABLE),
                'is_senior_pwd_discount_eligible' => $this->normalizeBoolean(
                    $data['senior_pwd_eligible']
                        ?? $data['is_senior_pwd_discount_eligible']
                        ?? false
                ),
            ];

            $validator = Validator::make($payload, [
                'category_id' => ['required', 'integer', Rule::exists('categories', 'id')->where(fn ($query) => $query->where('status', Category::STATUS_ACTIVE)->where('account_id', auth()->user()->account_id))],
                'sku' => ['required', 'string', 'max:100', Rule::unique('products', 'sku')->where(fn ($query) => $query->where('account_id', auth()->user()->account_id))],
                'barcode' => ['nullable', 'string', 'max:100', Rule::unique('products', 'barcode')->where(fn ($query) => $query->where('account_id', auth()->user()->account_id))],
                'name' => ['required', 'string', 'max:150'],
                'cost_price' => ['required', 'numeric', 'min:0'],
                'selling_price' => ['required', 'numeric', 'min:0'],
                'stock_quantity' => ['required', 'integer', 'min:0'],
                'low_stock_level' => ['required', 'integer', 'min:0'],
                'status' => ['required', Rule::in([Product::STATUS_ACTIVE, Product::STATUS_INACTIVE])],
                'tax_type' => ['required', Rule::in([
                    Product::TAX_VATABLE,
                    Product::TAX_VAT_EXEMPT,
                    Product::TAX_ZERO_RATED,
                ])],
                'is_senior_pwd_discount_eligible' => ['required', 'boolean'],
            ], [
                'category_id.required' => 'Category was not found or is inactive.',
                'category_id.exists' => 'Category was not found or is inactive.',
            ]);

            if ($validator->fails()) {
                $failed++;
                $this->addError($errors, $rowNumber, $validator->errors()->first());
                continue;
            }

            if (
                $payload['is_senior_pwd_discount_eligible']
                && $payload['tax_type'] !== Product::TAX_VATABLE
            ) {
                $failed++;
                $this->addError(
                    $errors,
                    $rowNumber,
                    'Senior/PWD eligibility may only be enabled for VATable products.'
                );
                continue;
            }

            try {
                DB::transaction(function () use ($payload, $sourceFileName): void {
                    $product = Product::query()->create($payload);

                    Audit::record(
                        'product.created',
                        $product,
                        'Product created through bulk import.',
                        [
                            'source' => 'bulk_import',
                            'source_file' => $sourceFileName,
                            'sku' => $product->sku,
                            'name' => $product->name,
                            'selling_price' => (string) $product->selling_price,
                            'stock_quantity' => $product->stock_quantity,
                            'status' => $product->status,
                        ],
                    );
                });

                $imported++;
            } catch (QueryException) {
                $failed++;
                $this->addError(
                    $errors,
                    $rowNumber,
                    'The SKU or barcode already exists. The row was skipped.'
                );
            } catch (Throwable) {
                $failed++;
                $this->addError(
                    $errors,
                    $rowNumber,
                    'The row could not be imported. Check its values and try again.'
                );
            }
        }

        Audit::record(
            'product.bulk_import_completed',
            null,
            'Bulk product import completed.',
            [
                'source_file' => $sourceFileName,
                'imported_count' => $imported,
                'failed_count' => $failed,
            ],
        );

        return [
            'imported' => $imported,
            'failed' => $failed,
            'errors' => $errors,
        ];
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    protected function normalizeRow(array $row): array
    {
        $aliases = [
            'category_name' => 'category',
            'category_id' => 'category',
            'cost' => 'cost_price',
            'price' => 'selling_price',
            'sellingprice' => 'selling_price',
            'stock' => 'stock_quantity',
            'quantity' => 'stock_quantity',
            'low_stock' => 'low_stock_level',
            'low_stock_threshold' => 'low_stock_level',
            'tax' => 'tax_type',
            'senior_pwd_discount_eligible' => 'senior_pwd_eligible',
            'is_senior_pwd_discount_eligible' => 'senior_pwd_eligible',
        ];

        $normalized = [];

        foreach ($row as $key => $value) {
            if ($key === '_row') {
                $normalized[$key] = $value;
                continue;
            }

            $canonical = $aliases[$key] ?? $key;
            $normalized[$canonical] = $value;
        }

        return $normalized;
    }

    protected function normalizeStatus(mixed $value): string
    {
        $value = strtolower(trim((string) $value));

        return $value === '' ? Product::STATUS_ACTIVE : $value;
    }

    protected function normalizeTaxType(mixed $value): string
    {
        $value = strtolower(trim((string) $value));
        $value = str_replace([' ', '-'], '_', $value);

        return match ($value) {
            '', 'vat', 'taxable', 'vatable' => Product::TAX_VATABLE,
            'exempt', 'vat_exempt', 'vatexempt' => Product::TAX_VAT_EXEMPT,
            'zero', 'zero_rate', 'zero_rated', 'zerorated' => Product::TAX_ZERO_RATED,
            default => $value,
        };
    }

    protected function normalizeBoolean(mixed $value): bool|string
    {
        if (is_bool($value)) {
            return $value;
        }

        $value = strtolower(trim((string) $value));

        if ($value === '') {
            return false;
        }

        if (in_array($value, ['1', 'true', 'yes', 'y', 'on', 'eligible'], true)) {
            return true;
        }

        if (in_array($value, ['0', 'false', 'no', 'n', 'off', 'not eligible', 'not_eligible'], true)) {
            return false;
        }

        return $value;
    }

    protected function nullableTrim(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value === '' ? null : $value;
    }

    /**
     * @param array<int, array{row:int,message:string}> $errors
     */
    protected function addError(array &$errors, int $row, string $message): void
    {
        if (count($errors) >= 100) {
            return;
        }

        $errors[] = [
            'row' => $row,
            'message' => $message,
        ];
    }
}
