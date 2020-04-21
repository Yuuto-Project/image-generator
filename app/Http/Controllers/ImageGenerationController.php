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

namespace App\Http\Controllers;

use Imagick;
use ImagickDraw;
use ImagickPixel;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class ImageGenerationController extends Controller
{
    const SCALE_FACTOR = 2;

    public function dialog(Request $request)
    {
        $data = $this->validate($request, [
            'background' => 'required|string',
            'character' => 'required|string',
            'text' => 'required|string|max:120',
        ]);

        $cachedImage = storage_path("app/images/dialog/$data[character]_$data[background].png");

        if (!\file_exists($cachedImage)) {
            throw new BadRequestHttpException('This character and background combination does not exist.');
        }

        $img = new Imagick($cachedImage);

        // full size images
        /*$textImage = $this->autofit_text_to_image(
            $img,
            $data['text'],
            50,
            690,
            350,
            0,
            0,
            resource_path('fonts/halogen.regular.ttf'),
            'white',
            1,
            'transparent'
        );*/

        /*$textImage = $this->autofit_text_to_image(
            $img,
            $data['text'],
            50 / self::SCALE_FACTOR,
            690 / self::SCALE_FACTOR,
            (350 / self::SCALE_FACTOR) - 10,
            0,
            0,
            resource_path('fonts/halogen.regular.ttf'),
            'white',
            1,
            'transparent'
        );*/

        $textImage = $this->autofit_text_to_image(
            $img,
            $data['text'],
            50 / self::SCALE_FACTOR,
            680 / self::SCALE_FACTOR,
            340 / self::SCALE_FACTOR,
            0,
            0,
            resource_path('fonts/halogen.regular.ttf'),
            'white',
            1,
            'transparent'
        );

        // scaled down by half
        /*$textImage = $this->autofit_text_to_image(
            $img,
            $data['text'],
            25,
            345,
            170,
            0,
            0,
            resource_path('fonts/halogen.regular.ttf'),
            'white',
            1,
            'transparent'
        );*/

//        $img->compositeImage($textImage, Imagick::COMPOSITE_DEFAULT, 60, 730);
        $img->compositeImage($textImage, Imagick::COMPOSITE_DEFAULT, 63 / self::SCALE_FACTOR, 730 / self::SCALE_FACTOR);
//        $img->compositeImage($textImage, Imagick::COMPOSITE_DEFAULT, 30, 365);
//        $textImage->destroy();

        return response($img)->header('Content-Type', 'image/png');
    }

    public function dialogRaw(Request $request)
    {
        $data = $this->validate($request, [
            'background' => 'required|string',
            'character' => 'required|string',
            'text' => 'required|string|max:120',
        ]);

        $charPath = resource_path("images/dialog/characters/$data[character].png");
        $bgPath = resource_path("images/dialog/backgrounds/$data[background].png");
        $ribbonPath = resource_path("images/dialog/ribbons/$data[character].png");

        if (!\file_exists($charPath)) {
            throw new BadRequestHttpException('This character does not exist.');
        }

        if (!\file_exists($bgPath)) {
            throw new BadRequestHttpException('This background does not exist.');
        }

        if (!\file_exists($ribbonPath)) {
            throw new BadRequestHttpException('Ribbon is missing');
        }

        $flagsTopLeft = new Imagick(resource_path('images/dialog/flag_overlay.png'));
        $ribbon = new Imagick($ribbonPath);
        $charImg = new Imagick($charPath);
        $bgImg = new Imagick($bgPath);

        $bgImg->compositeImage(
            $charImg,
            Imagick::COMPOSITE_DEFAULT,
            0,
            0
        );

        $charImg->destroy();

        $flagsTopLeft->scaleImage(
            $flagsTopLeft->getImageWidth() / 1.3,
            $flagsTopLeft->getImageHeight() / 1.3
        );

        $bgImg->compositeImage(
            $flagsTopLeft,
            Imagick::COMPOSITE_DEFAULT,
            $bgImg->getImageWidth() - $flagsTopLeft->getImageWidth(),
            10
        );

        $flagsTopLeft->destroy();

        $ribbon->scaleImage(
            $ribbon->getImageWidth() / 1.3,
            $ribbon->getImageHeight() / 1.3
        );

        /*$ribbon->scaleImage(
            475,
            206
        );*/

        $bgImg->compositeImage(
            $ribbon,
            Imagick::COMPOSITE_DEFAULT,
            0,
            653
        );

        $ribbon->destroy();

        $textImage = $this->autofit_text_to_image(
            $bgImg,
            $data['text'],
            50,
            680,
            340,
            0,
            0,
            resource_path('fonts/halogen.regular.ttf'),
            'white',
            1,
            'transparent'
        );

        $bgImg->compositeImage($textImage, Imagick::COMPOSITE_DEFAULT, 63, 730);

        $textImage->destroy();

        return response($bgImg)->header('Content-Type', 'image/png');
    }

    /**
     * Inspired from https://gist.github.com/clifgriffin/728cc3a4ce7b81fa2d8a
     *
     * @param Imagick $targeta
     * @param string  $text
     * @param int     $starting_font_size
     * @param int     $max_width
     * @param int     $max_height
     * @param int     $x_pos
     * @param int     $y_pos
     * @param string  $font_file
     * @param string  $font_color
     * @param int     $line_height_ratio
     * @param string  $background_color
     *
     * @return bool|Imagick
     */
    private function autofit_text_to_image(Imagick $targeta, string $text, int $starting_font_size,
                                           int $max_width, int $max_height, int $x_pos, int $y_pos,
                                           string $font_file, string $font_color = 'black', int $line_height_ratio = 1,
                                           string $background_color = 'white')
    {
        if (!$targeta || empty($text) || !$font_file || empty($font_color) || empty($max_width) || empty($max_height)) {
            return false;
        }

        // Load image into Imagick
        $newImage = new Imagick();
        $newImage->newImage($max_width, $max_height, $background_color);

        // Instantiate Imagick utility objects
        $draw = new ImagickDraw();
        $pixel = new ImagickPixel($font_color);

        // Load Font
        $font_size = $starting_font_size;
        $draw->setFont($font_file);
        $draw->setFontSize($font_size);
        $draw->setFillColor($pixel);

        // Holds calculated height of lines with given font, font size
        $total_height = 0;

        // Run until we find a font size that doesn't exceed $max_height in pixels
        while (0 === $total_height || $total_height > $max_height) {
            if ($total_height > 0) {
                $font_size--; // we're still over height, decrement font size and try again
            }

            $draw->setFontSize($font_size);

            // Calculate number of lines / line height
            // Props users Sarke / BMiner: http://stackoverflow.com/questions/5746537/how-can-i-wrap-text-using-imagick-in-php-so-that-it-is-drawn-as-multiline-text
            $words = preg_split('%\s%', $text, -1, PREG_SPLIT_NO_EMPTY);
            $lines = [];
            $i = 0;
            $line_height = 0;

            while (count($words) > 0) {
                $metrics = $newImage->queryFontMetrics($draw, implode(' ', array_slice($words, 0, ++$i)));
                $line_height = max($metrics['textHeight'], $line_height);

                if ($metrics['textWidth'] > $max_width || count($words) < $i) {
                    // this indicates long words and forces the font to decrease in the first loop
                    if ($i == 1) {
                        $total_height = $max_height + 1;
                        continue 2;
                    }

                    $lines[] = implode(' ', array_slice($words, 0, --$i));
                    $words = array_slice($words, $i);
                    $i = 0;
                }
            }

            $total_height = count($lines) * $line_height * $line_height_ratio;

            if ($total_height === 0) {
                return false; // don't run endlessly if something goes wrong
            }
        }

        // Writes text to image
        for ($i = 0; $i < count($lines); $i++) {
            $iToCalc = $i + 1;
            $newImage->annotateImage($draw, $x_pos, $y_pos + ($iToCalc * $line_height * $line_height_ratio), 0, $lines[$i]);
        }

        return $newImage;
    }
}
