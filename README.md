# Moodle Plugin: Custom API SMS Gateway

**Author:** Kewayne Davidson  
**License:** GNU GPL v3 or later  
**Requires:** Moodle 4.4+ (SMS Gateway Subsystem)  
**Version:** 1.1.0 (`2026081300`)  

---

## 1. Overview

The **Custom API SMS Gateway** (`smsgateway_customapi`) is a powerful, highly customizable Moodle plugin designed to connect Moodle's native SMS messaging subsystem (`core_sms`) and Multi-Factor Authentication (MFA) to any third-party SMS, WhatsApp, or HTTP API provider.

Featuring a Postman-like configuration interface, the plugin gives site administrators full flexibility to configure HTTP endpoints, request methods (`GET`, `POST`, `PUT`, `PATCH`, `DELETE`), custom headers, query parameters, form parameters, or raw JSON payloads.

---

## 2. Key Features in v1.1.0

* **Postman-Style Configuration Interface:**
  * Clean, tabbed layout for endpoints, headers, query parameters, body payloads, and response verification.
  * Support for `GET`, `POST`, `PUT`, `PATCH`, and `DELETE` HTTP methods.
  * Choice of payload format: **JSON Payload** (`application/json`), **Form Data** (`application/x-www-form-urlencoded`), or **Raw Text**.
* **Live JSON Auto-Beautifier:**
  * 1-Click **Format / Beautify JSON** button automatically formats raw or messy JSON payloads into clean, 2-space indented JSON structures while preserving placeholders.
* **Real-Time JSON Syntax Validator:**
  * Live status indicator badge alerts administrators to JSON syntax errors (missing quotes, unclosed braces, trailing commas) in real time as they type.
* **Quick Placeholder Chips:**
  * Instant insertion buttons (`+ {{recipient}}` and `+ {{message}}`) insert variables directly at the text cursor position.
* **Automatic JSON String Escaping:**
  * Automatically JSON-encodes message placeholders when formatting JSON payloads to prevent API parse errors on Multi-Factor Authentication (MFA) codes, double quotes, and multi-line SMS messages.
* **Embedded Live Connection Tester:**
  * Test your API connection directly from the settings page before saving. Features a Postman-style response card with HTTP status badges, latency meter, pretty-printed response body, sent request summary, and response headers.
* **Custom Success Conditions:**
  * Verify message delivery using HTTP 2xx status codes or response body substrings (e.g. `"status":"success"`).
* **Moodle Core Standards Compliant:**
  * Registered Web Services in `db/services.php`, 100% localized language strings in `lang/en/smsgateway_customapi.php`, AMD JavaScript modules, and Privacy API compliance.

---

## 3. Installation

1. **Upload / Extract:** Ensure the plugin files are placed in:  
   `your_moodle_site/sms/gateway/customapi/`
2. **Database Upgrade:** Log in as an administrator and go to **Site administration** > **Notifications** to complete the installation.

---

## 4. Configuration Guide

Navigate to **Site administration** > **Plugins** > **SMS** > **SMS Gateways**, select **Custom API Gateway** from the dropdown, and click **Add**.

### Endpoint Settings
* **API URL:** The destination URL (e.g., `https://api.smsprovider.com/v1/send`).
* **HTTP Method:** Select `GET`, `POST`, `PUT`, `PATCH`, or `DELETE`.

### Request Setup (Postman Style)
* **HTTP Headers:** One per line in `Key: Value` format (e.g. `Authorization: Bearer your_token`).
* **Query Parameters:** Key=value pairs appended after `?`.
* **JSON Payload:**
  ```json
  {
    "to": "{{recipient}}",
    "text": "{{message}}"
  }
  ```

---

## 5. Support & Contributions

Developed by **Kewayne Davidson**. Distributed under GNU General Public License v3.
