<?php
/*
|--------------------------------------------------------------------------
| Private AI Service Configuration
|--------------------------------------------------------------------------
| These settings are used by PHP only.
| Never send the API key to browser JavaScript.
*/

$AI_API_URL = 'http://127.0.0.1:11434/v1/chat/completions';

$AI_MODEL = 'qwen2.5:7b';

/*
 * For a local Ollama server, this can be empty.
 * For an authenticated AI gateway, configure the secret securely.
 */
$AI_API_KEY = '';

$AI_CONNECT_TIMEOUT = 5;
$AI_TIMEOUT = 90;

?>