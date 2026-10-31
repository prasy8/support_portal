<?php
function generateOllamaAIResponse($systemPrompt,$userPrompt)
{
    // Load the private configuration file.
	$configPath = '../config/ai-config.php';

    if (!file_exists($configPath)) {
        return [
            'success' => false,
            'message' => 'Ollama AI configuration file not found.'
        ];
    }

    $config = include($configPath);

    $apiKey = isset($config['api_key']) ? $config['api_key']:'';
    $model  = isset($config['model']) ? $config['model'] : '';
	$apiUrl = isset($config['api_url']) ? $config['api_url'] : '';
	$connect_timeout = isset($config['connect_timeout']) ? $config['connect_timeout'] : '';
	$timeout = isset($config['timeout']) ? $config['timeout'] : '';
	
	if ($model === '') {
        return [
            'success' => false,
            'message' => 'Ollama API configuration is incomplete.'
        ];
    }
    if (!function_exists('curl_init')) {
        return [
            'success' => false,
            'message' => 'PHP cURL extension is not enabled.'
        ];
    }

 	/*
	 * Call the private OpenAI-compatible endpoint.
	 */
	
		$requestBody = [
			'model' => $model,
			'messages' => [
				[
					'role' => 'system',
					'content' => $systemPrompt
				],
				[
					'role' => 'user',
					'content' => $userPrompt
				]
			],
			'temperature' => 0.2,
			'stream' => false
		];

		$headers = ['Content-Type: application/json','Accept: application/json'];

		if (!empty($apiKey)) {
			$headers[] = 'Authorization: Bearer ' . $apiKey;
		}

    	$curl = curl_init($apiUrl);

		curl_setopt_array($curl, [
			CURLOPT_POST => true,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_HTTPHEADER => $headers,
			CURLOPT_POSTFIELDS => json_encode($requestBody),
			CURLOPT_CONNECTTIMEOUT => (int)$connect_timeout,
			CURLOPT_TIMEOUT => (int)$timeout
		]);

		$response = curl_exec($curl);
		$curlError = curl_error($curl);
		$httpCode = (int)curl_getinfo($curl,CURLINFO_HTTP_CODE);
		curl_close($curl);

		if ($response === false || $curlError !== '') {
			throw new RuntimeException('AI service connection failed: ' . $curlError);
		}

		if ($httpCode < 200 || $httpCode >= 300) {
			throw new RuntimeException('AI service returned HTTP ' . $httpCode);
		}

    	$responseData = json_decode($response,true);
		// Extract generated text from the Responses API output.
    	$generatedText = isset($responseData['choices'][0]['message']['content']) ? trim((string)$responseData['choices'][0]['message']['content']) : '';
		$generatedText = trim($generatedText);

		if ($generatedText === '') {
			return [
				'success' => false,
				'message' => 'Ollama AI returned an empty response.'
			];
		}

		return [
			'success' => true,
			'text' => $generatedText,
			'model' => isset($responseData['model']) ? trim((string)$responseData['model']) : $model
		];
		
}
/**
 * OpenAI API service
 * File: includes/config/openai-service.php
 */

function generateOpenAIResponse($systemPrompt,$userPrompt)
{
    // Load the private configuration file.
    //$configPath = dirname(__DIR__, 4) . '../private/openai-config.php';
    $configPath = '../config/openai-config.php';

    if (!file_exists($configPath)) {
        return [
            'success' => false,
            'message' => 'OpenAI configuration file not found.'
        ];
    }

    $config = require $configPath;

    $apiKey = isset($config['api_key']) ? $config['api_key']:'';
    $model  = isset($config['model']) ? $config['model'] : '';

    if ($apiKey === '' || $model === '') {
        return [
            'success' => false,
            'message' => 'OpenAI API configuration is incomplete.'
        ];
    }

    if (!function_exists('curl_init')) {
        return [
            'success' => false,
            'message' => 'PHP cURL extension is not enabled.'
        ];
    }

    // OpenAI Responses API endpoint.
    $apiUrl = 'https://api.openai.com/v1/responses';

    $requestData = [
        'model' => $model,
        'instructions' => $systemPrompt,
        'input' => $userPrompt,
        'max_output_tokens' => 800
    ];

    $curl = curl_init($apiUrl);

    curl_setopt_array($curl, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 15,
        CURLOPT_TIMEOUT => 90,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ],
        CURLOPT_POSTFIELDS => json_encode($requestData)
    ]);

    $response = curl_exec($curl);
    $curlError = curl_error($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
	
    curl_close($curl);

    // Handle connection errors.
 /*   if ($response === false) {
        error_log('OpenAI connection error: ' . $curlError);

        return [
            'success' => false,
            'message' => 'Could not connect to the AI service.'
        ];
    }*/
	if ($response === false) {
		$curlErrorNumber = curl_errno($curl);
		error_log('OpenAI connection error. '. 'cURL error number: ' . $curlErrorNumber. '. Error message: ' . $curlError);
		return [
			'success' => false,
			'message' => 'Could not connect to the AI service. Check the PHP error log.'
		];
	}

    $responseData = json_decode($response, true);

    // Handle API errors.
    if ($httpCode < 200 || $httpCode >= 300) {
        $errorMessage  = isset($responseData['error']['message']) ? $responseData['error']['message'] : 'Unknown OpenAI API error.';
        // Store details in the PHP error log, not in the browser.
        error_log('OpenAI API error. HTTP status: '. $httpCode. '. Message: '. $errorMessage );
        return [
            'success' => false,
            'message' => 'AI response generation failed.'
        ];
    }
	if (!is_array($responseData)) {
        throw new RuntimeException('OpenAI returned an invalid JSON response.');
    }
    // Extract generated text from the Responses API output.
    $generatedText = '';

    foreach ((isset($responseData['output']) ? $responseData['output'] : []) as $outputItem) {
        if ((isset($outputItem['type']) ? $outputItem['type'] : '') !== 'message') {
            continue;
        }

        foreach ((isset($outputItem['content']) ? $outputItem['content'] : []) as $contentItem) {
            if ((isset($contentItem['type']) ? $contentItem['type'] : '') === 'output_text') {
                $generatedText .= isset($contentItem['text']) ? $contentItem['text'] : '';
            }
        }
    }

    $generatedText = trim($generatedText);

    if ($generatedText === '') {
        return [
            'success' => false,
            'message' => 'OpenAI returned an empty response.'
        ];
    }

    return [
        'success' => true,
        'text' => $generatedText,
		'model' => isset($responseData['model']) ? $responseData['model']:$model
    ];
}