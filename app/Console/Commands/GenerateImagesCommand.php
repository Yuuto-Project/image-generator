<?php
/**
 *                GLWT(Good Luck With That) Public License
 *                  Copyright (c) Everyone, except Author
 *
 * Everyone is permitted to copy, distribute, modify, merge, sell, publish,
 * sublicense or whatever they want with this software but at their OWN RISK.
 *
 *                             Preamble
 *
 * The author has absolutely no clue what the code in this project does.
 * It might just work or not, there is no third option.
 *
 *
 *                 GOOD LUCK WITH THAT PUBLIC LICENSE
 *    TERMS AND CONDITIONS FOR COPYING, DISTRIBUTION, AND MODIFICATION
 *
 *   0. You just DO WHATEVER YOU WANT TO as long as you NEVER LEAVE A
 * TRACE TO TRACK THE AUTHOR of the original product to blame for or hold
 * responsible.
 *
 * IN NO EVENT SHALL THE AUTHORS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER
 * DEALINGS IN THE SOFTWARE.
 *
 * Good luck and Godspeed.
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
