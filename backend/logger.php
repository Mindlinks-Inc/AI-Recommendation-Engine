<?php
// Location: public_html/Assesment-Templates/backend/logger.php

class FormLogger {
    private $logDir;
    private $logFile;
    private $maxLogSize = 5242880; // 5MB

    public function __construct() {
        // Set up log directory and file
        $this->logDir = __DIR__ . '/logs';
        $this->logFile = $this->logDir . '/form_submissions.log';
        
        // Create log directory if it doesn't exist
        if (!file_exists($this->logDir)) {
            mkdir($this->logDir, 0755, true);
        }
        
        // Create log file if it doesn't exist
        if (!file_exists($this->logFile)) {
            touch($this->logFile);
            chmod($this->logFile, 0644);
        }
        
        // Rotate log if needed
        $this->rotateLogIfNeeded();
    }

    public function logSubmission($data) {
        try {
            // Format the log entry
            $logEntry = $this->formatLogEntry($data);
            
            // Write to log file
            return file_put_contents($this->logFile, $logEntry, FILE_APPEND | LOCK_EX) !== false;
        } catch (Exception $e) {
            error_log("Logging error: " . $e->getMessage());
            return false;
        }
    }

    private function formatLogEntry($data) {
        $entry = "=== Form Submission ===\n";
        $entry .= "Date: " . date('Y-m-d H:i:s') . "\n";
        
        foreach ($data as $key => $value) {
            // Skip empty values and sensitive data
            if (empty($value) || in_array($key, ['password', 'credit_card'])) {
                continue;
            }
            
            // Format the key name
            $keyName = ucwords(str_replace('_', ' ', $key));
            
            // Handle arrays and objects
            if (is_array($value) || is_object($value)) {
                $value = json_encode($value);
            }
            
            $entry .= "{$keyName}: {$value}\n";
        }
        
        $entry .= "====================\n\n";
        
        return $entry;
    }

    private function rotateLogIfNeeded() {
        if (file_exists($this->logFile) && filesize($this->logFile) > $this->maxLogSize) {
            $timestamp = date('Y-m-d_H-i-s');
            $backupFile = $this->logDir . '/form_submissions_' . $timestamp . '.log';
            
            rename($this->logFile, $backupFile);
            touch($this->logFile);
            chmod($this->logFile, 0644);
        }
    }
}
?>