<?php declare(strict_types=1);

namespace ILIAS\Plugin\cmbl\Data;
require_once __DIR__ . "/../../vendor/autoload.php";

use ILIAS\Plugin\cmbl\Model\CustomLink;

/**
 * Class CustomLinksIRepository
 * @author  Nadimo Staszak <nadimo.staszak@uni-koeln.de>
 * @version $Id$
 */
interface CustomLinksIRepository
{
    // Create operations
    /**
     * @param CustomLink $a_link
     * @return CustomLink|null
     */
    public function createLink(CustomLink $a_link): ?CustomLink;

    /**
     * @param CustomLink[] $a_links
     * @return void
     */
    public function createLinks(array $a_links): void;

    // Read operations
    /**
     * @param int $a_id
     * @return CustomLink|null
     */
    public function getLinkById(int $a_id): ?CustomLink;

    /**
     * @return CustomLink[]|null
     */
    public function getLinks(): ?array;

    // Update operations
    /**
     * @param CustomLink $a_link
     * @return CustomLink|null
     */
    public function updateLink(CustomLink $a_link): ?CustomLink;

    /**
     * @param CustomLink[] $a_links
     * @return void
     */
    public function updateLinks(array $a_links): void;

    // Delete operations
    /**
     * @param int $a_id
     * @return bool
     */
    public function deleteLinkById(int $a_id): bool;

    /**
     * @param int[] $a_ids
     * @return void
     */
    public function deleteLinksByIds(array $a_ids): void;
}