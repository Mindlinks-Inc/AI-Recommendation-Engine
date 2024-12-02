<?php
ob_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'php_error.log');

session_start();
require_once(__DIR__ . '/../tcpdf/tcpdf.php');

// Function to log messages
function debug_log($message) {
    error_log($message);
    echo $message . "<br>";
}

debug_log("Script started");

$servername = "127.0.0.1";
$username = "u783522058_QTyvg";
$password = "TGF0v4G5Mv";
$dbname = "u783522058_gt5An";

try {
    $db = new DatabaseConnection($servername, $username, $password, $dbname);
    $conn = $db->getConnection();
    
    if (!$conn) {
        throw new Exception("Failed to establish database connection");
    }
    
    debug_log("Database connection established successfully");
    
} catch (Exception $e) {
    debug_log("Database connection error: " . $e->getMessage());
    die("Connection failed: " . $e->getMessage());
}

debug_log("Database connection established");


class DatabaseConnection {
    private $conn;
    private $config;
    
    public function __construct($servername, $username, $password, $dbname) {
        $this->config = [
            'servername' => $servername,
            'username' => $username,
            'password' => $password,
            'dbname' => $dbname,
            'max_retries' => 3,
            'retry_delay' => 2
        ];
        
        $this->connect();
    }
    
    private function connect() {
        $retries = 0;
        
        while ($retries < $this->config['max_retries']) {
            try {
                $this->conn = new mysqli(
                    $this->config['servername'],
                    $this->config['username'],
                    $this->config['password'],
                    $this->config['dbname']
                );

                // Set session wait_timeout and other crucial settings
                $this->conn->query("SET session wait_timeout=28800"); // 8 hours
                $this->conn->query("SET session interactive_timeout=28800");
                $this->conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 300);
                $this->conn->options(MYSQLI_OPT_READ_TIMEOUT, 300);
                
                if (!$this->conn->connect_error) {
                    return true;
                }
                
            } catch (Exception $e) {
                debug_log("Connection attempt " . ($retries + 1) . " failed: " . $e->getMessage());
            }
            
            $retries++;
            if ($retries < $this->config['max_retries']) {
                sleep($this->config['retry_delay']);
            }
        }
        
        throw new Exception("Failed to connect to database after {$this->config['max_retries']} attempts");
    }
    
    public function getConnection() {
        if (!$this->conn->ping()) {
            $this->connect();
        }
        return $this->conn;
    }
    
    public function prepare($query) {
        $retries = 0;
        
        while ($retries < $this->config['max_retries']) {
            try {
                if (!$this->conn->ping()) {
                    $this->connect();
                }
                
                $stmt = $this->conn->prepare($query);
                if ($stmt) {
                    return $stmt;
                }
                
            } catch (Exception $e) {
                debug_log("Prepare attempt " . ($retries + 1) . " failed: " . $e->getMessage());
            }
            
            $retries++;
            if ($retries < $this->config['max_retries']) {
                sleep($this->config['retry_delay']);
            }
        }
        
        throw new Exception("Failed to prepare statement after {$this->config['max_retries']} attempts");
    }
}

// Replace your existing connection code with this
try {
    $db = new DatabaseConnection($servername, $username, $password, $dbname);
    $conn = $db->getConnection();
    
    if (!$conn) {
        throw new Exception("Failed to establish database connection");
    }
    
    debug_log("Database connection established successfully");
    
} catch (Exception $e) {
    debug_log("Database connection error: " . $e->getMessage());
    die("Connection failed: " . $e->getMessage());
}


// Utility Functions
function addColumnIfNotExists($conn, $table, $column, $type) {
    $check = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
    if ($check === false) {
        debug_log("Error checking for column existence: " . $conn->error);
        return false;
    }
    if ($check->num_rows == 0) {
        $sql = "ALTER TABLE `$table` ADD `$column` $type";
        if ($conn->query($sql) === FALSE) {
            debug_log("Error adding column $column: " . $conn->error);
            return false;
        }
        debug_log("Column $column added successfully");
    }
    return true;
}

function getClientIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    }
    return $_SERVER['REMOTE_ADDR'];
}

function generateFileName($companyName) {
    $safeName = preg_replace('/[^a-z0-9]+/', '_', strtolower($companyName));
    $timestamp = date('Y-m-d_H-i-s');
    return "ai_recommendations_{$safeName}_{$timestamp}.html";
}



class RoadmapGenerator {
    private $width = 3000;
    private $height = 800;
    private $taskSpacing = 200;
    private $maxRetries = 3;
    private $retryDelay = 3;
    private $minProcessingTime = 2;
    
    private function validateOutput($output) {
        // First check if output exists
        if ($output === null || $output === false) {
            debug_log("Validation failed: Output is null or false");
            return false;
        }

        // Handle array output (from parseRecommendationsForRoadmap)
        if (is_array($output)) {
            if (empty($output)) {
                debug_log("Validation failed: Empty array output");
                return false;
            }
            // Check if it's the phases array with required structure
            foreach ($output as $phase) {
                if (!isset($phase['name']) || !isset($phase['timeline']) || !isset($phase['tasks'])) {
                    debug_log("Validation failed: Invalid phase structure - " . print_r($phase, true));
                    return false;
                }
            }
            debug_log("Validated " . count($output) . " phases successfully");
            return true;
        }

        // Handle string output (SVG content)
        if (is_string($output)) {
            if (strlen($output) < 500) {
                debug_log("Validation failed: SVG content too short (" . strlen($output) . " bytes)");
                return false;
            }
            if (strpos($output, '<svg') === false) {
                debug_log("Validation failed: No SVG tag found");
                return false;
            }
            debug_log("Validated SVG content successfully");
            return true;
        }

        debug_log("Validation failed: Unexpected output type: " . gettype($output));
        return false;
    }
    
     private function validateAndRetry($function, $params = []) {
        $attempts = 0;
        while ($attempts < $this->maxRetries) {
            try {
                $result = call_user_func_array([$this, $function], $params);
                if ($this->validateOutput($result)) {
                    return $result;
                }
                throw new Exception("Invalid output generated for function: " . $function);
            } catch (Exception $e) {
                $attempts++;
                $this->lastError = $e->getMessage();
                debug_log("Attempt {$attempts}: {$this->lastError}");
                
                if ($attempts < $this->maxRetries) {
                    sleep($this->retryDelay * $attempts);
                }
            }
        }
        return false;
    }
    


     public function generateRoadmap($recommendations) {
        try {
            debug_log("Starting roadmap generation");
            
            // Parse recommendations into phases
            $phases = $this->validateAndRetry('parseRecommendationsForRoadmap', [$recommendations]);
            if (!$phases) {
                debug_log("Failed to parse recommendations into phases");
                return $this->generateFallbackVisualization();
            }

            // Generate SVG from phases
            $svg = $this->validateAndRetry('createRoadmapSVG', [$phases]);
            if (!$svg) {
                debug_log("Failed to create SVG from phases");
                return $this->generateFallbackVisualization();
            }

            debug_log("Roadmap generated successfully");
            return $svg;

        } catch (Exception $e) {
            debug_log("Error in generateRoadmap: " . $e->getMessage());
            return $this->generateFallbackVisualization();
        }
    }

    private function addMetadata($svg) {
        // Add generation timestamp and version info
        $timestamp = date('Y-m-d H:i:s');
        $metadata = "<!-- Generated: {$timestamp} -->\n";
        return $metadata . $svg;
    }
    
     private function waitForProcessing() {
        // Ensure minimum processing time
        $startTime = microtime(true);
        $elapsedTime = 0;
        
        while ($elapsedTime < $this->minProcessingTime) {
            usleep(100000); // Sleep for 0.1 seconds
            $elapsedTime = microtime(true) - $startTime;
        }
    }
    
    
    private function validateContent($phases) {
        if (empty($phases)) {
            debug_log("No phases found in roadmap content");
            return false;
        }

        foreach ($phases as $phase) {
            if (empty($phase['name']) || empty($phase['timeline']) || empty($phase['tasks'])) {
                debug_log("Invalid phase structure detected");
                return false;
            }

            // Validate tasks
            $validTasks = array_filter($phase['tasks'], function($task) {
                return !empty(trim($task)) && strlen(trim($task)) > 5;
            });

            if (empty($validTasks)) {
                debug_log("No valid tasks found in phase: " . $phase['name']);
                return false;
            }
        }

        return true;
    }



