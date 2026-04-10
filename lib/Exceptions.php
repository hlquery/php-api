<?php

namespace Hlquery;

class HlqueryException extends \Exception {}
class AuthenticationException extends HlqueryException {}
class RequestException extends HlqueryException {}
class ValidationException extends HlqueryException {}
class CollectionException extends HlqueryException {}
class DocumentException extends HlqueryException {}
class SearchException extends HlqueryException {}
