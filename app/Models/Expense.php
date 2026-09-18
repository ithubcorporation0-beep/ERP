<?php

namespace App\Models;

use App\Concerns\Auditable;
use App\Concerns\HasDocuments;
use App\Enums\ExpenseCategory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;

class Expense extends Model implements HasMedia
{
    use Auditable, HasDocuments, HasFactory;

    protected $fillable = [
        'customer_id',
        'project_id',
        'category',
        'amount',
        'currency',
        'expense_date',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'category' => ExpenseCategory::class,
            'amount' => 'decimal:2',
            'expense_date' => 'date',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * @return array<int, string>
     */
    protected function auditableFields(): array
    {
        return ['customer_id', 'project_id', 'category', 'amount', 'currency', 'expense_date', 'notes'];
    }
}
