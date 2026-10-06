<?php
defined('BASEPATH') or exit('No direct script access allowed');

// Require Composer's autoloader
require_once FCPATH . 'vendor/autoload.php';

class ImageText extends CI_Controller
{
    public function index()
    {
        // Define file paths
        $sourceImage = FCPATH . 'uploads/source1.png';
        $destinationImage = FCPATH . 'uploads/destination1.png';
        $fontPath = FCPATH . 'admin_assets/fonts/DejaVuSans.ttf'; // Update with your font path

        // Ensure the source image exists
        if (!file_exists($sourceImage)) {
            show_error('Source image not found!');
        }

        // Create image instance
        $image = new \NMC\ImageWithText\Image($sourceImage);

        // Add the first styled text block
        $text1 = new \NMC\ImageWithText\Text('Thanks for using our image text PHP library!', 3, 25);
        $text1->align = 'left';
        $text1->color = 'FFFFFF';
        $text1->font = $fontPath;
        $text1->lineHeight = 36;
        $text1->size = 24;
        $text1->startX = 40;
        $text1->startY = 40;
        $image->addText($text1);

        // Add the second styled text block
        $text2 = new \NMC\ImageWithText\Text('No, really, thanks!', 1, 30);
        $text2->align = 'left';
        $text2->color = '000000';
        $text2->font = $fontPath;
        $text2->lineHeight = 20;
        $text2->size = 14;
        $text2->startX = 40;
        $text2->startY = 140;
        $image->addText($text2);

        // Render and save the image
        $image->render($destinationImage);

        echo "Image created successfully at: " . base_url('uploads/destination1.png');
    }
}
