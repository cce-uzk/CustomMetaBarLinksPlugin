<?php declare(strict_types=1);

namespace ILIAS\Plugin\cmbl\Model;
require_once __DIR__ . "/../../vendor/autoload.php";

use Exception;
use ilCustomLinksResourceStakeholder;
use ILIAS\FileUpload\Exception\IllegalStateException;

/**
 * Class CustomLink
 * CustomLink Object
 * @author  Nadimo Staszak <nadimo.staszak@uni-koeln.de>
 * @version $Id$
 */
class CustomLink
{
    /** @var ?int */
    protected ?int $id;
    /** @var string */
    protected string $title;
    /** @var string */
    protected string $external_link;
    /** @var ?string */
    protected ?string $icon;
    /** @var bool */
    protected bool $active;
    /** @var array | null */
    protected array | null $rolesIds;

    public function __construct(
        ?int $id = null,
        string $title = "",
        string $external_link = "",
        ?string $icon = null,
        bool|string|int $active = false,
        array $rolesIds = array()
    ) {
        $this->setId($id);
        $this->setTitle($title);
        $this->setExternalLink($external_link);
        $this->setIconId($icon);
        $this->setActive($active);
        $this->setRolesIds($rolesIds);
    }

    public function setId(?int $a_val) : void
    {
        $this->id = $a_val;
    }

    public function getId() : ?int
    {
        return $this->id;
    }

    public function setTitle(string $a_val) : void
    {
        $this->title = $a_val;
    }

    public function getTitle() : string
    {
        return $this->title;
    }

    public function setExternalLink(string $a_val) : void
    {
        $this->external_link = $a_val;
    }

    public function getExternalLink() : string
    {
        return $this->external_link;
    }

    public function setIconId(?string $a_val) : void
    {
        $this->icon = $a_val;
    }

    public function getIconId() : ?string
    {
        return $this->icon;
    }

    public function setActive(bool|string|int $a_val) : void
    {
        $value = filter_var($a_val, FILTER_VALIDATE_BOOLEAN);
        $this->active = $value;
    }

    public function isActive() : bool
    {
        return $this->active;
    }

    public function getRolesIds() : array | null
    {
        return $this->rolesIds;
    }

    public function setRolesIds(array | null $a_val) : void
    {
        $this->rolesIds = $a_val;
    }

    private function getRolesIdsAsJSON() : string
    {
        return json_encode($this->rolesIds);
    }

    private function setRolesIdsFromJSON(string $a_val) : void
    {
        if (!($a_val === "")) {
            $this->rolesIds = (array) json_decode($a_val);
        } else {
            $this->rolesIds = array();
        }
    }

    /**
     * @param string|null $public_ressource_id
     * @throws IllegalStateException
     * @throws Exception
     */
    public function updateIcon(string $public_ressource_id = null) : void
    {
        try {
            global $DIC;
            $irss = $DIC->resourceStorage();
            $stakeholder = new ilCustomLinksResourceStakeholder();

            // Remove existing icon
            if ($this->getIconId() != null) {
                $identification = $irss->manage()->find($this->getIconId());
                if ($identification !== null && ($identification->serialize() !== $public_ressource_id || $public_ressource_id == null)) {
                    $irss->manage()->remove($identification, $stakeholder);
                    $this->setIconId(null);
                }
            }

            if ($public_ressource_id === '') {
                $public_ressource_id = null;
            }
            $this->setIconId($public_ressource_id);
        } catch (Exception $e) {
            // Handle exception
            throw new Exception("Failed to update icon: " . $e->getMessage());
        }
    }

    /**
     * @return string
     */
    public function getIconTitle() : string
    {
        global $DIC;
        $rs = $DIC->resourceStorage();

        if (!empty($this->getIconId())) {
            $identification = $rs->manage()->find($this->getIconId());
            if ($identification !== null) {
                $currentRevision = $rs->manage()->getCurrentRevision($identification);
                if (!empty($currentRevision->getIdentification())) {
                    return $currentRevision->getTitle();
                }
            }
        }
        return '';
    }

    /**
     * @return string|null
     */
    public function getIconSrc(): ?string
    {
        global $DIC;
        $rs = $DIC->resourceStorage();

        if (!empty($this->getIconId())) {
            $identification = $rs->manage()->find($this->getIconId());
            if ($identification !== null) {
                try {
                    if (!empty($rs->manage()->find($identification->serialize()))) {
                        $src = $rs->consume()->src($identification);
                        return $src->getSrc();
                    }
                } catch (Exception $e) {
                    return null;
                }
            }
        }
        return null;
    }

    /**
     * Write the properties to an array
     * @return array
     */
    public function toArray() : array
    {
        return array(
            "link_id" => array('integer', $this->getId()),
            "title" => array('text', $this->getTitle()),
            "external_link" => array('text', $this->getExternalLink()),
            "icon_id" => array('text', $this->getIconId()),
            "is_active" => array('integer', $this->isActive()),
            "roles_ids" => array('text', $this->getRolesIdsAsJSON())
        );
    }

    /**
     * @return array
     */
    public function toDataArray() : array
    {
        return array(
            'link_id' => $this->getId(),
            'title' => $this->getTitle(),
            'external_link' => $this->getExternalLink(),
            "icon_id" => $this->getIconId(),
            'is_active' => $this->isActive(),
            "roles_ids" => $this->getRolesIds()
        );
    }

    /**
     * @return array[]
     */
    public function toPrimaryArray() : array
    {
        return array(
            "link_id" => array('int', $this->getId())
        );
    }

    /**
     * Get the properties from an array
     * @param array $array
     * @return void
     */
    public function fromArray(array $array = array()): void
    {
        $this->setId($array['link_id']);
        $this->setTitle($array['title']);
        $this->setExternalLink($array['external_link']);
        $this->setIconId($array['icon_id']);
        $this->setActive($array['is_active']);
        $this->setRolesIdsFromJSON($array['roles_ids']);
    }
}