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

/**
 * English language strings for smsgateway_customapi.
 *
 * @package    smsgateway_customapi
 * @copyright  2025 Kewayne Davidson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['api_settings'] = 'API Endpoint & Settings';
$string['api_url'] = 'API URL';
$string['api_url_desc'] = 'The full endpoint URL for the API call (e.g. https://api.smsprovider.com/v1/send).';
$string['body_type'] = 'Request Body Format';
$string['body_type_desc'] = 'Select how the request payload should be formatted for POST, PUT, or PATCH requests.';
$string['body_type_form'] = 'Form Data (key=value parameters)';
$string['body_type_json'] = 'JSON Payload (application/json)';
$string['body_type_raw'] = 'Raw Text / Plain Payload';
$string['countrycode'] = 'Default country code';
$string['countrycode_help'] = 'Country code to be added to phone numbers if users don\'t enter their own country code. Enter the number without the leading \'+\' symbol.';
$string['error_api_url_missing'] = 'API URL is not defined.';
$string['error_api_url_not_configured'] = 'API URL is not configured in gateway settings.';
$string['error_http_status'] = 'HTTP request failed with status code {$a}';
$string['error_request_exception'] = 'Request Exception: {$a}';
$string['error_success_condition_not_found'] = 'HTTP status {$a->statuscode} succeeded, but success condition substring "{$a->condition}" was not found in response body.';
$string['format_json'] = 'Format / Beautify JSON';
$string['gateway_name'] = 'Custom API Gateway';
$string['headers'] = 'HTTP Headers';
$string['headers_desc'] = 'One header per line in `Key: Value` format (e.g., `Authorization: Bearer your_token`).';
$string['insert_message'] = '+ {{message}}';
$string['insert_recipient'] = '+ {{recipient}}';
$string['json_body'] = 'JSON Payload Body';
$string['json_body_desc'] = 'Enter raw JSON string with placeholders. Example: {"to": "{{recipient}}", "message": "{{message}}"}';
$string['json_invalid'] = 'Invalid JSON Syntax';
$string['json_tools'] = 'JSON Tools & Placeholders';
$string['json_valid'] = 'Valid JSON Syntax';
$string['placeholders_heading'] = 'Available Placeholders';
$string['placeholders_info'] = 'You can use the following placeholders in headers, query parameters, or body payloads: <code>{{recipient}}</code> Recipient Phone Number &bull; <code>{{message}}</code> SMS Message Content.';
$string['pluginname'] = 'Custom API Gateway';
$string['post_body_parameters'] = 'Body Parameters (Form URL-encoded)';
$string['post_body_parameters_desc'] = 'One parameter per line in `key=value` format. Sent as application/x-www-form-urlencoded.';
$string['privacy:metadata'] = 'The Custom API SMS Gateway plugin does not store personal data.';
$string['query_parameters'] = 'Query Parameters';
$string['query_parameters_desc'] = 'One parameter per line in `key=value` format. Appended to the endpoint URL after `?`.';
$string['request_params_header'] = 'Request Parameters & Payload';
$string['request_type'] = 'HTTP Method';
$string['request_type_desc'] = 'The HTTP method to use for the API request.';
$string['response_settings'] = 'Response Verification';
$string['success_condition'] = 'Success Condition Substring';
$string['success_condition_desc'] = 'A string that must be present in the API response body to consider the message successfully sent (e.g. "status":"success"). Leave empty to check for HTTP status 2xx.';
$string['test_body_sent'] = 'Body Sent';
$string['test_button'] = 'Send Test Request';
$string['test_connection'] = 'Test Gateway Connection';
$string['test_connection_desc'] = 'Test your API request directly without leaving the settings page.';
$string['test_empty_body'] = '(Empty response body)';
$string['test_error_ajax'] = 'AJAX Request Failed:';
$string['test_error_missing_url'] = 'Please enter an API URL before sending a test request.';
$string['test_failed'] = 'Test Failed';
$string['test_headers_sent'] = 'Headers Sent';
$string['test_latency'] = 'Latency:';
$string['test_loading'] = 'Executing API test request...';
$string['test_message'] = 'Test Message Content';
$string['test_message_default'] = 'Test message from Moodle Custom API Gateway';
$string['test_message_desc'] = 'Sample message content to send during testing.';
$string['test_no_headers'] = '(No headers received)';
$string['test_none'] = '(None)';
$string['test_note'] = 'Note';
$string['test_number'] = 'Test Recipient Number';
$string['test_number_desc'] = 'Phone number with country code (e.g. +18765550199).';
$string['test_request_sent'] = 'Request Sent';
$string['test_response_body'] = 'Response Body';
$string['test_response_headers'] = 'Response Headers';
$string['test_status'] = 'Status';
$string['test_successful'] = 'Test Successful';
$string['test_url'] = 'URL';
$string['test_warning'] = 'Warning';
