<?php
class RoadmapGenerator {
    private $width = 3000;  // Increased from 2400 to 3000
    private $height = 800;
    private $taskSpacing = 200;  // Reduced from 300 to 200 to fit more points

    private function getStaticData() {
        return [
            [
                'name' => 'Foundation',
                'timeline' => '0-3',
                'tasks' => [
                    'Establish AI integration guidelines',
                    'Assemble a dedicated AI integration team',
                    'Develop automated model testing and validation proof-of-concept'
                ]
            ],
            [
                'name' => 'Implementation',
                'timeline' => '4-8',
                'tasks' => [
                    'Implement automated model testing and validation system',
                    'Initiate knowledge transfer and collaboration platform development',
                    'Train staff on AI integration best practices'
                ]
            ],
            [
                'name' => 'Integration',
                'timeline' => '9-14',
                'tasks' => [
                    'Roll out knowledge transfer and collaboration platform',
                    'Optimize model deployment pipelines with AI-driven solutions',
                    'Monitor and evaluate AI integration progress'
                ]
            ],
            [
                'name' => 'Optimization',
                'timeline' => '15-20',
                'tasks' => [
                    'Leverage predictive analytics for resource allocation',
                    'Continuously refine AI-driven systems based on feedback',
                    'Promote a data-driven culture within JP TECH'
                ]
            ],
            [
                'name' => 'Scale',
                'timeline' => '21-24',
                'tasks' => [
                    'Expand AI integration to additional teams and projects',
                    'Explore advanced AI capabilities in partnership',
                    'Monitor and report on cost savings and improvements'
                ]
            ]
        ];
    }

    private function createCurvedPath($yStart, $width, $height, $amplitude = 80) {
        $points = [];
        $segments = 100;
        $numWaves = 4; // Increase number of waves for more zig-zags
        
        for ($i = 0; $i <= $segments; $i++) {
            $x = ($i / $segments) * $width;
            // Use a modified sine function to create sharper curves
            $y = $yStart + $amplitude * sin((2 * M_PI * $numWaves * $x) / $width);
            $points[] = [$x, $y];
        }
        
        return $points;
    }

    private function getYPositionOnCurve($x) {
        $roadY = $this->height / 2;
        $amplitude = 80;
        $numWaves = 4;
        return $roadY + $amplitude * sin((2 * M_PI * $numWaves * $x) / $this->width);
    }

    private function createRoadPath() {
        $roadY = $this->height / 2;
        $roadHeight = 40;
        
        $topPoints = $this->createCurvedPath($roadY - $roadHeight/2, $this->width, $this->height);
        $bottomPoints = $this->createCurvedPath($roadY + $roadHeight/2, $this->width, $this->height);
        
        // Create path
        $path = "M" . $topPoints[0][0] . "," . $topPoints[0][1] . " ";
        
        // Add top curve
        foreach ($topPoints as $point) {
            $path .= "L" . $point[0] . "," . $point[1] . " ";
        }
        
        // Add bottom curve (in reverse)
        for ($i = count($bottomPoints) - 1; $i >= 0; $i--) {
            $path .= "L" . $bottomPoints[$i][0] . "," . $bottomPoints[$i][1] . " ";
        }
        
        $path .= "Z";
        return $path;
    }


    private function createLocationMarker($x, $y, $number) {
        $adjustedY = $this->getYPositionOnCurve($x);
        
        return sprintf('
            <g transform="translate(%d,%d)">
                <path d="M0,-35 C-15,-35 -25,-25 -25,-10 C-25,10 0,30 0,30 C0,30 25,10 25,-10 C25,-25 15,-35 0,-35Z" 
                    fill="#FF0000" 
                    stroke="#ffffff" 
                    stroke-width="2"
                    filter="url(#shadow)" />
                <circle cx="0" cy="-10" r="18" 
                    fill="#ffffff" 
                    stroke="#FF0000" 
                    stroke-width="2" />
                <text x="0" y="-6" 
                    text-anchor="middle" 
                    fill="#FF0000" 
                    font-size="16" 
                    font-weight="bold">%d</text>
            </g>',
            $x, $adjustedY, $number
        );
    }

