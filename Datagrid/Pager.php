<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\DoctrineORMAdminBundle\Datagrid;

use Doctrine\ORM\Query;
use Sonata\AdminBundle\Datagrid\Pager as BasePager;

/**
 * Doctrine pager class.
 *
 * @author Jonathan H. Wage <jonwage@gmail.com>
 */
class Pager extends BasePager implements SimpleQueryPagerInterface
{
    /**
     * NEXT_MAJOR: remove this property.
     *
     * @deprecated This property is deprecated since version 2.4 and will be removed in 3.0
     */
    protected $queryBuilder = null;

    /**
     * @var bool
     */
    protected $simpleQueryEnabled = false;

    /**
     * If set to true, the generated query will not contain any duplicate identifier check (e.g. DISTINCT keyword).
     * Enabling simple qurery will improve query performance, but can also return duplicate items. It depends on the query and the database schema.
     * Please enable the simple query only if you are sure that the duplicate identifier check in the query is useless.
     *
     * @param bool $simpleQueryEnabled
     */
    public function setSimpleQueryEnabled($simpleQueryEnabled)
    {
        $this->simpleQueryEnabled = (bool) $simpleQueryEnabled;
    }

    /**
     * {@inheritdoc}
     */
    public function computeNbResult()
    {
        $countQuery = clone $this->getQuery();

        if (count($this->getParameters()) > 0) {
            $countQuery->setParameters($this->getParameters());
        }

        $distinct = '';
        if (!$this->simpleQueryEnabled) {
            $distinct = 'DISTINCT ';
        }

        $countQuery->select(sprintf(
            'count(%s%s.%s) as cnt',
            $distinct,
            $countQuery->getRootAlias(),
            current($this->getCountColumn())
        ));

        return $countQuery->resetDQLPart('orderBy')->getQuery()->getSingleScalarResult();
    }

    /**
     * {@inheritdoc}
     */
    public function getResults($hydrationMode = Query::HYDRATE_OBJECT)
    {
        return $this->getQuery()->execute(array(), $hydrationMode);
    }

    /**
     * {@inheritdoc}
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        $this->resetIterator();

        $this->setNbResults($this->computeNbResult());

        $this->getQuery()->setFirstResult(null);
        $this->getQuery()->setMaxResults(null);

        if (count($this->getParameters()) > 0) {
            $this->getQuery()->setParameters($this->getParameters());
        }

        if (0 == $this->getPage() || 0 == $this->getMaxPerPage() || 0 == $this->getNbResults()) {
            $this->setLastPage(0);
        } else {
            $offset = ($this->getPage() - 1) * $this->getMaxPerPage();

            $this->setLastPage(ceil($this->getNbResults() / $this->getMaxPerPage()));

            $this->getQuery()->setFirstResult($offset);
            $this->getQuery()->setMaxResults($this->getMaxPerPage());
        }
    }
}
