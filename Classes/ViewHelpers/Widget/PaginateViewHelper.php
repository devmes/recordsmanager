<?php

namespace Sng\Recordsmanager\ViewHelpers\Widget;

/*
 * This file is part of the "recordsmanager" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

/**
 * This ViewHelper renders a Pagination of objects.
 * Inspired by class of "news" extension
 *
 * = Examples =
 *
 * <code title="required arguments">
 * <f:widget.paginate objects="{blogs}" as="paginatedBlogs">
 *   // use {paginatedBlogs} as you used {blogs} before, most certainly inside
 *   // a <f:for> loop.
 * </f:widget.paginate>
 * </code>
 *
 * @package    TYPO3
 * @subpackage Recordsmanager
 */
class PaginateViewHelper extends \TYPO3\CMS\Fluid\Core\Widget\AbstractWidgetViewHelper
{

	/**
	 * @var \Sng\Recordsmanager\ViewHelpers\Widget\Controller\PaginateController
	 */
	protected $controller;

    /**
     * Initialize arguments.
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('objects', \TYPO3\CMS\Extbase\Persistence\QueryResultInterface::class, 'The QueryResult containing all objects.', true);
        $this->registerArgument('as', 'string', 'as', true);
        $this->registerArgument('configuration', 'array', 'configuration', false, ['itemsPerPage' => 10, 'insertAbove' => false, 'insertBelow' => true, 'maximumNumberOfLinks' => 99]);
    }

	/**
	 * Inject controller
	 *
	 * @param \Sng\Recordsmanager\ViewHelpers\Widget\Controller\PaginateController $controller
	 * @return void
	 */
	public function injectController(\Sng\Recordsmanager\ViewHelpers\Widget\Controller\PaginateController $controller) {
		$this->controller = $controller;
	}

	/**
	 * Render everything
	 *
	 * @return string
	 */
	public function render() {
		return $this->initiateSubRequest();
	}
}
