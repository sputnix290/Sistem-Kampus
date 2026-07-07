<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Faq extends Model
{
    use HasFactory;

    protected $table = 'faqs';
    
    protected $fillable = [
        'question',
        'answer',
        'category',
        'order',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'order' => 'integer'
    ];

    protected $attributes = [
        'is_active' => true,
        'order' => 0
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order')->orderBy('id');
    }

    public function activate()
    {
        $this->update(['is_active' => true]);
    }

    public function deactivate()
    {
        $this->update(['is_active' => false]);
    }

    public function getCategoryLabelAttribute()
    {
        $labels = [
            'academic' => 'Akademik',
            'admission' => 'Penerimaan',
            'finance' => 'Keuangan',
            'facilities' => 'Fasilitas',
            'general' => 'Umum',
        ];
        
        return $labels[$this->category] ?? 'Umum';
    }

    public function getCategoryColorAttribute()
    {
        $colors = [
            'academic' => 'bg-blue-100 text-blue-800',
            'admission' => 'bg-purple-100 text-purple-800',
            'finance' => 'bg-green-100 text-green-800',
            'facilities' => 'bg-yellow-100 text-yellow-800',
            'general' => 'bg-gray-100 text-gray-800',
        ];
        
        return $colors[$this->category] ?? 'bg-gray-100 text-gray-800';
    }

    public function getExcerptAttribute($length = 150)
    {
        return str(strip_tags($this->answer))->limit($length);
    }

    public static function getCategories()
    {
        return [
            'academic' => 'Akademik',
            'admission' => 'Penerimaan',
            'finance' => 'Keuangan',
            'facilities' => 'Fasilitas',
            'general' => 'Umum',
        ];
    }
}
