<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BooksBook extends Model
{
    protected $table = 'books_book';
    protected $fillable = [
        'download_count',
        'gutenberg_id',
        'media_type',
        'title'
    ];

    public function authors()
    {
        return $this->belongsToMany(BooksAuthor::class, 'books_book_authors', 'book_id', 'author_id')->select('name', 'birth_year', 'death_year');
    }

    public function languages()
    {
        return $this->belongsToMany(BooksLanguage::class, 'books_book_languages', 'book_id', 'language_id')->select('code');
    }

    public function subjects()
    {
        return $this->belongsToMany(BooksSubject::class, 'books_book_subjects', 'book_id', 'subject_id');
    }

    public function bookshelves()
    {
        return $this->belongsToMany(BooksBookShelf::class, 'books_book_bookshelves', 'book_id', 'bookshelf_id');
    }

    public function formats()
    {
        return $this->hasMany(BooksFormat::class, 'book_id', 'id');
    }
}
