<?php
/**
 * MIT License
 *
 * Copyright (c) 2020 The Yuuto Project
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 *
 */

namespace App\Console\Commands;

use App\Http\Controllers\ImageGenerationController;
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
        $outputPath = storage_path('app/images/dialog/');
        $ribbonPath = $imagesBasePath . 'ribbons/';

        // load the characters and backgrounds
        $bgDir = new DirectoryIterator($imagesBasePath . 'backgrounds/');
        $charDir = new DirectoryIterator($imagesBasePath . 'characters/');

        $flagsTopLeft = new Imagick(resource_path('images/dialog/flag_overlay.png'));
        $textBox = new Imagick(resource_path('images/dialog/text_box.png'));

        // Scale the flags down a bit
        $flagsTopLeft->scaleImage(
            $flagsTopLeft->getImageWidth() / 1.3,
            $flagsTopLeft->getImageHeight() / 1.3
        );

        $textBox->scaleImage(
            810, // width of the images
            $textBox->getImageHeight() / 1.44
        );

        // Loop over the characters and load them before the backgrounds
        // This is done to only load the character once and have more efficient code
        foreach ($charDir as $character) {
            if ($character->isDot()) {
                continue;
            }

            $charName = $this->stripExtension($character->getFilename());
            $charPath = $character->getRealPath();

            $ribbonLoc = "$ribbonPath/$charName.png";

            if (!\file_exists($ribbonLoc)){
                $this->error('Missing ribbon for ' . $charName);
                continue;
            }

            $ribbon = new Imagick($ribbonLoc);

            $ribbon->scaleImage(
                $ribbon->getImageWidth() / 1.3,
                $ribbon->getImageHeight() / 1.3
            );

            $charImg = new Imagick($charPath);

            foreach ($bgDir as $background) {
                if ($background->isDot()) {
                    continue;
                }

                $bgName = $this->stripExtension($background->getFilename());

                $outputName = "{$outputPath}{$charName}_{$bgName}.png";

                if (\file_exists($outputName)) {
                    continue;
                }

                $bgPath = $background->getRealPath();

                $start = \microtime(true);

                $bgImg = new Imagick($bgPath);

                $bgImg->compositeImage(
                    $charImg,
                    Imagick::COMPOSITE_DEFAULT,
                    0,
                    0
                );

                $bgImg->compositeImage(
                    $textBox,
                    Imagick::COMPOSITE_DEFAULT,
                    0,
                    $bgImg->getImageHeight() - $textBox->getImageHeight() + 13
                );

                $bgImg->compositeImage(
                    $flagsTopLeft,
                    Imagick::COMPOSITE_DEFAULT,
                    $bgImg->getImageWidth() - $flagsTopLeft->getImageWidth(),
                    10
                );

                $bgImg->compositeImage(
                    $ribbon,
                    Imagick::COMPOSITE_DEFAULT,
                    0,
                    653
                );

                $scale = ImageGenerationController::SCALE_FACTOR;

                $bgImg->scaleImage(
                    $bgImg->getImageWidth() / $scale,
                    $bgImg->getImageHeight() / $scale
                );

                $bgImg->writeImage($outputName);

                $bgImg->destroy();

                $end = \microtime(true);

                $this->info('Took ' . ($end - $start) . ' seconds to generate ' . $charName . ' on ' . $bgName);
            }

            $charImg->destroy();
        }

        $flagsTopLeft->destroy();
        $textBox->destroy();
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
