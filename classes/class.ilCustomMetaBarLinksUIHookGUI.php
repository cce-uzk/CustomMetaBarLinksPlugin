<?php declare(strict_types=1);

require_once __DIR__ . "/../vendor/autoload.php";

use ILIAS\Plugin\cmbl\Data\CustomLinksRepository;
use ILIAS\DI\RBACServices;

/**
 * Class ilCustomMetaBarLinksUIHookGUI
 * CustomMetaBar Userinterface-Hook class
 * @author  Nadimo Staszak <nadimo.staszak@uni-koeln.de>
 * @version $Id$
 *
 * @ilCtrl_isCalledBy ilCustomMetaBarLinksUIHookGUI: ilUIPluginRouterGUI
 */
class ilCustomMetaBarLinksUIHookGUI extends ilUIHookPluginGUI
{
    protected ilCtrl $ctrl;
    protected ilObjUser $user;
    protected RBACServices $rbac;
    protected CustomLinksRepository $customLinksRepository;

    /**
     * ilCustomMetaBarLinksUIHookGUI constructor
     * @throws Exception
     */
    public function __construct()
    {
        $this->setPluginObject(ilCustomMetaBarLinksPlugin::getInstance());
    }
}