<?php

/**
 * @deprecated  This script exists for legacy purposes only and will be removed in a future release.
 */

declare(strict_types=1);

$request = \Symfony\Component\HttpFoundation\Request::createFromGlobals();
$config = \SimpleSAML\Configuration::getInstance();
$controller = new \SimpleSAML\Module\authoauth2\Controller\BUTSingleLogout($config);

$headers = $config->getOptionalArray('headers.security', \SimpleSAML\Configuration::DEFAULT_SECURITY_HEADERS);

$response = $controller->singleLogout($request);
foreach ($headers as $header => $value) {
    // Some pages may have specific requirements that we must follow. Don't touch them.
    if (!$response->headers->has($header)) {
        $response->headers->set($header, $value);
    }
}
$response->send();
