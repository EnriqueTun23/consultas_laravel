<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Post extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'category_id',
        'title',
        'slug',
        'content',
        'likes',
        'dislikes',

    ];

    // protected $with = [
    //     'user:id,name,email',
    // ];

    protected $appends = [
        'title_with_author'
    ];

    protected $casts = [
        'created_at' => 'datetime:Y-m-d'
    ];

    // Esta funcion es encargada de trabajar con los global scope
    protected static function booted()
    {
        static::addGlobalScope("currentMonth", function (Builder $builder) {
            $builder->whereMonth("created_at",  now()->month());
        });
    }

    // El metodo withDefault se asigno para cuando el usuario no existe y se tiene que validar e asignar un usuario vacio
    public function user(): BelongsTo {
        return $this->belongsTo(User::class)->withDefault();
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function tags(): BelongsToMany {
        return $this->belongsToMany(Tag::class);
    }

    public function sortedTags(): BelongsToMany {
        return $this->belongsToMany(Tag::class)->orderBy('tag');
    }


    public function setTitleAttribute($title) {
        $this->attributes["title"] = $title;
        $this->attributes["slug"] = Str::slug($title);
    }

    public function getTitleWithAuthorAttribute(): string {
        return sprintf("%s - %s", $this->title, $this->user->name);
    }

    /**
     * @param Builder $builder
     * @return Builder
     */
    public function scopeWhereHasTagsWithTags(Builder $builder): Builder {
        return $builder
            ->select(['id', 'title'])
            ->with('tags:id,tag')
            ->whereHas('tags');
    }
}
