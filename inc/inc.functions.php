<?php 

// Funktion zur Alt-Text-Generierung
$generateAltText = function (Kirby\Cms\File $file) {
    // Nur fortfahren, wenn in Konfiguration 'enabled' nicht "false" gesetzt ist
    $isEnabled = option('mountbatt/alt.enabled');
    if ($isEnabled == FALSE) {
        return;
    }
    
    // Nur fortfahren, wenn es sich um ein Bild handelt
    if ($file->type() !== 'image') {
        return;
    }
    
    // Nur fortfahren, wenn es ein aiModel gibt
    $aiModel = option('mountbatt/alt.aiModel');
    if (empty($aiModel)) {
        throw new Exception('No AI-Model like »gpt-4o« is configured in config.php.');
        return;
    }
    
    // OpenAI API-Key aus der Konfiguration holen
    $apiKey = option('mountbatt/alt.openai.apiKey');
    if (empty($apiKey)) {
        throw new Exception('OpenAI API key is not configured in config.php.');
        return;
    }
    
    // Das Zielfeld im File Blueprint aus der Konfiguration holen
    $altTargetField = option('mountbatt/alt.targetFieldName');
    if (empty($altTargetField)) {
        throw new Exception('No Target Field Name configured in config.php.');
        return;
    }
    
    // Öffentliche URL des Bildes
    $imageUrl = $file->url();
    if (empty($imageUrl)) {
        throw new Exception('No valid image URL found.');
        return;
    }
    
    // Debug Mode on
    if(option('mountbatt/alt.debug') == TRUE) {
      $imageUrl = "https://upload.wikimedia.org/wikipedia/commons/thumb/d/d7/Pica_Pau.jpg/800px-Pica_Pau.jpg";
    }
    
    // Alle installierten Sprachen abrufen
    $languages = kirby()->languages();
    $languageCodes = [];
    foreach ($languages as $language) {
        $languageCodes[] = $language->code();
    }
    $languageCodesString = implode(', ', $languageCodes);
    
    // Formuliere den Prompt so, dass das Modell in allen gewünschten Sprachen antwortet
    $prompt = "Please analyse the following image and generate a descriptive alt text for each of the following languages that is a maximum of approx. 125 characters long: {$languageCodesString}. ";
    $prompt .= "Please return a valid JSON object in which each key is a language code and the respective value is the alt text in this language. No additional text or comments may be included.";
    
    // Payload für den API-Call
    $payload = [
        'model'      => $aiModel,
        'messages'   => [
            [
                'role'    => 'user',
                'content' => [
                    [
                        'type' => 'text',
                        'text' => $prompt
                    ],
                    [
                        'type'      => 'image_url',
                        'image_url' => [
                            'url' => $imageUrl
                        ]
                    ]
                ]
            ]
        ],
        'max_tokens' => 300
    ];
    
    $jsonPayload = json_encode($payload);
    
    // API-Call mit cURL
    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey,
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);
    
    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        throw new Exception('Curl Error: ' . curl_error($ch));
        curl_close($ch);
        return;
    }
    curl_close($ch);
    
    $result = json_decode($response, true);
    if (
        !$result ||
        !isset($result['choices'][0]['message']['content']) ||
        empty($result['choices'][0]['message']['content'])
    ) {
        throw new Exception('OpenAI API did not return any alt texts.');
        return;
    }
    
    $content = $result['choices'][0]['message']['content'];
    
    // Prüfen, ob die Antwort in einem Markdown-Codeblock eingebettet ist
    // Wir suchen nach einem Muster wie: ```json ... ```
    if (preg_match('/```json\s*(\{.*\})\s*```/s', $content, $matches)) {
        $jsonString = $matches[1];
    } else {
        // Falls nicht, versuchen wir den gesamten Inhalt als JSON zu interpretieren
        $jsonString = $content;
    }
    
    $altTexts = json_decode($jsonString, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Error parsing the JSON response: ' . json_last_error_msg());
        return;
    }
    
    // Für jede Sprache den Alt-Text in dem Ziel-Feld aktualisieren
    foreach ($altTexts as $langCode => $altText) {
        try {
            $file->update([$altTargetField => $altText], $langCode);
        } catch (Exception $e) {
            throw new Exception("Error updating ALT-Text for language {$langCode}: " . $e->getMessage());
        }
    }
};



?>