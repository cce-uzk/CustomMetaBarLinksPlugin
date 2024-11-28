<?php declare(strict_types=1);

require_once __DIR__ . "/../vendor/autoload.php";

use ILIAS\FileUpload\Handler\AbstractCtrlAwareIRSSUploadHandler;
use ILIAS\ResourceStorage\Stakeholder\ResourceStakeholder;

/**
 * Class ilCustomMetaBarLinksFileUploadHandlerGUI
 * @author  Nadimo Staszak <nadimo.staszak@uni-koeln.de>
 * @ilCtrl_isCalledBy ilCustomMetaBarLinksFileUploadHandlerGUI: ilCustomMetaBarLinksConfigGUI
 * @ilCtrl_IsCalledBy ilCustomMetaBarLinksFileUploadHandlerGUI: ilUIPluginRouterGUI
 */
class ilCustomMetaBarLinksFileUploadHandlerGUI extends AbstractCtrlAwareIRSSUploadHandler
{
    /**
     * @return ResourceStakeholder
     */
    protected function getStakeholder(): ResourceStakeholder
    {
        return new ilCustomLinksResourceStakeholder();
    }

    /**
     * @return array
     */
    protected function getClassPath(): array
    {
        return [ilUIPluginRouterGUI::class, self::class];
    }

    /**
     * @return bool
     */
    public function supportsChunkedUploads(): bool
    {
        return true;
    }
}
