<?php declare(strict_types=1);

namespace ILIAS\Plugin\cmbl\MetaBar;
require_once __DIR__ . "/../../vendor/autoload.php";

use ILIAS\GlobalScreen\Identification\IdentificationInterface;
use ILIAS\GlobalScreen\Scope\MetaBar\Provider\AbstractStaticMetaBarPluginProvider;
use ILIAS\Plugin\cmbl\Data\CustomLinksRepository;

/**
 * Class CustomLinksMetaBarProvider
 * @author  Nadimo Staszak <nadimo.staszak@uni-koeln.de>
 * @version $Id$
 */
class CustomLinksMetaBarProvider extends AbstractStaticMetaBarPluginProvider
{
    public function getMetaBarItems(): array
    {
        global $DIC;
        $dic = $DIC;
        $user = $dic->user();
        $ui = $dic->ui();
        $factory = $ui->factory();

        if (isset($DIC['global_screen'])) {
            // Instantiate repository to retrieve custom links
            $customLinksRepository = new CustomLinksRepository();

            // Retrieve all global roles assigned to the user
            $userGlobalRoles = $DIC->rbac()->review()->assignedGlobalRoles($user->getId());

            // Function to create a unique identifier for UI elements
            $identificationInterface = function ($id) : IdentificationInterface {
                return $this->if->identifier($id);
            };

            $custom_links = $customLinksRepository->getLinks();

            // Link-Selection
            $custom_links_selection = $this->meta_bar
                ->topParentItem($identificationInterface($this->getPluginID()))
                ->withSymbol($factory->symbol()->glyph()->launch())
                ->withVisibilityCallable(function () use ($custom_links) {
                    return \count($custom_links) > 1;
                })
                ->withTitle($this->plugin->txt('custom_links'));

            $visible_custom_links = 0;
            foreach ($custom_links as $custom_link) {
                if ($custom_link->isActive() && count(array_intersect($userGlobalRoles, $custom_link->getRolesIds())) > 0) {

                    $link_title = $custom_link->getTitle();
                    $link_icon_src = $custom_link->getIconSrc();
                    if (isset($link_icon_src)) {
                        $link_icon = $factory->symbol()->icon()->custom($link_icon_src, $link_title);
                    }
                    else {
                        $link_icon = $factory
                            ->symbol()
                            ->icon()
                            ->standard('none', $link_title)
                            ->withAbbreviation(substr($link_title, 0, 2));
                    }

                    $s = $this->meta_bar
                        ->linkItem($identificationInterface($this->getPluginID() . '-' . $custom_link->getId()))
                        ->withSymbol($link_icon)
                        ->withAction($custom_link->getExternalLink())
                        ->withTitle($link_title);

                    $custom_links_selection->appendChild($s);
                    $visible_custom_links++;
                }
            }

            if ($visible_custom_links > 0) {
                return [
                    $custom_links_selection,
                ];
            } else {
                return [];
            }
        } else {
            return [];
        }
    }

    /**
     * Check if current user is logged in
     * @return bool
     */
    private function isUserLoggedIn() : bool
    {
        global $DIC;
        $user = $DIC->user();
        return (!$user->isAnonymous() && $user->getId() != 0);
    }
}