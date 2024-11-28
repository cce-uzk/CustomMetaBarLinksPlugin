<?php declare(strict_types=1);

use ILIAS\ResourceStorage\Stakeholder\AbstractResourceStakeholder;

/**
 * Class ilCustomLinksResourceStakeholder
 * Required Class for Integrated-Ressource-Storage-Service (IRSS) usage
 * @author  Nadimo Staszak <nadimo.staszak@uni-koeln.de>
 * @version $Id$
 */
class ilCustomLinksResourceStakeholder extends AbstractResourceStakeholder
{
    public function __construct()
    {
        global $DIC;
    }

    /**
     * Get IRSS-ProviderId (in this case: PluginId)
     * @return string
     */
    public function getId() : string
    {
        return ilCustomMetabarLinksPlugin::getPluginId();
    }

    /**
     * Get RessourceOwnerId (in this case: System-User-Id)
     * @return int
     */
    public function getOwnerOfNewResources() : int
    {
        return 6;
    }
}