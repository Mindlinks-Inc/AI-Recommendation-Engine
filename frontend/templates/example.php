<?php
session_start();

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "Nueral_kit";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get client IP function
function getClientIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}

function fetchAndSaveData($conn, $company_name) {
    try {
        // Fetch the correct company information based on the company name
        $sql_user = "SELECT company_name, job_role FROM user_information WHERE company_name = '$company_name'";
        $result_user = $conn->query($sql_user);

        // Check if we have the correct user data
        if ($result_user->num_rows > 0) {
            $user_data = $result_user->fetch_assoc();
            echo "DEBUG: Fetched Company Name: " . $user_data['company_name'] . "<br>"; // Debugging output
            echo "DEBUG: Fetched Job Role: " . $user_data['job_role'] . "<br>"; // Debugging output

            $sql_kit = "SELECT * FROM kit_details ORDER BY id DESC LIMIT 1";
            $result_kit = $conn->query($sql_kit);
            $kit_data = $result_kit->fetch_assoc();

            // Prepare company data based on fetched user and kit details
            $companyData = [
                "company" => [
                    "companyName" => $user_data['company_name'],
                    "jobRole" => $user_data['job_role']
                ],
                "companyDetails" => [
                    "briefAboutCompany" => $kit_data['Brief'],
                    "companySize" => $kit_data['company_size'],
                    "industry" => $kit_data['industry']
                ],
                "businessChallenges" => [
                    "currentBusinessChallenges" => $kit_data['cbc'],
                    "inefficienciesOrBottlenecks" => $kit_data['ib'],
                    "expectedAIOutcomes" => $kit_data['ai_outcomes'],
                    "priorityAreas" => [
                        "customerService" => $kit_data['customer_service'],
                        "salesAndMarketing" => $kit_data['sales_marketing'],
                        "operationsAndLogistics" => $kit_data['operations_logistics'],
                        "productDevelopment" => $kit_data['product_development'],
                        "financialManagement" => $kit_data['financial_management']
                    ]
                ],
                "aiKnowledgeAndExpectations" => [
                    "existingAIMLSolutions" => $kit_data['existing_ai_ml'],
                    "specifyAIMLSolutions" => $kit_data['specify_ai_ml'],
                    "primaryAIAdoptionConcerns" => $kit_data['ai_concerns'],
                    "interestedAICapabilities" => $kit_data['ai_capabilities'],
                    "aiInvestmentReadiness" => $kit_data['ai_investment_readiness'],
                    "preferredAIImplementationApproach" => $kit_data['ai_implementation_approach'],
                    "additionalCommentsOrQuestions" => $kit_data['additional_comments']
                ]
            ];

            // Convert data to JSON and update the user_information table
            $jsonData = json_encode($companyData, JSON_PRETTY_PRINT);
            $filename = preg_replace('/[^a-zA-Z0-9_]/', '_', $user_data['company_name'] . '_' . $user_data['job_role']) . '.json';
            $tempFile = tempnam(sys_get_temp_dir(), 'json_');
            file_put_contents($tempFile, $jsonData);
            $fileContent = file_get_contents($tempFile);

            $sql_update = "UPDATE user_information SET json_file = ?, json_filename = ? WHERE company_name = ?";
            $stmt = $conn->prepare($sql_update);
            $null = NULL;
            $stmt->bind_param("bss", $null, $filename, $user_data['company_name']);
            $stmt->send_long_data(0, $fileContent);
            $stmt->execute();
            $stmt->close();

            unlink($tempFile);

            echo "Updated JSON file for company: " . $user_data['company_name'] . " as " . $filename . "<br>";
        } else {
            echo "DEBUG: No company found for company name: " . $company_name . "<br>"; // Debugging output
        }
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
}

function generateFileName($companyName) {
    $safeName = preg_replace('/[^a-z0-9]+/', '_', strtolower($companyName));
    $timestamp = date('Y-m-d_H-i-s');
    return "ai_recommendations_{$safeName}_{$timestamp}.html";
}

function generateAIRecommendations($companyData) {
    $prompt = "You are a visionary AI solutions architect with extensive experience in applying cutting-edge AI technologies to transform businesses across various industries. Your task is to analyze the provided company data and generate comprehensive, innovative, and highly customized AI recommendations that will revolutionize the company's operations and strategic position in their industry.
    Present your analysis and recommendations in a format that combines engaging narrative explanations with visual representations and structured data. Structure your response as follows:
    1. Executive Summary
    2. Company Context and Industry Landscape
    3. AI Solution Recommendations
    4. Expected Overall Impact
    " . json_encode($companyData);

    $data = [
        "model" => "mixtral-8x7b-32768",
        "messages" => [
            ["role" => "system", "content" => "You are an AI solutions architect providing recommendations based on company data."],
            ["role" => "user", "content" => $prompt]
        ],
        "max_tokens" => 4000,
        "temperature" => 0.7
    ];

    $ch = curl_init('https://api.groq.com/openai/v1/chat/completions');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer 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',
        'Content-Type: application/json'
    ]);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        die('Error making API request: ' . curl_error($ch));
    }

    curl_close($ch);

    // Log API response for debugging
    file_put_contents('api_response_log.txt', $response); // Log the response to a file for review

    $result = json_decode($response, true);

    // Check if the response format is as expected
    if (!isset($result['choices'][0]['message']['content'])) {
        echo "Unexpected API response format. Full response logged in 'api_response_log.txt'.";
        return false;
    }

    return $result['choices'][0]['message']['content'];
}

