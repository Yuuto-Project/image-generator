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

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use GDText\Box;
use GDText\Color;

class ImageGenerationController extends Controller
{
    const SCALE_FACTOR = 2;

    public function dialog(Request $request)
    {
        $data = $this->validate($request, [
            'background' => 'required|string',
            'character' => 'required|string',
            'text' => 'required|string|max:140',
        ]);

        $cachedImage = storage_path("app/images/dialog/$data[character]_$data[background].png");

        if (!\file_exists($cachedImage)) {
            throw new BadRequestHttpException('This character and background combination does not exist.');
        }

        $im = \imagecreatefrompng($cachedImage);

        $box = new Box($im);
        $box->setFontFace(resource_path('fonts/halogen.regular.ttf'));
        $box->setFontColor(new Color(255, 255, 255));
        $box->setFontSize(50 / self::SCALE_FACTOR);
        $box->setBox(
            68 / self::SCALE_FACTOR,
            730 / self::SCALE_FACTOR,
            680 / self::SCALE_FACTOR,
            340 / self::SCALE_FACTOR
        );
        $box->drawFitFontSize($data['text'], 10, 80 / self::SCALE_FACTOR);

        \ob_start();
        \imagepng($im);
        $img = \ob_get_contents();
        \ob_end_clean();

        return response($img)->header('Content-Type', 'image/png');
    }

    public function dialogRaw(Request $request)
    {
        $data = $this->validate($request, [
            'background' => 'required|string',
            'character' => 'required|string',
            'text' => 'required|string|max:140',
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

        $flagsTopLeft = \imagecreatefrompng(resource_path('images/dialog/flag_overlay.png'));
        $ribbon = \imagecreatefrompng($ribbonPath);
        $charImg = \imagecreatefrompng($charPath);
        $bgImg = \imagecreatefrompng($bgPath);
        $textBox = \imagecreatefrompng(resource_path('images/dialog/text_box.png'));

        \imagecopy(
            $bgImg,
            $charImg,
            0,
            0,
            0,
            0,
            \imagesx($charImg), // width
            \imagesy($charImg) // height
        );

        \imagedestroy($charImg);

        $boxWith = \imagesx($textBox);
        $boxHeight = \imagesy($textBox);

        $backgroundWidth = \imagesx($bgImg);
        $backgroundHeight = \imagesy($bgImg);

        $textBox = \imagescale(
            $textBox,
            $boxWith / 1.45,
            $boxHeight / 1.44
        );

        $boxWith = $boxWith / 1.45;
        $boxHeight = $boxHeight / 1.44;

        \imagecopy(
            $bgImg,
            $textBox,
            0,
            $backgroundHeight - $boxHeight + 13,
            0,
            0,
            $boxWith,
            $boxHeight
        );

        \imagedestroy($textBox);

        $flagsWidth = \imagesx($flagsTopLeft);
        $flagsHeight = \imagesy($flagsTopLeft);

        $flagsTopLeft = \imagescale(
            $flagsTopLeft,
            $flagsWidth / 1.3,
            $flagsHeight / 1.3
        );

        $flagsWidth = $flagsWidth / 1.3;
        $flagsHeight = $flagsHeight / 1.3;

        \imagecopy(
            $bgImg,
            $flagsTopLeft,
            $backgroundWidth - $flagsWidth,
            10,
            0,
            0,
            $flagsWidth,
            $flagsHeight
        );

        \imagedestroy($flagsTopLeft);

        $ribbonWidth = \imagesx($ribbon);
        $ribbonHeight = \imagesy($ribbon);

        $ribbon = \imagescale(
            $ribbon,
            $ribbonWidth / 1.3,
            $ribbonHeight / 1.3
        );

        $ribbonWidth = $ribbonWidth / 1.3;
        $ribbonHeight = $ribbonHeight / 1.3;

        \imagecopy(
            $bgImg,
            $ribbon,
            0,
            653,
            0,
            0,
            $ribbonWidth,
            $ribbonHeight
        );

        \imagedestroy($ribbon);

        $box = new Box($bgImg);
        $box->enableDebug();
        $box->setFontFace(resource_path('fonts/halogen.regular.ttf'));
        $box->setFontColor(new Color(255, 255, 255));
        $box->setFontSize(50);
        $box->setBox(
            68,
            730,
            680,
            340
        );
        $box->drawFitFontSize($data['text'], 10, 80);

        \ob_start();
        \imagepng($bgImg);
        $img = \ob_get_contents();
        \ob_end_clean();

        return response($img)->header('Content-Type', 'image/png')->header('Cache-Control', 'no-cache');
    }
}
