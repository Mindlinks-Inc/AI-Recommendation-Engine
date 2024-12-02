<?php

class RoadmapGenerator {
    private $width = 1200;
    private $height = 800;
    private $image;
    private $mainColor;
    
    public function __construct() {
        // Create image with white background
        $this->image = imagecreatetruecolor($this->width, $this->height);
        
        // Set background to white
        $white = imagecolorallocate($this->image, 255, 255, 255);
        imagefilledrectangle($this->image, 0, 0, $this->width, $this->height, $white);
        
        // Set main color (pink/red as shown in the image)
        $this->mainColor = imagecolorallocate($this->image, 255, 182, 193);
    }
    
    private function drawCircle($x, $y, $radius) {
        // Draw circle outline
        imagesetthickness($this->image, 2);
        imagearc($this->image, $x, $y, $radius * 2, $radius * 2, 0, 360, $this->mainColor);
    }
    
    private function drawHexagon($x, $y, $size) {
        $points = [];
        for ($i = 0; $i < 6; $i++) {
            $angle = $i * 60 * M_PI / 180;
            $points[] = $x + $size * cos($angle);
            $points[] = $y + $size * sin($angle);
        }
        
        // Draw hexagon outline
        imagesetthickness($this->image, 2);
        for ($i = 0; $i < 6; $i++) {
            $next = ($i + 1) % 6;
            imageline(
                $this->image,
                $points[$i * 2],
                $points[$i * 2 + 1],
                $points[$next * 2],
                $points[$next * 2 + 1],
                $this->mainColor
            );
        }
    }
    
    private function drawConnectingLine($x1, $y1, $x2, $y2) {
        // Draw straight line connecting points
        imagesetthickness($this->image, 2);
        imageline($this->image, $x1, $y1, $x2, $y2, $this->mainColor);
    }
    
    public function generate() {
        // Starting coordinates
        $startX = 200;
        $startY = 400;
        $spacing = 200;
        
        // Draw first three circles
        for ($i = 0; $i < 3; $i++) {
            $x = $startX + ($i * $spacing);
            $this->drawCircle($x, $startY, 30);
            
            // Draw connecting line to next point if not last circle
            if ($i < 2) {
                $this->drawConnectingLine(
                    $x + 30, 
                    $startY,
                    $x + $spacing - 30,
                    $startY
                );
            }
        }
        
        // Draw hexagon at the end
        $hexX = $startX + (3 * $spacing);
        $this->drawHexagon($hexX, $startY, 35);
        
        // Draw final connecting line to hexagon
        $this->drawConnectingLine(
            $startX + (2 * $spacing) + 30,
            $startY,
            $hexX - 35,
            $startY
        );
        
        // Save the image
        imagepng($this->image, 'roadmap.png');
        imagedestroy($this->image);
    }
}

// Create and generate the roadmap
$generator = new RoadmapGenerator();
$generator->generate();
echo "Roadmap has been generated as 'roadmap.png'";