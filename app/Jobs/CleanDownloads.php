<?php

namespace App\Jobs;

use App\Models\Download;
use Illuminate\Support\Carbon;

class CleanDownloads extends Job
{
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $expired_downloads = Download::where('valid_until', '<', Carbon::now())->get();

        if ($expired_downloads->count() > 0) {
            foreach ($expired_downloads as $file) {
                if (file_exists($file->path)) {
                    unlink($file->path);
                }

                $file->delete();
            }
        }
    }
}
