<?php declare(strict_types=1);

namespace ILIAS\Plugin\cmbl\Config;
require_once __DIR__ . "/../../vendor/autoload.php";

use ILIAS\Plugin\cmbl\Data\CustomLinksRepository;
use ILIAS\Plugin\cmbl\Model\CustomLink;
use ILIAS\UI\Component\Table\DataRetrieval;
use ILIAS\UI\Component\Table\DataRowBuilder;
use ILIAS\Data\Range;
use ILIAS\Data\Order;
use ILIAS\UI\Factory as UIFactory;
use ILIAS\UI\Renderer as UIRenderer;
use ILIAS\UI\Component\Symbol\Icon\Icon;
use ilLanguage;
use ilObjMainMenuAccess;
use ilUtil;

class CustomMetaBarLinksTableDataRetrieval implements DataRetrieval
{
    /*protected CustomLinksTableFilter $filter;*/
    protected ilLanguage $lng;
    protected UIFactory $ui_factory;
    protected UIRenderer $ui_renderer;
    protected CustomLinksRepository $customLinksRepository;

    public function __construct(
        /*CustomLinksTableFilter $filter,*/
        ilLanguage $lng,
        UIFactory $ui_factory,
        UIRenderer $ui_renderer,
    ) {
        // General Dependencies
        /*$this->filter = $filter;*/
        $this->lng = $lng;
        $this->ui_factory = $ui_factory;
        $this->ui_renderer = $ui_renderer;

        // Repositories
        $this->customLinksRepository = new CustomLinksRepository();
    }

    /**
     * @param DataRowBuilder $row_builder
     * @param array $visible_column_ids
     * @param Range $range
     * @param Order $order
     * @param array|null $filter_data
     * @param array|null $additional_parameters
     * @return \Generator
     */
    public function getRows(
        DataRowBuilder $row_builder,
        array $visible_column_ids,
        Range $range,
        Order $order,
        ?array $filter_data,
        ?array $additional_parameters
    ): \Generator
    {
        $records = $this->getRecords($order, $range);
        foreach ($records as $record) {
            $row = $row_builder->buildDataRow((string) $record['link_id'], $record);
            /*if ($record['deleted']) {
                $row = $row->withDisabledAction('action_disabled_name');
            }*/
            yield $row;
        }
    }

    /**
     * @param array|null $filter_data
     * @param array|null $additional_parameters
     * @return int|null
     */
    public function getTotalRowCount(
        ?array $filter_data,
        ?array $additional_parameters
    ): ?int {
        return count($this->getCustomLinks());
    }

    /**
     * @return CustomLink[]
     */
    protected function getCustomLinks(): array
    {
        $custom_links = $this->customLinksRepository->getLinks();
        /*return $this->filter->filter($custom_links);*/
        return $custom_links;
    }

    /**
     * @param Order $order
     * @param Range $range
     * @return array
     */
    protected function getRecords(Order $order, Range $range): array
    {
        $records = [];
        foreach ($this->getCustomLinks() as $custom_link) {
            // title
            $title = $custom_link->getTitle();

            // icon
            $icon = null;
            $icon_label = $title;
            $icon_path = $custom_link->getIconSrc();
            if ($icon_path) {
                $icon = $this->ui_factory->symbol()->icon()->custom(
                    $icon_path,
                    $icon_label
                );
            }

            // activation
            $active = $this->ui_factory->symbol()->icon()->custom(
                $custom_link->isActive() ?
                    ilUtil::getImagePath('standard/icon_ok.svg') :
                    ilUtil::getImagePath('standard/icon_not_ok.svg'),
                $custom_link->isActive() ? $this->lng->txt('active') : $this->lng->txt('inactive'),
                Icon::SMALL
            );

            // roles
            $roles = '';
            $access = new ilObjMainMenuAccess();
            $global_roles = $access->getGlobalRoles();
            foreach ($custom_link->getRolesIds() as $role_id) {
                $roles .= $global_roles[$role_id] . '<br/>';
            }

            $record = [
                'link_id' => $custom_link->getId(),
                'title' => $title,
                'external_link' => $custom_link->getExternalLink(),
                'active' => $active,
                'roles' => $roles
            ];
            if (isset($icon)) {
                $record['icon'] = $icon;
            }
            $records[] = $record;
        }
        list($order_field, $order_direction) = $order->join([], fn($ret, $key, $value) => [$key, $value]);
        usort($records, fn($a, $b) => $a[$order_field] <=> $b[$order_field]);
        if (
            $order_direction === 'DESC'
        ) {
            $records = array_reverse($records);
        }
        $selected_records = array_slice(
            $records,
            $range->getStart(),
            $range->getLength()
        );
        return $selected_records;
    }
}