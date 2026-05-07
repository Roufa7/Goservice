<?php
session_start();

// Generate a random string
$permitted_chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
$captcha_string = '';
for($i = 0; $i < 6; $i++) {
    $captcha_string .= $permitted_chars[rand(0, strlen($permitted_chars) - 1)];
}

// Store in session
$_SESSION['captcha_code'] = $captcha_string;

// Create image
$width = 200;
$height = 60;
$image = imagecreatetruecolor($width, $height);

// Colors
$background = imagecolorallocate($image, 255, 255, 255);
$text_color = imagecolorallocate($image, 20, 40, 80);
$line_color = imagecolorallocate($image, 200, 200, 200);

imagefilledrectangle($image, 0, 0, $width, $height, $background);

// Add some noise (lines)
for($i = 0; $i < 10; $i++) {
    imageline($image, rand(0, $width), rand(0, $height), rand(0, $width), rand(0, $height), $line_color);
}

// Add text
// Use internal font if custom TTF is not available
$font_size = 5; // Internal font size (1-5)
$x = 40;
$y = 20;

// Draw characters with slight random offset
for($i = 0; $i < strlen($captcha_string); $i++) {
    imagestring($image, 5, $x + ($i * 20), $y + rand(-5, 5), $captcha_string[$i], $text_color);
}

header('Content-type: image/png');
imagepng($image);
imagedestroy($image);
?>
