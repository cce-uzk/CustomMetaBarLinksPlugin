<?php declare(strict_types=1);

require_once __DIR__ . "/../vendor/autoload.php";

use ILIAS\DI\Container;
use ILIAS\DI\UIServices;
use ILIAS\HTTP\Services;
use ILIAS\UI\Factory as UIFactory;
use ILIAS\UI\Renderer as UIRenderer;
use ILIAS\Refinery\Factory;
use ILIAS\Plugin\cmbl\Config\CustomMetaBarLinksTableDataRetrieval;
use ILIAS\Plugin\cmbl\Config\CustomMetaBarLinksTableGUI;
use ILIAS\Plugin\cmbl\Data\CustomLinksRepository;
use ILIAS\Plugin\cmbl\Model\CustomLink;

/**
 * Class ilCustomMetaBarLinksConfigGUI
 *
 * Plug-In Configuration interface class
 * @author  Nadimo Staszak <nadimo.staszak@uni-koeln.de>
 * @version $Id$
 *
 * @ilCtrl_IsCalledBy ilCustomMetaBarLinksConfigGUI: ilObjComponentSettingsGUI
 * @ilCtrl_isCalledBy ilCustomMetaBarLinksConfigGUI: ilAdministrationGUI
 *
 * @ilCtrl_Calls ilCustomMetaBarLinksConfigGUI: ilFormPropertyDispatchGUI
 */
class ilCustomMetaBarLinksConfigGUI extends ilPluginConfigGUI
{
    private const CMD_CONFIGURE = 'configure';
    private const CMD_HANDLETABLEACTIONS = 'handleTableActions';
    private const CMD_SHOWLINKLIST = 'showLinkList';
    private const CMD_ACTIVATELINKS = 'activateLinks';
    private const CMD_DEACTIVATELINKS = 'deactivateLinks';
    private const CMD_CONFIRMDELETELINKS = 'confirmDeleteLinks';
    private const CMD_ADDLINK = 'addLink';
    private const CMD_EDITLINK = 'editLink';
    private const CMD_SAVELINK = 'saveLink';

    protected ilCtrl $ctrl;
    protected Services $http;
    private Factory $refinery;
    protected ilLanguage $lng;
    protected UIServices $ui;
    protected UIFactory $ui_factory;
    protected ilTabsGUI $tabs;
    private ilToolbarGUI $toolbar;
    protected UIRenderer $renderer;
    protected CustomLinksRepository $customLinksRepository;
    protected CustomLink $link;

    /**
     * ilCustomMetaBarConfigGUI constructor
     * @throws Exception
     */
    function __construct()
    {
        /** @var Container $DIC */
        global $DIC;

        // General Dependencies
        $this->ctrl = $DIC->ctrl();
        $this->http = $DIC->http();
        $this->refinery = $DIC->refinery();
        $this->lng = $DIC->language();
        $this->ui = $DIC->ui();
        $this->ui_factory = $this->ui->factory();
        $this->tabs = $DIC->tabs();
        $this->toolbar = $DIC->toolbar();
        $this->renderer = $this->ui->renderer();

        // Repositories
        $this->customLinksRepository = new CustomLinksRepository();

        $this->setPluginObject(ilCustomMetaBarLinksPlugin::getInstance());
    }

    /*public function executeCommand(): void
    {
        global $DIC;
        try {
            $next_class = $this->ctrl->getNextClass();

            switch ($next_class) {
                case strtolower(ilCustomMetaBarLinksFileUploadHandlerGUI::class):
                    $this->safelyForward(ilCustomMetaBarLinksFileUploadHandlerGUI::class);
                    break;
                default:
                    $this->performCommand($this->ctrl->getCmd(self::CMD_ADDLINK));
                    break;

            }
        } catch (Throwable $exception) {
            $this->ui->mainTemplate()->setOnScreenMessage(
                'failure',
                ($this->isDebugModeEnabled()) ?
                    $this->getExceptionString($exception) :
                    $exception->getMessage()
            );
        }
    }*/

