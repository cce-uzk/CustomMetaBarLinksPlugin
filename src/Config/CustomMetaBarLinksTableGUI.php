<?php declare(strict_types=1);

namespace ILIAS\Plugin\cmbl\Config;
require_once __DIR__ . "/../../vendor/autoload.php";

use ilAccessHandler;
use ilCtrl;
use ilCustomMetaBarLinksConfigGUI;
use ilCustomMetaBarLinksPlugin;
use ILIAS\Data\URI;
use ILIAS\UI\Factory as UIFactory;
use ILIAS\UI\Component\Table\Data;
use ILIAS\UI\Renderer as UIRenderer;
use ILIAS\DATA\Factory as DataFactory;
use ILIAS\UI\URLBuilder;
use ILIAS\HTTP\Services as HTTPServices;
use ILIAS\Refinery\Factory as RefineryFactory;
use ILIAS\UI\URLBuilderToken;
use ilLanguage;

/**
 * Class CustomMetaBarLinksTableGUI
 * @author  Nadimo Staszak <nadimo.staszak@uni-koeln.de>
 */
class CustomMetaBarLinksTableGUI
{
    protected ilAccessHandler $access;
    protected UIRenderer $renderer;
    protected UIFactory $ui_factory;
    protected DataFactory $data_factory;
    protected ilLanguage $lng;
    protected ilCtrl $ctrl;
    protected HTTPServices $http;
    protected RefineryFactory $refinery;
    protected int $ref_id;
    protected ?object $parent_obj = null;

    protected ilCustomMetaBarLinksPlugin $plugin;

    /**
     * @param object|null $a_parent_obj upper GUI class, which calls TableGUI
     */
    public function __construct(
        ?object $a_parent_obj,
        int $ref_id
    ) {
        global $DIC;
        $this->ref_id = $ref_id;
        $this->renderer = $DIC->ui()->renderer();
        $this->ui_factory = $DIC->ui()->factory();
        $this->lng = $DIC->language();
        $this->ctrl = $DIC->ctrl();
        $this->http = $DIC->http();
        $this->access = $DIC->access();
        $this->refinery = $DIC->refinery();
        $this->data_factory = new DataFactory();

        $this->parent_obj = $a_parent_obj;
        $this->plugin = $a_parent_obj->getPluginObject();
    }

    /**
     * @param CustomMetaBarLinksTableDataRetrieval $data_retrieval
     * @return Data
     * @throws \ilCtrlException
     */
    protected function createTable(
        CustomMetaBarLinksTableDataRetrieval $data_retrieval
    ): Data {
        $this->lng->loadLanguageModule('search');
        $this->lng->loadLanguageModule('meta');

        $columns = [
            'title' => $this->ui_factory->table()->column()->text($this->plugin->txt('link_title')),
            'external_link' => $this->ui_factory->table()->column()->text($this->plugin->txt('link_external_link')),
            'active' => $this->ui_factory->table()->column()->statusIcon($this->plugin->txt('link_active')),
            'icon' => $this->ui_factory->table()->column()->statusIcon($this->plugin->txt('link_icon'))
                ->withIsSortable(false)
                ->withIsOptional(true),
            'roles' => $this->ui_factory->table()->column()->text($this->plugin->txt('link_roles'))
                ->withIsSortable(false)
                ->withIsOptional(true)
        ];

        /**
         * @var URLBuilder $url_builder
         * @var URLBuilderToken $action_parameter_token
         * @var URLBuilderToken $row_id_token
         */
        $query_params_namespace = ['custom_link'];
        $url_builder = new URLBuilder(
            new URI(
                ILIAS_HTTP_PATH . '/' . $this->ctrl->getLinkTargetByClass(
                    ilCustomMetaBarLinksConfigGUI::class,
                    'handleTableActions'
                )
            )
        );
        list($url_builder, $action_parameter_token, $row_id_token) = $url_builder->acquireParameters(
            $query_params_namespace,
            'table_action',
            'ids'
        );

        $actions = [];
        //if ($this->access->checkAccess('write', '', $this->ref_id)) {
            $actions = [
                'editLink' => $this->ui_factory->table()->action()->single(
                    $this->lng->txt('edit'),
                    $url_builder->withParameter($action_parameter_token, 'editLink'),
                    $row_id_token
                ),
                'deleteLinks' => $this->ui_factory->table()->action()->single(
                    $this->lng->txt('delete'),
                    $url_builder->withParameter($action_parameter_token, 'confirmDeleteLinks'),
                    $row_id_token
                ),
                'activateLinks' => $this->ui_factory->table()->action()->multi(
                    $this->lng->txt('activate'),
                    $url_builder->withParameter($action_parameter_token, 'activateLinks'),
                    $row_id_token
                ),
                'deactivateLinks' => $this->ui_factory->table()->action()->multi(
                    $this->lng->txt('deactivate'),
                    $url_builder->withParameter($action_parameter_token, 'deactivateLinks'),
                    $row_id_token
                )
            ];
        //}

        return $this->ui_factory->table()->data(
            $this->plugin->txt('custom_links'),
            $columns,
            $data_retrieval
        )
            ->withActions($actions)
            ->withRequest($this->http->request());
    }

    /**
     * @param CustomMetaBarLinksTableDataRetrieval $data_retrieval
     * @return string
     * @throws \ilCtrlException
     */
    public function getHTML(
        CustomMetaBarLinksTableDataRetrieval $data_retrieval
    ): string {
        return $this->renderer->render(
            $this->createTable($data_retrieval)
        );
    }
}