<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace smsgateway_customapi;

use core\http_client;
use core_sms\gateway as core_gateway;
use core_sms\manager;
use core_sms\message;
use core_sms\message_status;
use GuzzleHttp\Exception\GuzzleException;
use stdClass;

/**
 * A generic, configurable API gateway for sending SMS with Postman-like flexibility.
 *
 * @package     smsgateway_customapi
 * @copyright   2025 Kewayne Davidson
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class gateway extends core_gateway {

    /**
     * Sends an SMS message using the configured custom API settings.
     *
     * @param message $message The message object to send.
     * @return message The updated message object.
     */
    #[\Override]
    public function send(message $message): message {
        $result = self::execute_api_request(
            config: $this->config,
            recipientnumber: $message->recipientnumber,
            content: $message->content
        );

        return $message->with(
            status: $result['success'] ? message_status::GATEWAY_SENT : message_status::GATEWAY_FAILED,
        );
    }

    /**
     * Executes an API request with full customization for headers, query params, form params, or raw JSON body.
     *
     * @param stdClass $config Gateway configuration object.
     * @param string $recipientnumber Target phone number.
     * @param string $content Message text.
     * @return array Execution details including success, statuscode, responsebody, responseheaders, requestsent, executiontime.
     */
    public static function execute_api_request(stdClass $config, string $recipientnumber, string $content): array {
        $apiurl = trim($config->api_url ?? '');
        if (empty($apiurl)) {
            return [
                'success' => false,
                'statuscode' => 0,
                'responsebody' => get_string('error_api_url_not_configured', 'smsgateway_customapi'),
                'responseheaders' => '',
                'requestsent' => [
                    'url' => '',
                    'method' => 'GET',
                    'headers' => [],
                    'body' => '',
                ],
                'executiontime' => 0.0,
                'errormessage' => get_string('error_api_url_missing', 'smsgateway_customapi'),
            ];
        }

        // Format recipient number.
        $countrycode = !empty($config->countrycode) ? $config->countrycode : null;
        $formattedrecipient = manager::format_number(
            phonenumber: $recipientnumber,
            countrycode: $countrycode,
        );
        $formattedrecipient = preg_replace('/[^\d]/', '', $formattedrecipient);

        // Define raw replacements for placeholders.
        $replacements = [
            '{{recipient}}' => $formattedrecipient,
            '{{message}}' => $content,
        ];

        // HTTP Method.
        $method = strtoupper(trim($config->request_type ?? 'GET'));
        if (!in_array($method, ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            $method = 'GET';
        }

        // Build Guzzle options.
        $options = [
            'connect_timeout' => 10,
            'timeout' => 30,
            'http_errors' => false,
        ];

        // 1. Parse Headers.
        $headers = self::parse_key_value_pairs($config->headers ?? '', $replacements, ':');
        $options['headers'] = $headers;

        // Add default User-Agent if not explicitly specified to pass Oracle ORDS WAF checks.
        $hasUserAgent = false;
        foreach (array_keys($options['headers']) as $hKey) {
            if (strtolower($hKey) === 'user-agent') {
                $hasUserAgent = true;
                break;
            }
        }
        if (!$hasUserAgent) {
            $options['headers']['User-Agent'] = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';
        }

        // 2. Parse Query Parameters.
        $queryParams = self::parse_key_value_pairs($config->query_parameters ?? '', $replacements, '=');
        if (!empty($queryParams)) {
            $options['query'] = $queryParams;
        }

        // 3. Request Body.
        $bodyType = $config->body_type ?? 'form';
        $sentBody = '';

        if (in_array($method, ['POST', 'PUT', 'PATCH'], true)) {
            if ($bodyType === 'json') {
                $rawJson = $config->json_body ?? '';

                // JSON-encode string placeholders to safely handle quotes, newlines, and backslashes in JSON payloads.
                $jsonReplacements = [];
                foreach ($replacements as $key => $val) {
                    $encodedVal = json_encode((string)$val, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    if (str_starts_with($encodedVal, '"') && str_ends_with($encodedVal, '"')) {
                        $encodedVal = substr($encodedVal, 1, -1);
                    }
                    $jsonReplacements[$key] = $encodedVal;
                }

                $sentBody = str_replace(array_keys($jsonReplacements), array_values($jsonReplacements), $rawJson);
                $options['body'] = $sentBody;

                // Ensure Content-Type header is set.
                $hasContentType = false;
                foreach (array_keys($options['headers']) as $hName) {
                    if (strtolower($hName) === 'content-type') {
                        $hasContentType = true;
                        break;
                    }
                }
                if (!$hasContentType) {
                    $options['headers']['Content-Type'] = 'application/json';
                }
            } else if ($bodyType === 'raw') {
                $rawText = $config->json_body ?? '';
                $sentBody = str_replace(array_keys($replacements), array_values($replacements), $rawText);
                $options['body'] = $sentBody;
            } else {
                // Form params.
                $formParams = self::parse_key_value_pairs($config->post_body_parameters ?? '', $replacements, '=');
                $options['form_params'] = $formParams;
                $sentBody = $formParams;
            }
        }

        // Build full URL for reporting.
        $fullUrl = $apiurl;
        if (!empty($queryParams)) {
            $queryString = http_build_query($queryParams);
            $fullUrl .= (str_contains($apiurl, '?') ? '&' : '?') . $queryString;
        }

        $requestSentInfo = [
            'url' => $fullUrl,
            'method' => $method,
            'headers' => $options['headers'],
            'body' => $sentBody,
        ];

        $starttime = microtime(true);
        $client = \core\di::get(http_client::class);

        $statuscode = 0;
        $responsebody = '';
        $responseheaders = '';
        $success = false;
        $errormessage = '';

        try {
            $response = $client->request($method, $apiurl, $options);

            $endtime = microtime(true);
            $executiontime = round(($endtime - $starttime) * 1000, 2);

            $statuscode = $response->getStatusCode();
            $responsebody = (string)$response->getBody();

            // Format response headers.
            $rawHeaders = [];
            foreach ($response->getHeaders() as $name => $values) {
                $rawHeaders[] = $name . ': ' . implode(', ', $values);
            }
            $responseheaders = implode("\n", $rawHeaders);

            // Check success condition.
            $successcondition = trim($config->success_condition ?? '');
            if ($statuscode >= 200 && $statuscode < 300) {
                if (empty($successcondition) || str_contains($responsebody, $successcondition)) {
                    $success = true;
                } else {
                    $errormessage = get_string('error_success_condition_not_found', 'smsgateway_customapi', (object)[
                        'statuscode' => $statuscode,
                        'condition'  => $successcondition,
                    ]);
                }
            } else {
                $errormessage = get_string('error_http_status', 'smsgateway_customapi', $statuscode);
            }

        } catch (GuzzleException $e) {
            $endtime = microtime(true);
            $executiontime = round(($endtime - $starttime) * 1000, 2);

            $statuscode = $e->getCode();
            $responsebody = get_string('error_request_exception', 'smsgateway_customapi', $e->getMessage());
            $errormessage = $e->getMessage();
        }

        return [
            'success' => $success,
            'statuscode' => $statuscode,
            'responsebody' => $responsebody,
            'responseheaders' => $responseheaders,
            'requestsent' => $requestSentInfo,
            'executiontime' => $executiontime,
            'errormessage' => $errormessage,
        ];
    }

    /**
     * Parses key-value pairs from multiline text.
     *
     * @param string $text Key-value lines.
     * @param array $replacements Placeholder replacements.
     * @param string $separator Separator between key and value (default =).
     * @return array Parsed associative array.
     */
    public static function parse_key_value_pairs(string $text, array $replacements, string $separator = '='): array {
        $params = [];
        $lines = explode("\n", trim($text));

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || str_starts_with($line, '#')) {
                continue;
            }

            $parts = explode($separator, $line, 2);
            if (count($parts) === 2) {
                $key = trim($parts[0]);
                $value = trim($parts[1]);
                $value = str_replace(array_keys($replacements), array_values($replacements), $value);
                $params[$key] = $value;
            }
        }

        return $params;
    }

    /**
     * Returns send priority for message dispatch.
     *
     * @param message $message The SMS message.
     * @return int Priority (higher = more preferred).
     */
    #[\Override]
    public function get_send_priority(message $message): int {
        return 100;
    }
}
