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

use stdClass;

/**
 * Unit tests for Custom API SMS Gateway.
 *
 * @package    smsgateway_customapi
 * @category   test
 * @copyright  2025 Kewayne Davidson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \smsgateway_customapi\gateway
 */
final class gateway_test extends \advanced_testcase {

    /**
     * Test key-value parser and placeholder replacement logic.
     */
    public function test_parse_key_value_pairs(): void {
        $text = "Authorization: Bearer 12345\nTo: {{recipient}}\nText: {{message}}\n# Comment line";
        $replacements = [
            '{{recipient}}' => '18765550199',
            '{{message}}'   => 'Hello World',
        ];

        $parsed = gateway::parse_key_value_pairs($text, $replacements, ':');

        $this->assertArrayHasKey('Authorization', $parsed);
        $this->assertEquals('Bearer 12345', $parsed['Authorization']);
        $this->assertEquals('18765550199', $parsed['To']);
        $this->assertEquals('Hello World', $parsed['Text']);
        $this->assertArrayNotHasKey('# Comment line', $parsed);
    }

    /**
     * Test missing API URL validation.
     */
    public function test_execute_api_request_missing_url(): void {
        $config = new stdClass();
        $config->api_url = '';

        $result = gateway::execute_api_request($config, '1234567890', 'Test message');

        $this->assertFalse($result['success']);
        $this->assertEquals(0, $result['statuscode']);
        $this->assertEquals('API URL is not configured.', $result['responsebody']);
    }

    /**
     * Test gateway send priority.
     */
    public function test_get_send_priority(): void {
        $gw = $this->createMock(gateway::class);
        $message = new \core_sms\message(
            recipientnumber: '1234567890',
            content: 'Test',
            component: 'smsgateway_customapi',
            messagetype: 'test',
            recipientuserid: null,
            issensitive: false,
            gatewayid: 1,
        );

        $this->assertEquals(100, $gw->get_send_priority($message));
    }
}
