<?php

namespace App\Http\Livewire\Traits;

trait WithErrorMessage
{
    public $error_message;

    protected function parseErrorMessage($errorMessage) {
        // Find the position of the first occurrence of a substring in a string
        $pos = strpos($errorMessage, '{');
    
        // Extract the part before the JSON
        $messagePart = trim(substr($errorMessage, 0, $pos));
    
        // Extract the JSON part
        $jsonPart = substr($errorMessage, $pos);
    
        // Convert JSON string to PHP object
        $jsonPart = json_decode($jsonPart, true);
    
        return $jsonPart 
            ? [
                'status_code' => $messagePart,
                'details' => $jsonPart,
            ]
            : $errorMessage;
    }

    protected function extractStatusCode($message) 
    {
        $pattern = '/\s(\d{3})[\s|:]/';
    
        return preg_match($pattern, $message, $matches)
            ? intval($matches[1])
            : $message; // Return the whole message if no status code found
    }
    
    protected function getStatusCode()
    {
        return array_key_exists('status_code', is_array($this->error_message) ? $this->error_message : [])
            ? $this->extractStatusCode($this->error_message['status_code'])
            : __(500);
    }

    protected function getErrorMessage()
    {
        return array_key_exists('message', $this->error_message['details'] ?? [])
            ? $this->error_message['details']['message']
            : __('No detailed error message available: ').$this->error_message;
    }

    protected function hasErrorMessage(): bool
    {
        return isset($this->error_message);
    }
}