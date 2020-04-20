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

        // load the characters and backgrounds
        $bgDir = new DirectoryIterator($imagesBasePath . 'backgrounds/');
        $charDir = new DirectoryIterator($imagesBasePath . 'characters/');

        /*foreach ($bgDir as $background) {
            if ($background->isDot()) {
                continue;
            }

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

                $bgImg->scaleImage(
                    $bgImg->getImageWidth() / 2,
                    $bgImg->getImageHeight() / 2
                );

                $bgImg->writeImage($outputName);

                $end = microtime(true);

                $this->info('Took ' . ($end - $start) . ' seconds to generate ' . $charName . ' on ' . $bgName);
            }
        }*/

        // Loop over the characters and load them before the backgrounds
        // This is done to only load the character once and have more efficient code
        foreach ($charDir as $character) {
            if ($character->isDot()) {
                continue;
            }

            $charName = $this->stripExtension($character->getFilename());
            $charPath = $character->getRealPath();

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
