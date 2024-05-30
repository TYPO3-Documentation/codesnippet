..  php:namespace::  TYPO3\CMS\Core\Routing

..  php:class:: PageArguments

    Contains all resolved parameters when a page is resolved from a page path segment plus all fragments.

    ..  php:method:: get(string $name)
        :returns: `string|array<string,string|array>|null`

        :param $name: the name

    ..  php:method:: getDynamicArguments()
        :returns: `array<string,string|array>`

