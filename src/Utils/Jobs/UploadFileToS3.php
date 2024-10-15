<?php

namespace Core\Utils\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;


class UploadFileToS3 implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private string $fileLocalPath;
    private string $s3Path;


    /**
     * Create a new job instance.
     */
    public function __construct($fileLocalPath, $s3Path)
    {
        $this->fileLocalPath = $fileLocalPath;
        $this->s3Path = $s3Path;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $fileContents = file_get_contents($this->fileLocalPath);
        Storage::put($this->s3Path, $fileContents);
        unlink($this->fileLocalPath);
    }
}
