
# Kirby CMS Auto ALT Text

Kirby plugin that retrieves the descriptive ALT text of an image during upload to the CMS via OpenAI and updates it in all installed languages.

## Installation

- Copy the plugin to `/plugins/kirby-autoalt`
- Get an OpenAI API-Key [here](https://platform.openai.com/api-keys)
- Copy and edit the options into your config.php (see below)
- Create a file blueprint with a 'alt' textfield
- Upload an image and check the retrieved ALT text in the panel-view of your new image

#### File Blueprint example
You need to create a file blueprint to add an `alt` textfield. Choose a fieldname you want. Check the example below:

`/blueprints/files/default.yml`

`````
title: Image

fields:
  alt:
    label: ALT Text
    type: text
    help: "Enter an image description. Avoid more than 125 characters."
`````

### Setup your `config.php`
````
<?php
    return [
        // Set to false to disable the plugin
        'mountbatt/alt.enabled' => true, 
        'mountbatt/alt.openai.apiKey' => 'YOUR_API_KEY',
        'mountbatt/alt.aiModel' => 'gpt-4o',
        'mountbatt/alt.targetFieldName' => 'alt' 
    ]
````

### Note for development environments
Due to the fact that the URL of the uploaded image is sent to OpenAI, your Kirby installation must be accessible from the Internet. It will therefore not work to retrieve the ALT Text on/from localhost. 
