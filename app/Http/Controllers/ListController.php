<?php

namespace App\Http\Controllers;

use App\Models\Sample;
use Illuminate\Http\Request;
use TeamTNT\TNTSearch\TNTSearch;

class ListController extends Controller
{
    /**
     * General list function
     *
     * @param $q keyword from input text list
     * @param $sortBy sort by title, content or updated at
     * @param $sortType asc or desc
     *
     * @return $data json list of searched data
     */
    public function list(Request $request)
    {
        ini_set('memory_limit', '-1');
        $tnt = new TNTSearch;
        $tnt->loadConfig([
            'driver'    => 'mysql',
            'host'      => env('DB_HOST'),
            'database'  => env('DB_DATABASE'),
            'username'  => env('DB_USERNAME'),
            'password'  => env('DB_PASSWORD'),
            'storage'   => $this->storage_path('app/'),
            'stemmer'   => \TeamTNT\TNTSearch\Stemmer\PorterStemmer::class//optional
        ]);
        $tnt->loadConfig($config);

        $tnt->selectIndex("text.index");

        $keyword = $request->input('q');
        $paginatedData = $tnt->search($keyword, $request->input('perpage'));

        // $data = $this->pagination($rawData, $request->input('perpage'), $request->input('page'));
        // if (!$data) {
        //     return response()->json([0 => ['title' => 'Error', 'content' => 'Empty data from pagination.']]);
        // }
        $paginatedData = $rawData->get();
        $data = [
            'meta' => [
                'total' => 0,
                'page' => 0,
                'offsetStart' => 0,
                'totalPage' => 0
            ],
            'data' => $paginatedData
        ];

        return response()->json($data);
    }

    /**
     * Simple pagination
     *
     * @param $data Data from queried search
     * @param $perpage Offset number per page
     * @param $page Current page
     *
     * @return $data array list of searched data
     */
    private function pagination($rawData, $perpage = 5, $page = 1)
    {
        $numPosts = ctype_digit((string)$perpage) ? $perpage : 5;
        $ostart = $start = max(1, ctype_digit((string)$page)) - 1;

        $lines = $rawData->count();

        // get total number of pages
        $numPages = ceil($lines / $numPosts);

        // additional sanity checks (also sets $ostart if it was invalid; used later)
        $numPosts = min($perpage, max(1, $numPosts));
        if ($start * $numPosts > $lines ) {
            $ostart = $start = max(0, $lines - $numPosts);
        } else {
            $start *= $numPosts;
        }

        $paginatedData = $rawData->skip(10)->take((int)$perpage)->get();

        $data = [
            'meta' => [
                'total' => (int) $lines,
                'page' => (int) $page,
                'offsetStart' => $ostart,
                'totalPage' => (int) $numPages
            ],
            'data' => $paginatedData
        ];

        return $data;
    }
}