    /**
     * @param string $cmd
     * @throws Exception
     */
    public function performCommand(string $cmd) : void
    {
        switch ($cmd) {
            case self::CMD_CONFIGURE:
                $this->configure();
                break;
            case self::CMD_HANDLETABLEACTIONS:
                $this->handleTableActions();
                break;
            case self::CMD_SHOWLINKLIST:
                $this->showLinkList();
                break;
            case self::CMD_ACTIVATELINKS:
                $this->activateLinks(null);
                break;
            case self::CMD_DEACTIVATELINKS:
                $this->deactivateLinks(null);
                break;
            case self::CMD_CONFIRMDELETELINKS:
                $this->confirmDeleteLinks(null);
                break;
            case self::CMD_EDITLINK:
                $this->editLink(null);
                break;
            case self::CMD_ADDLINK:
                $this->addLink();
                break;
            case self::CMD_SAVELINK:
                $this->saveLink();
                break;
        }

        $this->ui->mainTemplate()->setTitle(
            $this->plugin_object->txt('custom_links')
        );

        $this->ui->mainTemplate()->setDescription(
            $this->plugin_object->txt('custom_links_description')
        );
    }

    /**
     * @return void
     * @throws Exception
     */
    protected function handleTableActions(): void
    {
        $query = $this->http->wrapper()->query();
        if (!$query->has('custom_link_table_action')) {
            return;
        }
        $action = $query->retrieve('custom_link_table_action', $this->refinery->to()->string());

        $ids = $this->http->wrapper()->query()->retrieve(
            'custom_link_ids',
            $this->refinery->custom()->transformation(function ($q_ids) {
                if (is_array($q_ids)) {
                    return $q_ids;
                }
                return strlen($q_ids) > 0 ? explode(',', $q_ids) : [];
            })
        );

        switch ($action) {
            case 'editLink':
                $this->editLink((int) $ids[0]);
                break;
            case 'copyLink':
                $this->copyLink((int) $ids[0]);
                break;
            case 'activateLinks':
                $this->activateLinks($ids);
                break;
            case 'deactivateLinks':
                $this->deactivateLinks($ids);
                break;
            case 'confirmDeleteLinks':
                $this->confirmDeleteLinks($ids);
                break;
        }
    }

    /**
     * Load configure-screen
     */
    public function configure() : void
    {
        $this->showLinkList();
    }

    /**
     * Load CustomLinks-Configuration Table
     */
    public function showLinkList() : void
    {
        $this->toolbar->addButton(
            $this->plugin_object->txt('add_link'),
            $this->ctrl->getLinkTarget($this, self::CMD_ADDLINK)
        );

        $data_retrieval = new CustomMetaBarLinksTableDataRetrieval(
            //$filter,
            $this->lng,
            $this->ui_factory,
            $this->renderer
        );

        //$filter = new CustomMetaBarLinksTableFilter($this->ctrl->getFormAction($this, 'configure'));
        //$filter->init();

        $table = new CustomMetaBarLinksTableGUI($this, 0);
        $this->ui->mainTemplate()->setContent(
            //$filter->render() .
            $table->getHTML($data_retrieval)
        );
    }

    /**
     * Initialize process setting links activation to active
     * @param array|null $ids
     * @return void
     */
    public function activateLinks(array|null $ids) : void
    {
        try {
            $this->changeLinkActivation($ids, true);
        } catch (Exception) {
        }
    }

    /**
     * Initialize process setting links activation to de-active
     * @param array|null $ids
     * @return void
     */
    public function deactivateLinks(array|null $ids) : void
    {
        try {
            $this->changeLinkActivation($ids, false);
        } catch (Exception) {
        }
    }

