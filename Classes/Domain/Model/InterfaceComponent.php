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

namespace T3docs\Codesnippet\Domain\Model;

class InterfaceComponent extends Component
{
    /**
     * @param string[] $modifiers
     */
    public function __construct(
        string $namespace,
        string $shortname,
        array $modifiers,
        string $description,
    ) {
        parent::__construct('interface', $namespace, $shortname, $modifiers, $description);
    }
}
