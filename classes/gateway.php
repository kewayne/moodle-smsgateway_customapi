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

/**
 * A generic, configurable API gateway for sending SMS.
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
        $result = $this->send_request($message->recipientnumber, $message->content);

        return $message->with(
            status: $result['success'] ? message_status::GATEWAY_SENT : message_status::GATEWAY_FAILED,
        );
    }

    /**
     * Sends an API request with the configured parameters.
     *
     * @param string $recipientnumber The recipient's phone number.
     * @param string $content The message content.
     * @return array Result array with keys: success, statuscode, response.
     */
    private function send_request(string $recipientnumber, string $content): array {
        $apiurl = $this->config->api_url ?? '';
        if (empty($apiurl)) {
            return ['success' => false, 'statuscode' => 0, 'response' => 'API URL is not configured.'];
        }

        // Format and sanitize recipient number.
        $formattedrecipient = manager::format_number(
            phonenumber: $recipientnumber,
            countrycode: $this->config->countrycode ?? null,
        );
        $formattedrecipient = preg_replace('/[^\d]/', '', $formattedrecipient);

        // Placeholder replacements.
        $replacements = [
            '{{recipient}}' => $formattedrecipient,
            '{{message}}' => $content,
        ];

        // Build HTTP client options.
        $options = [
            'connect_timeout' => 5,
            'timeout' => 10,
        ];

        // 1. Headers.
        $options['headers'] = $this->parse_key_value_pairs($this->config->headers ?? '', $replacements, ':');

        // 2. Query parameters (for GET or POST).
        $options['query'] = $this->parse_key_value_pairs($this->config->query_parameters ?? '', $replacements);

        // 3. POST body.
        if (($this->config->request_type ?? 'GET') === 'POST') {
            $bodyparams = $this->parse_key_value_pairs($this->config->post_body_parameters ?? '', $replacements);
            if ($this->has_json_content_type($options['headers'])) {
                $options['json'] = $this->decode_json_body_values($bodyparams);
            } else {
                $options['form_params'] = $bodyparams;
            }
        }

        $client = \core\di::get(http_client::class);
        $statuscode = 0;
        $responsebody = '';
        $success = false;

        try {
            $response = ($this->config->request_type ?? 'GET') === 'POST'
                ? $client->post($apiurl, $options)
                : $client->get($apiurl, $options);

            $statuscode = $response->getStatusCode();
            $responsebody = $response->getBody()->getContents();

            // Check for expected success condition.
            $successcondition = trim($this->config->success_condition ?? '');
            if ($statuscode >= 200 && $statuscode < 300) {
                if (empty($successcondition) || str_contains($responsebody, $successcondition)) {
                    $success = true;
                }
            }

            debugging("CustomAPI response status: $statuscode", DEBUG_DEVELOPER);
            debugging("CustomAPI response body: $responsebody", DEBUG_DEVELOPER);

        } catch (GuzzleException $e) {
            $statuscode = $e->getCode();
            $responsebody = 'GuzzleException: ' . $e->getMessage();
            debugging("CustomAPI exception: " . $e->getMessage(), DEBUG_DEVELOPER);
        }

        return [
            'success' => $success,
            'statuscode' => $statuscode,
            'response' => $responsebody,
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
    private function parse_key_value_pairs(string $text, array $replacements, string $separator = '='): array {
        $params = [];
        $lines = explode("\n", trim($text));

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) {
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
     * Checks whether configured headers request a JSON body.
     *
     * @param array $headers Parsed request headers.
     * @return bool True when the Content-Type header is application/json.
     */
    private function has_json_content_type(array $headers): bool {
        foreach ($headers as $name => $value) {
            if (strtolower($name) === 'content-type' && stripos($value, 'application/json') !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Decodes JSON-compatible scalar and structured body values.
     *
     * @param array $params Parsed body parameters.
     * @return array Body parameters with valid JSON values decoded.
     */
    private function decode_json_body_values(array $params): array {
        foreach ($params as $key => $value) {
            $decoded = json_decode($value);
            if (json_last_error() !== JSON_ERROR_NONE || is_string($decoded)) {
                continue;
            }

            $params[$key] = $decoded;
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
        return 100; // Highest priority for this configurable gateway.
    }
}
