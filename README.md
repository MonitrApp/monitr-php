Monitr.io - PHP Client Library
===


How to use
---


**Setup:**

    Monitr::getInstance()->init( <api_key>, <domain>, <registerErrorHandler = true>, <registerShutdownFunction = true> );


**Log an error:**


File & Line are automatically collected through debug_backgtrace().

    Monitr::log( <message>, <errorCode> ); // Default error-code: E_WARNING



More options:

    Monitr::logError( <message>, <errorCode>, <file>, <line> );