    private function createCurvedPath($yStart, $width, $height, $amplitude = 80) {
        $points = [];
        $segments = 100;
        $numWaves = 4;
        
        for ($i = 0; $i <= $segments; $i++) {
            $x = ($i / $segments) * $width;
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
        
        $path = "M" . $topPoints[0][0] . "," . $topPoints[0][1] . " ";
        
        foreach ($topPoints as $point) {
            $path .= "L" . $point[0] . "," . $point[1] . " ";
        }
        
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
                <text x="0" y="-70" 
                    text-anchor="middle" 
                    fill="#333333" 
                    font-size="12">%s</text>
                
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
                
                <text x="-10" y="-275" 
                    text-anchor="middle" 
                    fill="#333333" 
                    font-size="16" 
                    font-weight="bold">%s</text>
                
                <rect x="-130" y="-265" width="240" height="22" rx="11" 
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
        $totalTasks = array_reduce($phases, fn($carry, $phase) => $carry + count($phase['tasks']), 0);
        $this->width = max($this->width, $this->taskSpacing * ($totalTasks + 1));

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

        $svg .= sprintf('
            <path d="%s" 
                fill="#505050" 
                stroke="#404040"
                stroke-width="1"/>',
            $roadPath
        );

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

//     public function generateRoadmap($recommendations) {
//     $attempts = 0;
//     $success = false;
//     $lastError = null;
    
//     while ($attempts < $this->maxRetries && !$success) {
//         try {
//             debug_log("Attempt " . ($attempts + 1) . " to generate roadmap");
            
//             $this->waitForProcessing();
//             $phases = $this->parseRecommendationsForRoadmap($recommendations);
            
//             if (!$this->validateContent($phases)) {
//                 throw new Exception("Invalid or incomplete roadmap content");
//             }
            
//             $svg = $this->createRoadmapSVG($phases);
//             $svg = $this->validateAndPersistSVG($svg);
            
//             sleep($this->renderDelay);
            
//             debug_log("Roadmap generated successfully");
//             return $svg;
            
//         } catch (Exception $e) {
//             $lastError = $e->getMessage();
//             debug_log("Attempt " . ($attempts + 1) . " failed: " . $lastError);
//             $attempts++;
            
//             if ($attempts < $this->maxRetries) {
//                 sleep($this->retryDelay * $attempts); // Progressive delay
//             }
//         }
//     }
    
//     debug_log("All roadmap generation attempts failed");
//     return $this->generateFallbackVisualization();
// }
    
    private function validateAndPersistSVG($svg) {
    if (!$this->validateSVG($svg)) {
        throw new Exception("Invalid SVG content");
    }
    
    // Add unique identifier to SVG
    $svgId = uniqid('roadmap_');
    $svg = str_replace('<svg', '<svg id="' . $svgId . '"', $svg);
    
    return $svg;
}

    
    
    private function validateSVG($svg) {
        if (empty($svg)) {
            debug_log("Empty SVG content");
            return false;
        }

        // Check for minimum SVG size
        if (strlen($svg) < 500) {
            debug_log("SVG content too small: " . strlen($svg) . " bytes");
            return false;
        }

        // Verify essential SVG elements
        $requiredElements = [
            '<svg',
            'viewBox',
            '<path',
            '<text',
            '<g transform'
        ];

        foreach ($requiredElements as $element) {
            if (strpos($svg, $element) === false) {
                debug_log("Missing required SVG element: " . $element);
                return false;
            }
        }

        return true;
    }
    
    private function generateFallbackVisualization() {
        debug_log("Generating fallback visualization");
        return <<<SVG
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 {$this->width} {$this->height}">
            <rect width="100%" height="100%" fill="#f8f9fa"/>
            <text x="50%" y="40%" text-anchor="middle" font-size="24" fill="#666">
                Roadmap Generation in Progress
            </text>
            <text x="50%" y="50%" text-anchor="middle" font-size="16" fill="#999">
                Please refresh the page in a few moments
            </text>
            <script type="text/javascript">
                setTimeout(function() {
                    window.location.reload();
                }, 5000);
            </script>
        </svg>
SVG;
    }
    



 private function parseRecommendationsForRoadmap($recommendations) {
        if (empty($recommendations)) {
            debug_log("Empty recommendations provided");
            throw new Exception("Empty recommendations");
        }

        // Debug the input
        debug_log("Parsing recommendations. Length: " . strlen($recommendations));
        debug_log("First 500 chars: " . substr($recommendations, 0, 500));

        // Try multiple phase heading patterns
        $patterns = [
            '/\*\*Phase (\d+)[:\s]+\((.*?)\)\*\*(.*?)(?=\*\*Phase|\Z)/s',  // **Phase 1: (Timeline)** format
            '/\*Phase (\d+)[:\s]+\((.*?)\)\*(.*?)(?=\*Phase|\Z)/s',        // *Phase 1: (Timeline)* format
            '/Phase (\d+)[:\s]+\((.*?)\)(.*?)(?=Phase|\Z)/s',              // Phase 1: (Timeline) format
            '/\*\*Implementation Phase (\d+)[:\s]+\((.*?)\)\*\*(.*?)(?=\*\*Implementation Phase|\Z)/s' // **Implementation Phase 1: (Timeline)** format
        ];

        $phases = [];
        $matched = false;

        foreach ($patterns as $pattern) {
            preg_match_all($pattern, $recommendations, $matches, PREG_SET_ORDER);
            
            if (!empty($matches)) {
                debug_log("Found matches using pattern: " . $pattern);
                $matched = true;
                
                foreach ($matches as $match) {
                    $phase_number = trim($match[1]);
                    $timeline = trim($match[2]);
                    $content = trim($match[3]);
                    
                    // Debug the matched content
                    debug_log("Matched Phase " . $phase_number . " with timeline: " . $timeline);
                    debug_log("Content snippet: " . substr($content, 0, 100));

                    $tasks = [];
                    $lines = explode("\n", $content);
                    foreach ($lines as $line) {
                        $line = trim($line);
                        // Match different bullet point styles
                        if (preg_match('/^[-•●\*]\s*(.+)$/', $line, $task_match) || 
                            preg_match('/^\d+\.\s*(.+)$/', $line, $task_match)) {
                            $task = trim($task_match[1]);
                            if (!empty($task)) {
                                $tasks[] = $task;
                            }
                        }
                    }

                    if (!empty($tasks)) {
                        $phases[] = [
                            'name' => "Phase " . $phase_number,
                            'timeline' => $timeline,
                            'tasks' => $tasks
                        ];
                    }
                }
                
                break; // Exit after first successful pattern match
            }
        }

        if (!$matched) {
            debug_log("No phase matches found with any pattern");
            // Try to extract any numbered sections as phases
            if (preg_match_all('/(\d+)\.\s*(.*?)(?=\d+\.|$)/s', $recommendations, $fallback_matches, PREG_SET_ORDER)) {
                debug_log("Found numbered sections to use as phases");
                foreach ($fallback_matches as $match) {
                    $phase_number = trim($match[1]);
                    $content = trim($match[2]);
                    
                    $tasks = [];
                    $lines = explode("\n", $content);
                    foreach ($lines as $line) {
                        $line = trim($line);
                        if (!empty($line) && strlen($line) > 10) { // Basic validation for task content
                            $tasks[] = $line;
                        }
                    }
                    
                    if (!empty($tasks)) {
                        $phases[] = [
                            'name' => "Phase " . $phase_number,
                            'timeline' => "Month " . ($phase_number * 3), // Estimate timeline
                            'tasks' => $tasks
                        ];
                    }
                }
            }
        }

        if (empty($phases)) {
            debug_log("No valid phases extracted after all attempts");
            throw new Exception("No valid phases extracted from recommendations");
        }

        debug_log("Successfully extracted " . count($phases) . " phases");
        return $phases;
    }

}



function insertRoadmapIntoHTML($html, $recommendations) {
    debug_log("Starting roadmap insertion with improved validation");
    
    // Define roadmap styles
    $roadmap_styles = <<<STYLES
        .roadmap-container {
        margin: 2rem auto;
        max-width: 100%;
        padding: 20px;
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        overflow: hidden;
    }
        
        .roadmap-container.loading::after {
            content: 'Loading roadmap...';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 16px;
            color: white;
        }
        
        .roadmap-visualization {
        width: 100%;
        overflow-x: auto;
        padding: 20px 0;
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
        width: 3000px;
        height: auto;
        min-height: 400px;
        display: block;
    }
        @media (max-width: 768px) {
            .roadmap-container {
                margin: 1rem 0;
                padding: 10px;
            }
            .roadmap-visualization {
                padding: 10px 0;
            }
        }
STYLES;

    try {
        // Add retry counter in session
        if (!isset($_SESSION['roadmap_retries'])) {
            $_SESSION['roadmap_retries'] = 0;
        }
        
        $roadmapGenerator = new RoadmapGenerator();
        $roadmap_svg = $roadmapGenerator->generateRoadmap($recommendations);
        
        if ($roadmap_svg && strpos($roadmap_svg, '<svg') !== false) {
            // Create the roadmap section with loading state and verification script
            $roadmap_section = sprintf(
                '<div class="roadmap-container loading" id="roadmap-%s">
                    <div class="roadmap-visualization">%s</div>
                </div>
                <script>
                    (function() {
                        let retryCount = 0;
                        const maxRetries = 3;
                        const roadmapContainer = document.getElementById("roadmap-%s");
                        
                        function checkRoadmap() {
                            const svg = roadmapContainer.querySelector("svg");
                            if (svg && svg.getBoundingClientRect().height > 100) {
                                roadmapContainer.classList.remove("loading");
                                return true;
                            }
                            return false;
                        }
                        
                        function verifyRoadmap() {
                            if (checkRoadmap()) {
                                return;
                            }
                            
                            retryCount++;
                            if (retryCount < maxRetries) {
                                console.log("Retrying roadmap render...", retryCount);
                                setTimeout(verifyRoadmap, 2000);
                            } else {
                                // Only refresh if session retry count is less than 3
                                const sessionRetries = %d;
                                if (sessionRetries < 3) {
                                    console.log("Refreshing page for roadmap...");
                                    window.location.reload();
                                } else {
                                    console.log("Max retries reached");
                                    roadmapContainer.innerHTML = `
                                        <div style="text-align: center; padding: 20px;">
                                            <p>Unable to load roadmap. Please try refreshing the page.</p>
                                            <button onclick="window.location.reload()" 
                                                    style="padding: 10px 20px; margin-top: 10px; 
                                                    background: #0078D4; color: white; 
                                                    border: none; border-radius: 4px; 
                                                    cursor: pointer;">
                                                Refresh Page
                                            </button>
                                        </div>
                                    `;
                                }
                            }
                        }
                        
                        // Start verification after a short delay
                        setTimeout(verifyRoadmap, 1000);
                    })();
                </script>',
                uniqid(),
                $roadmap_svg,
                uniqid(),
                $_SESSION['roadmap_retries']
            );
            
            // Increment retry counter
            $_SESSION['roadmap_retries']++;
            
            // Insert the roadmap section and styles
            $search_text = '**Implementation Roadmap**';
            $pos = strpos($html, $search_text);
            
            if ($pos !== false) {
                // Insert roadmap section
                $html = substr_replace($html, $roadmap_section, $pos + strlen($search_text), 0);
                
                // Insert styles
                $style_pos = strpos($html, '</style>');
                if ($style_pos !== false) {
                    $html = substr_replace($html, $roadmap_styles, $style_pos, 0);
                }
                
                debug_log("Roadmap inserted successfully");
            }
        } else {
            debug_log("Invalid roadmap SVG generated");
            throw new Exception("Invalid roadmap SVG content");
        }
        
    } catch (Exception $e) {
        debug_log("Error in roadmap insertion: " . $e->getMessage());
        // Add error message to the page instead of infinite refresh
        $error_section = '
            <div class="roadmap-container">
                <div style="text-align: center; padding: 20px;">
                    <p>Unable to generate roadmap visualization.</p>
                    <button onclick="window.location.reload()" 
                            style="padding: 10px 20px; margin-top: 10px; 
                            background: #0078D4; color: white; 
                            border: none; border-radius: 4px; 
                            cursor: pointer;">
                        Try Again
                    </button>
                </div>
            </div>';
        
        $search_text = '**Implementation Roadmap**';
        $pos = strpos($html, $search_text);
        if ($pos !== false) {
            $html = substr_replace($html, $error_section, $pos + strlen($search_text), 0);
        }
    }
    
    return $html;
}


function insertRoadmapIntoPDF($pdf, $recommendations) {
    try {
        debug_log("Starting roadmap insertion into PDF");
        
        $roadmapGenerator = new RoadmapGenerator();
        $roadmap_svg = $roadmapGenerator->generateRoadmap($recommendations);
        
        // Save SVG to temporary file with proper XML declaration and encoding
        $temp_file = tempnam(sys_get_temp_dir(), 'roadmap_');
        $svg_content = '<?xml version="1.0" encoding="UTF-8" standalone="no"?>' . "\n" . $roadmap_svg;
        file_put_contents($temp_file, $svg_content);
        
        // Calculate dimensions for the roadmap
        $svg_width = 3000; // Original width from RoadmapGenerator
        $svg_height = 800; // Original height from RoadmapGenerator
        
        // Calculate scaling to fit PDF page width while maintaining aspect ratio
        $pdf_max_width = $pdf->getPageWidth() - 20; // 10mm margins on each side
        $scale = $pdf_max_width / $svg_width;
        $pdf_height = $svg_height * $scale;
        
        // Get current Y position
        $current_y = $pdf->GetY();
        
        // Add new page if needed
        if ($current_y + $pdf_height > $pdf->getPageHeight() - 20) {
            $pdf->AddPage();
            $current_y = $pdf->GetY();
        }
        
        // Add section title
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->SetTextColor(21, 60, 190);
        $pdf->Cell(0, 10, 'Implementation Roadmap', 0, 1, 'L');
        $pdf->Ln(5);
        
        // Add the roadmap SVG
        $pdf->ImageSVG($temp_file, 10, $current_y + 15, $pdf_max_width, $pdf_height);
        
        // Move position after roadmap
        $pdf->Ln($pdf_height + 20);
        
        // Clean up temp file
        unlink($temp_file);
        
        debug_log("Roadmap successfully inserted into PDF");
        return $pdf;
        
    } catch (Exception $e) {
        debug_log("Error inserting roadmap into PDF: " . $e->getMessage());
        // Continue with PDF generation even if roadmap fails
        return $pdf;
    }
}

class ChartGenerator {
    private $width = 800;
    private $height = 400;
    private $margins = [
        'top' => 50,
        'right' => 40,
        'bottom' => 70,
        'left' => 80
    ];

    private function parseRecommendations($recommendations) {
        // Default data structure
        $defaultData = [
            ['year' => 0, 'cost' => 100],
            ['year' => 1, 'cost' => 94],
            ['year' => 2, 'cost' => 96],
            ['year' => 3, 'cost' => 88]
        ];

        if (empty($recommendations)) {
            debug_log("Empty recommendations, using default data");
            return $defaultData;
        }

        try {
            $costData = [];
            
            // Pattern to match cost reductions
            $patterns = [
                '/(\d+(?:\.\d+)?%?)(?:\s+cost\s+reduction|\s+savings|\s+decrease)(?:\s+(?:in|within|by)\s+(\d+)(?:\s+years?))/i',
                '/reduce\s+costs?\s+by\s+(\d+(?:\.\d+)?%?)(?:\s+(?:in|within|by)\s+(\d+)(?:\s+years?))/i'
            ];

            foreach ($patterns as $pattern) {
                if (preg_match_all($pattern, $recommendations, $matches, PREG_SET_ORDER)) {
                    foreach ($matches as $match) {
                        $percentage = floatval(str_replace(['%', ' '], '', $match[1]));
                        $year = intval($match[2]);
                        
                        if ($percentage > 0 && $year > 0) {
                            $costData[$year] = 100 - $percentage;
                            debug_log("Found cost data: Year {$year}, Cost {$percentage}%");
                        }
                    }
                }
            }

            if (empty($costData)) {
                debug_log("No cost data found, using default data");
                return $defaultData;
            }

            // Format the data
            $formattedData = [['year' => 0, 'cost' => 100]];
            ksort($costData);

            foreach ($costData as $year => $cost) {
                $formattedData[] = [
                    'year' => $year,
                    'cost' => $cost
                ];
            }

            debug_log("Parsed data: " . print_r($formattedData, true));
            return $formattedData;

        } catch (Exception $e) {
            debug_log("Error parsing recommendations: " . $e->getMessage());
            return $defaultData;
        }
    }

    public function generateCharts($recommendations) {
        try {
            $data = $this->parseRecommendations($recommendations);
            return [
                'cost_time_chart' => $this->generateCostTimeChart('Cost Reduction Over Time', $data)
            ];
        } catch (Exception $e) {
            debug_log("Error in generateCharts: " . $e->getMessage());
            return [
                'cost_time_chart' => $this->generateEmptyChart("Error generating chart")
            ];
        }
    }

    private function generateCostTimeChart($title, $data) {
        $svg = sprintf('<svg width="100%%" height="100%%" viewBox="0 0 %d %d" xmlns="http://www.w3.org/2000/svg">', 
            $this->width, 
            $this->height
        );

        // Background (optional, for better visibility of grid)
        $svg .= sprintf('<rect x="0" y="0" width="%d" height="%d" fill="#ffffff"/>', 
            $this->width, 
            $this->height
        );

        // Add definitions
        $svg .= <<<EOT
        <defs>
            <linearGradient id="barGradient" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0%" stop-color="#3498db"/>
                <stop offset="100%" stop-color="#2980b9"/>
            </linearGradient>
        </defs>
EOT;

        // Add title
        $svg .= sprintf('<text x="%d" y="30" text-anchor="middle" font-size="20" font-weight="bold" fill="#333">%s</text>',
            $this->width / 2,
            htmlspecialchars($title, ENT_QUOTES, 'UTF-8')
        );

        // Calculate dimensions
        $chartWidth = $this->width - $this->margins['left'] - $this->margins['right'];
        $chartHeight = $this->height - $this->margins['top'] - $this->margins['bottom'];

        // Draw grid lines first (background)
        $svg .= $this->drawGrid($chartWidth, $chartHeight);

        // Calculate bar dimensions
        $totalBars = count($data);
        $barWidth = min(80, ($chartWidth / ($totalBars + 1)));
        $barSpacing = ($chartWidth - ($barWidth * $totalBars)) / ($totalBars + 1);

        // Draw bars
        foreach ($data as $index => $point) {
            $x = $this->margins['left'] + $barSpacing + ($index * ($barWidth + $barSpacing));
            $barHeight = $chartHeight * ($point['cost'] / 100);
            $y = $this->height - $this->margins['bottom'] - $barHeight;

            // Draw bar
            $svg .= sprintf('<rect x="%d" y="%d" width="%d" height="%d" 
                fill="url(#barGradient)" 
                rx="3" ry="3"/>',
                $x, $y, $barWidth, $barHeight
            );

            // Value label
            $svg .= sprintf('<text x="%d" y="%d" text-anchor="middle" font-size="12" fill="#333" font-weight="bold">%d%%</text>',
                $x + ($barWidth / 2),
                $y - 10,
                round($point['cost'])
            );
        }

        // Draw axes
        $svg .= sprintf('<line x1="%d" y1="%d" x2="%d" y2="%d" stroke="#666" stroke-width="1.5"/>',
            $this->margins['left'],
            $this->margins['top'],
            $this->margins['left'],
            $this->height - $this->margins['bottom']
        );

        $svg .= sprintf('<line x1="%d" y1="%d" x2="%d" y2="%d" stroke="#666" stroke-width="1.5"/>',
            $this->margins['left'],
            $this->height - $this->margins['bottom'],
            $this->width - $this->margins['right'],
            $this->height - $this->margins['bottom']
        );

        // Draw Y-axis labels and ticks
        for ($i = 0; $i <= 100; $i += 20) {
            $y = $this->margins['top'] + ($chartHeight * (1 - $i / 100));
            // Label
            $svg .= sprintf('<text x="%d" y="%d" text-anchor="end" font-size="12" fill="#666">%d%%</text>',
                $this->margins['left'] - 10,
                $y + 4,
                $i
            );
            // Tick mark
            $svg .= sprintf('<line x1="%d" y1="%d" x2="%d" y2="%d" stroke="#666" stroke-width="1"/>',
                $this->margins['left'] - 5,
                $y,
                $this->margins['left'],
                $y
            );
        }

        // Draw X-axis labels only once
        foreach ($data as $index => $point) {
            $x = $this->margins['left'] + $barSpacing + ($index * ($barWidth + $barSpacing));
            $svg .= sprintf('<text x="%d" y="%d" text-anchor="middle" font-size="12" fill="#666">%d</text>',
                $x + ($barWidth / 2),
                $this->height - $this->margins['bottom'] + 20,
                $point['year']
            );
        }

        // Add axis labels
        $svg .= sprintf('<text x="%d" y="%d" text-anchor="middle" font-size="14" fill="#666" transform="rotate(-90, %d, %d)">Cost (%%)</text>',
            $this->margins['left'] - 45,
            $this->height / 2,
            $this->margins['left'] - 45,
            $this->height / 2
        );

        $svg .= sprintf('<text x="%d" y="%d" text-anchor="middle" font-size="14" fill="#666">Time (Years)</text>',
            $this->width / 2,
            $this->height - 20
        );

        $svg .= '</svg>';
        return $svg;
    }

    private function drawGrid($chartWidth, $chartHeight) {
        $svg = '';
        // Horizontal grid lines
        for ($i = 0; $i <= 100; $i += 20) {
            $y = $this->margins['top'] + ($chartHeight * (1 - $i / 100));
            $svg .= sprintf('<line x1="%d" y1="%d" x2="%d" y2="%d" stroke="#f0f0f0" stroke-width="1"/>',
                $this->margins['left'],
                $y,
                $this->width - $this->margins['right'],
                $y
            );
        }
        return $svg;
    }

    private function drawAxes($chartWidth, $chartHeight) {
        $svg = '';
        
        // Y-axis
        $svg .= sprintf('<line x1="%d" y1="%d" x2="%d" y2="%d" stroke="#666" stroke-width="1.5"/>',
            $this->margins['left'],
            $this->margins['top'],
            $this->margins['left'],
            $this->height - $this->margins['bottom']
        );

        // X-axis
        $svg .= sprintf('<line x1="%d" y1="%d" x2="%d" y2="%d" stroke="#666" stroke-width="1.5"/>',
            $this->margins['left'],
            $this->height - $this->margins['bottom'],
            $this->width - $this->margins['right'],
            $this->height - $this->margins['bottom']
        );

        // Axis labels
        $svg .= sprintf('<text x="%d" y="%d" text-anchor="middle" font-size="14" fill="#666" transform="rotate(-90, %d, %d)">Cost (%%)</text>',
            $this->margins['left'] - 45,
            $this->height / 2,
            $this->margins['left'] - 45,
            $this->height / 2
        );

        $svg .= sprintf('<text x="%d" y="%d" text-anchor="middle" font-size="14" fill="#666">Time (Years)</text>',
            $this->width / 2,
            $this->height - ($this->margins['bottom'] / 3)
        );

        return $svg;
    }

   

    private function generateEmptyChart($message) {
        return sprintf(
            '<svg width="%d" height="%d" xmlns="http://www.w3.org/2000/svg">
                <text x="%d" y="%d" text-anchor="middle" fill="#666">%s</text>
            </svg>',
            $this->width,
            $this->height,
            $this->width / 2,
            $this->height / 2,
            htmlspecialchars($message, ENT_QUOTES, 'UTF-8')
        );
    }
}


function insertChartsIntoHTML($html, $company_data) {
    debug_log("Starting chart insertion");
    
    try {
        // Create chart generator instance
        $chartGenerator = new ChartGenerator();
        
        // Extract recommendations from HTML with better debugging
        preg_match('/<div class=\'container\'>(.*?)<div class=\'button-group\'>/s', $html, $matches);
        $recommendations = !empty($matches[1]) ? strip_tags($matches[1]) : "";
        
        debug_log("Extracted recommendations length: " . strlen($recommendations));
        debug_log("First 200 characters of recommendations: " . substr($recommendations, 0, 200));
        
        // Generate charts
        $charts = $chartGenerator->generateCharts($recommendations);
        
        // Create visualization section HTML
        $visualization_section = '<div class="chart-container">';
        if (isset($charts['cost_time_chart'])) {
            $visualization_section .= '<div class="cost-time-container">' . 
                $charts['cost_time_chart'] . 
                '</div>';
        }
        $visualization_section .= '</div>';
        
        // Insert the chart in the appropriate location
        $search_text = '*Cost Analysis*';
        $pos = strpos($html, $search_text);
        
        if ($pos !== false) {
            $insert_pos = $pos + strlen($search_text);
            $html = substr_replace($html, $visualization_section, $insert_pos, 0);
            debug_log("Chart inserted after Cost Analysis heading");
        } else {
            debug_log("Cost Analysis heading not found, inserting before button group");
            $button_group_pos = strpos($html, '<div class=\'button-group\'>');
            if ($button_group_pos !== false) {
                $html = substr_replace($html, $visualization_section, $button_group_pos, 0);
            }
        }
        
    } catch (Exception $e) {
        debug_log("Error in insertChartsIntoHTML: " . $e->getMessage());
    }
    
    return $html;
}

function addChartStyles($html) {
    $styles = '<style>
        .chart-container {
            margin: 2rem 0;
            max-width: 800px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .cost-time-container {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-top: 1rem;
        }
        
        .cost-time-container svg {
            width: 100%;
            height: auto;
            max-width: 800px;
            margin: 0 auto;
            display: block;
        }
        
        @media (max-width: 768px) {
            .chart-container {
                margin: 1rem 0;
            }
            
            .cost-time-container {
                padding: 1rem;
            }
        }
    </style>';

    $head_end_pos = strpos($html, '</head>');
    if ($head_end_pos !== false) {
        $html = substr_replace($html, $styles, $head_end_pos, 0);
    }
    
    return $html;
}

function insertChartsIntoPDF($pdf, $recommendations, $company_data) {
    try {
        $chartGenerator = new ChartGenerator();
        $charts = $chartGenerator->generateCharts($recommendations);
        
        // Calculate dimensions for the chart
        $svg_width = 800;
        $svg_height = 400;
        $pdf_max_width = $pdf->getPageWidth() - 40;
        $scale = $pdf_max_width / $svg_width;
        $pdf_height = $svg_height * $scale;
        
        // Save cost-time chart SVG to temp file
        if (isset($charts['cost_time_chart'])) {
            $cost_time_temp = tempnam(sys_get_temp_dir(), 'svg_');
            file_put_contents($cost_time_temp, $charts['cost_time_chart']);
            
            // Get current Y position
            $current_y = $pdf->GetY();
            
            // Check if we need a new page
            if ($current_y + $pdf_height > $pdf->getPageHeight() - 20) {
                $pdf->AddPage();
                $current_y = $pdf->GetY();
            }
            
            // Add some spacing before chart
            $pdf->Ln(5);
            $current_y = $pdf->GetY();
            
            // Add chart with proper positioning
            $pdf->ImageSVG($cost_time_temp, 20, $current_y, $pdf_max_width, $pdf_height);
            
            // Clean up temp file
            unlink($cost_time_temp);
            
            // Move position after chart
            $pdf->Ln($pdf_height + 10);
            
            debug_log("Cost-time chart added to PDF successfully at Y position: " . $current_y);
        }
        
    } catch (Exception $e) {
        debug_log("Error generating charts for PDF: " . $e->getMessage());
    }
    
    return $pdf;
}




class APIResponseLogger {
    private $logDir;
    private $baseLogName = 'api_response';
    private $maxFileSize = 104857600; // 100MB in bytes
    private $currentLogFile;
    
    public function __construct($logDirectory = null) {
        try {
            // Set log directory with default to a logs folder in current directory
            $this->logDir = $logDirectory ?? __DIR__ . '/logs';
            
            // Ensure directory exists and is writable
            if (!$this->ensureDirectoryExists()) {
                throw new Exception("Cannot create or access log directory: " . $this->logDir);
            }
            
            $this->currentLogFile = $this->getCurrentLogFile();
            
        } catch (Exception $e) {
            error_log("APIResponseLogger initialization error: " . $e->getMessage());
            throw $e;
        }
    }
    
    private function ensureDirectoryExists() {
        if (!file_exists($this->logDir)) {
            return mkdir($this->logDir, 0755, true);
        }
        return is_writable($this->logDir);
    }
    
    private function getCurrentLogFile() {
        try {
            $files = glob($this->logDir . "/{$this->baseLogName}_*.log");
            
            if (empty($files)) {
                return $this->createNewLogFile();
            }
            
            $latestFile = end($files);
            
            // Check if file exists and is writable
            if (!is_writable($latestFile) || filesize($latestFile) >= $this->maxFileSize) {
                return $this->createNewLogFile();
            }
            
            return $latestFile;
            
        } catch (Exception $e) {
            error_log("Error getting current log file: " . $e->getMessage());
            throw $e;
        }
    }
    
    private function createNewLogFile() {
        try {
            $timestamp = date('Y-m-d_His');
            $newFile = "{$this->logDir}/{$this->baseLogName}_{$timestamp}.log";
            
            // Create file with header
            $header = "=== API Response Log Created: " . date('Y-m-d H:i:s') . " ===\n\n";
            
            if (file_put_contents($newFile, $header) === false) {
                throw new Exception("Cannot write to new log file: " . $newFile);
            }
            
            // Set proper permissions
            chmod($newFile, 0644);
            
            $this->cleanOldLogs();
            return $newFile;
            
        } catch (Exception $e) {
            error_log("Error creating new log file: " . $e->getMessage());
            throw $e;
        }
    }
    
    private function cleanOldLogs() {
        try {
            $files = glob($this->logDir . "/{$this->baseLogName}_*.log");
            
            if (count($files) > 10) {
                sort($files);
                $filesToRemove = array_slice($files, 0, count($files) - 10);
                foreach ($filesToRemove as $file) {
                    if (is_writable($file)) {
                        unlink($file);
                    }
                }
            }
        } catch (Exception $e) {
            error_log("Error cleaning old logs: " . $e->getMessage());
        }
    }
    
    public function logAPIResponse($httpCode, $requestHeaders, $requestBody, $responseHeaders, $responseBody, $verboseDebug = '') {
        try {
            // Check if we need a new log file
            if (!is_writable($this->currentLogFile) || filesize($this->currentLogFile) >= $this->maxFileSize) {
                $this->currentLogFile = $this->createNewLogFile();
            }
            
            $logEntry = $this->formatLogEntry(
                $httpCode,
                $requestHeaders,
                $requestBody,
                $responseHeaders,
                $responseBody,
                $verboseDebug
            );
            
            if (file_put_contents($this->currentLogFile, $logEntry, FILE_APPEND) === false) {
                throw new Exception("Cannot write to log file: " . $this->currentLogFile);
            }
            
            return true;
            
        } catch (Exception $e) {
            error_log("Error logging API response: " . $e->getMessage());
            return false;
        }
    }
    
    private function formatLogEntry($httpCode, $requestHeaders, $requestBody, $responseHeaders, $responseBody, $verboseDebug) {
        $timestamp = date('Y-m-d H:i:s');
        $separator = str_repeat("-", 80) . "\n";
        
        $entry = "Timestamp: {$timestamp}\n\n";
        $entry .= "HTTP Code: {$httpCode}\n\n";
        
        $entry .= "Request Headers:\n";
        $entry .= is_array($requestHeaders) ? print_r($requestHeaders, true) : $requestHeaders;
        $entry .= "\n\n";
        
        $entry .= "Request Body:\n";
        $entry .= $this->formatJSON($requestBody);
        $entry .= "\n\n";
        
        $entry .= "Response Headers:\n";
        $entry .= is_array($responseHeaders) ? print_r($responseHeaders, true) : $responseHeaders;
        $entry .= "\n\n";
        
        $entry .= "Response Body:\n";
        $entry .= $this->formatJSON($responseBody);
        $entry .= "\n\n";
        
        if (!empty($verboseDebug)) {
            $entry .= "Verbose Debug:\n";
            $entry .= $verboseDebug . "\n\n";
        }
        
        $entry .= $separator;
        return $entry;
    }
    
    private function formatJSON($data) {
        if (is_string($data)) {
            $decoded = json_decode($data, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
            return $data;
        }
        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}

// Modified generateAIRecommendations function with better error handling
function generateAIRecommendations($companyData) {
    debug_log("Starting AI recommendations generation");
    
    // Get and validate config file
    $config_file = __DIR__ . '/config.txt';
    if (!file_exists($config_file)) {
        debug_log("Configuration file not found: " . $config_file);
        return false;
    }
    
    if (!is_readable($config_file)) {
        debug_log("Configuration file is not readable: " . $config_file);
        return false;
    }
    
    $config = parse_ini_file($config_file, false, INI_SCANNER_RAW);
    if ($config === false) {
        debug_log("Failed to parse configuration file");
        return false;
    }
    
    $logger = null;
    $ch = null;
    $verboseFile = null;
    
    try {
        // Initialize logger
        $logger = new APIResponseLogger(__DIR__ . '/logs');
        debug_log("Logger initialized");
        
        // Prepare request data
        $data = [
            "model" => $config['model'],
            "messages" => [
                [
                    "role" => "system",
                    "content" => $config['system_role']
                ],
                [
                    "role" => "user",
                    "content" => json_encode($companyData)
                ]
            ],
            "max_tokens" => (int)$config['max_tokens'],
            "temperature" => (float)$config['temperature']
        ];
        
        // Initialize cURL
        $ch = curl_init($config['api_url']);
        if ($ch === false) {
            throw new Exception("Failed to initialize cURL");
        }
        
        // Create temp file for verbose output
        $verboseFile = tmpfile();
        if ($verboseFile === false) {
            throw new Exception("Failed to create temporary file for verbose output");
        }
        
        // Set cURL options
        $curlOptions = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . trim($config['api_key']),
                'Content-Type: application/json',
                'Accept: application/json'
            ],
            CURLOPT_VERBOSE => true,
            CURLOPT_STDERR => $verboseFile,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2
        ];
        
        if (!curl_setopt_array($ch, $curlOptions)) {
            throw new Exception("Failed to set cURL options");
        }
        
        // Execute request with retry logic
        $maxRetries = 3;
        $retryDelay = 2;
        $attempt = 0;
        
        do {
            $attempt++;
            debug_log("API request attempt {$attempt} of {$maxRetries}");
            
            // Execute the request
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            // Get verbose debug information
            rewind($verboseFile);
            $verboseLog = stream_get_contents($verboseFile);
            
            // Log the API interaction
            if ($logger) {
                $logger->logAPIResponse(
                    $httpCode,
                    curl_getinfo($ch),
                    $data,
                    curl_getinfo($ch),
                    $response,
                    $verboseLog
                );
            }
            
            // Check for cURL errors
            if (curl_errno($ch)) {
                $curlError = curl_error($ch);
                debug_log("cURL Error on attempt {$attempt}: " . $curlError);
                
                if ($attempt >= $maxRetries) {
                    throw new Exception('Error making API request: ' . $curlError);
                }
                
                sleep($retryDelay * $attempt);
                continue;
            }
            
            // Handle different HTTP status codes
            if ($httpCode === 503) {
                debug_log("API server temporarily unavailable (503) on attempt {$attempt}");
                if ($attempt >= $maxRetries) {
                    throw new Exception('API server is temporarily unavailable (503)');
                }
                sleep($retryDelay * $attempt);
                continue;
            }
            
            if ($httpCode !== 200) {
                throw new Exception('API request failed with HTTP code ' . $httpCode);
            }
            
            // Parse and validate response
            $result = json_decode($response, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Failed to parse JSON response: ' . json_last_error_msg());
            }
            
            if (!isset($result['choices'][0]['message']['content'])) {
                throw new Exception('Unexpected API response format');
            }
            
            debug_log("AI recommendations generated successfully");
            return $result['choices'][0]['message']['content'];
            
        } while ($attempt < $maxRetries);
        
        throw new Exception('Maximum retry attempts reached');
        
    } catch (Exception $e) {
        debug_log("Error in generateAIRecommendations: " . $e->getMessage());
        return false;
        
    } finally {
        // Clean up resources
        if ($verboseFile) {
            fclose($verboseFile);
        }
        if ($ch) {
            curl_close($ch);
        }
    }
}

class MYPDF_AIRecommendations extends TCPDF {
    public function Header() {
        $image_file = $_SERVER['DOCUMENT_ROOT'] . '/Assesment-Templates/logo2.jpg';
        if (file_exists($image_file)) {
            $this->Image($image_file, 10, 10, 20, '', 'JPG', '', 'T', false, 300, '', false, false, 0, false, false, false);
        }
        $this->SetFont('helvetica', 'B', 14);
        $this->SetTextColor(70, 70, 70);
        $this->SetXY(35, 10);
        $this->Cell(0, 10, 'Neural Roots, An AI services company', 0, 1, 'C');
        $this->SetFont('helvetica', 'I', 12);
        $this->SetTextColor(100, 100, 100);
        $this->SetXY(35, 15);
        $this->SetDrawColor(150, 150, 150);
        $this->SetLineWidth(0.5);
        $this->Line(10, 30, $this->getPageWidth() - 10, 30);
    }

    public function Footer() {
        $this->SetDrawColor(150, 150, 150);
        $this->SetLineWidth(0.5);
        $this->Line(10, $this->getPageHeight() - 20, $this->getPageWidth() - 10, $this->getPageHeight() - 20);
        $this->SetY(-15);
        $this->SetFont('helvetica', 'I', 8);
        $this->SetTextColor(100, 100, 100);
        $this->Cell(60, 10, '', 0, 0, 'L');
        $this->Cell(60, 10, '© ' . date('Y') . ' Neural Roots', 0, 0, 'C');
        $this->Cell(60, 10, 'Page ' . $this->getAliasNumPage() . '/' . $this->getAliasNbPages(), 0, 0, 'R');
    }
}




function processStyledText($content, $pdf) {
    $content = preg_replace('/^\s*(\d+\.|\w\.)\s*/m', '', $content);
    $sections = preg_split('/(\*\*.*?\*\*)/', $content, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

    foreach ($sections as $section) {
        $section = trim($section);
        if (empty($section)) continue;

        if (preg_match('/^\*\*(.*?)\*\*$/', $section, $matches)) {
            $pdf->Ln(8);
            $pdf->SetFont('helvetica', 'B', 14);
            $pdf->SetTextColor(21, 60, 190);
            $pdf->MultiCell(0, 7, trim($matches[1]), 0, 'L');
            $pdf->Ln(2);
        } else {
            $subheadings = preg_split('/(\*.*?\*)/', $section, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
            $subheading_index = 0;

            foreach ($subheadings as $subheading) {
                $subheading = trim($subheading);
                if (empty($subheading)) continue;

                if (preg_match('/^\*(.*?)\*$/', $subheading, $matches)) {
                    $subheading_index++;
                    $pdf->Ln(4);
                    $pdf->SetFont('helvetica', 'B', 12);
                    $pdf->SetTextColor(40, 116, 252);
                    $pdf->MultiCell(0, 6, trim($matches[1]), 0, 'L');
                    $pdf->Ln(1);
                } else {
                    $paragraphs = preg_split('/\n+/', $subheading);
                    foreach ($paragraphs as $paragraph) {
                        $paragraph = trim($paragraph);
                        if (empty($paragraph)) continue;
                        $paragraph = preg_replace('/^:\s*/', '', $paragraph);
                        $pdf->SetFont('helvetica', '', 11);
                        $pdf->SetTextColor(0, 0, 0);
                        $pdf->MultiCell(0, 6, $paragraph, 0, 'L');
                        $pdf->Ln(1);
                    }
                }
            }
        }
    }
}



// Add these functions to the existing code where charts are generated



// PDF Download Functionality
if (isset($_GET['download_pdf']) && isset($_GET['company'])) {
    debug_log("PDF download requested");
    $company_name = $_GET['company'];
    
    $sql = "SELECT pdf_content FROM user_information WHERE company_name = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $company_name);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $pdf_content = $row['pdf_content'];
        
        if (strlen($pdf_content) > 0) {
            debug_log("PDF content retrieved. Size: " . strlen($pdf_content) . " bytes");
            
            if (substr($pdf_content, 0, 4) === '%PDF') {
                if (ob_get_length()) ob_clean();
                
                header('Content-Type: application/pdf');
                header('Content-Disposition: attachment; filename="AI_Recommendations_' . $company_name . '.pdf"');
                header('Content-Length: ' . strlen($pdf_content));
                header('Cache-Control: private, max-age=0, must-revalidate');
                header('Pragma: public');
                
                echo $pdf_content;
                debug_log("PDF sent successfully");
                exit();
            } else {
                debug_log("Retrieved content is not a valid PDF");
                echo "Error: The retrieved content is not a valid PDF. Please contact support.";
            }
        } else {
            debug_log("PDF content is empty for company: " . $company_name);
            echo "Error: PDF content is empty. Please try regenerating the recommendations.";
        }
    } else {
        debug_log("PDF not found for company: " . $company_name);
        echo "PDF not found for this company.";
    }
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    debug_log("POST request received");
    
    // Dynamically collect form fields
    $form_data = array();
    $columns_to_add = array();
    foreach ($_POST as $key => $value) {
        if (isset($_POST[$key])) {
            $safe_key = strtolower(str_replace('-', '_', $key));
            if (is_array($value)) {
                $form_data[$safe_key] = implode(", ", array_map([$conn, 'real_escape_string'], $value));
            } else {
                $form_data[$safe_key] = $conn->real_escape_string($value);
            }
            $columns_to_add[] = $safe_key;
        }
    }

    debug_log("Form data processed. Columns to add: " . implode(", ", $columns_to_add));

    // Add new columns to database
    $columns_added = true;
    foreach ($columns_to_add as $column) {
        if (!addColumnIfNotExists($conn, 'kit_details', $column, 'TEXT')) {
            $columns_added = false;
            break;
        }
    }
    
    if (!$columns_added) {
        debug_log("Error: Failed to add necessary columns to the database.");
        echo "Error: Failed to add necessary columns to the database. Please contact support.";
        exit;
    }

    // Add metadata
    $ip_address = getClientIP();
    $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $conn->real_escape_string($_SERVER['HTTP_USER_AGENT']) : '';
    $submission_date = date('Y-m-d H:i:s');
    
    // Prepare SQL for kit_details
    $columns = implode(", ", array_keys($form_data)) . ", ip_address, user_agent, submission_date";
    $values = "'" . implode("', '", $form_data) . "', '$ip_address', '$user_agent', '$submission_date'";
    $sql_kit = "INSERT INTO kit_details ($columns) VALUES ($values)";

    debug_log("SQL Query: " . $sql_kit);

    if ($conn->query($sql_kit) === TRUE) {
        debug_log("Data inserted successfully");
        
        // Get latest user info
        $sql_latest_user = "SELECT company_name FROM user_information ORDER BY id DESC LIMIT 1";
        $result_latest_user = $conn->query($sql_latest_user);
        
        if ($result_latest_user->num_rows > 0) {
            $latest_user_data = $result_latest_user->fetch_assoc();
            $company_name = $latest_user_data['company_name'];
        } else {
            $company_name = 'Unknown Company';
        }

        debug_log("Company name retrieved: " . $company_name);

        // Get user data
        $sql_user = "SELECT company_name, job_role FROM user_information WHERE company_name = ?";
        $stmt_user = $conn->prepare($sql_user);
        $stmt_user->bind_param("s", $company_name);
        $stmt_user->execute();
        $result_user = $stmt_user->get_result();
        $user_data = $result_user->fetch_assoc();

        // Get kit data
        $sql_kit = "SELECT * FROM kit_details ORDER BY submission_date DESC LIMIT 1";
        $result_kit = $conn->query($sql_kit);
        $kit_data = $result_kit->fetch_assoc();

        debug_log("User and kit data fetched");

        // Prepare data for AI
        $company_data = [
            "company" => [
                "companyName" => $user_data['company_name'] ?? 'Unknown Company',
                "jobRole" => $user_data['job_role'] ?? 'Unknown Role'
            ],
            "companyDetails" => [],
            "businessChallenges" => [],
            "aiKnowledgeAndExpectations" => [],
            "additionalInfo" => []
        ];

        // Categorize form fields
        $company_details_fields = ['Brief', 'company_size', 'industry'];
        $business_challenges_fields = ['cbc', 'ib', 'ai_outcomes', 'customer_service', 'sales_marketing', 
            'operations_logistics', 'product_development', 'financial_management'];
        $ai_knowledge_fields = ['existing_ai_ml', 'specify_ai_ml', 'ai_concerns', 'ai_capabilities', 
            'ai_investment_readiness', 'ai_implementation_approach', 'additional_comments'];

        foreach ($kit_data as $key => $value) {
            if (in_array($key, $company_details_fields)) {
                $company_data['companyDetails'][$key] = $value;
            } elseif (in_array($key, $business_challenges_fields)) {
                $company_data['businessChallenges'][$key] = $value;
            } elseif (in_array($key, $ai_knowledge_fields)) {
                $company_data['aiKnowledgeAndExpectations'][$key] = $value;
            } else {
                $company_data['additionalInfo'][$key] = $value;
            }
        }

        debug_log("Company data prepared for AI recommendations");

        // Save JSON
        $jsonData = json_encode($company_data, JSON_PRETTY_PRINT);
        $filename = preg_replace('/[^a-zA-Z0-9_]/', '_', $company_name) . '.json';
        $sql_update = "UPDATE user_information SET json_file = ?, json_filename = ? WHERE company_name = ?";
        $stmt = $conn->prepare($sql_update);
        $null = NULL;
        $stmt->bind_param("bss", $null, $filename, $company_name);
        $stmt->send_long_data(0, $jsonData);
        $stmt->execute();
        $stmt->close();

        // Generate recommendations
        $recommendations = generateAIRecommendations($company_data);

        if ($recommendations === false) {
            debug_log("Failed to generate AI recommendations");
            exit();
        }

        debug_log("AI recommendations generated successfully");

        // Generate PDF
        $pdf_string = generatePDF($company_name, $recommendations, $company_data);
        debug_log("PDF generated");

        // Save PDF to database
        $sql_update_pdf = "UPDATE user_information SET pdf_content = ? WHERE company_name = ?";
        $stmt = $conn->prepare($sql_update_pdf);
        if ($stmt === false) {
            debug_log("Failed to prepare statement: " . $conn->error);
            echo "Failed to prepare statement: " . $conn->error;
        } else {
            $stmt->bind_param("bs", $pdf_string, $company_name);
            $stmt->send_long_data(0, $pdf_string);
            $result = $stmt->execute();
            if (!$result) {
                debug_log("Error updating PDF content: " . $stmt->error);
            }
            $stmt->close();
        }
        
        // Generate HTML file
        $fileName = generateFileName($company_name);
        $company_name = htmlspecialchars($company_name);
        $recommendations_html = nl2br($recommendations);

        $html = <<<HTML
<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>AI Recommendations for {$company_name}</title>
    <link href='https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap' rel='stylesheet'>
    <style>
         :root {
            --primary-color: #0078D4;
            --secondary-color: #166088;
            --accent-color: #4caf50;
            --background-color: #f0f4f8;
            --text-color: #333;
            --section-bg-color: #ffffff;
            --shadow-color: rgba(0, 0, 0, 0.1);
            --line-color: #c0c0c0;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            line-height: 1.8;
            color: var(--text-color);
            margin: 0;
            padding: 0;
            background-color: var(--background-color);
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        
        .container {
            width: 100%;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            flex: 1;
            box-sizing: border-box;
        }
        
        h1 {
            color: var(--primary-color);
            font-weight: 600;
            font-size: 2.5em;
            text-align: center;
            margin-bottom: 40px;
            border-bottom: 3px solid var(--line-color);
            padding-bottom: 15px;
            word-wrap: break-word;
        }
        
        h2 {
            color: var(--primary-color);
            font-weight: 600;
            font-size: 1.8em;
            margin-top: 30px;
            word-wrap: break-word;
        }
        
        .section {
            margin-bottom: 40px;
            background-color: var(--section-bg-color);
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 6px 12px var(--shadow-color);
            transition: transform 0.3s ease;
            overflow-x: hidden;
        }
        
        .executive-summary, 
        .company-context, 
        .expected-impact {
            background-color: #e8f4fd;
            padding: 20px;
            border-radius: 10px;
            border-left: 5px solid var(--secondary-color);
            margin-bottom: 25px;
            overflow-wrap: break-word;
        }
        
        .recommendation {
            margin-bottom: 30px;
            padding-left: 15px;
            border-left: 3px solid var(--accent-color);
            overflow-wrap: break-word;
        }
        
        .button-group {
            display: flex;
            justify-content: center;
            gap: 15px;
            flex-wrap: wrap;
            padding: 20px 10px;
        }
        
        .download-btn {
            padding: 15px 20px;
            background-color: var(--primary-color);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            min-width: 200px;
            text-align: center;
            transition: all 0.3s ease;
            margin: 5px;
            display: inline-block;
            box-sizing: border-box;
        }

        .download-btn:hover {
            background-color: #005a9c;
            transform: translateY(-3px);
        }
        
        .footer-btns {
            text-align: center;
            padding: 20px 10px;
            background-color: var(--section-bg-color);
            width: 100%;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
        }

        /* Mobile Responsive Styles */
        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }

            h1 {
                font-size: 2em;
                padding: 10px 0;
                margin-bottom: 20px;
            }

            h2 {
                font-size: 1.5em;
            }

            .section {
                padding: 15px;
                margin-bottom: 20px;
            }

            .executive-summary, 
            .company-context, 
            .expected-impact {
                padding: 15px;
                margin-bottom: 20px;
            }

            .button-group {
                padding: 10px 5px;
            }

            .download-btn {
                width: 100%;
                max-width: none;
                margin: 5px 0;
                font-size: 16px;
                padding: 12px 15px;
            }
        }

        /* Small Mobile Devices */
        @media (max-width: 480px) {
            h1 {
                font-size: 1.8em;
            }

            h2 {
                font-size: 1.3em;
            }

            .container {
                padding: 10px;
            }

            .section {
                padding: 12px;
            }

            .executive-summary, 
            .company-context, 
            .expected-impact {
                padding: 12px;
            }

            .recommendation {
                padding-left: 10px;
            }

            .footer-btns {
                padding: 15px 5px;
            }

            .download-btn {
                padding: 10px 15px;
                font-size: 14px;
            }
        }

        /* Handle extremely small screens */
        @media (max-width: 320px) {
            h1 {
                font-size: 1.5em;
            }

            .download-btn {
                padding: 8px 12px;
                font-size: 13px;
            }
        }

        
        
    </style>
</head>
<body>
<div class='container'>
    <h1>AI Recommendations for {$company_name}</h1>
    {$recommendations_html}
    
    <div class='button-group'>
        <a href='#' class='download-btn' onclick='downloadHTML()'>Download HTML</a>
        <a href='#' class='download-btn' onclick='downloadPDF()'>Download PDF</a>
    </div>
</div>

<div class='footer-btns'>
    <a href='https://neuralroots.ai/quick-starter-kit-40-hours/' class='download-btn'onclick='downloadConsultingKit()'>Try our - Consulting Kit </a>
    <a href='https://neuralroots.ai/ethical-ai-enablement-kit-100-business-oriented/' class='download-btn' onclick='downloadEnablementKit()'>Try our - Enablement Kit</a>
    <a href="https://neuralroots.ai/contact-us/?source_url=Assessment-HTML" class='download-btn'>Contact Us</a>
</div>

<script>
function downloadHTML() {
    var htmlContent = document.documentElement.outerHTML;
    var blob = new Blob([htmlContent], { type: 'text/html' });
    var link = document.createElement('a');
    link.href = window.URL.createObjectURL(blob);
    link.download = '{$fileName}';
    link.click();
    window.location.href = 'valid.html';
}

function downloadPDF() {
    var link = document.createElement('a');
    link.href = '../../backend/submit_form.php?download_pdf=1&company={$company_name}';
    link.download = '{$company_name}_AI_Recommendations.pdf';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    setTimeout(function() {
        window.location.href = 'https://neuralroots.ai/';
    }, 1000);
}
</script>
</body>
</html>
HTML;

    // Ad// Insert roadmap first
debug_log("Inserting roadmap into HTML");
// Add roadmap
$html = insertRoadmapIntoHTML($html, $recommendations);
$html = str_replace('</style>', $roadmap_styles . '</style>', $html);


       // Add charts
        debug_log("Generating charts for recommendations");
        $html = insertChartsIntoHTML($html, $company_data);
        $html = addChartStyles($html);
        debug_log("Charts generated successfully");

        // Save HTML file
        $file_path = $_SERVER['DOCUMENT_ROOT'] . "/Assesment-Templates/backend/htmlfiles/" . $fileName;

        if (file_put_contents($file_path, $html) !== false) {
            debug_log("HTML file created successfully: " . $file_path);
            
            // Redirect to the created HTML file
            header("Location: /Assesment-Templates/backend/htmlfiles/" . $fileName);
            exit();
        } else {
            debug_log("Error saving the AI recommendations HTML file.");
            echo "Error saving the AI recommendations. Please try again or contact support.";
        }
    } else {
        debug_log("Error inserting data: " . $conn->error);
        echo "Error: Failed to save form data. Please try again or contact support.";
        exit;
    }

    // Close database connection
    $conn->close();
    debug_log("Database connection closed");
}

// Function to generate the PDF
function generatePDF($company_name, $recommendations, $company_data) {
    debug_log("Generating PDF for " . $company_name);
    
    try {
        // Create PDF object
        $pdf = new MYPDF_AIRecommendations(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        
        // Set document information
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('Neural Roots');
        $pdf->SetTitle('AI Recommendations for ' . $company_name);
        
        // Set header and footer
        $pdf->setPrintHeader(true);
        $pdf->setPrintFooter(true);
        
        // Set margins
        $pdf->SetMargins(10, 35, 10);
        $pdf->SetHeaderMargin(5);
        
        // Add first page
        $pdf->AddPage();
        
        // Process and format recommendations content
        processStyledText($recommendations, $pdf);
        
         debug_log("Adding roadmap to PDF");
       $pdf = insertRoadmapIntoPDF($pdf, $recommendations);
        debug_log("Roadmap added successfully"); 
        
        // Add charts to PDF
        debug_log("Adding charts to PDF");
        $pdf = insertChartsIntoPDF($pdf, $recommendations, $company_data);
        debug_log("Charts added successfully");
        
        // Return PDF as string
        return $pdf->Output('', 'S');
        
    } catch (Exception $e) {
        debug_log("Error generating PDF: " . $e->getMessage());
        throw new Exception("Failed to generate PDF: " . $e->getMessage());
    }
}

// Function to write to SVG temp file
function writeSvgTemp($svg_content) {
    try {
        $temp_file = tempnam(sys_get_temp_dir(), 'svg_');
        if ($temp_file === false) {
            throw new Exception("Failed to create temporary file");
        }
        
        if (file_put_contents($temp_file, $svg_content) === false) {
            throw new Exception("Failed to write SVG content to temporary file");
        }
        
        return $temp_file;
    } catch (Exception $e) {
        debug_log("Error writing SVG temp file: " . $e->getMessage());
        throw $e;
    }
}

// Error handling for the whole script
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    debug_log("Error [$errno] $errstr on line $errline in file $errfile");
    
    // For critical errors, throw an exception
    if ($errno == E_ERROR || $errno == E_USER_ERROR) {
        throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
    }
    
    // For warnings and notices, just log them
    return true;
});

try {
    // Register shutdown function to catch fatal errors
    register_shutdown_function(function() {
        $error = error_get_last();
        if ($error !== NULL && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            debug_log("Fatal Error: " . $error['message']);
        }
    });
    
    // End output buffering and flush
    if (ob_get_length()) ob_end_flush();
    
} catch (Exception $e) {
    debug_log("Critical error: " . $e->getMessage());
    echo "An error occurred. Please try again or contact support.";
    exit;
}
?>