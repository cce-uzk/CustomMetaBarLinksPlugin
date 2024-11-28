<?php declare(strict_types=1);

namespace ILIAS\Plugin\cmbl\Data;
require_once __DIR__ . "/../../vendor/autoload.php";

use ILIAS\Plugin\cmbl\Model\CustomLink;
use ILIAS\DI\Container;
use ilDBInterface;
use ILIAS\DI\Exceptions\Exception;
use ilDatabaseException;
use InvalidArgumentException;

/**
 * Class CustomLinksRepository
 * @author  Nadimo Staszak <nadimo.staszak@uni-koeln.de>
 * @version $Id$
 */
class CustomLinksRepository implements CustomLinksIRepository
{
    protected ilDBInterface $db;

    public function __construct()
    {
        /** @var Container $DIC */
        global $DIC;

        $this->db = $DIC->database();
    }

    /*
     * Create operations
     */
    /**
     * @param CustomLink $a_link
     * @return CustomLink|null
     */
    public function createLink(CustomLink $a_link): ?CustomLink
    {
        try {
            if (empty($a_link->getId())) {
                $nextId = $this->db->nextId("cmbl_links");
                $a_link->setId($nextId);
                $data = $a_link->toArray();

                $rows_affected = $this->db->insert('cmbl_links', $data);

                if ($rows_affected > 0) {
                    return $this->getLinkById($a_link->getId());
                } else {
                    // Query did not execute successfully
                    error_log("No changes were made for link ID: " . $a_link->getId());
                }
            } else {
                // Link id is still specified
                error_log("Failed to update link.");
            }
        } catch (Exception $e) {
            // Log the exception
            error_log($e->getMessage());
        }
        return null;
    }

    /**
     * @param CustomLink[] $a_links
     * @return void
     */
    public function createLinks(array $a_links): void
    {
        foreach ($a_links as $a_link) {
            if (!($a_link instanceof CustomLink)) {
                error_log("Invalid object encountered in the array");
                continue; // Skip if the item is not an instance of CustomLink
            }

            // Call createLink for each CustomLink object
            $createdLink = $this->createLink($a_link);
            if (null === $createdLink) {
                error_log("Failed to create link.");
            }
        }

    }

    /*
     * Read operations
     */
    /**
     * @param int|string $a_id
     * @return CustomLink|null
     */
    public function getLinkById(int|string $a_id): ?CustomLink
    {
        // Convert and validate the input
        $a_id = filter_var($a_id, FILTER_VALIDATE_INT);
        if ($a_id === false) {
            error_log("Invalid ID provided.");
        }

        // Prepare and execute the query to fetch the link by ID
        $query = "SELECT * FROM cmbl_links WHERE link_id = " . $this->db->quote($a_id, "integer");
        $result = $this->db->query($query);

        // Fetch the record
        $record = $this->db->fetchAssoc($result);

        if ($record) {
            // Create a new CustomLink object and populate it from the array
            $link = new CustomLink();
            $link->fromArray($record);
            return $link;
        }

        // Return null if no link is found
        return null;
    }

    /**
     * @return CustomLink[]
     */
    public function getLinks(): array
    {
        $links = [];
        try {
            $statement = $this->db->query("SELECT * FROM cmbl_links");
            $records = $this->db->fetchAll($statement);
            if ($records) {
                foreach ($records as $record) {
                    $obj = new CustomLink();
                    $obj->fromArray($record);
                    $links[] = $obj;
                }
            } else {
                // Query did not execute successfully
                error_log("Failed to fetch links.");
            }
        } catch (Exception $e) {
            // Log the exception
            error_log($e->getMessage());
        }
        return $links;
    }

    /*
     * Update operations
     */
    /**
     * @param CustomLink $a_link
     * @return CustomLink|null
     */
    public function updateLink(CustomLink $a_link): ?CustomLink
    {
        try {
            if (!empty($a_link->getId())) {
                $data = $a_link->toArray();
                $primary = $a_link->toPrimaryArray();

                $rows_affected = $this->db->update('cmbl_links', $data, $primary);

                if ($rows_affected > 0) {
                    return $this->getLinkById((int) $primary['link_id']);
                } else {
                    // Query did not execute successfully
                    error_log("No changes were made for link ID: " . $a_link->getId());
                }
            } else {
                // Link id is not specified
                error_log("Failed to update unknown or invalid link.");
            }
        } catch (Exception $e) {
            // Log the exception
            error_log($e->getMessage());
        }
        return null;
    }

    /**
     * @param CustomLink[] $a_links
     * @return void
     */
    public function updateLinks(array $a_links): void
    {
        foreach ($a_links as $a_link) {
            if (!($a_link instanceof CustomLink)) {
                error_log("Invalid object encountered in the array");
                continue; // Skip if the item is not an instance of CustomLink
            }

            // Call updateLink for each CustomLink object
            $updatedLink = $this->updateLink($a_link);
            if (null === $updatedLink) {
                error_log("Failed to update link with ID: " . $a_link->getId());
            }
        }
    }

    /*
     * Delete operations
     */
    /**
     * @param int|string $a_id
     * @return bool
     */
    public function deleteLinkById(int|string $a_id): bool
    {
        // Convert and validate the input
        $a_id = filter_var($a_id, FILTER_VALIDATE_INT);
        if ($a_id === false) {
            error_log("Invalid ID provided.");
        }

        $this->deleteLinksByIds([$a_id]);

        // Return true to indicate successful deletion
        return true;
    }

    /**
     * @param int[] $a_ids
     * @return void
     */
    public function deleteLinksByIds(array $a_ids): void
    {
        // Prepare and execute the SQL statement to delete links from the database
        $statement = $this->db->prepareManip(
            "DELETE FROM cmbl_links WHERE link_id IN (?)",
            array("int")
        );

        try {
            $this->db->execute($statement, $a_ids);
        } catch (ilDatabaseException $e) {
            // Log the exception
            error_log($e->getMessage());
        } finally {
            $this->db->free($statement);
        }
    }
}