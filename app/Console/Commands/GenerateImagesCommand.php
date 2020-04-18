<?php

namespace App\Console\Commands;

use DirectoryIterator;
use Illuminate\Console\Command;
use Imagick;

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
        $imagesBasePath = resource_path('images/dialog/');
        $outputPath = storage_path('images/dialog/');

        // loop over backgrounds
        //    -> loop over characters
        //    -> generate images with characters and backgrounds
        //    -> Store image in cache

        $bgDir = new DirectoryIterator($imagesBasePath . 'backgrounds/');

        foreach ($bgDir as $background) {
            if ($background->isDot()) {
                continue;
            }

            $charDir = new DirectoryIterator($imagesBasePath . 'characters/');

            $bgName = $this->stripExtension($background->getFilename());
            $bgPath = $background->getRealPath();

            foreach ($charDir as $character) {
                if ($character->isDot()) {
                    continue;
                }

                $charName = $this->stripExtension($character->getFilename());

                $outputName = "{$outputPath}{$charName}_{$bgName}.png";

                if (\file_exists($outputName)) {
                    continue;
                }

                $charPath = $character->getRealPath();

                $start = microtime(true);

                // We have to make a new image to combine them
                $bgImg = new Imagick($bgPath);
                $charImg = new Imagick($charPath);

                $bgImg->compositeImage(
                    $charImg,
                    Imagick::COMPOSITE_DEFAULT,
                    0,
                    0
                );

                $bgImg->writeImage($outputName);

                $end = microtime(true);

                $this->info('Took ' . ($end - $start) . ' seconds to generate ' . $charName . ' on ' . $bgName);
            }
        }

    }

    private function stripExtension(string $name): string
    {
        $index = \strrpos($name, '.');

        if ($index === false) {
            return $name;
        }

        return \substr($name, 0, $index);
    }
}
