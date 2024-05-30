..  php:namespace::  TYPO3\CMS\Core\Authentication

..  php:interface:: MimicServiceInterface

    ..  php:method:: mimicAuthUser()
        :returns: `bool`

        Mimics user authentication for known invalid authentication requests. This method can be used
        to mitigate timing discrepancies for invalid authentication attempts, which can be used for
        user enumeration.

        Authentication services can implement this method to simulate(!) corresponding processes that
        would be processed during valid requests - e.g. perform password hashing (timing) or call
        remote services (network latency).

        :Return description: bool whether other services shall continue

