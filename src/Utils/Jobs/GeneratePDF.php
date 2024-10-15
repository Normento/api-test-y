<?php

namespace Core\Utils\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use PDF;
class GeneratePDF implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public bool $useLandscape;
    public string $view;
    public string $s3Path;
    public array $viewContext;


    public function __construct(string $view, array $viewContext, string $s3Path, bool $useLandscape = false)
    {
        $this->useLandscape = $useLandscape;
        $this->view = $view;
        $this->s3Path = $s3Path;
        $this->viewContext = $viewContext;
    }
    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $pdf = PDF::loadView($this->view, $this->viewContext);
        $this->useLandscape ? $pdf->setPaper('a4', 'landscape')->setWarnings(false) : null;
        Storage::put($this->s3Path, $pdf->output());
    }
}
