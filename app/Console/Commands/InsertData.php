<?php

namespace App\Console\Commands;

use App\Models\Sample;
use Illuminate\Console\Command;

class InsertData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'insert:data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Insert gzip data to mysql';

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
      $rawData = $this->readFile($this->public_path('data.gz'));
      if (!$rawData) {
          return response()->json([0 => ['title' => 'Error', 'content' => 'File cannot be read.']]);
      }

      $bar = $this->output->createProgressBar(count($rawData));
      $bar->start();
      foreach ($rawData as $key => $value) {
        $sample = Sample::create($value);

        $bar->advance();
      }
      $bar->finish();
    }

    private function public_path($path = null)
    {
        return rtrim(app()->basePath('public/' . $path), '/');
    }

    /**
     * Read the available gzip file
     *
     * @param $file input the path of the file
     *
     * @return $data array list of data
     */
    private function readFile($file)
    {
        // Raising this value may increase performance
        $bufferSize = 4096; // read 4kb at a time
        $outFileName = str_replace('.gz', '', $file);

        // Open our files (in binary mode)
        $file = gzopen($file, 'rb');
        if (!$file) {
            return response()->json([0 => ['title' => 'Error', 'content' => 'Cannot read gzip file.']]);
        }
        $outFile = fopen($outFileName, 'wb');
        if (!$outFile) {
            return response()->json([0 => ['title' => 'Error', 'content' => 'Cannot read extracted data from gzip file.']]);
        }

        // Keep repeating until the end of the input file
        while (!gzeof($file)) {
            // Read buffer-size bytes
            // Both fwrite and gzread and binary-safe
            fwrite($outFile, gzread($file, $bufferSize));
        }

        // Files are done, close files
        fclose($outFile);
        gzclose($file);

        $openFile = fopen($outFileName, 'r');
        if (!$openFile) {
            return response()->json([0 => ['title' => 'Error', 'content' => 'Cannot read extracted data from gzip file.']]);
        }
        $readFile = fread($openFile, filesize($outFileName));
        if (!$readFile) {
            return response()->json([0 => ['title' => 'Error', 'content' => 'Cannot read extracted data from gzip file.']]);
        }
        $parseLine = explode("\n", $readFile);
        if (!$parseLine) {
            return response()->json([0 => ['title' => 'Error', 'content' => 'Cannot read extracted data in a different format.']]);
        }

        $header = [];
        $rawData = [];
        foreach ($parseLine as $key => $value) {
          $rawData[$key] = json_decode($value, true);

          // Add index phonetics words
          $rawData[$key]['index'] = metaphone($rawData[$key]['title']) . metaphone($rawData[$key]['content']);
          $rawData[$key]['tags'] = serialize($rawData[$key]['tags']);
          $rawData[$key]['image_urls'] = serialize($rawData[$key]['image_urls']);
        }

        return $rawData;
    }
}