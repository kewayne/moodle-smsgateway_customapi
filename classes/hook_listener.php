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

use core_sms\hook\after_sms_gateway_form_hook;
use html_writer;

defined('MOODLE_INTERNAL') || die();

/**
 * Hook listener for extending SMS Gateway configuration form.
 *
 * @package    smsgateway_customapi
 * @copyright  2025 Kewayne Davidson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class hook_listener {

    /**
     * Primary callback for core_sms hook.
     *
     * @param after_sms_gateway_form_hook $hook Hook instance.
     */
    public static function set_form_definition_for_customapi_sms_gateway(after_sms_gateway_form_hook $hook): void {
        self::extend_gateway_form($hook);
    }

    /**
     * Extends the gateway form with custom API parameters.
     *
     * @param mixed $hook Hook instance.
     */
    public static function extend_gateway_form(mixed $hook): void {
        global $PAGE;

        $gateway = method_exists($hook, 'get_gateway') ? $hook->get_gateway() : ($hook->plugin ?? '');

        // Only extend if this is our customapi gateway.
        if ($gateway !== 'smsgateway_customapi') {
            return;
        }

        $mform = method_exists($hook, 'get_mform') ? $hook->get_mform() : ($hook->mform ?? null);
        if (!$mform) {
            return;
        }

        // -------------------------------------------------------------
        // 1. Endpoint Settings
        // -------------------------------------------------------------
        $mform->addElement('header', 'api_settings_header', get_string('api_settings', 'smsgateway_customapi'));

        $mform->addElement('text', 'api_url', get_string('api_url', 'smsgateway_customapi'), ['size' => 60, 'placeholder' => 'https://api.smsprovider.com/v1/send']);
        $mform->setType('api_url', PARAM_TEXT);
        $mform->addRule('api_url', null, 'required', null, 'client');
        $mform->addElement('static', 'api_url_desc', '', get_string('api_url_desc', 'smsgateway_customapi'));

        $mform->addElement('select', 'request_type', get_string('request_type', 'smsgateway_customapi'), [
            'GET'    => 'GET',
            'POST'   => 'POST',
            'PUT'    => 'PUT',
            'PATCH'  => 'PATCH',
            'DELETE' => 'DELETE',
        ]);
        $mform->setDefault('request_type', 'POST');
        $mform->addElement('static', 'request_type_desc', '', get_string('request_type_desc', 'smsgateway_customapi'));

        // -------------------------------------------------------------
        // 2. Headers, Query Parameters & Payload
        // -------------------------------------------------------------
        $mform->addElement('header', 'request_params_header', get_string('request_params_header', 'smsgateway_customapi'));

        // Placeholder Guide.
        $mform->addElement('static', 'placeholders_guide',
            get_string('placeholders_heading', 'smsgateway_customapi'),
            html_writer::tag('div', get_string('placeholders_info', 'smsgateway_customapi'), [
                'class' => 'alert alert-info py-2 px-3 mb-3 small',
            ])
        );

        // HTTP Headers.
        $mform->addElement('textarea', 'headers',
            get_string('headers', 'smsgateway_customapi'),
            'wrap="virtual" rows="3" cols="60" class="font-monospace" placeholder="Content-Type: application/json&#10;Authorization: Bearer your_token"');
        $mform->setType('headers', PARAM_TEXT);
        $mform->addElement('static', 'headers_desc', '', get_string('headers_desc', 'smsgateway_customapi'));

        // Query Parameters.
        $mform->addElement('textarea', 'query_parameters',
            get_string('query_parameters', 'smsgateway_customapi'),
            'wrap="virtual" rows="3" cols="60" class="font-monospace" placeholder="apikey=xyz123&#10;format=json"');
        $mform->setType('query_parameters', PARAM_TEXT);
        $mform->addElement('static', 'query_parameters_desc', '', get_string('query_parameters_desc', 'smsgateway_customapi'));

        // Body Type.
        $mform->addElement('select', 'body_type', get_string('body_type', 'smsgateway_customapi'), [
            'form' => get_string('body_type_form', 'smsgateway_customapi'),
            'json' => get_string('body_type_json', 'smsgateway_customapi'),
            'raw'  => get_string('body_type_raw', 'smsgateway_customapi'),
        ]);
        $mform->setDefault('body_type', 'form');
        $mform->addElement('static', 'body_type_desc', '', get_string('body_type_desc', 'smsgateway_customapi'));
        $mform->hideIf('body_type', 'request_type', 'eq', 'GET');
        $mform->hideIf('body_type_desc', 'request_type', 'eq', 'GET');

        // Form Body Parameters (Key=Value).
        $mform->addElement('textarea', 'post_body_parameters',
            get_string('post_body_parameters', 'smsgateway_customapi'),
            'wrap="virtual" rows="5" cols="60" class="font-monospace" placeholder="to={{recipient}}&#10;message={{message}}"');
        $mform->setType('post_body_parameters', PARAM_TEXT);
        $mform->addElement('static', 'post_body_parameters_desc', '', get_string('post_body_parameters_desc', 'smsgateway_customapi'));
        $mform->hideIf('post_body_parameters', 'request_type', 'eq', 'GET');
        $mform->hideIf('post_body_parameters', 'body_type', 'ne', 'form');
        $mform->hideIf('post_body_parameters_desc', 'request_type', 'eq', 'GET');
        $mform->hideIf('post_body_parameters_desc', 'body_type', 'ne', 'form');

        // JSON Tools & Beautifier toolbar.
        $jsonToolsHtml = html_writer::start_div('d-flex align-items-center justify-content-between mb-2 mt-2') .
            html_writer::start_div('btn-group btn-group-sm', ['role' => 'group']) .
            html_writer::tag('button', '<i class="fa fa-magic me-1"></i>' . get_string('format_json', 'smsgateway_customapi'), [
                'type' => 'button',
                'class' => 'btn btn-outline-primary btn-sm me-1',
                'id' => 'customapi_btn_format_json',
            ]) .
            html_writer::tag('button', get_string('insert_recipient', 'smsgateway_customapi'), [
                'type' => 'button',
                'class' => 'btn btn-outline-secondary btn-sm customapi-chip me-1',
                'data-insert' => '{{recipient}}',
            ]) .
            html_writer::tag('button', get_string('insert_message', 'smsgateway_customapi'), [
                'type' => 'button',
                'class' => 'btn btn-outline-secondary btn-sm customapi-chip',
                'data-insert' => '{{message}}',
            ]) .
            html_writer::end_div() .
            html_writer::div('', 'ms-2', ['id' => 'json_syntax_badge']) .
            html_writer::end_div();

        $mform->addElement('static', 'json_tools_wrapper', get_string('json_tools', 'smsgateway_customapi'), $jsonToolsHtml);
        $mform->hideIf('json_tools_wrapper', 'request_type', 'eq', 'GET');
        $mform->hideIf('json_tools_wrapper', 'body_type', 'eq', 'form');

        // JSON / Raw Body Textarea with code font.
        $mform->addElement('textarea', 'json_body',
            get_string('json_body', 'smsgateway_customapi'),
            'wrap="virtual" rows="7" cols="60" class="font-monospace" placeholder="{&#10;  &quot;to&quot;: &quot;{{recipient}}&quot;,&#10;  &quot;text&quot;: &quot;{{message}}&quot;&#10;}"');
        $mform->setType('json_body', PARAM_TEXT);
        $mform->addElement('static', 'json_body_desc', '', get_string('json_body_desc', 'smsgateway_customapi'));
        $mform->hideIf('json_body', 'request_type', 'eq', 'GET');
        $mform->hideIf('json_body', 'body_type', 'eq', 'form');
        $mform->hideIf('json_body_desc', 'request_type', 'eq', 'GET');
        $mform->hideIf('json_body_desc', 'body_type', 'eq', 'form');

        // -------------------------------------------------------------
        // 3. Response Handling
        // -------------------------------------------------------------
        $mform->addElement('header', 'response_settings_header', get_string('response_settings', 'smsgateway_customapi'));

        $mform->addElement('text', 'success_condition', get_string('success_condition', 'smsgateway_customapi'), ['size' => 60, 'placeholder' => '"status":"success"']);
        $mform->setType('success_condition', PARAM_TEXT);
        $mform->addElement('static', 'success_condition_desc', '', get_string('success_condition_desc', 'smsgateway_customapi'));

        // -------------------------------------------------------------
        // 4. Live Gateway Tester (Postman style embedded runner)
        // -------------------------------------------------------------
        $mform->addElement('header', 'test_settings_header', get_string('test_connection', 'smsgateway_customapi'));

        $mform->addElement('static', 'test_info', '', get_string('test_connection_desc', 'smsgateway_customapi'));

        $mform->addElement('text', 'test_number', get_string('test_number', 'smsgateway_customapi'), ['size' => 30, 'placeholder' => '+18765550199']);
        $mform->setType('test_number', PARAM_TEXT);
        $mform->addElement('static', 'test_number_desc', '', get_string('test_number_desc', 'smsgateway_customapi'));

        $mform->addElement('textarea', 'test_message', get_string('test_message', 'smsgateway_customapi'), 'wrap="virtual" rows="2" cols="60"');
        $mform->setType('test_message', PARAM_TEXT);
        $mform->addElement('static', 'test_message_desc', '', get_string('test_message_desc', 'smsgateway_customapi'));

        // Embedded runner button & results container.
        $testRunnerHtml = html_writer::start_div('mt-2') .
            html_writer::tag('button', get_string('test_button', 'smsgateway_customapi'), [
                'type'  => 'button',
                'id'    => 'customapi_test_btn',
                'class' => 'btn btn-primary',
            ]) .
            html_writer::div('', '', ['id' => 'customapi_test_results']) .
            html_writer::end_div();

        $mform->addElement('static', 'test_runner_wrapper', '', $testRunnerHtml);

        // Require AMD JavaScript module for live testing & JSON tools.
        $PAGE->requires->js_call_amd('smsgateway_customapi/test_connection', 'init');
    }
}
