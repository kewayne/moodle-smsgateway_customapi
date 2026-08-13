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

namespace smsgateway_customapi\external;

use context_system;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use smsgateway_customapi\gateway;
use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * External API service to test Custom API SMS Gateway connections.
 *
 * @package    smsgateway_customapi
 * @copyright  2025 Kewayne Davidson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class test_connection extends external_api {

    /**
     * Parameter definitions.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'api_url' => new external_value(PARAM_RAW, 'API Endpoint URL', VALUE_DEFAULT, ''),
            'request_type' => new external_value(PARAM_ALPHA, 'HTTP Method', VALUE_DEFAULT, 'GET'),
            'body_type' => new external_value(PARAM_ALPHA, 'Body Format', VALUE_DEFAULT, 'form'),
            'headers' => new external_value(PARAM_RAW, 'Headers string', VALUE_DEFAULT, ''),
            'query_parameters' => new external_value(PARAM_RAW, 'Query Parameters string', VALUE_DEFAULT, ''),
            'post_body_parameters' => new external_value(PARAM_RAW, 'Form parameters string', VALUE_DEFAULT, ''),
            'json_body' => new external_value(PARAM_RAW, 'JSON payload string', VALUE_DEFAULT, ''),
            'success_condition' => new external_value(PARAM_RAW, 'Success Condition substring', VALUE_DEFAULT, ''),
            'countrycode' => new external_value(PARAM_RAW, 'Country code', VALUE_DEFAULT, ''),
            'test_number' => new external_value(PARAM_RAW, 'Test recipient phone number', VALUE_DEFAULT, ''),
            'test_message' => new external_value(PARAM_RAW, 'Test SMS message content', VALUE_DEFAULT, ''),
        ]);
    }

    /**
     * Executes live connection test.
     *
     * @param string $api_url API endpoint.
     * @param string $request_type HTTP method (GET, POST, etc).
     * @param string $body_type Body format (form, json, raw).
     * @param string $headers Custom headers.
     * @param string $query_parameters Query params.
     * @param string $post_body_parameters Form params.
     * @param string $json_body JSON payload string.
     * @param string $success_condition Success substring.
     * @param string $countrycode Country code.
     * @param string $test_number Test recipient.
     * @param string $test_message Test content.
     * @return array Response details.
     */
    public static function execute(
        string $api_url = '',
        string $request_type = 'GET',
        string $body_type = 'form',
        string $headers = '',
        string $query_parameters = '',
        string $post_body_parameters = '',
        string $json_body = '',
        string $success_condition = '',
        string $countrycode = '',
        string $test_number = '',
        string $test_message = ''
    ): array {
        // Validate parameter structure.
        $params = self::validate_parameters(self::execute_parameters(), [
            'api_url' => $api_url,
            'request_type' => $request_type,
            'body_type' => $body_type,
            'headers' => $headers,
            'query_parameters' => $query_parameters,
            'post_body_parameters' => $post_body_parameters,
            'json_body' => $json_body,
            'success_condition' => $success_condition,
            'countrycode' => $countrycode,
            'test_number' => $test_number,
            'test_message' => $test_message,
        ]);

        // Security check.
        $context = context_system::instance();
        self::validate_context($context);
        require_capability('moodle/site:config', $context);

        $config = new stdClass();
        $config->api_url = $params['api_url'];
        $config->request_type = $params['request_type'];
        $config->body_type = $params['body_type'];
        $config->headers = $params['headers'];
        $config->query_parameters = $params['query_parameters'];
        $config->post_body_parameters = $params['post_body_parameters'];
        $config->json_body = $params['json_body'];
        $config->success_condition = $params['success_condition'];
        $config->countrycode = $params['countrycode'];

        $recipientNumber = !empty($params['test_number']) ? $params['test_number'] : '1234567890';
        $messageContent = !empty($params['test_message']) ? $params['test_message'] : get_string('test_message_default', 'smsgateway_customapi');

        $result = gateway::execute_api_request($config, $recipientNumber, $messageContent);

        // Format request sent headers and body as strings.
        $reqSent = $result['requestsent'] ?? [];
        $reqHeadersStr = '';
        if (!empty($reqSent['headers']) && is_array($reqSent['headers'])) {
            $hLines = [];
            foreach ($reqSent['headers'] as $k => $v) {
                $hLines[] = "$k: $v";
            }
            $reqHeadersStr = implode("\n", $hLines);
        }

        $reqBodyStr = '';
        if (isset($reqSent['body'])) {
            if (is_array($reqSent['body'])) {
                $reqBodyStr = http_build_query($reqSent['body']);
            } else {
                $reqBodyStr = (string)$reqSent['body'];
            }
        }

        return [
            'success' => (bool)$result['success'],
            'statuscode' => (int)$result['statuscode'],
            'responsebody' => (string)($result['responsebody'] ?? ''),
            'responseheaders' => (string)($result['responseheaders'] ?? ''),
            'requestsent_url' => (string)($reqSent['url'] ?? ''),
            'requestsent_method' => (string)($reqSent['method'] ?? ''),
            'requestsent_headers' => $reqHeadersStr,
            'requestsent_body' => $reqBodyStr,
            'executiontime' => (float)($result['executiontime'] ?? 0.0),
            'errormessage' => (string)($result['errormessage'] ?? ''),
        ];
    }

    /**
     * Output definition.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'True if test request met success criteria'),
            'statuscode' => new external_value(PARAM_INT, 'HTTP status code'),
            'responsebody' => new external_value(PARAM_RAW, 'Raw response body'),
            'responseheaders' => new external_value(PARAM_RAW, 'Response headers'),
            'requestsent_url' => new external_value(PARAM_RAW, 'Request URL sent'),
            'requestsent_method' => new external_value(PARAM_RAW, 'Request HTTP method'),
            'requestsent_headers' => new external_value(PARAM_RAW, 'Request headers sent'),
            'requestsent_body' => new external_value(PARAM_RAW, 'Request body payload sent'),
            'executiontime' => new external_value(PARAM_FLOAT, 'Latency in ms'),
            'errormessage' => new external_value(PARAM_RAW, 'Error details if failed'),
        ]);
    }
}
