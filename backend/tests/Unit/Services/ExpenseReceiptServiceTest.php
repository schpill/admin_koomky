<?php

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
use App\Services\ExpenseReceiptService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('expense receipt service uploads image and pdf receipts and can delete files', function () {
    Storage::disk('receipts')->deleteDirectory('expenses');

    $user = User::factory()->create();
    $category = ExpenseCategory::factory()->create(['user_id' => $user->id]);
    $expense = Expense::factory()->create([
        'user_id' => $user->id,
        'expense_category_id' => $category->id,
    ]);

    $service = app(ExpenseReceiptService::class);

    $imageReceipt = UploadedFile::fake()->create('receipt.jpg', 50, 'image/jpeg');
    $uploadImage = $service->upload($expense, $imageReceipt);
    expect($uploadImage['path'])->toBeString();
    Storage::disk('receipts')->assertExists($uploadImage['path']);

    $pdfReceipt = UploadedFile::fake()->create('receipt.pdf', 100, 'application/pdf');
    $uploadPdf = $service->upload($expense->fresh(), $pdfReceipt);
    expect($uploadPdf['path'])->toBeString();
    Storage::disk('receipts')->assertExists($uploadPdf['path']);

    $service->delete($expense->fresh());

    $expense->refresh();
    expect($expense->receipt_path)->toBeNull();
});

test('expense receipt service rejects unsupported files', function () {
    Storage::disk('receipts')->deleteDirectory('expenses');

    $user = User::factory()->create();
    $category = ExpenseCategory::factory()->create(['user_id' => $user->id]);
    $expense = Expense::factory()->create([
        'user_id' => $user->id,
        'expense_category_id' => $category->id,
    ]);

    $file = UploadedFile::fake()->create('receipt.txt', 1, 'text/plain');

    $service = app(ExpenseReceiptService::class);

    expect(fn () => $service->upload($expense, $file))
        ->toThrow(\RuntimeException::class);
});