     private function createTaskMarker($x, $y, $number, $task) {
        $adjustedY = $this->getYPositionOnCurve($x);
        
        return sprintf('
            <g transform="translate(%d,%d)">
                <!-- Task text positioned above with reduced gap (from -120 to -40) -->
                <text x="0" y="-70" 
                    text-anchor="middle" 
                    fill="#333333" 
                    font-size="12">%s</text>
                
                <!-- Location marker -->
                <path d="M0,-20 C-10,-20 -15,-15 -15,-5 C-15,5 0,20 0,20 C0,20 15,5 15,-5 C15,-15 10,-20 0,-20Z" 
                    fill="#0078D4" 
                    stroke="#ffffff" 
                    stroke-width="1"
                    filter="url(#shadow)" />
                <circle cx="0" cy="-5" r="8" 
                    fill="#ffffff" 
                    stroke="#0078D4" 
                    stroke-width="1" />
                <text x="0" y="-2" 
                    text-anchor="middle" 
                    fill="#0078D4" 
                    font-size="10" 
                    font-weight="bold">%d</text>
            </g>',
            $x, $adjustedY - 40,
            htmlspecialchars($task),
            $number
        );
    }

    private function createPhaseHeading($x, $y, $phase) {
        $adjustedY = $this->getYPositionOnCurve($x);
        
        return sprintf('
            <g transform="translate(%d,%d)">
                <!-- Phase heading with pointer shape -->
                <path d="
                    M-100,-300 
                    H80 
                    C90,-300 100,-300 100,-290
                    V-270 
                    C100,-260 90,-260 80,-260
                    H20
                    L0,-240
                    L-20,-260
                    H-100
                    C-110,-260 -120,-260 -120,-270
                    V-290
                    C-120,-300 -110,-300 -100,-300
                    Z" 
                    fill="#E8F1FB" 
                    filter="url(#shadow)"/>
                
                <!-- Phase name -->
                <text x="-10" y="-275" 
                    text-anchor="middle" 
                    fill="#333333" 
                    font-size="16" 
                    font-weight="bold">%s</text>
                
                <!-- Timeline badge - made smaller and repositioned -->
                <rect x="-50" y="-265" width="80" height="22" rx="11" 
                    fill="#0078D4" 
                    filter="url(#shadow)" />
                <text x="-10" y="-250" 
                    text-anchor="middle" 
                    fill="white" 
                    font-size="12" 
                    font-weight="bold">Month %s</text>
            </g>',
            $x, $adjustedY,
            htmlspecialchars($phase['name']),
            htmlspecialchars($phase['timeline'])
        );
    }

    private function createDashedLine($points) {
        $path = "M" . $points[0][0] . "," . $points[0][1] . " ";
        foreach ($points as $point) {
            $path .= "L" . $point[0] . "," . $point[1] . " ";
        }
        return $path;
    }

