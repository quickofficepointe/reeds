<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AdvantaSMSService
{
    private $baseUrl;
    private $partnerId;
    private $apiKey;
    private $shortcode;

    public function __construct()
    {
        $this->baseUrl = config('services.advanta.base_url', 'https://quicksms.advantasms.com');
        $this->partnerId = config('services.advanta.partner_id');
        $this->apiKey = config('services.advanta.api_key');
        $this->shortcode = config('services.advanta.shortcode', 'QuickOffice');
    }

    /**
     * Send single SMS
     */
    public function sendSMS($mobile, $message, $clientSmsId = null)
    {
        try {
            // Format mobile number (ensure it's 2547... format)
            $mobile = $this->formatMobileNumber($mobile);

            $payload = [
                'apikey' => $this->apiKey,
                'partnerID' => $this->partnerId,
                'message' => $message,
                'shortcode' => $this->shortcode,
                'mobile' => $mobile,
            ];

            if ($clientSmsId) {
                $payload['clientsmsid'] = $clientSmsId;
            }

            $response = Http::post("{$this->baseUrl}/api/services/sendsms/", $payload);

            Log::info('AdvantaSMS Response', [
                'mobile' => $mobile,
                'response' => $response->json(),
                'status' => $response->status(),
            ]);

            return $response->json();

        } catch (\Exception $e) {
            Log::error('AdvantaSMS Error: ' . $e->getMessage());
            return [
                'responses' => [[
                    'respose-code' => 500,
                    'response-description' => 'System Error: ' . $e->getMessage(),
                    'mobile' => $mobile,
                ]]
            ];
        }
    }

    /**
     * Send bulk SMS (up to 20 messages)
     */
    public function sendBulkSMS($messages)
    {
        try {
            $smsList = [];

            foreach ($messages as $index => $messageData) {
                $mobile = $this->formatMobileNumber($messageData['mobile']);

                $smsList[] = [
                    'partnerID' => $this->partnerId,
                    'apikey' => $this->apiKey,
                    'pass_type' => 'plain',
                    'clientsmsid' => $messageData['client_sms_id'] ?? $index + 1,
                    'mobile' => $mobile,
                    'message' => $messageData['message'],
                    'shortcode' => $this->shortcode,
                ];
            }

            $payload = [
                'count' => count($smsList),
                'smslist' => $smsList,
            ];

            $response = Http::post("{$this->baseUrl}/api/services/sendbulk/", $payload);

            Log::info('AdvantaSMS Bulk Response', [
                'count' => count($smsList),
                'response' => $response->json(),
            ]);

            return $response->json();

        } catch (\Exception $e) {
            Log::error('AdvantaSMS Bulk Error: ' . $e->getMessage());
            return [
                'responses' => [[
                    'respose-code' => 500,
                    'response-description' => 'System Error: ' . $e->getMessage(),
                ]]
            ];
        }
    }

    /**
     * Check SMS delivery status
     */
    public function getDeliveryReport($messageId)
    {
        try {
            $payload = [
                'apikey' => $this->apiKey,
                'partnerID' => $this->partnerId,
                'messageID' => $messageId,
            ];

            $response = Http::post("{$this->baseUrl}/api/services/getdlr/", $payload);

            return $response->json();

        } catch (\Exception $e) {
            Log::error('AdvantaSMS DLR Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get account balance
     */
    public function getBalance()
    {
        try {
            $payload = [
                'apikey' => $this->apiKey,
                'partnerID' => $this->partnerId,
            ];

            $response = Http::post("{$this->baseUrl}/api/services/getbalance/", $payload);

            return $response->json();

        } catch (\Exception $e) {
            Log::error('AdvantaSMS Balance Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Format mobile number to 2547... format
     */
    private function formatMobileNumber($mobile)
    {
        // Remove any non-digit characters
        $mobile = preg_replace('/\D/', '', $mobile);

        // If starts with 0, convert to 254
        if (substr($mobile, 0, 1) === '0') {
            $mobile = '254' . substr($mobile, 1);
        }

        // If starts with 7, add 254
        if (substr($mobile, 0, 1) === '7' && strlen($mobile) === 9) {
            $mobile = '254' . $mobile;
        }

        // Ensure it's exactly 12 digits
        if (strlen($mobile) === 12) {
            return $mobile;
        }

        throw new \Exception("Invalid mobile number format: {$mobile}");
    }
}
