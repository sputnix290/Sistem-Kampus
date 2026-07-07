<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class News extends Model
{
    use HasFactory;

    protected $table = 'news';
    
    protected $fillable = [
        'title',
        'slug',
        'excerpt',
        'content',
        'featured_image',
        'category',
        'tags',
        'author_id',
        'is_published',
        'published_at',
        'views'
    ];

    protected $casts = [
        'tags' => 'array',
        'is_published' => 'boolean',
        'published_at' => 'datetime',
        'views' => 'integer'
    ];

    protected $attributes = [
        'views' => 0,
        'is_published' => false
    ];

    // Event untuk generate slug otomatis
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($news) {
            if (empty($news->slug)) {
                $news->slug = Str::slug($news->title);
            }
        });

        static::updating(function ($news) {
            if ($news->isDirty('title')) {
                $news->slug = Str::slug($news->title);
            }
        });
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true)
                    ->whereNotNull('published_at')
                    ->orderBy('published_at', 'desc');
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopeRecent($query, $limit = 5)
    {
        return $query->published()->limit($limit);
    }

    public function scopePopular($query, $limit = 5)
    {
        return $query->published()->orderBy('views', 'desc')->limit($limit);
    }

    public function incrementViews()
    {
        $this->increment('views');
    }

    public function publish()
    {
        $this->update([
            'is_published' => true,
            'published_at' => now()
        ]);
    }

    public function unpublish()
    {
        $this->update([
            'is_published' => false,
            'published_at' => null
        ]);
    }

    public function getReadingTimeAttribute()
    {
        $wordCount = str_word_count(strip_tags($this->content));
        $readingTime = ceil($wordCount / 200); // 200 words per minute
        return $readingTime . ' min read';
    }

    public function getFeaturedImageUrlAttribute()
    {
        if ($this->featured_image) {
            return asset('storage/' . $this->featured_image);
        }
        return null;
    }

    public function getCategoryColorAttribute()
    {
        $colors = [
            'academic' => 'bg-blue-100 text-blue-800',
            'research' => 'bg-purple-100 text-purple-800',
            'event' => 'bg-green-100 text-green-800',
            'achievement' => 'bg-yellow-100 text-yellow-800',
            'general' => 'bg-gray-100 text-gray-800',
        ];
        
        return $colors[$this->category] ?? 'bg-gray-100 text-gray-800';
    }

    public function getRelatedNewsAttribute($limit = 3)
    {
        return self::where('category', $this->category)
                  ->where('id', '!=', $this->id)
                  ->published()
                  ->limit($limit)
                  ->get();
    }
}