    private function createRoadmapSVG($phases) {
        // Calculate total tasks and ensure minimum width
        $totalTasks = array_reduce($phases, fn($carry, $phase) => $carry + count($phase['tasks']), 0);
        $this->width = max($this->width, $this->taskSpacing * ($totalTasks + 1)); // Added buffer

        $svg = sprintf('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 %d %d">', 
            $this->width, $this->height);

        $svg = sprintf('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 %d %d">', 
            $this->width, $this->height);
        
        $svg .= <<<DEFS
        <defs>
            <filter id="shadow" x="-20%" y="-20%" width="140%" height="140%">
                <feDropShadow dx="2" dy="2" stdDeviation="3" flood-opacity="0.2"/>
            </filter>
        </defs>
DEFS;

        $roadY = $this->height / 2;
        $roadPath = $this->createRoadPath();

        // Draw the base road
        $svg .= sprintf('
            <path d="%s" 
                fill="#505050" 
                stroke="#404040"
                stroke-width="1"/>',
            $roadPath
        );

        // Draw the center dashed line
        $centerLinePoints = $this->createCurvedPath($roadY, $this->width, $this->height);
        $svg .= sprintf('
            <path d="%s"
                fill="none"
                stroke="#ffffff" 
                stroke-width="2" 
                stroke-dasharray="10,10"/>',
            $this->createDashedLine($centerLinePoints)
        );

        $taskNumber = 1;
        $taskX = $this->taskSpacing;

        foreach ($phases as $index => $phase) {
            $phaseMarkerX = $taskX - ($this->taskSpacing / 2);
            $svg .= $this->createPhaseHeading($phaseMarkerX, $roadY - 100, $phase);
            $svg .= $this->createLocationMarker($phaseMarkerX, $roadY, $index + 1);
            
            foreach ($phase['tasks'] as $task) {
                $svg .= $this->createTaskMarker($taskX, $roadY, $taskNumber, $task);
                $taskX += $this->taskSpacing;
                $taskNumber++;
            }
        }

        $svg .= '</svg>';
        return $svg;
    }

    public function generateRoadmap() {
        $phases = $this->getStaticData();
        return $this->createRoadmapSVG($phases);
    }
}

// function displayRoadmap() {
//     $generator = new RoadmapGenerator();
//     $roadmapSVG = $generator->generateRoadmap();
    
//     echo <<<HTML
//     <!DOCTYPE html>
//     <html>
//     <head>
//         <style>
//             .roadmap-container {
//                 margin: 2rem auto;
//                 max-width: 100%;
//                 padding: 20px;
//                 background: white;
//                 border-radius: 12px;
//                 box-shadow: 0 2px 8px rgba(0,0,0,0.1);
//                 overflow-x: auto;
//             }
//             .roadmap-visualization {
//                 width: 100%;
//                 overflow-x: auto;
//                 padding: 20px 0;
//                 scrollbar-width: thin;
//                 scrollbar-color: #0078D4 #f0f0f0;
//             }
//             .roadmap-visualization::-webkit-scrollbar {
//                 height: 8px;
//             }
//             .roadmap-visualization::-webkit-scrollbar-track {
//                 background: #f0f0f0;
//                 border-radius: 4px;
//             }
//             .roadmap-visualization::-webkit-scrollbar-thumb {
//                 background: #0078D4;
//                 border-radius: 4px;
//             }
//             .roadmap-visualization svg {
//                 width: 100%;
//                 height: auto;
//                 min-width: 2400px;
//             }
//         </style>
//     </head>
//     <body>
//         <div class="roadmap-container">
//             <div class="roadmap-visualization">
//                 $roadmapSVG
//             </div>
//         </div>
//     </body>
//     </html>
// HTML;
// }
function displayRoadmap() {
    $generator = new RoadmapGenerator();
    $roadmapSVG = $generator->generateRoadmap();
    
    echo <<<HTML
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            .roadmap-container {
                margin: 2rem auto;
                max-width: 100%;
                padding: 20px;
                background: white;
                border-radius: 12px;
                box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                overflow-x: auto;
            }
            .roadmap-visualization {
                width: 100%;
                overflow-x: auto;
                padding: 20px 0;
                scrollbar-width: thin;
                scrollbar-color: #0078D4 #f0f0f0;
            }
            .roadmap-visualization::-webkit-scrollbar {
                height: 8px;
            }
            .roadmap-visualization::-webkit-scrollbar-track {
                background: #f0f0f0;
                border-radius: 4px;
            }
            .roadmap-visualization::-webkit-scrollbar-thumb {
                background: #0078D4;
                border-radius: 4px;
            }
            .roadmap-visualization svg {
                width: 100%;
                height: auto;
                min-width: 3000px;  // Updated to match new width
            }
        </style>
    </head>
    <body>
        <div class="roadmap-container">
            <div class="roadmap-visualization">
                $roadmapSVG
            </div>
        </div>
    </body>
    </html>
HTML;
}

displayRoadmap();
?>