<?php

namespace App\Console\Commands;

use TeamTNT\TNTSearch\TNTSearch;
use Illuminate\Console\Command;

class IndexData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'index:data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Index sample data';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
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

        $indexer = $tnt->createIndex('text.index');
        $indexer->query('SELECT id, title, content FROM sample;');
        //$indexer->setLanguage('german');
        $indexer->run();
    }

    private function storage_path($path = null)
    {
        return rtrim(app()->basePath('storage/' . $path), '/');
    }
}