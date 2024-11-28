<?php declare(strict_types=1);

require_once __DIR__ . "/../vendor/autoload.php";

use ILIAS\DI\Container;
use ILIAS\Plugin\cmbl\MetaBar\CustomLinksMetaBarProvider;
use ILIAS\GlobalScreen\Provider\ProviderCollection;

/**
 * Class ilCustomMetaBarLinksPlugin
 * @author  Nadimo Staszak <nadimo.staszak@uni-koeln.de>
 * @version $Id$
 */
class ilCustomMetaBarLinksPlugin extends ilUserInterfaceHookPlugin
{
    private const PLUGIN_ID = "cmbl";
    private const PLUGIN_NAME = "CustomMetaBarLinks";
    private const CTYPE = "Services";
    private const CNAME = "UIComponent";
    private const SLOT_ID = "uihk";

    private static ?self $instance = null;
    private static bool $initialized = false;

    private Container $dic;
    protected ProviderCollection $provider_collection;

    /**
     * ilCustomMetaBarLinksPlugin constructor
     */
    public function __construct(
        ilDBInterface $db,
        ilComponentRepositoryWrite $component_repository,
        string $id)
    {
        global $DIC;

        $this->dic = $DIC;
        $this->db = $db;
        $this->component_repository = $component_repository;
        $this->id = $id;

        parent::__construct($db, $component_repository, $id);
    }

    /**
     * @return void
     */
    protected function init(): void
    {
        parent::init();

        if (!self::isInitialized()) {
            self::setInitialized();

            // Add CustomLinksMetaBarProvider to provider collection
            $this->addPluginProviders();

            // Add scripts and styles to metadata
            //$this->addMetadata();
        }
    }

    /**
     * @return void
     */
    private function addPluginProviders(): void
    {
        global $DIC;

        if (!isset($DIC["global_screen"])) {
            return;
        }

        $this->provider_collection->setMetaBarProvider(new CustomLinksMetaBarProvider($DIC, $this));
    }

    /**
     * @return void
     */
    private function addMetadata(): void {
        if (!isset($this->dic["global_screen"])) {
            return;
        }

        $globalScreen = $this->dic['global_screen'];
        $directory = $this->getDirectory();

        $meta_content = $globalScreen->layout()->meta();
        $meta_content->addJs($directory . '/js/main.js', false, 1);
    }

    /**
     * Get plugin instance
     * @return self|null
     * @throws Exception
     */
    public static function getInstance(): ?self
    {
        global $DIC;

        if (self::$instance instanceof self) {
            return self::$instance;
        }

        /** @var ilComponentRepository $component_repository */
        $component_repository = $DIC['component.repository'];
        /** @var ilComponentFactory $component_factory */
        $component_factory = $DIC['component.factory'];

        if (isset($component_factory) && isset($component_repository)) {
            $plugin_info = $component_repository->getComponentByTypeAndName(
                self::CTYPE,
                self::CNAME
            )->getPluginSlotById(self::SLOT_ID)->getPluginByName(self::PLUGIN_NAME);

            self::$instance = $component_factory->getPlugin($plugin_info->getId());

            return self::$instance;
        } else {
            return null;
        }
    }

    /**
     * Define uninstall handling
     * @return bool
     */
    public function uninstall(): bool
    {
        // uninstall languages
        $this->getLanguageHandler()->uninstall();

        // deregister from component repository
        $this->component_repository->removeStateInformationOf($this->getId());

        // drop tables
        $this->db->dropTable('cmbl_links');

        return true;
    }

    /**
     * @return bool
     */
    public function isInitialized(): bool
    {
        return self::$initialized;
    }

    /**
     * @return void
     */
    public function setInitialized(): void
    {
        self::$initialized = true;
    }

    /**
     * Get plugin name
     * @return string
     */
    public function getPluginName() : string
    {
        return self::PLUGIN_NAME;
    }

    /**
     * Get plugin id
     * @return string
     */
    public static function getPluginId(): string
    {
        return self::PLUGIN_ID;
    }
}