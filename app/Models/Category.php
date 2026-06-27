<?php

namespace App\Models;

use App\Enums\ActiveStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use SoftDeletes;

    protected $table = 'categories';

    protected $fillable = [
        'code',
        'name',
        'parent_id',
        'note',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => ActiveStatus::class,
        ];
    }

    // ===== RELATIONSHIPS =====

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    /**
     * Eager-load đệ quy toàn bộ cây con — dùng cho view tree.
     */
    public function allChildren()
    {
        return $this->children()->with('allChildren');
    }

    public function products()
    {
        return $this->hasMany(Product::class, 'category_id');
    }

    // ===== SCOPES =====

    public function scopeActive($query)
    {
        return $query->where('status', ActiveStatus::Active->value);
    }

    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }

    // ===== HELPERS =====

    public function hasChildren(): bool
    {
        return $this->children()->exists();
    }

    /**
     * Hiển thị đường dẫn đầy đủ: Nhóm cha › Nhóm con.
     */
    public function getFullPathAttribute(): string
    {
        return $this->parent
            ? $this->parent->full_path . ' › ' . $this->name
            : $this->name;
    }
}
