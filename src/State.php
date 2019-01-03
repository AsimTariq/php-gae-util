<?php
/**
 * Created by PhpStorm.
 * User: michael
 * Date: 20/11/2017
 * Time: 23:25
 */

namespace GaeUtil;

use Fig\Link\GenericLinkProvider;
use Fig\Link\Link;
use GaeUtil\Model\AppStatusDto;
use google\appengine\api\app_identity\AppIdentityService;
use google\appengine\api\users\UserService;

class State {

    /**
     * @param array $links
     * @return AppStatusDto
     * @throws \Noodlehaus\Exception\EmptyDirectoryException
     * @throws \google\appengine\api\users\UsersException
     */
    static function status($links = []) {

        $status = new AppStatusDto();

        $status->applicationId = Util::getApplicationId();

        $status->service = Util::getModuleId();
        $status->isDevServer = Util::isDevServer();
        $status->defaultHostname = AppIdentityService::getDefaultVersionHostname();
        $status->isAdmin = UserService::isCurrentUserAdmin();

        $linkProvider = new GenericLinkProvider();

        $user = UserService::getCurrentUser();

        if ($user) {
            $status->user = $user;
            $linkProvider = $linkProvider->withLink(new Link("logout", Auth::createLogoutURL()));
        } else {
            $linkProvider = $linkProvider->withLink(new Link("login", Auth::createLoginURL()));
        }

        foreach ($links as $link) {
            $linkProvider = $linkProvider->withLink(new Link("menu", $link));
        };
        foreach ($linkProvider->getLinks() as $link) {
            $status->links[] = [
                "href" => $link->getHref(),
                "rels" => $link->getRels(),
                "attributes" => $link->getAttributes(),
            ];
        }

        if ($status->isAdmin) {
            $status->errors = [];
            if (JWT::internalSecretIsConfigured()) {
                $status->internalToken = "Bearer " . JWT::getInternalToken();
            } else {
                $status->errors[] = [
                    "message" => "Internal secret is not configured. Add jwt_internal_secret to a configuration file."
                ];
            }

            $status->externalToken = "Bearer " . JWT::getExternalToken(Auth::getCurrentUserEmail(), Moment::ONEDAY);
            $status->composer = ProjectUtils::getComposerData();
        }

        return $status;
    }
}