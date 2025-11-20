<?php

namespace App\Http\Controllers;

use App\Http\Resources\BooksResource;
use App\Http\Resources\CategoriesResource;
use App\Models\BooksBook;
use App\Models\BooksSubject;
use Illuminate\Http\Request;

use function PHPUnit\Framework\isNull;
class BooksController extends Controller
{

    /**
     * @OA\Get(
     *     path="/get-categories",
     *     summary="Get category list",
     *     tags={"Users"},
     *     @OA\Response(
     *         response=200,
     *         description="Success"
     *     )
     * )
     */
    public function getCategories()
    {
        $categories = BooksSubject::paginate(25);
        return [
            'count' => $categories->total(),
            'next' => $categories->nextPageUrl(),
            'previous' => $categories->previousPageUrl(),
            'results' => CategoriesResource::collection($categories)
        ];
    }

    /**
     * @OA\Get(
     *     path="/get-books",
     *     summary="Get books list",
     *     tags={"Users"},
     *     @OA\Response(
     *         response=200,
     *         description="Success"
     *     )
     * )
     */
    public function getBooks(Request $request)
    {
        $search = (request('search') != null) ? request('search') : "";
        $books = BooksBook::select('id', 'title', 'download_count', 'media_type', 'gutenberg_id')
                    ->with(['authors', 'languages', 'subjects', 'bookshelves', 'formats'])
                    ->when(request('id'), function ($q, $id) {
                        $q->where('gutenberg_id', $id);
                    })
                    ->when(request('category'), function($q, $categoryId) {
                        $q->whereHas('subjects', function ($sub) use ($categoryId) {
                            $sub->where('subject_id', $categoryId);
                        });
                    })
                    ->when(request('language'), function ($q, $lang) {
                        $q->whereHas('languages', function ($sub) use ($lang) {
                            $sub->where('code', $lang);
                        });
                    })
                    ->when(request('mime'), function ($q, $mime) {
                        $q->whereHas('formats', function ($sub) use ($mime) {
                            $sub->where('mime_type', $mime);
                        });
                    })
                    ->when(request('topic'), function ($q, $topic) {
                        $q->where(function ($inner) use ($topic) {
                            $inner->whereHas('subjects', function ($sub) use ($topic) {
                                $sub->where('name', 'ILIKE', "%{$topic}%");
                            })
                            ->orWhereHas('bookshelves', function ($sub) use ($topic) {
                                $sub->where('name', 'ILIKE', "%{$topic}%");
                            });
                        });
                    })
                    ->when(request('author'), function ($q, $author) {
                        $q->whereHas('authors', function ($sub) use ($author) {
                            $sub->where('name', 'ILIKE', "%{$author}%");
                        });
                    })
                    ->when(request('title'), function ($q, $title) {
                        $q->where('title', 'ILIKE', "%{$title}%");
                    })
                    ->when(!empty($search), function ($q) use ($search) {
                        $q->where(function ($inner) use ($search) {
                            $inner->where('title', 'ILIKE', "%{$search}%")
                                  ->orWhereHas('authors', function($sub) use ($search) {
                                      $sub->where('name', 'ILIKE', "%{$search}%");
                                  });
                        });
                    })
                    ->orderByDesc('download_count')
                    ->paginate(25);

        $booksResult = BooksResource::collection($books);

        return [
            'count' => $books->total(),
            'next' => $books->nextPageUrl(),
            'previous' => $books->previousPageUrl(),
            'results' => $booksResult
        ];
    }
}
