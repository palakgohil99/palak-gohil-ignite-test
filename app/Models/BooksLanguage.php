<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BooksLanguage extends Model
{
    protected $table = 'books_language';

    public function books()
    {
        return $this->belongsToMany(BooksBook::class, 'books_book_languages', 'language_id', 'book_id');
    }
}
