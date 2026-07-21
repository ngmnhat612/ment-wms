<?php

namespace App\Models\Master;

use App\Enums\ActiveStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Category extends Model
{
    use HasFactory;

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

    /**
     * Quy tắc gán vị trí (PutawayRule) theo Danh mục — tự động tạo/cập nhật
     * khi Thêm/Sửa danh mục ở form Danh mục vật tư (đồng bộ với PutawayRuleService).
     */
    public function putawayRule(): HasOne
    {
        return $this->hasOne(PutawayRule::class, 'category_id');
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
            ? $this->parent->full_path . ' > ' . $this->name
            : $this->name;
    }
}
