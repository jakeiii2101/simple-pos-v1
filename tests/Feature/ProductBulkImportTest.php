<?php

namespace Tests\Feature;

use App\Livewire\Products\ProductList;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Support\ProductBulkImporter;
use App\Support\ProductImportReader;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Tests\TestCase;
use ZipArchive;

class ProductBulkImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_import_products_from_csv(): void
    {
        $admin = User::factory()->admin()->create();
        Category::query()->create([
            'name' => 'Supermarket',
            'status' => Category::STATUS_ACTIVE,
        ]);

        $csv = implode("\n", [
            'category,sku,barcode,name,cost_price,selling_price,stock_quantity,low_stock_level,status,tax_type,senior_pwd_eligible',
            'Supermarket,CSV-001,480000000001,Imported Coffee,80.00,99.50,25,5,active,vatable,no',
            'Supermarket,CSV-002,,Imported Rice,45.00,52.00,40,8,active,vat_exempt,no',
        ]);

        $file = UploadedFile::fake()->createWithContent('products.csv', $csv);

        Livewire::actingAs($admin)
            ->test(ProductList::class)
            ->set('importFile', $file)
            ->call('importProducts')
            ->assertHasNoErrors()
            ->assertSet('importSummary', [
                'imported' => 2,
                'failed' => 0,
            ]);

        $coffee = Product::query()->where('sku', 'CSV-001')->firstOrFail();

        $this->assertDatabaseHas('products', [
            'sku' => 'CSV-001',
            'name' => 'Imported Coffee',
            'barcode' => '480000000001',
            'selling_price' => '99.50',
            'stock_quantity' => 25,
            'tax_type' => Product::TAX_VATABLE,
        ]);

        $this->assertDatabaseHas('products', [
            'sku' => 'CSV-002',
            'name' => 'Imported Rice',
            'barcode' => null,
            'tax_type' => Product::TAX_VAT_EXEMPT,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'product.created',
            'auditable_id' => $coffee->id,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'product.bulk_import_completed',
        ]);
    }

    public function test_import_keeps_valid_rows_and_reports_invalid_rows(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::query()->create([
            'name' => 'Department Store',
            'status' => Category::STATUS_ACTIVE,
        ]);

        Product::query()->create([
            'category_id' => $category->id,
            'sku' => 'EXISTING-001',
            'name' => 'Existing Item',
            'cost_price' => 10,
            'selling_price' => 15,
            'stock_quantity' => 2,
            'low_stock_level' => 1,
            'status' => Product::STATUS_ACTIVE,
            'tax_type' => Product::TAX_VATABLE,
        ]);

        $csv = implode("\n", [
            'category,sku,name,selling_price,stock_quantity',
            'Department Store,VALID-001,Valid Imported Item,25.00,10',
            'Department Store,EXISTING-001,Duplicate SKU,30.00,5',
            'Missing Category,BAD-002,Unknown Category,40.00,5',
        ]);

        $file = UploadedFile::fake()->createWithContent('mixed-products.csv', $csv);

        Livewire::actingAs($admin)
            ->test(ProductList::class)
            ->set('importFile', $file)
            ->call('importProducts')
            ->assertSet('importSummary', [
                'imported' => 1,
                'failed' => 2,
            ])
            ->assertSet('importErrors.0.row', 3)
            ->assertSet('importErrors.1.row', 4);

        $this->assertDatabaseHas('products', ['sku' => 'VALID-001']);
        $this->assertSame(1, Product::query()->where('sku', 'EXISTING-001')->count());
        $this->assertDatabaseMissing('products', ['sku' => 'BAD-002']);
    }

    public function test_admin_can_import_products_from_xlsx(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        Category::query()->create([
            'name' => 'Pharmacy',
            'status' => Category::STATUS_ACTIVE,
        ]);

        $path = $this->makeXlsx([
            ['category', 'sku', 'barcode', 'name', 'cost_price', 'selling_price', 'stock_quantity', 'low_stock_level', 'status', 'tax_type', 'senior_pwd_eligible'],
            ['Pharmacy', 'XLSX-001', '480000000099', 'Imported Vitamins', '120.00', '150.00', '12', '3', 'active', 'vatable', 'yes'],
        ]);

        try {
            $rows = app(ProductImportReader::class)->read($path, 'xlsx');
            $result = app(ProductBulkImporter::class)->import($rows, 'products.xlsx');
        } finally {
            @unlink($path);
        }

        $this->assertSame(1, $result['imported']);
        $this->assertSame(0, $result['failed']);

        $this->assertDatabaseHas('products', [
            'sku' => 'XLSX-001',
            'name' => 'Imported Vitamins',
            'barcode' => '480000000099',
            'stock_quantity' => 12,
            'is_senior_pwd_discount_eligible' => 1,
        ]);
    }

    public function test_products_page_exposes_import_controls_to_admin(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get('/products')
            ->assertOk()
            ->assertSee('Import Products')
            ->assertSee('CSV or XLSX');
    }

    /**
     * @param array<int, array<int, string>> $rows
     */
    private function makeXlsx(array $rows): string
    {
        $path = tempnam(sys_get_temp_dir(), 'sniperpos-xlsx-');

        if ($path === false) {
            $this->fail('Could not create a temporary XLSX file.');
        }

        $zip = new ZipArchive;
        $opened = $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        if ($opened !== true) {
            $this->fail('Could not create the XLSX archive.');
        }

        $rowXml = [];

        foreach ($rows as $rowIndex => $row) {
            $cells = [];

            foreach ($row as $columnIndex => $value) {
                $reference = $this->xlsxColumnName($columnIndex + 1).($rowIndex + 1);
                $escaped = htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
                $cells[] = '<c r="'.$reference.'" t="inlineStr"><is><t>'.$escaped.'</t></is></c>';
            }

            $rowXml[] = '<row r="'.($rowIndex + 1).'">'.implode('', $cells).'</row>';
        }

        $sheet = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<sheetData>'.implode('', $rowXml).'</sheetData>'
            .'</worksheet>';

        $zip->addFromString('xl/worksheets/sheet1.xml', $sheet);
        $zip->close();

        return $path;
    }

    private function xlsxColumnName(int $number): string
    {
        $name = '';

        while ($number > 0) {
            $number--;
            $name = chr(65 + ($number % 26)).$name;
            $number = intdiv($number, 26);
        }

        return $name;
    }
}
