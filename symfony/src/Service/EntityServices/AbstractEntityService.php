<?php

namespace App\Service\EntityServices;

use Doctrine\Persistence\ObjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Abstract class that implements commonly used functions in entity services
 *
 * @package App\Service\EntityServices
 */
abstract class AbstractEntityService
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var ObjectRepository
     */
    protected $objectRepository;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    public function __construct(
        EntityManagerInterface $entityManager,
        ObjectRepository $repository,
        SerializerInterface $serializer
    ) {
        $this->entityManager = $entityManager;
        $this->objectRepository = $repository;
        $this->serializer = $serializer;
    }

    /**
     * Getting data from an entity by its id
     *
     * @param int $id                   Entity ID
     * @param string $optionalFields    String containing the names of the properties to be added to the return
     * @param array $allowedOptFields   Array of names of allowed additional properties for return
     * @param array $defaultFields      An array of properties names added to the return always
     *
     * @return array|null               Returns the normalized entity, null if the entity is not found
     */
    public function getEntityData(
        int $id,
        string $optionalFields = '',
        array $allowedOptFields = [],
        array $defaultFields = []
    ): ?array {
        $item = $this->objectRepository->find($id);
        if (!is_null($item)) {
            return $this->normalizeEntity($item, $optionalFields, $allowedOptFields, $defaultFields);
        } else {
            return null;
        }
    }

    /**
     * Getting an array with data from several entities
     *
     * @param int|null $page            Entities page number.
     *                                  Parameter will have an effect only when used with not null $resOnPage
     * @param int|null $resOnPage       Number of entities on return.
     *                                  Parameter will have an effect only when used with not null $page
     * @param string $optionalFields    String containing the names of the properties to be added to the return
     * @param array $allowedOptFields   Array of names of allowed additional properties for return
     * @param array $defaultFields      An array of properties names added to the return always
     * @param string $orderBy           String containing properties names with their sorting methods Ex: asc_id
     * @param array $orderlyFields      Array with names of allowed properties for sorting
     * @param array $defaultOrderCriteria
     * @param array|null $criteria
     *
     * @return array                    Array of normalized entities
     */
    public function getEntitiesPageData(
        int $page = null,
        int $resOnPage = null,
        string $optionalFields = '',
        array $allowedOptFields = [],
        array $defaultFields = [],
        string $orderBy = '',
        array $orderlyFields = [],
        array $defaultOrderCriteria = [],
        array $criteria = null
    ): array {
        $orderCriteria = $this->getOrderCriteria($orderBy, $orderlyFields, $defaultOrderCriteria);
        $criteria = $criteria ?? [];

        $offset = (is_null($page) or is_null($resOnPage)) ? null : (($page - 1) * $resOnPage);

        $items = $this->objectRepository->findBy(
            $criteria,
            $orderCriteria,
            $resOnPage,
            $offset
        );

        $itemsData = [];
        foreach ($items as $item) {
            $itemData = $this->normalizeEntity($item, $optionalFields, $allowedOptFields, $defaultFields);
            $itemsData[] = $itemData;
        }

        return $itemsData;
    }

    /**
     * Gets an array of criteria suitable for use in the repository
     *
     * @param string $orderBy               String containing properties names with their sorting methods Ex: asc_id
     *                                      Examples of possible values: asc(id), DESC(id), ASC_id, desc_id, etc.
     * @param array $orderlyFields          array with the names of the properties by which sorting is available
     * @param array $defaultOrderCriteria   default sort
     *
     * @return array                        Returns the criteria, keys - property names, values - asc or desc
     */
    public function getOrderCriteria(
        string $orderBy = '',
        array $orderlyFields = [],
        array $defaultOrderCriteria = []
    ): array {
        $orderCriteria = [];
        $orderBy = str_replace(['asc_', 'asc(', 'ASC_', 'ASC('], '+', $orderBy);
        $orderBy = str_replace(['desc_', 'desc(', 'DESC_', 'DESC('], '-', $orderBy);
        while ((false !== strpos($orderBy, '+')) or (false !== strpos($orderBy, '-'))) {
            $posA = strpos($orderBy, '+');
            $posD = strpos($orderBy, '-');
            $pos = ((false !== $posA) and (false !== $posD))
                ? (($posD < $posA) ? $posD : $posA)
                : ((false !== $posD) ? $posD : $posA);
            $ascDesc = substr($orderBy, $pos, 1);
            $orderBy = substr($orderBy, $pos + 1);
            foreach ($orderlyFields as $field) {
                if (substr($orderBy, 0, strlen($field)) == $field) {
                    $orderCriteria[$field] = ('+' === $ascDesc) ? 'asc' : 'desc';
                }
            }
        }

        return ((empty($orderCriteria)) ? $defaultOrderCriteria : $orderCriteria);
    }

    /**
     * Normalizes the entity according to the parameters
     *
     * @param $entity
     * @param string $optionalFields    String containing the names of the properties to be added to the return
     * @param array $allowedOptFields   Array of names of allowed additional properties for return
     * @param array $defaultFields      An array of properties names added to the return always
     *
     * @return array                    Returns array of entity properties
     */
    protected function normalizeEntity(
        $entity,
        string $optionalFields = '',
        array $allowedOptFields = [],
        array $defaultFields = []
    ): array {
        $itemData = [];
        foreach ($this->serializer->normalize($entity) as $itemField => $itemFieldValue) {
            if (
                in_array($itemField, $defaultFields)
                or (
                    in_array($itemField, $allowedOptFields)
                    and (false !== strpos($optionalFields, $itemField))
                )
            ) {
                $itemData[$itemField] = $itemFieldValue;
            }
        }

        return $itemData;
    }

    /**
     * Getting the count of entities
     *
     * @param array $criteria
     *
     * @return int  The count of the entities that match the given criteria.
     */
    public function count(array $criteria = null): int
    {
        $criteria = $criteria ?? [];

        return $this->objectRepository->count($criteria);
    }
}
