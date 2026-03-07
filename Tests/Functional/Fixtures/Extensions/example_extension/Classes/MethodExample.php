<?php

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace TYPO3Tests\ExampleExtension;

/**
 * Contains all resolved parameters when a page is resolved from a page path segment plus all fragments.
 */
class MethodExample
{
    /** @var array<string, mixed> */
    private array $arguments = [];

    /**
     * @return string|array<string,string|array>|null
     */
    public function get(string $name): mixed
    {
        return $this->arguments[$name] ?? null;
    }

    /**
     * @return array<string,string|array>
     */
    public function getDynamicArguments(): array
    {
        return $this->arguments;
    }
}