    /**
     * Change link activation status
     * @param array|null $ids
     * @param $active
     * @return void
     * @throws Exception
     */
    protected function changeLinkActivation(array|null $ids, $active) : void
    {
        $plugin = ilCustomMetaBarLinksPlugin::getInstance();

        if (isset($ids)) {
            $link_ids = $ids;
        } else {
            if (isset($_POST['custom_link_ids'])) {
                $link_ids = (array) $_POST['custom_link_ids'];
            } elseif (isset($_GET['custom_link_ids'])) {
                $link_ids = (array) $_GET['custom_link_ids'];
            }
        }

        if (empty($link_ids)) {
            $this->ui->mainTemplate()->setOnScreenMessage(ilGlobalTemplateInterface::MESSAGE_TYPE_FAILURE, $plugin->txt('no_link_selected'));
        } else {
            foreach ($link_ids as $link_id) {
                $link = $this->customLinksRepository->getLinkById($link_id);
                if (isset($link)) {
                    $link->setActive($active);
                    $this->customLinksRepository->updateLink($link);
                }
            }

            if (count($link_ids) == 1) {
                $this->ui->mainTemplate()->setOnScreenMessage(ilGlobalTemplateInterface::MESSAGE_TYPE_SUCCESS, $plugin->txt($active ? 'link_activated' : 'link_deactivated'));
            } else {
                $this->ui->mainTemplate()->setOnScreenMessage(ilGlobalTemplateInterface::MESSAGE_TYPE_SUCCESS, $plugin->txt($active ? 'links_activated' : 'links_deactivated'));
            }
        }
        try {
            $this->ctrl->redirect($this, 'showLinkList');
        } catch (ilCtrlException) {

        }
    }

    /**
     * Delete custom link(s)
     * @param array|null $ids
     * @return void
     */
    public function confirmDeleteLinks(array|null $ids) : void
    {
        if (isset($ids)) {
            $link_ids = $ids;
        } else {
            if (isset($_POST['custom_link_ids'])) {
                $link_ids = (array) $_POST['custom_link_ids'];
            } elseif (isset($_GET['custom_link_ids'])) {
                $link_ids = (array) $_GET['custom_link_ids'];
            }
        }

        if (empty($link_ids)) {
            $this->ui->mainTemplate()->setOnScreenMessage(ilGlobalTemplateInterface::MESSAGE_TYPE_FAILURE, $this->plugin_object->txt('no_link_selected'));
        } else {
            $this->customLinksRepository->deleteLinksByIds($link_ids);
        }
        try {
            $this->ctrl->redirect($this, 'showLinkList');
        } catch (ilCtrlException) {

        }
    }

    /**
     * Load add form
     * @throws Exception
     */
    public function addLink() : void
    {
        $this->tabs->clearTargets();
        $this->tabs->setBackTarget(
            $this->plugin_object->txt('back_to_list'),
            $this->ctrl->getLinkTarget($this, self::CMD_SHOWLINKLIST)
        );

        $this->tabs->addTab("view_addLink",
            $this->plugin_object->txt("add_Link"),
            $this->ctrl->getLinkTarget($this, self::CMD_ADDLINK));

        $this->tabs->setTabActive("view_addLink");

        $form = $this->getLinkForm();
        $this->ui->mainTemplate()->setContent($this->ui->renderer()->render($form));
    }

    /**
     * Load edit form by GET 'link id'
     * @param int|null $id
     * @return void
     * @throws ilCtrlException
     */
    public function editLink(int|null $id) : void
    {
        if (isset($id)) {
            $link_id = $id;
        } else {
            if (isset($_POST['v'])) {
                $link_id = $_POST['custom_link_id'];
            } elseif (isset($_GET['custom_link_id'])) {
                $link_id = $_GET['custom_link_id'];
            }
        }

        $this->tabs->clearTargets();
        $this->tabs->setBackTarget(
            $this->plugin_object->txt('back_to_list'),
            $this->ctrl->getLinkTarget($this, self::CMD_SHOWLINKLIST)
        );

        $this->ctrl->setParameter($this, 'custom_link_id', $link_id);
        $this->tabs->addTab("view_editLink",
            $this->plugin_object->txt('edit_link'),
            $this->ctrl->getLinkTarget($this, self::CMD_EDITLINK));

        $this->tabs->setTabActive("view_editLink");

        $form = $this->getLinkForm($link_id);
        $this->ui->mainTemplate()->setContent($this->ui->renderer()->render($form));
    }

