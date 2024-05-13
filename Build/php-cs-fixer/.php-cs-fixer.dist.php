<?php

$config = \TYPO3\CodingStandards\CsFixerConfig::create();
$rules = $config->getRules();
unset(
    $rules['@PER'],
    $rules['function_typehint_space'],
    $rules['curly_braces_position'],
);
$config->setRules($rules);
$config->addRules([
  '@PER-CS' => true,
   'type_declaration_spaces' => [
        'elements' => [
            'function',
            'property',
        ],
   ],
]);
$config
    ->getFinder()
    ->in(__DIR__)
    ->exclude([
        __DIR__ . '/.Build',
        __DIR__ . '/.ddev'
    ])
;

$header = <<<EOF
This file is part of the TYPO3 CMS project.

It is free software; you can redistribute it and/or modify it under
the terms of the GNU General Public License, either version 2
of the License, or any later version.

For the full copyright and license information, please read the
LICENSE.txt file that was distributed with this source code.

The TYPO3 project - inspiring people to share!
EOF;

$config->addRules([
    'header_comment' => ['header' => $header, 'separate' => 'both'],
]);

return $config;
