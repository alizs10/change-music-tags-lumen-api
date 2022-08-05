<?php

namespace App\Jobs;

use App\Models\Upload;
use Illuminate\Support\Carbon;

class CleanUploads extends Job
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
        $expired_uploads = Upload::where('valid_until', '<', Carbon::now())->get();

        if ($expired_uploads->count() > 0) {
            foreach ($expired_uploads as $file) {
                if (file_exists($file->path)) {
                    unlink($file->path);
                }
                
                $file->delete();
            }
        }
    }
}