    /**
     * Save link from form input
     * @throws Exception
     */
    public function saveLink() : void
    {
        $form = $this->getLinkForm($_GET['custom_link_id']);
        $request = $this->http->request();

        // form data processing.
        if ($request->getMethod() == "POST") {
            try {
                $form = $form->withRequest($request);
                $form_data = $form->getData() ?? null;

                if ($form->getError() != null) {
                    $this->ui->mainTemplate()->setContent($this->ui->renderer()->render($form));
                }
                else {
                    if ($form_data != null) {
                        $this->ui->mainTemplate()->setContent(
                            '<pre>' . print_r($form_data, true) . '</pre>'
                        );

                        // get link id (edit) by form input or get a new link id (create)
                        $linkId = $_GET['custom_link_id'];
                        if (isset($linkId)) {
                            $link = $this->customLinksRepository->getLinkById($linkId);
                        } else {
                            $link = new CustomLink();
                        }

                        // set and save all link attributes by form input
                        $link->setTitle($form_data['title']);
                        $link->setExternalLink($form_data['external_link']);
                        $icon = $form_data['icon'];
                        $icon_resource_id_string = $icon[0] ?? '';
                        $link->updateIcon($icon_resource_id_string);
                        $link->setActive($form_data['active']);
                        $link->setRolesIds($form_data['roles']);

                        if (isset($linkId)) {
                            // Update
                            $this->customLinksRepository->updateLink($link);
                        } else {
                            // Create
                            $this->customLinksRepository->createLink($link);
                        }
                        $this->ctrl->redirect($this, 'showLinkList');
                    } else {
                        $this->ui->mainTemplate()->setContent($this->ui->renderer()->render($form));
                    }
                }

            } catch (\InvalidArgumentException $e) {

            }
        }
    }

    /**
     * Link configuration form.
     * @param $a_link_id
     * @return \ILIAS\UI\Component\Input\Container\Form\Standard
     * @throws ilCtrlException
     */
    public function getLinkForm($a_link_id = null) : \ILIAS\UI\Component\Input\Container\Form\Standard
    {
        $factory = $this->ui->factory();
        $fields = array();

        if (isset($a_link_id) && $a_link_id > 0) {
            $this->link = $this->customLinksRepository->getLinkById($a_link_id);
            $title = $this->plugin_object->txt('edit_link');
            try {
                $this->ctrl->setParameter($this, 'custom_link_id', $a_link_id);
            } catch (ilCtrlException) {

            }
        } else {
            $this->link = new CustomLink();
            $title = $this->plugin_object->txt('add_link');
        }

        // title
        $title_input = $factory->input()->field()->text(
            $this->plugin_object->txt('link_title'),
            $this->plugin_object->txt('link_title_info')
        )
            ->withDedicatedName('title')
            ->withValue($this->link->getTitle())
            ->withRequired(true);
        $fields['title'] = $title_input;

        // externalLink
        $external_link_input = $factory->input()->field()->text(
            $this->plugin_object->txt('link_external_link'),
            $this->plugin_object->txt('link_external_link_info')
        )
            ->withDedicatedName('external_link')
            ->withValue($this->link->getExternalLink())
            ->withRequired(true);
        $fields['external_link'] = $external_link_input;

        // icon
        $upload_handler = new ilCustomMetaBarLinksFileUploadHandlerGUI();
        $icon_input = $factory->input()->field()->file(
            $upload_handler,
            $this->plugin_object->txt('link_icon'),
            $this->plugin_object->txt('link_icon_info')
        )
            ->withDedicatedName('icon')
            ->withMaxFiles(1)
            ->withAcceptedMimeTypes(['image/svg+xml', 'svg'])
            ->withMaxFileSize(1024 * 1000);
        if ($this->link->getIconId() !== null) {
            $icon_input = $icon_input->withValue([$this->link->getIconId()]);
        }
        $fields['icon'] = $icon_input;

        // activation
        $active_input = $factory->input()->field()->checkbox(
            $this->plugin_object->txt('link_active'),
            $this->plugin_object->txt('link_active_info')
        )
            ->withDedicatedName('is_active')
            ->withValue($this->link->isActive());
        $fields['active'] = $active_input;

        // roles
        $access = new ilObjMainMenuAccess();
        $roles_input = $factory->input()->field()->multiSelect(
            $this->plugin_object->txt('link_roles'),
            $access->getGlobalRoles(),
            $this->plugin_object->txt('link_roles_info')
        )
            ->withDedicatedName('roles')
            ->withValue($this->link->getRolesIds());
        $fields['roles'] = $roles_input;

        // form
        return $factory->input()->container()->form()->standard(
            $this->ctrl->getLinkTarget($this, self::CMD_SAVELINK),
            $fields
        )
            ->withSubmitLabel($this->lng->txt('save'));

    }
}
