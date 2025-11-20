<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BooksAuthor extends Model
{
    protected $table = 'books_author';
    protected $fillable = [
        'birth_year',
        'death_year',
        'name'
    ];

    public function books()
    {
        return $this->belongsToMany(BooksBook::class, 'books_book_authors', 'author_id', 'book_id');
    }
}