// Main processing logic when POST request is received
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $brief = isset($_POST['Brief']) ? $conn->real_escape_string($_POST['Brief']) : '';
    $company_size = isset($_POST['company_size']) ? $conn->real_escape_string($_POST['company_size']) : '';
    $industry = isset($_POST['industry']) ? $conn->real_escape_string($_POST['industry']) : '';
    $cbc = isset($_POST['cbc']) ? $conn->real_escape_string($_POST['cbc']) : '';
    $ib = isset($_POST['ib']) ? $conn->real_escape_string($_POST['ib']) : '';
    $ai_outcomes = isset($_POST['ai_outcomes']) ? implode(", ", $_POST['ai_outcomes']) : '';
    $customer_service = isset($_POST['customer_service']) ? $conn->real_escape_string($_POST['customer_service']) : '';
    $sales_marketing = isset($_POST['sales_marketing']) ? $conn->real_escape_string($_POST['sales_marketing']) : '';
    $operations_logistics = isset($_POST['operations_logistics']) ? $conn->real_escape_string($_POST['operations_logistics']) : '';
    $product_development = isset($_POST['product_development']) ? $conn->real_escape_string($_POST['product_development']) : '';
    $financial_management = isset($_POST['financial_management']) ? $conn->real_escape_string($_POST['financial_management']) : '';
    $existing_ai_ml = isset($_POST['existing-ai-ml']) ? $conn->real_escape_string($_POST['existing-ai-ml']) : '';
    $specify_ai_ml = isset($_POST['specify-ai-ml-textarea']) ? $conn->real_escape_string($_POST['specify-ai-ml-textarea']) : '';
    $ai_concerns = isset($_POST['ai_concerns']) ? implode(", ", $_POST['ai_concerns']) : '';
    $ai_capabilities = isset($_POST['ai_capabilities']) ? implode(", ", $_POST['ai_capabilities']) : '';
    $ai_investment_readiness = isset($_POST['ai_investment_readiness']) ? $conn->real_escape_string($_POST['ai_investment_readiness']) : '';
    $ai_implementation_approach = isset($_POST['ai_implementation_approach']) ? $conn->real_escape_string($_POST['ai_implementation_approach']) : '';
    $additional_comments = isset($_POST['additional_comments']) ? $conn->real_escape_string($_POST['additional_comments']) : '';

    $ip_address = getClientIP();
    $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $conn->real_escape_string($_SERVER['HTTP_USER_AGENT']) : '';
    $submission_date = date('Y-m-d H:i:s');

    // First, insert the user information and fetch its company_name
    $company_name = 'Example Company';  // Replace this with the actual value from user input or database
    
    // Insert into the kit_details table (no user_id here)
    $sql_kit = "INSERT INTO kit_details (Brief, company_size, industry, cbc, ib, ai_outcomes, customer_service, sales_marketing, operations_logistics, product_development, financial_management, existing_ai_ml, specify_ai_ml, ai_concerns, ai_capabilities, ai_investment_readiness, ai_implementation_approach, additional_comments, ip_address, user_agent, submission_date) 
            VALUES ('$brief', '$company_size', '$industry', '$cbc', '$ib', '$ai_outcomes', '$customer_service', '$sales_marketing', '$operations_logistics', '$product_development', '$financial_management', '$existing_ai_ml', '$specify_ai_ml', '$ai_concerns', '$ai_capabilities', '$ai_investment_readiness', '$ai_implementation_approach', '$additional_comments', '$ip_address', '$user_agent', '$submission_date')";

    if ($conn->query($sql_kit) === TRUE) {
        fetchAndSaveData($conn, $company_name);

        $sql_user = "SELECT company_name, job_role, json_file FROM user_information WHERE company_name = '$company_name'";
        $result_user = $conn->query($sql_user);
        $user_data = $result_user->fetch_assoc();

        $sql_kit = "SELECT * FROM kit_details ORDER BY submission_date DESC LIMIT 1";
        $result_kit = $conn->query($sql_kit);
        $kit_data = $result_kit->fetch_assoc();

        $company_data = [
            "company" => [
                "companyName" => $user_data['company_name'],
                "jobRole" => $user_data['job_role']
            ],
            "companyDetails" => [
                "briefAboutCompany" => $kit_data['Brief'],
                "companySize" => $kit_data['company_size'],
                "industry" => $kit_data['industry']
            ]
        ];

        $recommendations = generateAIRecommendations($company_data);

        if ($recommendations === false) {
            exit();
        }

        $fileName = generateFileName($company_data['company']['companyName']);

        $html = "<!DOCTYPE html>
        <html lang='en'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>AI Recommendations for " . htmlspecialchars($company_data['company']['companyName']) . "</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
                .container { width: 80%; margin: 0 auto; padding: 20px; }
                h1, h2 { color: #2c3e50; }
                .section { margin-bottom: 30px; border-bottom: 1px solid #eee; padding-bottom: 20px; }
                .executive-summary, .company-context, .expected-impact { background-color: #f9f9f9; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
                .recommendation { margin-bottom: 30px; }
                .download-btn { padding: 10px 20px; background-color: #4CAF50; color: white; text-decoration: none; border-radius: 5px; margin-top: 20px; display: inline-block; }
            </style>
        </head>
        <body>
            <div class='container'>
                <h1>AI Recommendations for " . htmlspecialchars($company_data['company']['companyName']) . "</h1>
                " . nl2br($recommendations) . "
                <a href='#' class='download-btn' onclick='downloadHTML()'>Download Recommendations</a>
            </div>
            <script>
            function downloadHTML() {
                var htmlContent = document.documentElement.outerHTML;
                var blob = new Blob([htmlContent], { type: 'text/html' });
                var link = document.createElement('a');
                link.href = window.URL.createObjectURL(blob);
                link.download = '" . $fileName . "';
                link.click();

                // After download, clear session and redirect to valid.html
                window.location.href = 'valid.html';
            }
            </script>
        </body>
        </html>";

        $file_path = $_SERVER['DOCUMENT_ROOT'] . "/Assesment-Templates/frontend/templates/" . $fileName;

        if (file_put_contents($file_path, $html) !== false) {
            header("Location: /Assesment-Templates/frontend/templates/" . $fileName);
            exit();
        } else {
            echo "Error saving the AI recommendations.";
        }
    } else {
        echo "Error: " . $sql_kit . "<br>" . $conn->error;
    }

    $conn->close();
}
?>
