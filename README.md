# Moodle Plugin: Custom API SMS Gateway

**Author:** Kewayne Davidson  
**License:** GNU GPL v3 or later  
**Requires:** Moodle 4.1+ (SMS Gateway Subsystem)  
**Version:** 1.1.0  

---

## 1. Overview

The **Custom API SMS Gateway** (`smsgateway_customapi`) is a powerful, versatile Moodle plugin designed to connect Moodle's native SMS messaging framework to any third-party SMS or HTTP API provider.

Featuring a Postman-like configuration interface, the plugin gives site administrators full control to build, customize, and test outbound API requests with custom HTTP methods (GET, POST, PUT, PATCH, DELETE), HTTP headers, query parameters, form data, or raw JSON payloads.

---

## 2. Key Features

* **Postman-Style Interface:** Clear, organized layout for endpoints, headers, query parameters, body payloads, and response conditions.
* **Multiple HTTP Methods & Formats:**
  * Support for `GET`, `POST`, `PUT`, `PATCH`, and `DELETE` requests.
  * Choice of request body payload format:
    * **Form Data:** `application/x-www-form-urlencoded` key-value pairs.
    * **JSON Payload:** `application/json` raw JSON payload.
    * **Raw Text:** Custom text body.
* **Dynamic Placeholders:** Easily inject recipient numbers (`{{recipient}}`) and message text (`{{message}}`) into headers, query parameters, or JSON payloads.
* **Live JSON Auto-Beautifier (New in v1.1.0):** 1-Click **Format / Beautify JSON** button automatically formats raw or messy JSON payloads into clean, 2-space indented JSON structures while preserving placeholders.
* **Real-Time JSON Syntax Validator (New in v1.1.0):** Live status badge alerts administrators to JSON syntax errors (missing quotes, unclosed braces, trailing commas) in real time as they type.
* **Quick Placeholder Chips (New in v1.1.0):** Instant insertion buttons (`+ {{recipient}}` and `+ {{message}}`) insert variables directly at the text cursor position.
* **Automatic JSON String Escaping (New in v1.1.0):** Automatically JSON-encodes message placeholders when formatting JSON payloads to prevent API parse errors on Multi-Factor Authentication (MFA) codes, double quotes, and multi-line SMS messages.
* **Embedded Live Connection Tester:** Test your API connection in real time directly from the settings page before saving. Features a Postman-style response card with HTTP status badges, latency meter, response body viewer (with JSON auto-formatting), request details, and response headers.
* **Custom Success Conditions:** Verify messages using HTTP status codes (2xx) or specific response body substrings (e.g. `"status":"success"`).
* **Moodle Standard Compliance:** Fully compliant with Moodle core SMS gateway specifications, AJAX Web Services, AMD JavaScript standards, and Privacy API requirements for official directory distribution.

---

## 3. Installation

1. **Upload / Extract:** Ensure the plugin files are placed in the directory:  
   `your_moodle_site/sms/gateway/customapi/`
2. **Database Upgrade:** Log in to Moodle as an administrator and go to **Site administration** > **Notifications**. Follow the prompts to upgrade the Moodle database.

---

## 4. Configuration Guide

Navigate to **Site administration** > **Plugins** > **SMS** > **SMS Gateways**, select **Custom API Gateway** from the gateway dropdown, and click **Add**.

### Endpoint Settings
* **API URL:** The full destination URL (e.g., `https://api.provider.com/v1/send`).
* **HTTP Method:** Select `GET`, `POST`, `PUT`, `PATCH`, or `DELETE`.

### Request Setup (Postman Style)
* **HTTP Headers:** One per line in `Key: Value` format (e.g. `Authorization: Bearer my_api_key`).
* **Query Parameters:** Key=value pairs appended to the URL after `?`.
* **Request Body Format:**
  * Select **JSON Payload** to send raw JSON like:
    ```json
    {
      "to": "{{recipient}}",
      "text": "{{message}}"
    }
    ```
  * Select **Form Data** for standard key=value lines:
    ```
    recipient={{recipient}}
    message={{message}}
    ```

### Response Verification
* **Success Condition Substring:** Text string required in the response body (e.g., `"status":"ok"`). Leave empty to check for HTTP 2xx status codes.

### Embedded Connection Tester
Enter a test phone number and sample message text, then click **Send Test Request**. The embedded tester executes the request via Moodle AJAX web services and displays:
* Response HTTP status badge & roundtrip latency (ms).
* Pretty-formatted response body.
* Sent request summary (URL, method, headers, payload sent).
* Response headers.

---

## 5. Support & Contributions

Developed by **Kewayne Davidson**. Distributed under GNU General Public License v3.
