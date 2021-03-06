<?php

namespace Sng\Recordsmanager\ViewHelpers\Widget\Controller;

use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Fluid\Core\Widget\AbstractWidgetController;

/*
 * This file is part of the "recordsmanager" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */
/**
 * Paginate controller to create the pagination.
 * Extended version from fluid core
 */
class PaginateController extends AbstractWidgetController
{

    /**
     * @var array
     */
    protected $configuration = [
        'itemsPerPage'           => 10,
        'insertAbove'            => false,
        'insertBelow'            => true,
        'pagesAfter'             => 3,
        'pagesBefore'            => 3,
        'lessPages'              => true,
        'forcedNumberOfLinks'    => 5,
        'forceFirstPrevNextlast' => false,
        'showFirstLast'          => true
    ];

    /**
     * @var \TYPO3\CMS\Extbase\Persistence\QueryResultInterface
     */
    protected $objects;

    /**
     * @var int
     */
    protected $currentPage = 1;

    /**
     * @var int
     */
    protected $pagesBefore = 1;

    /**
     * @var int
     */
    protected $pagesAfter = 1;

    /**
     * @var bool
     */
    protected $lessPages = false;

    /**
     * @var int
     */
    protected $forcedNumberOfLinks = 10;

    /**
     * @var int
     */
    protected $numberOfPages = 1;

    /**
     * Initialize the action and get correct configuration
     */
    protected function initializeAction()
    {
        $this->objects = $this->widgetConfiguration['objects'];
        ArrayUtility::mergeRecursiveWithOverrule(
            $this->configuration,
            $this->widgetConfiguration['configuration'],
            true
        );

        if (empty($this->configuration['itemsPerPage'])) {
            $this->configuration['itemsPerPage'] = 20;
        }

        $this->numberOfPages = ceil(count($this->objects) / (integer)$this->configuration['itemsPerPage']);
        $this->pagesBefore = (integer)$this->configuration['pagesBefore'];
        $this->pagesAfter = (integer)$this->configuration['pagesAfter'];
        $this->lessPages = (boolean)$this->configuration['lessPages'];
        $this->forcedNumberOfLinks = (integer)$this->configuration['forcedNumberOfLinks'];
    }

    /**
     * If a certain number of links should be displayed, adjust before and after
     * amounts accordingly.
     */
    protected function adjustForForcedNumberOfLinks()
    {
        $forcedNumberOfLinks = $this->forcedNumberOfLinks;
        if ($forcedNumberOfLinks > $this->numberOfPages) {
            $forcedNumberOfLinks = $this->numberOfPages;
        }
        $totalNumberOfLinks = min($this->currentPage, $this->pagesBefore) +
            min($this->pagesAfter, $this->numberOfPages - $this->currentPage) + 1;
        if ($totalNumberOfLinks <= $forcedNumberOfLinks) {
            $delta = (int)(ceil(($forcedNumberOfLinks - $totalNumberOfLinks) / 2));
            $incr = ($forcedNumberOfLinks & 1) === 0 ? 1 : 0;
            if ($this->currentPage - ($this->pagesBefore + $delta) < 1) {
                // Too little from the right to adjust
                $this->pagesAfter = $forcedNumberOfLinks - $this->currentPage - 1;
                $this->pagesBefore = $forcedNumberOfLinks - $this->pagesAfter - 1;
            } elseif ($this->currentPage + ($this->pagesAfter + $delta) >= $this->numberOfPages) {
                $this->pagesBefore = $forcedNumberOfLinks - ($this->numberOfPages - $this->currentPage);
                $this->pagesAfter = $forcedNumberOfLinks - $this->pagesBefore - 1;
            } else {
                $this->pagesBefore += $delta;
                $this->pagesAfter += $delta - $incr;
            }
        }
    }

    /**
     * Main action which does all the fun
     *
     * @param int $currentPage
     */
    public function indexAction($currentPage = 1)
    {
        // set current page
        $this->currentPage = (integer)$currentPage;
        if ($this->currentPage < 1) {
            $this->currentPage = 1;
        } elseif ($this->currentPage > $this->numberOfPages) {
            $this->currentPage = $this->numberOfPages;
        }

        // modify query
        $itemsPerPage = (integer)$this->configuration['itemsPerPage'];

        if (is_a($this->objects, QueryResultInterface::class)) {
            $query = $this->objects->getQuery();

            // limit should only be used if needed and pagination only if results > itemsPerPage
            if ($itemsPerPage < $this->objects->count()) {
                $query->setLimit($itemsPerPage);
            }

            if ($this->currentPage > 1) {
                $query->setOffset((integer)($itemsPerPage * ($this->currentPage - 1)));
            }
            $modifiedObjects = $query->execute();
        } else {
            if (empty($this->objects)) {
                return null;
            }

            $offset = 0;
            if ($this->currentPage > 1) {
                $offset = ((integer)($itemsPerPage * ($this->currentPage - 1)));
            }
            $modifiedObjects = array_slice($this->objects, $offset, (integer)$itemsPerPage);
        }

        $this->view->assign('contentArguments', [$this->widgetConfiguration['as'] => $modifiedObjects]);
        $this->view->assign('configuration', $this->configuration);
        $this->view->assign('pagination', $this->buildPagination());
    }

    /**
     * Returns an array with the keys
     * "pages", "current", "numberOfPages", "nextPage" & "previousPage"
     *
     * @return array
     */
    public function buildPagination()
    {
        $this->adjustForForcedNumberOfLinks();

        $pages = [];
        $start = max($this->currentPage - $this->pagesBefore, 0);
        $end = min($this->numberOfPages, $this->currentPage + $this->pagesAfter + 1);
        for ($i = $start; $i < $end; $i++) {
            $j = $i + 1;
            $pages[] = ['number' => $j, 'isCurrent' => ((int)$j === (int)($this->currentPage))];
        }

        $pagination = [
            'pages'         => $pages,
            'current'       => $this->currentPage,
            'numberOfPages' => $this->numberOfPages,
            'numberOfItems' => count($this->objects),
            'pagesBefore'   => $this->pagesBefore,
            'pagesAfter'    => $this->pagesAfter,
            'firstPageItem' => ($this->currentPage - 1) * (int)$this->configuration['itemsPerPage'] + 1
        ];
        if ($this->currentPage < $this->numberOfPages) {
            $pagination['nextPage'] = $this->currentPage + 1;
            $pagination['lastPageItem'] = $this->currentPage * (integer)$this->configuration['itemsPerPage'];
        } else {
            $pagination['lastPageItem'] = $pagination['numberOfItems'];
        }

        // previous pages
        if ($this->currentPage > 1) {
            $pagination['previousPage'] = $this->currentPage - 1;
        }

        // less pages (before current)
        if ($start > 0 && $this->lessPages) {
            $pagination['lessPages'] = true;
        }

        // next pages (after current)
        if ($end !== $this->numberOfPages && $this->lessPages) {
            $pagination['morePages'] = true;
        }

        return $pagination;
    }
}
