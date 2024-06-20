<?php

namespace SimpleSAML\Module\authoauth2\Controller;

use SimpleSAML\HTTP\RunnableResponse;
use SimpleSAML\Module\authoauth2\Auth\Source\OpenIDConnect;
use SimpleSAML\Module\saml\Controller\SingleLogout;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller class for the BUT Single Logout.
 *
 *  We initiate SAML single logout from OP (OpenID Provider) during logout
 *  from multiple SP.
 */
class BUTSingleLogout extends SingleLogout
{
    /**
     * @inheritDoc
     */
    public function singleLogout(Request $request): RunnableResponse
    {
        /**
         * Not the best option, but we didn't want to modify the simplesamlphp core module.
         *
         * The logout() method in the Auth Source (in our case OpenIDConnect) class takes a $state
         * parameter where it is possible to set oidc:localLogout = true, then it does not redirect
         * to the OP. But for now there is no way how to do it.
         */
        if ($request->query->has('OpInitiatedLogout')) {
            OpenIDConnect::$redirectToOp = false;
        }

        return parent::singleLogout($request);
    }

}