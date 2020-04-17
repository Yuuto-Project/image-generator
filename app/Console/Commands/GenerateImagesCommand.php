<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;

class GenerateImagesCommand extends Command {

    protected $name = 'generate:images';

    public function handle()
    {
        $this->info('Generating dialog templates');
        $this->generateDialog();
        $this->info('Generated dialog templates');
    }


    private function generateDialog()
    {
        //
    }
}
